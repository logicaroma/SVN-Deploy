<?php
	
function svndeploy_parse_wc_name( $value ) {
	$arr = explode( '/', $value );
	array_pop( $arr );
	return array_pop( $arr );
}

function svndeploy_scan_wc( $dir, $level = 0 ) {
	$dir .= '/';
	$list = array();
	$child = array();
	if ( is_dir( $dir ) && $level <= SVNDEPLOY_RECURSION_LEVEL && is_readable( $dir ) ) {
		if ( $dh = opendir( $dir ) ) {
			while ( ( $file = readdir( $dh ) ) !== false ) {
				$rp = $dir . $file;
				if ( is_dir( $rp ) && $file != '..' && $file != '.' ) {
					if ( $file == '.svn' ) {
						// working copy found!
						$list[ svndeploy_id( $dir ) ] = $dir;
						return $list;
					} else {
						$child[] = $rp;
					}
				}
			}	
	        closedir($dh);
    	}
    	if ( SVNDEPLOY_SEARCH_RECURSIVE ) {
    		if ( count( $child ) > 0 ) {
	    		foreach ( $child as $key => $value ) {
	    			$list = array_merge( $list, svndeploy_scan_wc( $value, $level + 1 ) );
	    		}
    		}
    	}
	}
	return $list;
}

function svndeploy_get_wc( $id = NULL ) {
	static $wc;
	
	if ( !isset( $wc ) ) {
		$wc = svndeploy_scan_wc( SVNDEPLOY_BASE );
	}
	
	if ( $id != NULL ) {
		return isset( $wc[ $id ] ) ? $wc[ $id ] : FALSE;
	}
	
	return $wc;
}

function svndeploy_id( $path ) {
	return strtoupper( 'uid' . md5( $path ) );
}

function svndeploy_auth_signature() {
	return sha1( $_SERVER[ 'REMOTE_ADDR' ] . session_id() . svndeploy_config_get_private_key() );
}

function svndeploy_logged( $auth = FALSE ) {
	if ( $auth ) {
		session_regenerate_id( TRUE );
		$_SESSION[ 'SVNDEPLOY_AUTH_SIGNATURE' ] = svndeploy_auth_signature();
	}
	if ( !isset( $_SESSION[ 'SVNDEPLOY_AUTH_SIGNATURE' ] ) ) {
		return FALSE;
	}
	return $_SESSION[ 'SVNDEPLOY_AUTH_SIGNATURE' ] == svndeploy_auth_signature();
}

function svndeploy_start() {
	if ( !svndeploy_install_check() ) {
		die( 'SVN Deploy is not installed. Please run <a href="setup.php">setup.php</a> to start setup.' );	
	}
	
	// create shortcuts
	svndeploy_config_shortcuts();
	
	$service = isset( $_GET[ 'q' ] ) ? $_GET[ 'q' ] : '';
	$services = svndeploy_srv_get();
	$pattern = '/api\/(.*)/';
	preg_match_all( $pattern, $service, &$res );
	$api = '';
	if ( isset( $res[ 1 ][ 0 ] ) ) {
		$api = $res[ 1 ][ 0 ];
	}
	if ( isset( $services[ $api ] ) ) {
		$auth = $services[ $api ][ 'auth' ];
		if ( $auth && !svndeploy_logged() ) {
			svndeploy_srv_auth_fail();
			return;
		}
		$cb = $services[ $api ][ 'name' ];
		if ( function_exists( $cb ) ) {
			call_user_func( $cb );
		}
	} else {
		if ( $api != '' ) {
			svndeploy_srv_out( 'API Service Not Found! [ ' . $api . ' ]', SVNDEPLOY_ERR_404 );
		}
	}
}

function svndeploy_wc_changed( $wc, &$signature = NULL, &$err = NULL ) {
	$info_wc = svndeploy_svn_info( $wc, $err );
	$repo_url = $info_wc[ 'url' ];
	$info_repo = svndeploy_svn_info( $repo_url, $err );
	$wc_rev = $info_wc[ 'last_changed_rev' ];
	if ( !isset( $info_repo[ 'last_changed_rev' ] ) ) {
		$repo_rev = '0';
	} else {
		$repo_rev = $info_repo[ 'last_changed_rev' ];
	}
	$signature = svndeploy_id( $wc ) . ':' . $wc_rev . ':' . $repo_rev;
	return $wc_rev != $repo_rev;
}

function svndeploy_wc_signature_cache( $id, $signature = FALSE ) {
	$signature_file = 'signature' . $id;
	if ( $signature ) {
		svndeploy_core_tmpfile( $signature_file, $signature );
		return $signature_file;
	}
	if ( file_exists( svndeploy_core_tmpfile( $signature_file ) ) ) {
		return trim( svndeploy_core_tmpfile_read( $signature_file ) );
	}
	return FALSE;
}

function svndeploy_wc_signature_compare( $id, $wc, &$signature, &$changed ) {
	$signature_cache = svndeploy_wc_signature_cache( $id );
	$changed = svndeploy_wc_changed( $wc, $signature );
	return $signature_cache == $signature;
}

function svndeploy_wc_compare( $wc, &$signature, &$changed ) {
	$id = svndeploy_id( $wc );
	return svndeploy_wc_signature_compare( $id, $wc, $signature, $changed );
}

function svndeploy_wc_cache( $wc, $cache = NULL, $signature = NULL ) {
	$id = svndeploy_id( $wc );
	$cache_file = 'cache' . $id;
	if ( $cache && $signature ) {
		$serialized = serialize( $cache );
		svndeploy_core_tmpfile( $cache_file, $serialized );
		svndeploy_wc_signature_cache( $id, $signature );
		return TRUE;
	}
	if ( file_exists( svndeploy_core_tmpfile( $cache_file ) ) ) {
		return unserialize( trim( svndeploy_core_tmpfile_read( $cache_file ) ) );
	}
}

function svndeploy_srv_out( $data, $err = 0 ) {
	$out = array(
		'response' => array(
			'status'	=> ( $err > 0 ? 'KO' : 'OK' ),
			'err'		=> $err,
			'time'		=> date( 'r', time() )
		),
		
		'data' => $err > 0 ? ( 'Error ' . $err . ': ' . $data ) : $data
	);
	if ( ob_get_length() > 0 ) {
		ob_end_clean();
	}
	echo json_encode( $out );
	exit;
}

function svndeploy_srv_auth_fail() {
	svndeploy_srv_out( 'svn: Authorization Denied!', SVNDEPLOY_ERR_403 );
}

/** API */

function svndeploy_srv_get() {
	return array(
		'user/login.json' => array( 
			'auth'	=> FALSE,
			'name'	=> 'svndeploy_srv_login'
			),
		'user/logout.json' => array( 
			'auth'	=> FALSE,
			'name'	=> 'svndeploy_srv_logout'
			),
		'wc/list.json' => array( 
			'auth'	=> TRUE,
			'name'	=> 'svndeploy_srv_wc_list'
			),
		'wc/status.json' => array( 
			'auth'	=> TRUE,
			'name'	=> 'svndeploy_srv_wc_status'
			),
		'wc/update.json' => array( 
			'auth'	=> TRUE,
			'name'	=> 'svndeploy_srv_wc_update'
			)
	);
}

function svndeploy_srv_login() {
	if ( svndeploy_logged() ) {
		svndeploy_srv_out( array() );
		return;
	}
	$user = isset( $_POST[ 'user' ] ) ? $_POST[ 'user' ] : FALSE;
	$pass = isset( $_POST[ 'pass' ] ) ? $_POST[ 'pass' ] : FALSE;
	
	if ( $user != SVNDEPLOY_SVNDEPLOY_USR || $pass != SVNDEPLOY_SVNDEPLOY_PWD ) {
		if ( isset( $_POST[ 'redirect' ] ) ) {
			header( 'Location: ' . $_POST[ 'redirect' ] . '?error' );
			exit;
		} else {
			svndeploy_srv_auth_fail();
		}
	}
	svndeploy_logged( TRUE );
	if ( isset( $_GET[ 'redirect' ] ) ) {
		header( 'Location: ' . $_GET[ 'redirect' ] );
		exit;
	} else {
		svndeploy_srv_out( array() );	
	}
}

function svndeploy_srv_logout() {
	session_unset();
	session_destroy();
	if ( isset( $_GET[ 'redirect' ] ) ) {
		header( 'Location: ' . $_GET[ 'redirect' ] );
		exit;
	} else {
		svndeploy_srv_out( array() );	
	}
}
	
function svndeploy_srv_wc_list() {
	$wc = svndeploy_get_wc();
	$out = array();
	foreach( $wc as $key => $value ) {
		$changed = svndeploy_wc_changed( $value );
		$props = array(
			'id'	=> svndeploy_id( $value ),
			'name'	=> svndeploy_parse_wc_name( $value ),
			'path'	=> $value,
			'repo_changed' => $changed,
			'repo_changed_text' => $changed ? 'change' : 'keep'
		);
		$err;
		$info = svndeploy_svn_info( $value, $err );
		$res;
		if ( $err ) {
			$props[ 'err' ] = $err;
			$res = $props;
		} else {
			$res = array_merge( $props, $info );
		}
		$out[] = $res;
	}
	svndeploy_srv_out( $out );
}

function svndeploy_srv_wc_status() {
	$path = svndeploy_get_wc( $_GET[ 'id' ] );
	$err; $signature;
	if ( svndeploy_wc_compare( $path, $signature, $changed ) ) {
		svndeploy_srv_out( svndeploy_wc_cache( $path ) );
		continue;
	}
	$status = svndeploy_svn_status( $path, $err );
	if ( $err ) {
		svndeploy_srv_out( $err, SVNDEPLOY_ERR_500 );
	}
	$out = array(
		'id'		=> svndeploy_id( $path ),
		'name'		=> svndeploy_parse_wc_name( $path ),
		'updates'	=> count( $status ),
		'info'		=> $status
	);
	svndeploy_wc_cache( $path, $out, $signature );
	svndeploy_srv_out( $out );
}

function svndeploy_srv_wc_update() {
	$path = svndeploy_get_wc( $_GET[ 'id' ] );
	if ( $path ) {
		$err;
		$out = svndeploy_svn_update( $path, $err );
		if ( $err ) {
			svndeploy_srv_out( $err, SVNDEPLOY_ERR_500 );	
		}
		svndeploy_srv_out( $out );
	} else {
		svndeploy_srv_out( 'svn: Cannot find Working Copy UID: <i>' . $_GET[ 'id' ] . '</i>', SVNDEPLOY_ERR_404 );
	}
	
}

?>