<?php

/** SVN root folder */ 
define( 'SVNDEPLOY_ROOT', dirname( dirname( __FILE__ ) ) );

/** SVN tmp folder */ 
define( 'SVNDEPLOY_TMP', SVNDEPLOY_ROOT . '/tmp' );

/** SVN misc folder */ 
define( 'SVNDEPLOY_MISC', SVNDEPLOY_ROOT . '/misc' );

/** SVN config folder */ 
define( 'SVNDEPLOY_CFG', SVNDEPLOY_MISC . '/config' );

/** SVN repos folder */ 
define( 'SVNDEPLOY_REPOS', SVNDEPLOY_MISC . '/repos' );

function svndeploy_core_fwrite( $path, $content = NULL ) {
	if ( $content != NULL ) {
		$fp = fopen( $path, 'w' );
		fwrite( $fp, $content );
		fclose( $fp );
	}
	return $path;
}

function svndeploy_core_fread( $path ) {
	$fp = fopen( $path, 'r' );
	$content = fread( $fp, filesize( $path ) );
	fclose( $fp );
	return $content;
}

function svndeploy_core_rootfile( $name, $content = NULL ) {
	return svndeploy_core_fwrite( SVNDEPLOY_ROOT . '/' . $name, $content );
}

function svndeploy_core_miscfile( $name, $content = NULL ) {
	return svndeploy_core_fwrite( SVNDEPLOY_MISC . '/' . $name, $content );
}

function svndeploy_core_cfgfile( $name, $content = NULL ) {
	return svndeploy_core_fwrite( SVNDEPLOY_CFG . '/' . $name, $content );
}

function svndeploy_core_tmpfile( $name, $content = NULL ) {
	return svndeploy_core_fwrite( SVNDEPLOY_TMP . '/' . $name, $content );
}

function svndeploy_core_tmpcreate( $prefix, $content = NULL ) {
	return svndeploy_core_fwrite( SVNDEPLOY_TMP . '/' . $prefix . md5( microtime() . rand() ) , $content );
}

function svndeploy_core_rootfile_read( $name ) {
	return svndeploy_core_fread( SVNDEPLOY_ROOT . '/' . $name );
}

function svndeploy_core_miscfile_read( $name ) {
	return svndeploy_core_fread( SVNDEPLOY_MISC . '/' . $name );
}

function svndeploy_core_cfgfile_read( $name ) {
	return svndeploy_core_fread( SVNDEPLOY_CFG . '/' . $name );
}

function svndeploy_core_tmpfile_read( $name ) {
	return svndeploy_core_fread( SVNDEPLOY_TMP . '/' . $name );
}

require_once( 'shell.inc.php' );
require_once( 'crypto.inc.php' );
require_once( 'config.inc.php' );
require_once( 'install.inc.php' );
require_once( 'svn.inc.php' );
require_once( 'svndeploy.inc.php' );
require_once( 'xml.inc.php' );

/** Hide PHP Errors */
if ( SVNDEPLOY_HIDE_PHP_ERRORS ) {
	error_reporting( 0 );
}

/** Start PHP Session */
session_start();

?>