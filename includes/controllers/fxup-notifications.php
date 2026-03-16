<?php

namespace FXUP_User_Portal\Controllers;

class FXUP_Notifications
{

	use \FXUP_User_Portal\Core\Traits\Sends_Emails {
		\FXUP_User_Portal\Core\Traits\Sends_Emails::__construct as sends_emails;
	}
	protected static $instance;

	/**
	 * Initializes plugin variables and sets up WordPress hooks/actions.
	 *
	 * @return void
	 */
	protected function __construct()
	{
		$this->sends_emails();

		add_action( 'gform_after_submission', array( $this, 'queue_form_notifications' ), 10, 2 );
		add_action( 'wp_ajax_fxup_admin_send_guest_reminder', array( $this, 'send_guest_travel_deadline_reminder' ) );
		add_action( 'wp_ajax_fxup_send_guest_reminder', array( $this, 'send_guest_travel_deadline_reminder' ) );
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

	public function queue_form_notifications( $entry, $form )
	{
		if ( $form['id'] == 4 ) { // Guest List - Guest
			$this->queue_guest_added_by_guest_emails( $entry );
		}

		if ( $form['id'] == 5 ) { // Guest List - User
			$this->queue_guest_added_by_user_emails( $entry );
		}
	}

	private function queue_guest_added_by_guest_emails( $entry )
	{
		$itin_id = rgar( $entry, '17' );
		$email_data = array(
			'itinerary_id' => $itin_id,
			'itinerary_title' => get_the_title( $itin_id ),
			'guest_first_name' => rgar( $entry, '11' ),
			'guest_last_name' => rgar( $entry, '12' ),
			'guest_email' => rgar( $entry, '14' ),
			'source' => 'added by guest',
		);
		$args = array(
			'type' => 'email-digest-concierge-guest-added',
			'data' => $email_data,
			'is_sent' => false,
		);
		$Email_Service = FXUP_USER_PORTAL()->email( $itin_id );
		$Email = $Email_Service->create( $args );
	}

	private function queue_guest_added_by_user_emails( $entry )
	{
		$itin_id = rgar( $entry, '17' );
		$email_data = array(
			'itinerary_id' => $itin_id,
			'itinerary_title' => get_the_title( $itin_id ),
			'guest_first_name' => rgar( $entry, '11' ),
			'guest_last_name' => rgar( $entry, '12' ),
			'guest_email' => rgar( $entry, '14' ),
			'source' => 'added by client/concierge',
		);
		$args = array(
			'type' => 'email-digest-concierge-guest-added',
			'data' => $email_data,
			'is_sent' => false,
		);
		$Email_Service = FXUP_USER_PORTAL()->email( $itin_id );
		$Email = $Email_Service->create( $args );
	}
	
	public function send_guest_travel_deadline_reminder()
	{
		$itin = filter_var( $_POST['itinerary_post_id'], FILTER_SANITIZE_STRING );
		$guest_id = filter_var( $_POST['guest_id'], FILTER_SANITIZE_STRING );
		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itin );
		$Guests = $guest_id ? $this->get_guest_post_as_array( $guest_id, $Itinerary ) :  $Itinerary->getGuests();
		
		if ( ! empty( $Guests ) ) {
			foreach ( $Guests as $Guest ) {
				if ( ! $Guest->isTravelFinalized() ) {
					$email_data = array(
						'message_body' => $this->email_guest_travel_deadline_reminder_message,
						'travel_url' => $Guest->getTravelLink(),
					);
					$args = array(
						'type' => 'email-guest-travel-deadline-reminder',
						'data' => $email_data,
						'to' => $Guest->getEmail(),
						'is_sent' => false,
					);
					$Email_Service = FXUP_USER_PORTAL()->email( $Itinerary );
					$Email = $Email_Service->create( $args );
					$result = $Email_Service->send( $Email );
				}
			}
		}
		
		wp_send_json_success();
	}

	private function get_guest_post_as_array( $guest_id, $Itinerary )
	{
		return array_filter( $Itinerary->getGuests(), function( $Guest ) use ( $guest_id ) {
			return $Guest->getPostID() == $guest_id;
		} );
	}
}

FXUP_Notifications::instance();
