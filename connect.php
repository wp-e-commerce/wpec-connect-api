<?php
/*
Plugin Name: WP eCommerce Connect API
Plugin URI: https://wpecommerce.org/
Version: 1.0.0
Author: WP eCommerce
Description: Creation of REST Endpoints for future usage. ( Eg: braintree connect )
Author URI:  https://wpecommerce.org
*/

add_action( 'rest_api_init', function () {
  register_rest_route( 'wpec/v1/', '/braintree', array(
    'methods' => 'GET',
    'callback' => 'wpec_braintree_auth_connect',
  ) );
} );

function wpec_braintree_auth_connect( WP_REST_Request $request ) {
	require_once( 'includes/braintree/lib/Braintree.php' );

	$gateway = new Braintree_Gateway([
		'clientId' => 'client_id$sandbox$v58kmmff7349m9ny',
		'clientSecret' => 'client_secret$sandbox$5901187800ab76ef4917aaacc34c053f'
	]);

	if ( isset( $request['Auth'] ) && $request['Auth'] == 'WPeCBraintree' ) {

		$url = $gateway->oauth()->connectUrl([
			'redirectUri' => 'https://wpecommerce.org/wp-json/wpec/v1/braintree',
			'scope' => 'read_write',
			'state' => md5( esc_url( $request['business_website'] ) ),
			'landingPage' => 'signup',
			'user' => [
				'country' => 'USA',
				'email' => $request['user_email']
			],
			'business' => [
				'name' => '14 Ladders',
				'registeredAs' => '14.0 Ladders'
			],
			'paymentMethods' => [
				'credit_card',
				'paypal'
			]
		]);

		//Store the client return URL
		$client_site = md5( esc_url( $request['business_website'] ) );
		set_transient( 'wpec_braintree_client_return_' . $client_site , $request['redirect'], 4 * HOUR_IN_SECONDS );

		$data = array();
		// Create the response object
		$response = new WP_REST_Response( $data );

		// Add a custom status code
		$response->set_status( 200 );

		// Add a custom header
		$response->header( 'Location', $url );

		return $response;		
	}

	//Connect return URI process
	if ( isset( $request['state'] ) && $request['state'] != '' ) {

		//Validate returned state param
		$return_url = get_transient( 'wpec_braintree_client_return_' . $request['state'] );

		if ( false == $return_url ) {
			return;
		}

		delete_transient( 'wpec_braintree_client_return_' . $request['state'] );

		$result = $gateway->oauth()->createTokenFromCode([
			'code' => $request['code']
		]);

		$query_args = array(
			'access_token' => $result->credentials->accessToken,
		);

		$return_url = add_query_arg( $query_args, $return_url );

		$data = array();

		// Create the response object
		$response = new WP_REST_Response( $data );

		// Add a custom status code
		$response->set_status( 200 );

		// Add a custom header
		$response->header( 'Location', $return_url );

		return $response;			
	}

	return new WP_Error( 'access_denied', 'Access denied', array( 'status' => 404 ) );
}