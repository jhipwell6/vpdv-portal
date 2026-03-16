<?php

namespace FXUP_User_Portal\Helpers;

if ( ! defined( 'ABSPATH' ) )
	exit;

class Form_Handler
{

	static public function pre_validate_form( $nonce_name, $action, $form_data )
	{
		if ( self::filter_input( 'action' ) !== $action ) {
			$error = new \WP_Error( 'invalid_action', 'Something went wrong. Invalid form action.' );
			wp_send_json_error( $error, 500 );
			die();
		}

		if ( ! self::is_nonce_verified( $nonce_name, $action, $form_data ) ) {
			$error = new \WP_Error( 'invalid_nonce', 'Unauthorized. Invalid nonce.' );
			wp_send_json_error( $error, 403 );
			die();
		}
	}

	static public function get_form_data( $post_var )
	{
		parse_str( self::filter_input( $post_var ), $form_data );
		if ( is_null( $form_data ) ) {
			$form_data = self::filter_input( $post_var );
		}
		$sanitized_data = self::sanitize_form_data( $form_data );
		return self::merge_array_data( $sanitized_data );
	}

	static public function get_file_data( $post_var )
	{
		return isset( $_FILES[$post_var] ) ? $_FILES[$post_var] : null;
	}

	static public function merge_array_data( $data )
	{
		$new_data = array();
		if ( ! empty( $data ) ) {
			foreach ( $data as $key => $value ) {
				data_set( $new_data, $key, $value, '/' );
			}
		}
		return $new_data;
	}

	static public function validate_form_data( $form_data )
	{
		if ( empty( $form_data ) ) {
			$error = new \WP_Error( 'invalid_form', 'Something went wrong. Invalid form data.' );
			wp_send_json_error( $error, 500 );
			die();
		}
	}

	static public function is_nonce_verified( $nonce_name, $action, $form_data )
	{
		$nonce_field = $form_data[$nonce_name];
		return isset( $nonce_field ) && wp_verify_nonce( $nonce_field, $action );
	}

	static public function filter_input( $key )
	{
		return filter_input( INPUT_POST, $key, FILTER_UNSAFE_RAW );
	}
	
	static public function sanitize_form_data( $form_data )
	{
		return array_map( function( $value ) {
			if ( is_array( $value ) ) {
				return $value;
			} else {
				return sanitize_text_field( $value );
			}
		}, $form_data );
	}
	
	static public function sanitize_input( $input )
	{
		return sanitize_text_field( $input );
	}

}
