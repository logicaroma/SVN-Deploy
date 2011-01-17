<?php

function svndeploy_shell_command( $command, &$err = NULL ) {
	if ( SVNDEPLOY_USE_SUDO ) {
		$command = 'sudo ' . $command;
	}
	$_out; $_err;
	$result = svndeploy_shell_exec( $command, $_out, $_err );
	$err = count( $_err ) > 0 ? implode( '', $_err ) : FALSE;
	return count( $_out ) > 0 ? implode( '', $_out ) : $result;
}

function svndeploy_shell_exec( $cmd, &$stdout, &$stderr ) {
    $outfile = svndeploy_core_tmpcreate( 'stdout' );
    $errfile = svndeploy_core_tmpcreate( 'stderr' );
    if ( !svndeploy_install_check_tmp() ) {
    	$stdout = array();
    	$stderr = array( 'STDERR or STDOUT not writable. Check your /tmp folder writing privileges.' );
    	return FALSE;
    }	
    $descriptorspec = array(
        0 => array( 'pipe', 'r' ),
        1 => array( 'file', $outfile, 'w' ),
        2 => array( 'file', $errfile, 'w' )
    );
    $proc = proc_open( $cmd, $descriptorspec, $pipes );
    
    if ( !is_resource( $proc ) ) return 255;

    fclose( $pipes[ 0 ] );    //Don't really want to give any input

    $exit = proc_close( $proc );
    $stdout = file( $outfile );
    $stderr = file( $errfile );

    unlink( $outfile );
    unlink( $errfile );
    return $exit;
}

?>