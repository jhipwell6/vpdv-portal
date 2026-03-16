<?php

namespace FXUP_User_Portal\Controllers;

use \FXUP_User_Portal\Helpers\Form_Handler;

use \League\Csv\Reader;
use \League\Csv\Writer;
use \League\Csv\CharsetConverter;

class FXUP_Importer
{
	protected static $instance;

	/**
	 * Initializes plugin variables and sets up WordPress hooks/actions.
	 *
	 * @return void
	 */
	protected function __construct()
	{
		add_action( 'wp_ajax_fxup_import_guests', array( $this, 'import_guests' ) );
		add_action( 'wp_ajax_fxup_export_guests', array( $this, 'export_guests' ) );
	}

	/**
	 * Singleton factory Method
	 * Forces that only on instance of the class exists
	 *
	 * @return $instance Object, Returns the current instance or a new instance of the class
	 */
	public static function instance()
	{
		if ( ! isset( self::$instance ) ) {
			$className = __CLASS__;
			self::$instance = new $className();
		}
		return self::$instance;
	}

	public function import_guests()
	{
		// Get form data
		$itin_id = Form_Handler::filter_input( 'itinerary_id' );
		$file = Form_Handler::get_file_data( 'fxup_guest_import_file' );
		if ( $file ) {
			$tmp_file = $_FILES['fxup_guest_import_file']['tmp_name'];
			$csv = Reader::createFromPath( $tmp_file, 'r' );
			$csv->setHeaderOffset( 0 );
			$records = $csv->getRecords();
			$guests = iterator_to_array( $records );

			if ( ! empty( $guests ) ) {
				foreach ( $guests as $guest ) {
					$this->process_guest( $guest, $itin_id );
				}
			}
		}

		wp_send_json_success();
	}
	
	public function export_guests()
	{
		$itin_id = Form_Handler::filter_input( 'itinerary_id' );
//		$encoder = ( new CharsetConverter() )
//			->inputEncoding( 'utf-8' )
//			->outputEncoding( 'iso-8859-15' )
//		;

		$keys = self::get_allowed_keys();
		$csv = Writer::createFromString();
//		$csv->addFormatter( $encoder );
		$csv->insertOne( $keys );

		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itin_id );
		if ( $Itinerary && ! empty( $Itinerary->getGuests() ) ) {
			foreach ( $Itinerary->getGuests() as $Guest ) {
				$data = array_values( $Guest->toExportArray() );
				$csv->insertOne( $data );
			}
			$csv->output( 'itinerary-' . $itin_id . '-guests.csv' );
			die;
		}
	}

	private function process_guest( $guest_data, $itin_id )
	{
		$guest = $this->get_guest_by_email( $guest_data['guest_email'] );
		if ( $guest ) {
			$guest_id = $this->update_guest( $guest_data, $guest->ID, $itin_id );
		} else {
			$guest_id = $this->add_guest( $guest_data, $itin_id );
		}

		if ( $guest_id && ! is_wp_error( $guest_id ) ) {
			$itinerary_data = $this->get_itinerary_data( $itin_id );
			$data = array_merge( $guest_data, $itinerary_data );
			$this->save_guest_meta( $data, $guest_id );
		}
	}

	private function add_guest( $guest_data, $itin_id )
	{
		FXUP_USER_PORTAL()->debug_log( 'adding guest', $guest_data, $itin_id );
		$title = $this->get_itinerary_title( $itin_id ) . ' - ' . $guest_data['guest_first_name'] . ' ' . $guest_data['guest_last_name'];
		$args = [
			'post_title' => $title,
			'post_type' => 'guest',
			'post_status' => 'publish',
		];
		return wp_insert_post( $args );
	}

	private function update_guest( $guest_data, $guest_id, $itin_id )
	{
		FXUP_USER_PORTAL()->debug_log( 'updating guest', $guest_data, $itin_id );
		$title = $this->get_itinerary_title( $itin_id ) . ' - ' . $guest_data['guest_first_name'] . ' ' . $guest_data['guest_last_name'];
		$args = [
			'ID' => $guest_id,
			'post_title' => $title,
			'post_type' => 'guest',
			'post_status' => 'publish',
		];
		return wp_update_post( $args );
	}

	private function save_guest_meta( $guest_data, $guest_id )
	{
		$allowed_keys = self::get_allowed_keys();
		
		foreach ( $guest_data as $key => $value ) {
			if ( in_array( $key, $allowed_keys ) ) {
				update_post_meta( $guest_id, $key, $value );
			}
		}
	}

	private function get_guest_by_email( $email )
	{
		$args = array(
			'post_type' => 'guest',
			'posts_per_page' => 1,
			'meta_key' => 'guest_email',
			'meta_value' => $email,
		);
		$query = new \WP_Query( $args );

		return ( ! empty( $query->posts[0] ) ) ? $query->posts[0] : false;
	}
	
	private function get_itinerary_title( $itin_id )
	{
		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itin_id );
		if ( $Itinerary ) {
			return $Itinerary->getTitle();
		}
		return '';
	}
	
	private function get_itinerary_data( $itin_id )
	{
		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itin_id );
		if ( $Itinerary ) {
			return [
				'itinerary_id' => $Itinerary->getPostID(),
				'account_id' => $Itinerary->getAccountID(),
				'group_name' => $Itinerary->getTitle(),
			];
		}
		return [];
	}

	static function get_allowed_keys()
	{
		return [
			'guest_id',
			'guest_first_name',
			'guest_last_name',
			'guest_email',
			'guest_children',
			'guest_notes',
			'guest_dietary_restrictions',
			'guest_dietary_restriction_other',
			'guest_allergies',
			'airline',
			'flight_number',
			'arrival_date',
			'arrival_time_hour',
			'arrival_time_minute',
			'arrival_time_meridiem',
			'requires_arrival_transportation',
			'departure_airline',
			'departure_flight_number',
			'departure_date',
			'departure_time_hour',
			'departure_time_minute',
			'departure_time_meridiem',
			'requires_departure_transportation',
			'passport_number',
			'travel_notes',
			'onsite_stay',
			'stay_location',
			'stay_location_other',
			'group_name', // static
			'account_id', // static
			'itinerary_id', // static
		];
	}
}

FXUP_Importer::instance();
