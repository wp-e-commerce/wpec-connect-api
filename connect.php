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
	global $merchant_return;

	$secret = md5( 'client_secret$sandbox$5901187800ab76ef4917aaacc34c053f' );

	require_once( 'includes/braintree/lib/Braintree.php' );

	$gateway = new Braintree_Gateway([
		'clientId' => 'client_id$sandbox$v58kmmff7349m9ny',
		'clientSecret' => 'client_secret$sandbox$5901187800ab76ef4917aaacc34c053f'
	]);

	$merchant_return = isset( $request['redirect'] ) ? $request['redirect'] : '';

	if ( isset( $request['Auth'] ) && $request['Auth'] == 'WPeCBraintree' ) {

		$url = $gateway->oauth()->connectUrl([
			'redirectUri' => 'https://wpecommerce.org/wp-json/wpec/v1/braintree',
			'scope' => 'read_write',
			'state' => $secret,
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
	if ( isset( $request['state'] ) && $request['state'] == $secret ) {
		$result = $gateway->oauth()->createTokenFromCode([
			'code' => $request['code']
		]);

		$data = array();

		// Create the response object
		$response = new WP_REST_Response( $data );

		// Add a custom status code
		$response->set_status( 200 );

		var_dump($merchant_return);
		exit;
		// Add a custom header
		$response->header( 'Location', $merchant_return );

		return $response;			
	}

	return new WP_Error( 'access_denied', 'Access denied', array( 'status' => 404 ) );
	
	
	/*
	// You can access parameters via direct array access on the object:
	$param = $request['some_param'];

	// Or via the helper method:
	$param = $request->get_param( 'some_param' );

	// You can get the combined, merged set of parameters:
	$parameters = $request->get_params();

	// The individual sets of parameters are also available, if needed:
	$parameters = $request->get_url_params();
	$parameters = $request->get_query_params();
	$parameters = $request->get_body_params();
	$parameters = $request->get_json_params();
	$parameters = $request->get_default_params();

	// Uploads aren't merged in, but can be accessed separately:
	$parameters = $request->get_file_params();
	*/
}