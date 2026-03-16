<?php
namespace FXUP_User_Portal\Models;

class Email
{
	protected $id = 0;
	protected static $exists = false;
	private static $wpdb;
	private static $db_table;
	private $raw;
	public static $virtual = array(
		'subject',
		'message',
		'is_concierge',
	);
	public static $hidden = array(
		'raw',
		'is_concierge',
	);
	
	public $to;
	protected $itinerary_id;
	protected $type;
	protected $data;
	protected $is_sent;
	
	protected $subject; // virtual
	protected $message; // virtual
	protected $is_concierge; // virtual
	
	final public function __construct( $email = 0 )
    {
		global $wpdb;
		self::$wpdb = $wpdb;
		self::$db_table = self::$wpdb->prefix . 'fx_emails';
		
		if ( is_numeric( $email ) && $email > 0 ) {
			$this->set_id( $email );
		} elseif ( $email instanceof self ) {
			$this->set_id( $email->get_id() );
		} else {
			// doesn't exist yet
			return $this;
		}
		
		if ( $this->get_id() > 0 ) {
			$this->read();
		}

        return $this;
	}
	
	public function get_id()
	{
		return $this->id;
	}
	
	public function set_id( $id )
	{
		$this->id = absint( $id );
	}
	
	/*
	 *  Getters & Setters
	 */
	
	public function get_to()
	{
		return $this->get_prop('to');
	}
	
	public function get_subject()
	{
		return $this->get_prop('subject');
	}
	
	public function get_message()
	{
		return $this->get_prop('message');
	}
	
	public function get_is_sent()
	{
		return $this->get_prop('is_sent');
	}
	
	public function is_sent() /* alias for get_is_sent */
	{
		return $this->get_is_sent();
	}
	
	public function get_itinerary_id()
	{
		return $this->get_prop('itinerary_id');
	}
	
	public function get_type()
	{
		return $this->get_prop('type');
	}
	
	public function get_data()
	{
		return maybe_unserialize( $this->get_prop('data') );
	}
	
	public function get_is_concierge()
	{
		return $this->get_prop('is_concierge');
	}
	
	public function set_to( $value )
	{
		return $this->set_prop( 'to', $value );
	}
	
	public function set_subject( $value )
	{
		return $this->set_prop( 'subject', $value );
	}
	
	public function set_message( $value )
	{
		return $this->set_prop( 'message', $value );
	}
	
	public function set_is_sent( $value )
	{
		return $this->set_prop( 'is_sent', $value );
	}
	
	public function set_itinerary_id( $value )
	{
		return $this->set_prop( 'itinerary_id', $value );
	}
	
	public function set_type( $value )
	{
		return $this->set_prop( 'type', $value );
	}
	
	public function set_data( $value )
	{
		return $this->set_prop( 'data', maybe_serialize( $value ) );
	}
	
	public function set_is_concierge( $value )
	{
		return $this->set_prop( 'is_concierge', $value );
	}
	
	/*
	 *  General Methods
	 */
	
	protected function get_prop( $prop )
	{
		if ( ! $this->has_prop( $prop ) )
			return false;
		
		if ( null === $this->{$prop} || empty( $this->{$prop} ) ) {
			$this->{$prop} = $this->get_meta( $prop );
		}
        return $this->{$prop};
	}
	
	protected function get_props()
	{
		foreach ( $this->to_array() as $prop => $value ) {
			$getter = "get_{$prop}";
			if ( is_callable( array( $this, $getter ) ) ) {
				$this->{$getter}();
			}
		}
	}
	
	public function has_prop( $prop )
	{
		return property_exists( $this, $prop );
	}
	
	protected function set_prop( $prop, $value, $allowed_keys = array() )
	{
		if ( $this->has_prop( $prop ) ) {
			if ( ! empty( $allowed_keys ) ) {
				$this->{$prop} = $this->sanitize_array( $value, $allowed_keys );
			} else {
				$this->{$prop} = $value;
			}
			return $this->{$prop};
		}
		
		return false;
	}
	
	/*
	 * Alias method for set_props
	 */
	public function make( $props )
	{
		$this->set_props( $props );
	}
	
	public function set_props( $props )
	{
		foreach ( $props as $prop => $value ) {
			if ( is_null( $value ) || $prop == 'id' ) {
				continue;
			}
			$setter = "set_$prop";

			if ( is_callable( array( $this, $setter ) ) ) {
				$this->{$setter}( $value );
			}
		}
	}
	
	public function save()
	{
		if ( self::$exists ) {
			// update
			return $this->update();
		} else {
			// create
			return $this->create();
		}
	}
	
	public function create()
	{
		self::$wpdb->insert( self::$db_table, $this->to_array( self::$virtual ) );
		$email_id = self::$wpdb->insert_id;
		
		return new static( $email_id );
	}
	
	public function read( $id = 0 )
	{
		$id = ! $id ? $this->get_id() : $id;
		if ( ! $id ) {
			throw new \Exception( "No " . static::class . " found with ID: {$id}" );
		}
		
		$result = $this->get_where( 'id', $id );
		$email_object = ( ! empty( $result ) ) ? $result : false;
		
		if ( $email_object ) {
			self::$exists = true;

			// fill properties
			$this->get_props();
		}
	}
	
	public function update()
	{
		$this->save_all_meta();
		return $this;
	}
	
	public function exists()
	{
		// if we already read it from the database we know it exists
		if ( self::$exists ) {
			return true;
		}
		
		// get post by unique key
		$instance = get_by_id( $this->get_id() );
		
		return $instance::$exists;
	}
	
	public function delete()
	{
		// TODO
		// SQL delete
	}
	
	public function get_by_id( $id )
	{
		$result = $this->get_where( 'id', $id );
		
		$instance = $this;
		if ( ! empty( $result ) ) {
			$instance = new static( $result['id'] );
		}
		
		return $instance;
	}
	
	/* for retrieving the email from the db */
	private function get_where( $prop, $value )
	{
		if ( $this->raw === null ) {
			if ( ! $this->has_prop( $prop ) ) {
				return false;
			}
			$this->raw = self::$wpdb->get_row( self::$wpdb->prepare( 'SELECT * FROM `' . self::$db_table . '` WHERE ' . $prop . ' = %s', $value ), ARRAY_A );
		}
		
		return $this->raw;
	}
	
	public function save_all_meta()
	{
		return self::$wpdb->update( self::$db_table, $this->to_array( self::$virtual ), array( 'id' => $this->get_id() ) );
	}
	
	/*
	 * Helpers
	 */
	
	public function to_array( $exclude = array(), $context = 'view' )
	{
		$exclusions = wp_parse_args( $exclude, self::$hidden );
		$data = $context == 'save' ? array_map( 'maybe_serialize', get_object_vars( $this ) ) : get_object_vars( $this );
		return array_diff_key( $data, array_flip( $exclusions ) );
	}
	
	public function to_json( $exclude = array(), $flags = 0 )
	{
		return wp_json_encode( $this->to_array( $exclude ), $flags );
	}
	
	/* for filtering */
	public function where( $prop, $value )
	{
		return $this->get_prop( $prop ) == $value ? $this : false;
	}
	
	private function sanitize_array( $arr = array(), $allowed_keys = array() )
	{
		$arr = ! is_array( $arr ) ? (array) $arr : $arr;
		return ! empty( $allowed_keys ) ? $this->get_allowed_data( $arr, $allowed_keys ) : $arr;
	}
	
	private function get_allowed_data( $arr = array(), $allowed_keys = array() )
	{
		return array_intersect_key( $arr, array_flip( $allowed_keys ) );
	}
	
	private function get_meta( $prop )
	{
		if ( $this->raw === null ) {
			$this->get_where( 'id', $this->get_id() );
		}
		
		if ( is_array( $this->raw ) && array_key_exists( $prop, $this->raw ) ) {
			return $this->raw[ $prop ];
		}
		
		return null;
	}
	
	public function save_meta( $prop, $value )
	{
		// allow extending classes to hijack per property
		$saver = "save_{$prop}_meta";
		if ( is_callable( array( $this, $saver ) ) ) {
			return $this->{$saver}( $value );
		}

		return self::$wpdb->update( self::$db_table, array( $prop => maybe_serialize( $value ) ), array( 'id' => $this->get_id() ) );
	}
	
	public function send()
	{
		if ( ! $this->get_message() )
			return false;
		
		$headers = array('Content-Type: text/html; charset=UTF-8;');
		$sent = wp_mail( $this->get_to(), $this->get_subject(), $this->get_message(), $headers );
		$this->set_is_sent( $sent );
		
		return $sent;
	}
}

