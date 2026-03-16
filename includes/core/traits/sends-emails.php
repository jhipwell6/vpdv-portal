<?php
namespace FXUP_User_Portal\Core\Traits;

trait Sends_Emails
{
	// General
	public $email_concierge_notifications_to;
    public $email_on_site_staff_notifications_to;
    public $email_summary_notifications_to;
	
	// Concierge
	public $concierge_deadline_check_interval_in_days;
	public $concierge_deadline_check_interval_in_days_interval_object;
	public $email_concierge_digest_report_subject;
	public $email_concierge_newly_confirmed_activities_subject;
	public $email_concierge_guest_travel_arrangements_submitted_subject;
	public $email_concierge_guest_travel_arrangements_submitted_message;
	public $email_concierge_guest_removed_subject;
	public $email_concierge_guest_removed_message;
	public $email_concierge_deadline_check_subject;
	
	// Client
	public $new_account_login_interval_after_account_creation;
    public $new_account_login_interval_after_account_creation_interval_object;
    public $new_account_login_reminder_active = false;
    public $new_account_login_reminder_in_days;
    public $new_account_login_reminder_in_days_interval_object;
	public $client_upcoming_itinerary_deadline_notification_interval_in_days;
    public $client_upcoming_itinerary_deadline_notification_interval_in_days_interval_object;
    public $email_account_creation_reminder_subject;
    public $email_account_creation_reminder_message;
    public $email_client_itinerary_deadline_reminder_subject;
    public $email_client_itinerary_deadline_reminder_message;
    public $email_client_itinerary_submitted_subject;
    public $email_client_itinerary_submitted_message;
	public $email_client_itinerary_updated_subject;
    public $email_client_itinerary_updated_message;
	
	// Guest
	public $guest_travel_upcoming_itinerary_notification_interval_in_days;
    public $guest_travel_upcoming_itinerary_notification_interval_in_days_interval_object;    
    public $guest_travel_upcoming_itinerary_reminder_active = false;
    public $guest_travel_upcoming_itinerary_reminder_in_days;
    public $guest_travel_upcoming_itinerary_reminder_in_days_interval_object;
    public $email_guest_travel_deadline_reminder_subject;
    public $email_guest_travel_deadline_reminder_message;
	
	public function __construct()
	{
		$this->define_concierge_email_options();
		$this->define_client_email_options();
		$this->define_guest_email_options();
	}
	
	public function define_concierge_email_options()
	{
		// Email notifications
        $this->email_concierge_notifications_to = $this->_get_field('fxup_email_concierge_notifications_to');
        $this->email_concierge_notifications_to = is_string($this->email_concierge_notifications_to) ? $this->email_concierge_notifications_to : ''; // Default to empty string
        $this->email_on_site_staff_notifications_to = $this->_get_field('fxup_email_on_site_staff_notifications_to');
        $this->email_on_site_staff_notifications_to = is_string($this->email_on_site_staff_notifications_to) ? $this->email_on_site_staff_notifications_to : ''; // Default to empty string
		$this->email_summary_notifications_to = $this->_get_field('fxup_email_summary_notifications_to');
        $this->email_summary_notifications_to = is_string($this->email_summary_notifications_to) ? $this->email_summary_notifications_to : ''; // Default to empty string
		
		// Concierge deadline notification
        $this->concierge_deadline_check_interval_in_days = (int) $this->_get_field('fxup_concierge_deadline_check_interval_in_days');
        $this->concierge_deadline_check_interval_in_days_interval_object = new \DateInterval("P" . $this->concierge_deadline_check_interval_in_days . "D");
		
		// Email subjects
		$this->email_concierge_digest_report_subject = $this->_get_field( 'fxup_email_concierge_digest_report_subject');
		$this->email_concierge_newly_confirmed_activities_subject = $this->_get_field( 'fxup_email_concierge_newly_confirmed_activities_subject');
		$this->email_concierge_guest_travel_arrangements_submitted_subject = $this->_get_field( 'fxup_email_concierge_guest_travel_arrangements_submitted_subject');
		$this->email_concierge_guest_removed_subject = $this->_get_field( 'fxup_email_concierge_guest_removed_subject');
		$this->email_concierge_deadline_check_subject = $this->_get_field( 'fxup_email_concierge_deadline_check_subject');
		
		// Email message templates
		$this->email_concierge_guest_travel_arrangements_submitted_message = $this->_get_field( 'fxup_email_concierge_guest_travel_arrangements_submitted_message');
		$this->email_concierge_guest_removed_message = $this->_get_field( 'fxup_email_concierge_guest_removed_message');
	}
	
	public function define_client_email_options()
	{
		// When to start notifying a client (bride) that they have not logged in to their newly created account, how often to notify them.
        $this->new_account_login_interval_after_account_creation = (int) $this->_get_field('fxup_new_account_login_interval_after_account_creation');
        $this->new_account_login_interval_after_account_creation_interval_object = new \DateInterval("P" . $this->new_account_login_interval_after_account_creation . "D");
        $this->new_account_login_reminder_active = (bool) $this->_get_field('fxup_new_account_login_reminder_active');
        $this->new_account_login_reminder_in_days = (int) $this->_get_field('fxup_new_account_login_reminder_in_days');
        $this->new_account_login_reminder_in_days_interval_object = new \DateInterval("P" . $this->new_account_login_reminder_in_days . "D");
		
		// How far in advance of last edit day a customer should be notified that they are running out of time
        $this->client_upcoming_itinerary_deadline_notification_interval_in_days = (int) $this->_get_field('fxup_client_upcoming_itinerary_deadline_notification_interval_in_days');
        $this->client_upcoming_itinerary_deadline_notification_interval_in_days_interval_object = new \DateInterval("P" . $this->client_upcoming_itinerary_deadline_notification_interval_in_days . "D");
		
		// Email subjects
		$this->email_account_creation_reminder_subject = $this->_get_field( 'fxup_email_account_creation_reminder_subject');
		$this->email_client_itinerary_deadline_reminder_subject = $this->_get_field( 'fxup_email_client_itinerary_deadline_reminder_subject');
		$this->email_client_itinerary_submitted_subject = $this->_get_field( 'fxup_email_client_itinerary_submitted_subject');
		$this->email_client_itinerary_updated_subject = $this->_get_field( 'fxup_email_client_concierge_update_subject');
		
		// Email message templates
		$this->email_account_creation_reminder_message = $this->_get_field( 'fxup_email_account_creation_reminder_message');
		$this->email_client_itinerary_deadline_reminder_message = $this->_get_field( 'fxup_email_client_itinerary_deadline_reminder_message');
		$this->email_client_itinerary_submitted_message = $this->_get_field( 'fxup_email_client_itinerary_submitted_message');
		$this->email_client_itinerary_updated_message = $this->_get_field( 'fxup_email_client_concierge_update_message');
	}
	
	public function define_guest_email_options()
	{
		// How far in advance of the trip's start date *guests* should be notified to finalize their travel arrangements
        $this->guest_travel_upcoming_itinerary_notification_interval_in_days = (int) $this->_get_field('fxup_guest_travel_upcoming_itinerary_notification_interval_in_days');
        $this->guest_travel_upcoming_itinerary_deadline_notification_interval_in_days_interval_object = new \DateInterval("P" . $this->guest_travel_upcoming_itinerary_notification_interval_in_days . "D");
        $this->guest_travel_upcoming_itinerary_reminder_active = (bool) $this->_get_field('fxup_guest_travel_upcoming_itinerary_reminder_active');
        $this->guest_travel_upcoming_itinerary_reminder_in_days = (int) $this->_get_field('fxup_guest_travel_upcoming_itinerary_reminder_in_days');
        $this->guest_travel_upcoming_itinerary_reminder_in_days_interval_object = new \DateInterval("P" . $this->guest_travel_upcoming_itinerary_reminder_in_days . "D");
		
		// Email subjects
		$this->email_guest_travel_deadline_reminder_subject = $this->_get_field( 'fxup_email_guest_travel_deadline_reminder_subject');
		
		// Email message templates
		$this->email_guest_travel_deadline_reminder_message = $this->_get_field( 'fxup_email_guest_travel_deadline_reminder_message');
	}
	
	public function _get_field( $field_name, $object = 'option' )
	{
		if ( function_exists('get_field') ) {
			return get_field( $field_name, $object );
		} else {
			if ( is_numeric( $object ) ) {
				return get_post_meta( $object, $field_name, true );
			}
			return get_option( $field_name );
		}
	}
}