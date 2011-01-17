<?php

define( 'SVNDEPLOY_STATUS_NULL',			0 );
define( 'SVNDEPLOY_STATUS_NONE',			1 );
define( 'SVNDEPLOY_STATUS_UNVERSIONED',		2 );
define( 'SVNDEPLOY_STATUS_NORMAL',			3 );
define( 'SVNDEPLOY_STATUS_ADDED',			4 );
define( 'SVNDEPLOY_STATUS_MISSING',			5 );
define( 'SVNDEPLOY_STATUS_DELETED',			6 );
define( 'SVNDEPLOY_STATUS_REPLACED',		7 );
define( 'SVNDEPLOY_STATUS_MODIFIED',		8 );
define( 'SVNDEPLOY_STATUS_MERGED',			9 );
define( 'SVNDEPLOY_STATUS_CONFLICTED',		10 );
define( 'SVNDEPLOY_STATUS_IGNORED',			11 );
define( 'SVNDEPLOY_STATUS_OBSTRUCTED',		12 );
define( 'SVNDEPLOY_STATUS_EXTERNAL',		13 );
define( 'SVNDEPLOY_STATUS_INCOMPLETE',		14 );
		
function svndeploy_svn_command( $command, &$err = NULL, $auth = FALSE, $passive = FALSE ) {
	$command = SVNDEPLOY_BIN . ' ' . $command;
	if ( $auth ) {
		$command .= ' --username ' . SVNDEPLOY_USR . ' --password ' . SVNDEPLOY_PWD;
	}
	if ( $passive ) {
		$command .= ' --non-interactive';
	}
	return svndeploy_shell_command( $command, $err );
}

function svndeploy_svn_ver( &$err = NULL ) {
	$out = svndeploy_svn_command( '--version --quiet', $err );
	if ( $err ) {
		return $err;
	}
	return $out;
}

function svndeploy_svn_info( $dir, &$err = NULL ) {
	$command = 'info ' . $dir;
	$out = svndeploy_svn_command( $command, $err, TRUE, TRUE );
	$out = explode( PHP_EOL, $out );
	$props;
	foreach( $out as $value ) {
		if ( $value != '' ) {
			$key = str_replace( ' ', '_', strtolower( preg_replace( '/^(.*?): (.*)/', '$1', $value ) ) );
			$props[ $key ] = preg_replace( '/^(.*?): /', '', $value );
		}	
	}
	array_shift( $props );
	return $props;
}

function svndeploy_svn_status_list() {
	$values = array(
			'none'				=> SVNDEPLOY_STATUS_NONE,
			'unversioned'		=> SVNDEPLOY_STATUS_UNVERSIONED,
			'normal'			=> SVNDEPLOY_STATUS_NORMAL,
			'added'				=> SVNDEPLOY_STATUS_ADDED,
			'missing'			=> SVNDEPLOY_STATUS_MISSING,
			'deleted'			=> SVNDEPLOY_STATUS_DELETED,
			'replaced'			=> SVNDEPLOY_STATUS_REPLACED,
			'modified'			=> SVNDEPLOY_STATUS_MODIFIED,
			'merged'			=> SVNDEPLOY_STATUS_MERGED,
			'conflicted'		=> SVNDEPLOY_STATUS_CONFLICTED,
			'ignored'			=> SVNDEPLOY_STATUS_IGNORED,
			'obstructed'		=> SVNDEPLOY_STATUS_OBSTRUCTED,
			'external'			=> SVNDEPLOY_STATUS_EXTERNAL,
			'incomplete'		=> SVNDEPLOY_STATUS_INCOMPLETE
		);
	return $values;
}

function svndeploy_svn_status_parse( $status = NULL ) {
	static $values;
	if ( !$values ) {
		$values = svndeploy_svn_status_list();
	}
	return isset( $values[ $status ] ) ? $values[ $status ] : SVNDEPLOY_STATUS_NULL;
}

function svndeploy_svn_status_reverse( $int ) {
	static $values;
	if ( !$values ) {
		$record = svndeploy_svn_status_list();
		foreach( $record as $key => $value ) {
			$values[ $value ] = $key;
		}
	}
	return $values[ $int ];
}

function svndeploy_svn_status( $dir, &$err = NULL ) {
	$command = 'status ' . $dir . ' --show-updates --xml';
	$out = svndeploy_svn_command( $command, $err, TRUE, TRUE );
	try {
		error_reporting( 0 );
		$xml = simplexml_load_string( $out );
		$arr = svndeploy_simplexml_to_array( simplexml_load_string( $out ) );
	} catch ( Exception $e ) {
		return array();
	}
	if ( !isset( $arr[ 'target' ][ 'entry' ] ) ) {
		return array();
	}
	$entries = $arr[ 'target' ][ 'entry' ];
	if ( !isset( $entries[ 0 ] ) ) {
		$entries = array( $entries );
	}
	$remote_upd = array();
	foreach( $entries as $key => $value ) {
		$wc_status_p = SVNDEPLOY_STATUS_NULL;
		$wc_status_i = SVNDEPLOY_STATUS_NULL;
		$repo_status_p = SVNDEPLOY_STATUS_NULL;
		$repo_status_i = SVNDEPLOY_STATUS_NULL;
		if ( isset( $value[ 'wc-status' ] ) ) {
			$wc_status_p = svndeploy_svn_status_parse( $value[ 'wc-status' ][ 'props' ] );
			$wc_status_i = svndeploy_svn_status_parse( $value[ 'wc-status' ][ 'item' ] );
		}
		if ( isset( $value[ 'repos-status' ] ) ) {
			$repo_status_p = svndeploy_svn_status_parse( $value[ 'repos-status' ][ 'props' ] );
			$repo_status_i = svndeploy_svn_status_parse( $value[ 'repos-status' ][ 'item' ] );	
		}		
		$wc_status = max( $wc_status_p, $wc_status_i );
		$repo_status = max( $repo_status_p, $repo_status_i );
		if ( $wc_status != SVNDEPLOY_STATUS_MISSING && $repo_status < SVNDEPLOY_STATUS_ADDED ) {
			// skip 
			continue;
		}
		$status_value = max( $wc_status, $repo_status );
		$status = svndeploy_svn_status_reverse( $status_value );
		$path = $value[ 'path' ];
		$path = str_replace( $dir, '', $path );
		$entry = array(
			'path'		=> $path,
			'status'	=> $status
		); 
		if ( isset( $wc_status[ 'commit' ] ) ) {
			$entry[ 'commit' ] = $wc_status[ 'commit' ];
		}
		$remote_upd[] = $entry;
	}
	return array_reverse( $remote_upd );
}

function svndeploy_svn_update( $dir, &$err = NULL ) {
	$command = 'up ' . $dir;
	$out = svndeploy_svn_command( $command, $err, TRUE, TRUE );
	return $out;
}
	
?>