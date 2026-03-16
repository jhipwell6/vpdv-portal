<?php
namespace FXUP_User_Portal\Core;

class Zapier_Service
{
	private static $api_url = 'https://hooks.zapier.com/hooks/catch/2686334/3ygt7da/';
	protected $data = [];
	
	public function __construct()
	{
		return $this;
	}
	
	public function get_data()
	{
		return $this->data;
	}
	
	public function add_data( $data )
	{
		$this->data[] = $data;
	}
	
	public function clear_data()
	{
		$this->data = [];
	}
	
	public function post()
	{
		$args = [
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body' => wp_json_encode( $this->get_data() ),
		];
		
		$response = wp_remote_post( self::$api_url, $args );
		
		FXUP_USER_PORTAL()->debug_log( $response );
		
		$this->clear_data();
	}
}