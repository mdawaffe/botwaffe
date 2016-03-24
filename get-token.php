<?php

ini_set( 'display_errors', false );
error_reporting( 0 );

switch ( php_sapi_name() ) {
case 'cli' :
	cli_input();
	break;
case 'cli-server' :
	receive_token();
	break;
default:
	status_header( 400 );
	exit( 1 );
}

function cli_input() {
	$client = fopen( __DIR__ . '/client.php', 'w' );

	echo "1. Log in to WordPress.com at https://developer.wordpress.com/apps/\n";
	echo "2. Create a new application:\n";
	echo "   Name        : botwaffe-client-{your-wordpress.com-username}\n";
	echo "   Website URL : https://your.domain/path/to/botwaffe/\n";
	echo "   Redirect URL: https://your.domain:23166/\n";
	echo "3. Past the following app details here:\n";
	echo "   Client ID    : ";
	$client_id = trim( fgets( STDIN ) );
	echo "   Client Secret: ";
	$client_secret = trim( fgets( STDIN ) );
	echo "   Redirect URL : ";
	$redirect_url = trim( fgets( STDIN ) );

	fwrite( $client, sprintf( '<?php return %s;', var_export( compact(
		'client_id',
		'client_secret',
		'redirect_url'
	), true ) ) );

	$url = 'https://public-api.wordpress.com/oauth2/authorize?' . http_build_query( array(
		'redirect_uri' => $redirect_url,
		'response_type' => 'code',
		'client_id' => $client_id,
		'blog_id' => 0,
		'scope' => 'global',
	) );

	echo "4. Go to $url\n";
}

function receive_token() {
	header( 'Content-Type: text/plain' );

	$client = require __DIR__ . '/client.php';

	if ( isset( $_GET['code'] ) ) {
		$post = stream_context_create( array(
			'http' => array(
				'method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => http_build_query( array(
					'client_id' => $client['client_id'],
					'client_secret' => $client['client_secret'],
					'grant_type' => 'authorization_code',
					'code' => $_GET['code'],
					'redirect_uri' => $client['redirect_url'],
				) ),
				'ignore_errors' => true,
			),
		) );

		$response = file_get_contents(
			'https://public-api.wordpress.com/oauth2/token',
			false,
			$post
		);

		$response = json_decode( $response );

		$token_handle = fopen( __DIR__ . '/token.php', 'w' );
		$token_handle = fwrite( $token_handle, sprintf( "<?php /*\n%s\n*/", $response->access_token ) );
		die( 'Done. Close this page.' );
	}
}
