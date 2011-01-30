var svndeploy = {
	
	MAX_SVN_THREAD: 1,
	
	SVNSTATUS_NORMAL: 		'normal',
	SVNSTATUS_UPDATED:		'upd',
	SVNSTATUS_UNAVAILABLE: 	'unavailable',
	
	status: {
		'none'				: ' ',
		'unversioned'		: '?',
		'normal'			: ' ',
		'added'				: 'A',
		'missing'			: '!',
		'deleted'			: 'D',
		'replaced'			: 'R',
		'modified'			: 'M',
		'merged'			: 'G',
		'conflicted'		: 'C',
		'ignored'			: 'I',
		'obstructed'		: '~',
		'external'			: 'X',
		'incomplete'		: '-'
	},
	
	locks: {},
	queue: [],
	thread: [],
	
	show_updated_flag: false,
		
	init: function() {
		$( '#svndeploy-dialog' ).fadeTo( 0, 0, function() {
			$( '#svndeploy-dialog' ).hide();
		});
		this.log( 'SVN Deploy initialized...' );
		this.list();
		this.log_hide();
	},
	
	random_key: function() {
		var r = 'svnd-k-';
		for ( var i = 0; i < 8; i++ ) {
			r += ( 1 + Math.floor( Math.random() * 8191 ) ).toString( 16 );
		}
		return r.toUpperCase();
	},
	
	htmlencode: function( s ) {
		var el = document.createElement("div");
		el.innerText = el.textContent = s;
		s = el.innerHTML;
		delete el;
		return s;
	},	
	
	login: function() {
		$( '#login' ).show();
		$( '.login_box' ).fadeTo( 0, 0 );
		$( '.login_box' ).fadeTo( 200, 1 );
	},
	
	dialog: function( msg ) {
		this.log( msg );
		$( '#svndeploy-dialog' ).show();
		$( '#svndeploy-dialog .content' ).html( msg );
		$( '#svndeploy-dialog' ).fadeTo( 'slow', 1.0 ).delay( 1500 ).fadeTo( 'slow', 0.0, function() {
			$( '#svndeploy-dialog' ).hide();
		});	
	},
	
	log: function( msg ) {
		var d = new Date();
		var ds = d.format('[j-M-Y H:i:s]');
		var line = ds + '	' + msg;
		if ( line.substr( -1 ) != '\n' ) {
			line += '\n';
		}
		$( '#transcript pre' ).append( this.htmlencode( line ) );
	},
	
	log_clear: function( msg ) {
		$( '#transcript pre' ).html( '' );
		this.log( 'Transcript reset...' );
	},
	
	log_show: function() {
		$( '#transcript' ).show();
	},
	
	log_hide: function() {
		$( '#transcript' ).hide();
	},
	
	progress_show: function() {
		$( '#progress' ).show();
	},
	
	progress_hide: function() {
		$( '#progress' ).hide();
	},
	
	show_changed: function( show ) {
		if ( show ) {
			$( '#content ul li.keep' ).slideUp();
		} else {
			$( '#content ul li.keep' ).slideDown();
		}
		this.show_updated_flag = show;
	},
	
	show_changed_toggle: function() {
		this.show_changed( !this.show_updated_flag );
	},
	
	content: function( html, name ) {
		$( '#content' ).removeClass();
		$( '#content' ).addClass( name );
		$( '#content' ).html( html );
	},
	
	clear: function() {
		this.content( '', '' );
	},
	
	preload: function() {
		this.clear();
	},
	
	template: function( name ) {
		this.content( this.template_get( name ), name );
	},
	
	template_get: function( name ) {
		return $( '#templates ' + '.' + name ).html();
	},
	
	tokenize: function( html, params ) {
		for ( param in params ) {
			html = html.split( '{@' + param + '}' ).join( params[ param ] );
		}
		return html;
	},	
	
	resetlocks: function() {
		this.locks = {};
	},
	
	wclock: function( id ) {
		this.locks[ id ] = true;
		$( '#' + id + ' .repo' ).show().removeClass( 'unlock' ).addClass( 'lock' );
		$( '#' + id + ' .actions' ).fadeTo( 0, 0.5 )
	},
	
	wcprogress: function( id ) {
		$( '#' + id + ' .repo' ).hide();
		$( '#' + id + ' .loader' ).show();
	},
	
	wcunlock: function ( id ) {
		this.locks[ id ] = false;
		delete this.locks[ id ];
		$( '#' + id + ' .repo' ).show().removeClass( 'lock' ).addClass( 'unlock' );
		$( '#' + id + ' .loader' ).hide();
		$( '#' + id + ' .actions' ).fadeTo( 0, 1.0 )
	},
	
	wcislock: function( id ) {
		return this.locks[ id ] == true;
	},
	
	wcerror: function ( id ) {
		this.wcunlock( id );
		$( '#' + id + ' .repo' ).show().removeClass( 'unlock' ).addClass( 'fatal' );
	},	
	
	wcfiles: function( item ) {
		this.wcfiles_clear( item.id );
		if ( item.info.length == 0 ) {
			$( '#' + item.id + ' .files' ).append( 'This working copy is up to date.' );		
			return;
		}
		var ihtml = this.template_get( 'wc-item' );
		$.each( item.info, function( i, file ) {
			file.status_short = svndeploy.wcfiles_status_short( file.status );
			$( '#' + item.id + ' .files' ).append( svndeploy.tokenize( ihtml, file ) );		
		});
	},
	
	wcfiles_status_short: function( st ) {
		return this.status[ st ] != null ? this.status[ st ] : st;
	},
	
	wcfiles_clear: function( id ) {
		$( '#' + id + ' .files' ).html( '' );
	},
	
	wcfiles_progress: function( id ) {
		$( '#' + id + ' .files' ).html( 'Progress...' );
	},
	
	wcfiles_toggle: function( id ) {
		if ( $( '#' + id + ' .details-wrapper' ).is( ':visible' ) ) {
			$( '#' + id + ' .details-wrapper' ).hide();	
		} else {
			$( '#' + id + ' .details-wrapper' ).show();
		}
	},
	
	list: function() {
		this.show_updated_flag = false;
		this.resetlocks();
		this.preload();
		this.progress_show();
		$.getJSON( 'api/wc/list.json', function( json ) {
			if ( json.response.err > 0 ) {
				svndeploy.dialog( 'svn: Unable to load Working Copies' );
 				svndeploy.log( 'svn: Message: ' + json.data );
 				svndeploy.progress_hide();
 				return;
			}
			svndeploy.template( 'wc-list' );
			var ihtml = $( '#content ul' ).html();
			var ihtml_error = svndeploy.template_get( 'wc-list-error' );
			$( '#content ul' ).html( '' );
			if ( json.data.length == 0 ) {
				// no working copies available
				svndeploy.progress_hide();
				svndeploy.dialog( 'svn: No Working Copies Available!' );
				return;
			}	
			$.each( json.data, function( i, item ) {
				if ( !item.err ) {
					item.svndeploy_status = svndeploy.SVNSTATUS_NORMAL;
					$( '#content ul' ).append( svndeploy.tokenize( ihtml, item ) );
					svndeploy.svnstatus_queue( item );
				} else {
					item.svndeploy_status = svndeploy.SVNSTATUS_UNAVAILABLE;
					$( '#content ul' ).append( svndeploy.tokenize( ihtml_error, item ) );
				}
			});
			setTimeout( function() {
				svndeploy.svnstatus_thread();
			}, 200 );
		});
	},
	
	svnstatus_queue: function( item ) {
		this.wclock( item.id );
		this.queue.push( item );
	},
	
	svnstatus_thread: function() {
		var max = this.MAX_SVN_THREAD - this.thread.length;
		for ( var i = 0; i < max && this.queue.length > 0; i++ ) {
			var item = this.queue.shift();
			this.thread.push( item.id );
			this.wcunlock( item.id );
			svndeploy.svnstatus( item.id, item.path );
		}
	},
	
	svnstatus_dequeue: function( id ) {
		var copy = this.thread.concat();
		for ( var i = 0; i < copy.length; i++ ) {
			if ( copy[ i ] == id ) {
				this.thread.splice( i, 1 );
			}
		}
		if ( this.queue.length > 0 ) {
			this.svnstatus_thread();
		}
		if ( this.thread.length == 0 ) {
			this.progress_hide();
			this.dialog( 'svn: Status Queue Completed!' );
		}
	},
	
	svnstatus: function( id, path ) {
		if ( this.wcislock( id ) ) return;
		this.progress_show();	
		this.wclock( id );
		this.wcprogress( id );
		this.wcfiles_clear( id );
		this.wcfiles_progress( id );
		$( '#' + id + ' .version' ).removeClass( 'alpha' );
		$( '#' + id + ' .version' ).removeClass( 'release' );
		$( '#' + id + ' .version' ).addClass( 'beta' ).text( '?' );
		$.ajax({
			url: 'api/wc/status.json',
			dataType: 'json',
			data: { id: id },
			success: function( json ) {
				if ( json.response.err > 0 ) {
					svndeploy.log( 'svn: Unable to get status! Path: ' + path );
	 				svndeploy.log( 'svn: Message: ' + json.data );
	 				$( '#' + id ).removeClass( svndeploy.SVNSTATUS_NORMAL ).addClass( svndeploy.SVNSTATUS_UNAVAILABLE );
	 				if ( svndeploy.show_updated_flag ) {
						$( '#' + id ).slideDown();
					}
					svndeploy.wcerror( id );
					svndeploy.wcfiles( { id: id, info: [] } );
					svndeploy.svnstatus_dequeue( id );
					return;
				}
				var stat = json.data.updates > 0 ? 'alpha' : 'release';
				if ( json.data.updates > 0 ) {
					$( '#' + json.data.id ).removeClass( svndeploy.SVNSTATUS_NORMAL ).addClass( svndeploy.SVNSTATUS_UPDATED );
					if ( svndeploy.show_updated_flag ) {
						$( '#' + json.data.id ).slideDown();
					}
				}
				$( '#' + json.data.id + ' .version' ).removeClass( 'beta' );
				$( '#' + json.data.id + ' .version' ).text( json.data.updates ).addClass( stat );
				svndeploy.wcunlock( json.data.id );
				svndeploy.wcfiles( json.data );
				svndeploy.svnstatus_dequeue( json.data.id );
			},
			error: function( data, errorType, errorMessage ) {
 				svndeploy.log( 'svn: Unable to get status! Path: ' + path );
 				svndeploy.log( 'svn: Message: ' + errorMessage );
 				$( '#' + json.data.id ).removeClass( svndeploy.SVNSTATUS_NORMAL ).addClass( svndeploy.SVNSTATUS_UNAVAILABLE );
				svndeploy.wcerror( id );
				svndeploy.wcfiles( { id: id, info: [] } );
				svndeploy.svnstatus_dequeue( id );
			}
		});
	},
	
	svnup: function( id, path ) {
		if ( this.wcislock( id ) ) return;
		this.wclock( id );
		this.wcprogress( id );
		this.progress_show();
		$.ajax({
			url: 'api/wc/update.json',
			dataType: 'json',
			data: { id: id },
			success: function( json ) {
				if ( json.response.err > 0 ) {
					svndeploy.wcunlock( id );
					svndeploy.dialog( json.data );
				} else {
					svndeploy.log( 'svn: Update result\n\n--------BEGIN-UPDATE-TRANSCRIPT---------\n\n' + json.data + '\n--------END-UPDATE-TRANSCRIPT-----------\n\n' );
					var revision = json.data.toString().match( /\n(.* revision .*\.)/ );
					var err = 0;
					try {
						svndeploy.dialog( revision[ 0 ] );
					} catch ( e ) {
						err++;
						svndeploy.dialog( json.data );
					}
					if ( !err ) {
						var item = $( '#' + id ).removeClass( 'change' ).addClass( 'keep' );
						if ( svndeploy.show_updated_flag ) {
							item.slideUp();
						}
					}
					setTimeout( function() {
						svndeploy.wcunlock( id );
						svndeploy.svnstatus( id, path );
					}, 3000 );
				}
				svndeploy.progress_hide();
			},
			error: function( data, errorType, errorMessage ) {
				svndeploy.dialog( 'svn: Unable to perform update! Path: ' + path );
 				svndeploy.log( 'svn: Message: ' + errorMessage );
				svndeploy.wcerror( id );
				svndeploy.wcfiles( { id: id, info: [] } );
				svndeploy.svnstatus_dequeue( id );
				svndeploy.progress_hide();
			}
		});
	}
}

/** Plugins */

// Simulates PHP's date function
Date.prototype.format=function(format){var returnStr='';var replace=Date.replaceChars;for(var i=0;i<format.length;i++){var curChar=format.charAt(i);if(i-1>=0&&format.charAt(i-1)=="\\"){returnStr+=curChar;}else if(replace[curChar]){returnStr+=replace[curChar].call(this);}else if(curChar!="\\"){returnStr+=curChar;}}return returnStr;};Date.replaceChars={shortMonths:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],longMonths:['January','February','March','April','May','June','July','August','September','October','November','December'],shortDays:['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],longDays:['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],d:function(){return(this.getDate()<10?'0':'')+this.getDate();},D:function(){return Date.replaceChars.shortDays[this.getDay()];},j:function(){return this.getDate();},l:function(){return Date.replaceChars.longDays[this.getDay()];},N:function(){return this.getDay()+1;},S:function(){return(this.getDate()%10==1&&this.getDate()!=11?'st':(this.getDate()%10==2&&this.getDate()!=12?'nd':(this.getDate()%10==3&&this.getDate()!=13?'rd':'th')));},w:function(){return this.getDay();},z:function(){var d=new Date(this.getFullYear(),0,1);return Math.ceil((this-d)/86400000);},W:function(){var d=new Date(this.getFullYear(),0,1);return Math.ceil((((this-d)/86400000)+d.getDay()+1)/7);},F:function(){return Date.replaceChars.longMonths[this.getMonth()];},m:function(){return(this.getMonth()<9?'0':'')+(this.getMonth()+1);},M:function(){return Date.replaceChars.shortMonths[this.getMonth()];},n:function(){return this.getMonth()+1;},t:function(){var d=new Date();return new Date(d.getFullYear(),d.getMonth(),0).getDate()},L:function(){var year=this.getFullYear();return(year%400==0||(year%100!=0&&year%4==0));},o:function(){var d=new Date(this.valueOf());d.setDate(d.getDate()-((this.getDay()+6)%7)+3);return d.getFullYear();},Y:function(){return this.getFullYear();},y:function(){return(''+this.getFullYear()).substr(2);},a:function(){return this.getHours()<12?'am':'pm';},A:function(){return this.getHours()<12?'AM':'PM';},B:function(){return Math.floor((((this.getUTCHours()+1)%24)+this.getUTCMinutes()/60+this.getUTCSeconds()/3600)*1000/24);},g:function(){return this.getHours()%12||12;},G:function(){return this.getHours();},h:function(){return((this.getHours()%12||12)<10?'0':'')+(this.getHours()%12||12);},H:function(){return(this.getHours()<10?'0':'')+this.getHours();},i:function(){return(this.getMinutes()<10?'0':'')+this.getMinutes();},s:function(){return(this.getSeconds()<10?'0':'')+this.getSeconds();},u:function(){var m=this.getMilliseconds();return(m<10?'00':(m<100?'0':''))+m;},e:function(){return"Not Yet Supported";},I:function(){return"Not Yet Supported";},O:function(){return(-this.getTimezoneOffset()<0?'-':'+')+(Math.abs(this.getTimezoneOffset()/60)<10?'0':'')+(Math.abs(this.getTimezoneOffset()/60))+'00';},P:function(){return(-this.getTimezoneOffset()<0?'-':'+')+(Math.abs(this.getTimezoneOffset()/60)<10?'0':'')+(Math.abs(this.getTimezoneOffset()/60))+':00';},T:function(){var m=this.getMonth();this.setMonth(0);var result=this.toTimeString().replace(/^.+ \(?([^\)]+)\)?$/,'$1');this.setMonth(m);return result;},Z:function(){return-this.getTimezoneOffset()*60;},c:function(){return this.format("Y-m-d\\TH:i:sP");},r:function(){return this.toString();},U:function(){return this.getTime()/1000;}};
