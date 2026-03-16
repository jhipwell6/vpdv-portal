<?php
namespace FXUP_User_Portal\Models;

class EmailDigest
{
	private $type;
	private $emails;
	private $to;
	private $subject;
	private $message;
	private $data;
	
	
	final public function __construct( $emails, $type, $args )
    {
		$this->set_type( $type );
		$this->set_emails( $emails );
		$this->set_to( $args['to'] );
		$this->set_subject( $args['subject'] );
		$this->set_data();
	}
	
	public function send()
	{
		if ( ! $this->get_message() )
			return false;
		
		$headers = array('Content-Type: text/html; charset=UTF-8;');
		$sent = wp_mail( $this->get_to(), $this->get_subject(), $this->get_message(), $headers );
		foreach ( $this->get_emails() as $Email ) {
			$Email->set_is_sent( 1 );
			$Email->save_meta( 'is_sent', $Email->is_sent() );
		}
	}
	
	public function get_type()
	{
		return $this->type;
	}
	
	public function get_emails()
	{
		return (array) $this->emails;
	}
	
	public function get_to()
	{
		return $this->to;
	}
	
	public function get_subject()
	{
		return $this->subject;
	}
	
	public function get_message()
	{
		return $this->message;
	}
	
	public function get_data()
	{
		return $this->data;
	}
	
	public function has_emails()
	{
		return ( $this->emails !== null && ! empty( $this->emails ) ) ? true : false;
	}
	
	private function set_type( $value )
	{
		$this->type = $value;
		return $this->type;
	}
	
	/*
	 * Emails should be set in the constructor
	 */
	private function set_emails( $emails )
	{
		$this->emails = $emails;
		return $this->emails;
	}
	
	public function set_to( $value )
	{
		$this->to = $value;
		return $this->to;
	}
	
	private function set_subject( $value )
	{
		$this->subject = $value;
		return $this->subject;
	}
	
	public function set_message( $value )
	{
		$this->message = $value;
		return $this->message;
	}
	
	private function set_data()
	{
		$this->data = array_map( function( $Email ) {
			if ( $Email && is_object( $Email ) ) {
				return $Email->get_data();
			}
		}, $this->get_emails() );
		return $this->data;
	}
}