<?php

function svndeploy_crypto_encrypt( $what, $salt ) {
	if ( function_exists( 'openssl_encrypt' ) ) {
		return openssl_encrypt( $what, 'AES-256-CBC', $salt );
	}
	$encrypt = 'echo "' . addslashes( $what ) . '" | openssl enc -aes-256-cbc -a -salt -pass pass:' . addslashes( $salt );
	$result = trim( shell_exec( $encrypt ) );
	return $result;
}

function svndeploy_crypto_decrypt( $what, $salt ) {
	if ( function_exists( 'openssl_decrypt' ) ) {
		return openssl_decrypt( $what, 'AES-256-CBC', $salt );
	}
	$decrypt = 'echo "' . addslashes( $what ) . '" | openssl enc -aes-256-cbc -a -salt -pass pass:' . addslashes( $salt ) . ' -d';
	$err; $out;
	$result = trim( shell_exec( $decrypt ) );
	return $result;
}

?>