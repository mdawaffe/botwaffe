<?php

$sites = require __DIR__ . '/../sites.php';
$log = fopen( __DIR__ . '/log', 'a' );

function log_line( $line ) {
	global $log;

	fwrite( $log, sprintf( "[%s] %s\n", gmdate( 'Y-m-d\\TH:i:sO' ), rtrim( $line ) ) );
}

if ( isset( $_GET['hub_mode'] ) ) {
	switch ( $_GET['hub_mode'] ) {
	case 'subscribe' :
	case 'unsubscribe' :
		verify( $_GET['hub_mode'] );
		break;
	default :
		fail();
	}

	exit;
} else if ( isset( $_SERVER['HTTP_X_HUB_SIGNATURE'] ) ) {
	receive();
} else {
	fail();
}

function fail( $status = 400 ) {
	$status = (int) $status;
	if ( ! $status ) {
		$status = 400;
	}

	header( 'Content-Type: text/plain', true, $status );
	exit;
}

function verify( $mode ) {
	global $sites;

	if (
		empty( $_GET['hub_topic'] )
	||
		empty( $_GET['hub_verify_token'] )
	||
		empty( $_GET['hub_challenge'] )
	) {
		log_line( sprintf( 'VERIFY(%s): BAD INPUT', $mode ) );
		fail();
	}

	log_line( sprintf( 'VERIFY(%s): %s', $mode, $_GET['hub_topic'] ) );
	switch ( $mode ) {
	case 'subscribe' :
	case 'unsubscribe' :
		break;
	default :
		fail();
	}


	if ( ! isset( $sites[$_GET['hub_topic']] ) ) {
		log_line( sprintf( 'VERIFY(%s): %s - UNKNOWN hub_topic', $mode, $_GET['hub_topic'] ) );
		fail( 404 );
	}

	$token  = $sites[$_GET['hub_topic']]['token'];

	if ( empty( $token ) ) {
		log_line( sprintf( 'VERIFY(%s): %s - EMPTY token', $mode, $_GET['hub_topic'] ) );
		fail( 404 );
	}

	if ( md5( sha1( $token ) ) !== md5( sha1( $_GET['hub_verify_token'] ) ) ) {
		log_line( sprintf( 'VERIFY(%s): %s - MISMATCH in token', $mode, $_GET['hub_topic'] ) );
		fail( 404 );
	}

	header( 'Content-Type: text/plain', true, 200 );
	log_line( sprintf( 'VERIFY(%s): %s - VERIFIED!', $mode, $_GET['hub_topic'] ) );
	die( $_GET['hub_challenge'] );
}

function receive() {
	global $sites;

	$header = $_SERVER['HTTP_X_HUB_SIGNATURE'];
	list ( $algorithm, $signature ) = explode( '=', $header, 2 );

	log_line( sprintf( 'RECEIVE: algorithm=%s', $algorithm ) );
	if ( 'sha1' !== $algorithm ) {
		die();
	}

	$body = file_get_contents( 'php://input' );

	$valid_site = false;
	foreach ( $sites as $site => $site_data ) {
		$expected = hash_hmac( 'sha1', $body, $site_data['secret'] );

		if ( md5( sha1( $expected ) ) === md5( sha1( $signature ) ) ) {
			$valid_site = true;
			break;
		}
	}

	log_line( sprintf( 'RECEIVE: valid_site=%d, feed=%s', $valid_site, $site ) );
	if ( ! $valid_site ) {
		die();
	}

	header( 'X-Hub-On-Behalf-Of: 1' );
	if ( function_exists( 'fastcgi_finish_request' ) ) {
		fastcgi_finish_request();
	}

	chdir( __DIR__ );
	exec( sprintf( '%s %s', __DIR__ . '/received', $site ), $output, $exit_code );
	if ( $exit_code ) {
		log_line( sprintf( 'RECEIVE: COMMAND FAILED feed=%s, exit=%d', $site, $exit_code ) );
	} else {
		log_line( sprintf( 'RECEIVE: feed=%s SUCCESS!', $site ) );
	}

	die();
}
