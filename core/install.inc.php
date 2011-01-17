<?php

function svndeploy_install_check() {
	return count( svndeploy_config() ) > 0;
}

function svndeploy_install_check_temp() {
	return count( svndeploy_config_temp() ) > 0;
}

function svndeploy_install_check_tmp() {
	return is_writable( SVNDEPLOY_TMP );
}

function svndeploy_install_check_misc() {
	if ( is_writable( SVNDEPLOY_MISC ) ) {
		if ( !file_exists( SVNDEPLOY_REPOS ) ) {
			mkdir( SVNDEPLOY_REPOS );
		}
		if ( !file_exists( SVNDEPLOY_CFG ) ) {
			mkdir( SVNDEPLOY_CFG );
		}
		return TRUE;
	}
	return FALSE;
}

function svndeploy_install_check_htaccess() {
	if ( is_writable( svndeploy_core_rootfile( '.htaccess' ) ) ) {
		svndeploy_core_rootfile( '.htaccess', svndeploy_install_get_htaccess() );
		return TRUE;
	}
	return false;
}

function svndeploy_install_check_crypto() {
	$original = 'svndeploy-install-crypto-aes256';
	$salt = 'c3ZuZGVwbG95';
	$encoded = svndeploy_crypto_encrypt( $original, $salt );
	$decoded = svndeploy_crypto_decrypt( $encoded, $salt );
	return $decoded == $original;
}

function svndeploy_install_check_safe_mode() {
	if ( ini_get( 'safe_mode' ) ) {
		return FALSE;
	}
	return TRUE;
}

function svndeploy_install_check_svn() {
	$err;
	$out = svndeploy_svn_ver( $err );
	if ( $err ) {
		return FALSE;
	}
	return TRUE;
}

function svndeploy_install_check_deployment_path() {
	return file_exists( SVNDEPLOY_BASE );
}

function svndeploy_install_check_svn_privileges( &$err = NULL ) {
	if ( !is_readable( SVNDEPLOY_BASE ) ) {
		return FALSE;
	}
	// write test
	$tname = '/.svndeploy' . md5( SVNDEPLOY_BASE );
	$tname2 = '/.svndeploy' . md5( microtime() );
	$testdir = SVNDEPLOY_BASE . $tname;
	$testdir2 = $testdir . $tname2;
	if ( !is_dir( $testdir ) ) {
		$command = '/bin/mkdir -m 777 ' . $testdir;
		$dir = svndeploy_shell_command( $command, $err );
		$is_dir = is_dir( $testdir );
		if ( !$is_dir || !is_writable( $testdir ) ) {
			return FALSE;
		}
	}
	// try to create sub-folder
	$command = '/bin/mkdir -m 777 ' . $testdir2;
	$dir = svndeploy_shell_command( $command );
	$is_dir = is_dir( $testdir2 );
	if ( !$is_dir || !is_writable( $testdir2 ) ) {
		return FALSE;
	}
	rmdir( $testdir2 );
	return TRUE;
}

function svndeploy_install_get_htaccess() {	
	$htaccess = '<IfModule mod_rewrite.c>
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} 	!-f
RewriteCond %{REQUEST_FILENAME} 	!-d
RewriteBase {%INSTALL_PATH%}
RewriteRule ^(.*)$ index.php?q=$1 [L,QSA]
</IfModule>';

	$path = explode( '/', $_SERVER[ 'REQUEST_URI' ] );
	array_pop( $path );
	$path = implode( '/', $path );
	$htaccess = str_replace( '{%INSTALL_PATH%}', $path, $htaccess );
	return $htaccess;	
}

function svndeploy_install_whoami() {
	return trim( shell_exec( 'whoami' ) );
}

function svndeploy_install_do() {
	// save config & salt file
	svndeploy_config_save_from_temp();
	svndeploy_config_temp_clear();
}
