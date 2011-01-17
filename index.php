<?php
/**
 * The base configurations of the SVNDeploy Application.
 *
 * This file has the following configurations: SVNDeploy Base Path, Recursive working copies search,
 * SVN username, SVN password.
 *
 * @package SVNDeploy
 */

require_once( 'core/bootstrap.inc.php' ); 

/** INIT */
svndeploy_start();

$referral = array_shift( explode( '?', $_SERVER[ 'REQUEST_URI' ] ) );

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="Pragma" content="no-cache" />
		
		<title>SVN Deploy</title>
		
		<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="http://www.mutado.com/mobile/feed/" />
		
		<link href="assets/favicon.png" rel="shortcut icon"/>
		<link href="assets/favicon.png" rel="icon"/>

		<link type="text/css" rel="stylesheet" href="https://api.mutado.com/mobile/shared/style.css" />
		<link type="text/css" rel="stylesheet" href="https://api.mutado.com/mobile/shared/generic.css" />
		<link type="text/css" rel="stylesheet" href="assets/style.css" />
		
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js"></script>
		<script type="text/javascript" src="js/svndeploy.js"></script>
		
		<script type="text/javascript">
			
			$( document ).ready( function() {
				<?php if ( svndeploy_logged() ) : ?>
				svndeploy.init();
				<?php else : ?>
				svndeploy.login();
				<?php endif; ?>
			});
			
			function check_login_form() {
				var err = 0;
				var fields = [
					'user',
					'pass'
				];
				
				fields = fields.reverse();
				
				for ( var field in fields ) {
					var fid = '#' + fields[ field ];
					var yet = false;
					if ( $( fid ).val() == '' ) {
						$( fid ).addClass( 'missing' );
						if ( !yet ) {
							$( fid ).focus();
						}
						err++;
					} else {
						$( fid ).removeClass( 'missing' );
					}
				}
				return err == 0;
			}
					
		</script>
		
	</head>
	
	<body class="blue">
		
		<div id="svndeploy-dialog" class="dialog-wrapper">
			<div class="wrap1">
				<div class="wrap2">
					<div class="wrap3">
						<div class="content"></div>
					</div>
				</div>
			</div>
		</div>
		
		<div id="transcript">
			<div class="tclose"><a href="javascript:;" onclick="svndeploy.log_hide();">close</a></div>
			<div class="tclear"><a href="javascript:;" onclick="svndeploy.log_clear();">clear</a></div>
			<pre></pre>
		</div>
		
		<div id="login">
			<div class="login_box">
				<form action="api/user/login.json?redirect=<?php echo urlencode( $referral ); ?>" method="post" onsubmit="return check_login_form();">
					<fieldset>
						
						<span style="display: block; width: 100%; height: 20px; clear: both;"></span>
						
						<label>Username:</label>
						<input tabindex="1" type="text" id="user" name="user" class="text" value=""/>
						
						<label>Password:</label>
						<input tabindex="2" type="password" id="pass" name="pass" class="text" value=""/>
						
						<span style="display: block; width: 100%; clear: both;"></span>
						
						<input type="submit" class="submit" value="Access SVN Deploy" />
							
					</fieldset>				
				</form>
			
			</div>
		</div>
		
		<h1>
		<span style="padding: 0 20px 0 0;">SVN Deploy</span>
			<a class="selected" href="">WCs</a>
			<a href="javascript:;" onclick="svndeploy.show_changed_toggle();">Updates</a>
			<a href="javascript:;" onclick="svndeploy.list();" class="refresh"></a>
			<span style="padding-right: 50px;"></span>
			<a href="javascript:;" onclick="svndeploy.log_show();">Log</a>
			<!--a href="/mobile">Checkout</a-->
			<a href="setup.php">Setup</a>
			<a href="api/user/logout.json?redirect=<?php echo urlencode( $referral ); ?>">Logout</a>
			<span class="right">ver <?php echo SVNDEPLOY_VER ?> <span class="version <?php echo SVNDEPLOY_STATUS ?>"><?php echo SVNDEPLOY_STATUS ?></span></span>
		</h1>
		
		<div id="progress"><img class="loader" align="top" src="assets/ajax-loader-main.gif" /><span>Action in Progress</span></div>
		
		<div id="content">
		
		</div>
								
		<script type="text/javascript" src="https://api.mutado.com/mobile/shared/js/footer.js"></script>
		
		<!-- templates -->
		
		<div id="templates" style="display: none;">
			
			<!-- wc list template -->
			
			<div class="wc-list">
				<ul class="list-big">
					<li id="{@id}" class="{@svndeploy_status} {@repo_changed_text}">
						<div class="outer">
							<div class="inner">
								<label onclick="svndeploy.wcfiles_toggle( '{@id}' ); self.blur();"><img class="loader" align="top" src="assets/ajax-loader.gif" style="display: none" /><span class="repo"></span> {@name} <span class="rev">rev:{@revision}</span> <span class="version beta">?</span></label>
								<p>{@last_changed_author} @ {@last_changed_date}</p>
								<div class="details-wrapper">
									<span class="box-title grey">info</span>
									<span class="details">								
										<span class="label">UID:</span><span class="value">{@id}</span>
										<br/>
										<span class="label">Local Path:</span><span class="value">{@path}</span>
										<br/>
										<span class="label">URL:</span><span class="value">{@url}</span>
										<br/>
										<span class="label">Repository Root:</span><span class="value">{@repository_root}</span>
										<br/>
										<span class="label">Last Changed:</span><span class="value">{@last_changed_date}</span>
										<br/>										
										<span class="label">Revision:</span><span class="value">{@revision}</span>
										<br style="clear: left" />
									</span>
									<br/>
									<span class="box-title yellow">changelog</span>
									<code class="files"></code>
									<br/>
								</div>
							</div>
						</div>
						<div class="actions">
							<a href="javascript:;" class="button-mid" onclick="svndeploy.svnup( '{@id}', '{@path}' )">UPDATE ALL</a>
							<a href="javascript:;" class="button-mid" onclick="svndeploy.svnstatus( '{@id}', '{@path}' )">REFRESH</a>
						</div>
					</li>
				</ul>
			</div>
			
			<!-- wc list template end -->
			
			<!-- wc list error template -->
			
			<div class="wc-list-error">				
				<li id="{@id}" class="{@svndeploy_status}">
					<div class="outer">
						<div class="inner">
							<label><span class="repo fatal"></span> {@name} <span class="version undefined">fatal</span></label>
							<p>{@err}</p>
						</div>
					</div>
				</li>
			</div>
			
			<!-- wc list error template end -->
			
			<!-- wc item template -->
			
			<div class="wc-item">
				<span><span class="file-action {@status}">{@status_short}</span> {@path}</span><br/>
			</div>
			
			<!-- wc item template end -->
			
		</div>
		
	</body>
	
</html>

