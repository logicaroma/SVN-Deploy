<?php

/**
 * The base configurations of the SVNDeploy Application.
 *
 * This file has the following configurations: SVNDeploy Base Path, Recursive working copies search,
 * SVN username, SVN password.
 *
 * @package SVNDeploy
 */

/** SVN version */ 
define( 'SVNDEPLOY_VER', '0.6.0.' . svndeploy_config_rev() );

/** SVN development status */ 
define( 'SVNDEPLOY_STATUS', 'beta' );

/** SVN debug errors */ 
define( 'SVNDEPLOY_HIDE_PHP_ERRORS', 0 );

/** SVN custom config files */ 
define( 'SVNDEPLOY_CONFIG', 'svndeploy-config' );
define( 'SVNDEPLOY_SALT_FILE', 'svndeploy-salt.php' );	

/** SVN salt constant key */ 
define( 'SVNDEPLOY_SALT_KEY', 'SVNDEPLOY_SALT' );

/** SVN temp config session key */ 
define( 'SVNDEPLOY_TEMP_CONFIG', 'SVNDEPLOY_TEMP_CONFIG' );
define( 'SVNDEPLOY_TEMP_SALT', 'SVNDEPLOY_TEMP_SALT' );

/** Search for SVN working copies recursively */ 
define( 'SVNDEPLOY_SEARCH_RECURSIVE', 1 );

/** Search for SVN working copies recursively */ 
define( 'SVNDEPLOY_RECURSION_LEVEL', 2 );

/** Errors */ 
define( 'SVNDEPLOY_ERR_403', 403 );
define( 'SVNDEPLOY_ERR_404', 404 );
define( 'SVNDEPLOY_ERR_500', 500 );


function svndeploy_config_rev() {
	$content = svndeploy_core_rootfile_read( 'CHANGELOG.md' );
	$pattern = '/\*\* \$Rev: (.*) \$ \*/';
	preg_match_all( $pattern, $content, &$res );
	return $res[ 1 ][ 0 ];
}

function svndeploy_config_create_salt( $secret ) {
	$ifconfig = shell_exec( '/sbin/ifconfig' );
	$pattern = '/ ([a-f0-9]{2}(:)?){6} /';
	preg_match_all( $pattern, $ifconfig, &$hwds );
	$pass = str_replace( ' ', '', implode( ':', $hwds[ 0 ] ) );
	$rand = md5( $pass ) . md5( $secret . microtime() . rand() );
	$key = base64_encode( sha1( $rand ) );
	return $key;
}

function svndeploy_config( $key = NULL, $value = NULL ) {
	
	static $config;
	
	if ( !isset( $config ) ) {
		$cfg = svndeploy_core_cfgfile( SVNDEPLOY_CONFIG );
		if ( !file_exists( $cfg ) ) {
			$config = array();
		} else {
			$salt = svndeploy_config_get_private_key();
			$config_encoded = svndeploy_core_cfgfile_read( SVNDEPLOY_CONFIG );
			$config_temp = svndeploy_crypto_decrypt( $config_encoded, $salt );
			$config = unserialize( $config_temp );
		}
	}
	
	if ( $key != NULL && $value !== NULL ) {
		$config[ $key ] = $value;
		return $config;
	}
	
	if ( $key != NULL ) {
		return isset( $config[ $key ] ) ? $config[ $key ] : NULL;
	}
	
	return $config;
}

function svndeploy_config_temp( $key = NULL, $value = NULL ) {
	
	static $config;
	
	if ( !isset( $config ) ) {
		if ( !isset( $_SESSION[ SVNDEPLOY_TEMP_CONFIG ] ) || !file_exists( $_SESSION[ SVNDEPLOY_TEMP_CONFIG ] ) ) {
			$config = array();
		} else {
			$temp_file = $_SESSION[ SVNDEPLOY_TEMP_CONFIG ];
			$salt = $_SESSION[ SVNDEPLOY_TEMP_SALT ];
			$fp = fopen( $temp_file, 'r' );
			$config_encoded = fread( $fp, filesize( $temp_file ) ); 
			$config_temp = unserialize( svndeploy_crypto_decrypt( $config_encoded, $salt ) );
			$config = $config_temp;
		}
	}
	
	if ( $key != NULL && $value !== NULL ) {
		$config[ $key ] = $value;
		return $config;
	}
	
	if ( $key != NULL ) {
		return isset( $config[ $key ] ) ? $config[ $key ] : NULL;
	}
	
	return $config;
}

function svndeploy_config_save( $config, $secret ) {
	$salt = svndeploy_config_set_private_key( $secret );
	$config_encoded = svndeploy_crypto_encrypt( serialize( $config ), $salt );
	svndeploy_core_cfgfile( SVNDEPLOY_CONFIG, $config_encoded );
	return TRUE;
}

function svndeploy_config_save_from_current() {
	$config = svndeploy_config();
	$secret = svndeploy_config( 'SVNDEPLOY_USR' ) . ':' . svndeploy_config( 'SVNDEPLOY_PWD' );
	svndeploy_config_save( $config, $secret );
	return TRUE;
}

function svndeploy_config_save_from_temp() {
	if ( !isset( $_SESSION[ SVNDEPLOY_TEMP_CONFIG ] ) || !file_exists( $_SESSION[ SVNDEPLOY_TEMP_CONFIG ] ) ) {
		return FALSE;
	}
	$config = svndeploy_config_temp();
	if ( count( $config ) > 0 ) {
		$secret = svndeploy_config_temp( 'SVNDEPLOY_USR' ) . ':' . svndeploy_config_temp( 'SVNDEPLOY_PWD' );
		svndeploy_config_save( $config, $secret );
		return TRUE;
	}
	return FALSE;
}

function svndeploy_config_temp_save( $secret ) {
	if ( !isset( $_SESSION[ SVNDEPLOY_TEMP_CONFIG ] ) || !$_SESSION[ SVNDEPLOY_TEMP_CONFIG ] || !is_writable( $_SESSION[ SVNDEPLOY_TEMP_CONFIG ] ) ) {
		$temp_file = svndeploy_core_tmpcreate( 'install' );
		$_SESSION[ SVNDEPLOY_TEMP_CONFIG ] = $temp_file;
	}
	if ( $secret ) {
		$salt = svndeploy_config_create_salt( $secret );
		$_SESSION[ SVNDEPLOY_TEMP_SALT ] = $salt;
	}
	$config_temp = svndeploy_config_temp();
	$config_encoded = svndeploy_crypto_encrypt( serialize( $config_temp ), $_SESSION[ SVNDEPLOY_TEMP_SALT ] );
	if ( svndeploy_install_check_tmp() ) {
		$fp = fopen( $_SESSION[ SVNDEPLOY_TEMP_CONFIG ], 'w' );
		fwrite( $fp, $config_encoded );
		fclose( $fp );
		return TRUE;
	}
	return FALSE;
}

function svndeploy_config_temp_clear() {
	if ( isset( $_SESSION[ SVNDEPLOY_TEMP_CONFIG ] ) ) {
		if ( file_exists( $_SESSION[ SVNDEPLOY_TEMP_CONFIG ] ) ) {
			unlink( $_SESSION[ SVNDEPLOY_TEMP_CONFIG ] );
		}
		unset( $_SESSION[ SVNDEPLOY_TEMP_CONFIG ] );
		unset( $_SESSION[ SVNDEPLOY_TEMP_SALT ] );
	}
}

function svndeploy_config_set_private_key( $secret ) {
	$salt = svndeploy_config_create_salt( $secret );
	$include = '<?php define( \'' . SVNDEPLOY_SALT_KEY . '\', \'' . $salt . '\' ); ?>';
	$file = svndeploy_core_cfgfile( SVNDEPLOY_SALT_FILE, $include );
	chmod( $file, 0600 );
	return $salt;
}

function svndeploy_config_get_private_key() {
	$include = svndeploy_core_cfgfile( SVNDEPLOY_SALT_FILE );
	if ( file_exists( $include ) ) {
		include_once( $include );
		return SVNDEPLOY_SALT;
	}
	return 0;
}

function svndeploy_config_exists() {
	return count( svndeploy_config() ) > 0;
}

function svndeploy_config_shortcuts( $temp = FALSE ) {
	$config = $temp ? svndeploy_config_temp() : svndeploy_config();
	foreach ( $config as $key => $value ) {
		if ( !defined( $key ) ) {
			define( $key, $value );
		}
	}
}

function svndeploy_config_shortcuts_temp() {
	svndeploy_config_shortcuts( TRUE );
}

?>