<?php

namespace FXUP_User_Portal\Controllers;

use FXUP_User_Portal\Helpers\Form_Handler;
use FXUP_User_Portal\Helpers\Itinerary_Change_Logger;

class FXUP_Itinerary_Process
{

	use \FXUP_User_Portal\Core\Traits\Sends_Emails {
		\FXUP_User_Portal\Core\Traits\Sends_Emails::__construct as sends_emails;
	}

	protected static $instance;
	public $jump_to_links = array();
	public $link_center = array();
	public $label_for_travel_arrangements_confirm_checkbox = '';
	public $new_itinerary_form_link;
	public $activity_booking_time_options = array();
	public $activity_approval_warning_message;
	public $activity_approval_confirmed_message;
	public $activity_guests_attending_tooltip;
	public $activity_child_guests_tooltip;
	public $activity_booked_tooltip;
	public $activity_prices_tooltip;
	public $booking_confirmation_tooltip;
	public $activity_child_guests_names_example_placeholder;
	public $edit_day_cutoff_interval_in_days;
	public $edit_day_cutoff_interval_in_days_interval_object;
	public $above_itinerary_form_wysiwyg = '';
	public $welcome_video;
	public $transportation_reminder;
	public $models = array();
	public $views = array();
	public $user_id;
	public $user_display_name;
	public $user_role;
	public $is_concierge;
	public $template_data = array();
	private $cached_activities;

	/**
	 * Initializes plugin variables and sets up WordPress hooks/actions.
	 *
	 * @return void
	 */
	protected function __construct()
	{

		$this->define_models();
		// $this->include_models();
		$this->setup_admin();
		$this->define_user();
		$this->define_options();
		$this->define_views();
		$this->sends_emails();

		add_action( 'add_meta_boxes', array( $this, 'add_custom_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'meta_box_itinerary_bulk_activity_save' ), 10, 1 ); // post_id is passed

		add_action( 'wp_ajax_save_itinerary', array( $this, 'process' ) );
		add_action( 'wp_ajax_nopriv_save_itinerary', array( $this, 'process' ) );
		add_action( 'wp_ajax_save_submit_itinerary', array( $this, 'process_submit' ) );
		add_action( 'wp_ajax_nopriv_save_submit_itinerary', array( $this, 'process_submit' ) );
		add_action( 'wp_ajax_add_activity_days', array( $this, 'maybe_add_activity' ) );
		add_action( 'wp_ajax_nopriv_add_activity_days', array( $this, 'maybe_add_activity' ) );
		add_action( 'wp_ajax_add_custom_activity_days', array( $this, 'maybe_add_activity' ) );
		add_action( 'wp_ajax_nopriv_add_custom_activity_days', array( $this, 'maybe_add_activity' ) );

		add_action( 'wp_ajax_save_manual_override', array( $this, 'save_manual_override' ) );

		add_action( 'wp_ajax_save_notification_settings', array( $this, 'save_notification_settings' ) );
		add_action( 'wp_ajax_nopriv_save_notification_settings', array( $this, 'save_notification_settings' ) );

		add_action( 'wp_ajax_save_transportation', array( $this, 'process_transportation' ) );
		add_action( 'wp_ajax_nopriv_save_transportation', array( $this, 'process_transportation' ) );
		add_action( 'wp_ajax_regenerate_transportation', array( $this, 'regenerate_transportation' ) );
		add_action( 'wp_ajax_nopriv_regenerate_transportation', array( $this, 'regenerate_transportation' ) );
		add_action( 'wp_ajax_add_transport', array( $this, 'add_transport' ) );
		add_action( 'wp_ajax_nopriv_add_transport', array( $this, 'add_transport' ) );

		add_action( 'wp_ajax_share_summary', array( $this, 'set_summary_expiration' ) );
		add_action( 'wp_ajax_nopriv_share_summary', array( $this, 'set_summary_expiration' ) );

		add_action( 'wp_ajax_save_guest_list', array( $this, 'process_guest_list' ) );
		add_action( 'wp_ajax_nopriv_save_guest_list', array( $this, 'process_guest_list' ) );
		add_action( 'wp_ajax_add_guest_list_item', array( $this, 'maybe_add_guest_list_item' ) );
		add_action( 'wp_ajax_nopriv_add_guest_list_item', array( $this, 'maybe_add_guest_list_item' ) );
		add_action( 'wp_ajax_add_guest_item', array( $this, 'maybe_add_guest_item' ) );
		add_action( 'wp_ajax_nopriv_add_guest_item', array( $this, 'maybe_add_guest_item' ) );
		add_action( 'wp_ajax_user_add_guest_item', array( $this, 'maybe_add_user_guest_item' ) );
		add_action( 'wp_ajax_nopriv_user_add_guest_item', array( $this, 'maybe_add_user_guest_item' ) );
		add_action( 'wp_ajax_submit_guest_list', array( $this, 'submit_user_guest_list' ) );
		add_action( 'wp_ajax_nopriv_submit_guest_list', array( $this, 'submit_user_guest_list' ) );
		add_action( 'wp_ajax_edit_guest_list_row', array( $this, 'edit_guest_list_row' ) );
		add_action( 'wp_ajax_nopriv_edit_guest_list_row', array( $this, 'edit_guest_list_row' ) );
		add_action( 'wp_ajax_submit_guest_list_row_edit', array( $this, 'handle_guest_list_row_edit' ) );
		add_action( 'wp_ajax_nopriv_submit_guest_list_row_edit', array( $this, 'handle_guest_list_row_edit' ) );
		add_action( 'wp_ajax_edit_guest', array( $this, 'edit_guest' ) );
		add_action( 'wp_ajax_nopriv_edit_guest', array( $this, 'edit_guest' ) );

		add_action( 'wp_ajax_save_travel_list', array( $this, 'save_travel_details' ) );
		add_action( 'wp_ajax_nopriv_save_travel_list', array( $this, 'save_travel_details' ) );

		add_action( 'wp_ajax_save_guest_travel_list', array( $this, 'save_guest_travel_details' ) );
		add_action( 'wp_ajax_nopriv_save_guest_travel_list', array( $this, 'save_guest_travel_details' ) );

		add_action( 'wp_ajax_save_room_list', array( $this, 'save_room_details' ) );
		add_action( 'wp_ajax_nopriv_save_room_list', array( $this, 'save_room_details' ) );

		add_action( 'wp_ajax_user_remove_guest_item', array( $this, 'remove_user_guest_item' ) );
		add_action( 'wp_ajax_nopriv_user_remove_guest_item', array( $this, 'remove_user_guest_item' ) );

		add_action( 'wp_ajax_user_insert_guest_list_item', array( $this, 'maybe_insert_guest_item' ) );
		add_action( 'wp_ajax_nopriv_user_insert_guest_list_item', array( $this, 'maybe_insert_guest_item' ) );

		add_action( 'wp_ajax_update_guest_onsite_status', array( $this, 'update_onsite_status' ) );
		add_action( 'wp_ajax_nopriv_update_guest_onsite_status', array( $this, 'update_onsite_status' ) );

		// custom cron actions
		add_action( 'fxup_itinerary_deadline_check', array( $this, 'itinerary_deadline_process' ) );
		add_action( 'fxup_concierge_deadline_check', array( $this, 'concierge_deadline_process' ) );
		add_action( 'fxup_concierge_digest_report', array( $this, 'concierge_digest_report' ) );
		add_action( 'fxup_guest_travel_deadline_check', array( $this, 'guest_travel_deadline_process' ) );
		add_action( 'fxup_new_account_login_reminder_check', array( $this, 'new_account_login_reminder' ) );

		add_action( 'wp_ajax_itinerary_deadline_check', array( $this, 'itinerary_deadline_process' ) );
		add_action( 'wp_ajax_nopriv_itinerary_deadline_check', array( $this, 'itinerary_deadline_process' ) );

		add_filter( 'acf/load_value/name=trip_day_activities', array( $this, 'reorder_activities_by_time' ), 10, 3 );

		// add_action('wp_ajax_user_create_new_itinerary', array($this, 'user_create_new_itinerary'));
		// add_action('wp_ajax_nopriv_user_create_new_itinerary', array($this, 'user_create_new_itinerary'));

		add_action( 'wp_ajax_user_create_new_itinerary', array( $this, 'user_create_new_itinerary_callback' ) );
		add_action( 'wp_ajax_nopriv_user_create_new_itinerary', array( $this, 'user_create_new_itinerary_callback' ) );

		add_action( 'wp_ajax_get_event_type_flyout', array( $this, 'callback_get_event_type_flyout' ) );
		add_action( 'wp_ajax_noprive_get_event_type_flyout', array( $this, 'callback_get_event_type_flyout' ) );

		add_shortcode( 'fxup_itinerary_form', array( $this, 'view_itinerary_form' ) );
		add_shortcode( 'fxup_dashboard_view', array( $this, 'view_dashboard' ) );
		add_shortcode( 'fxup_single_itinerary', array( $this, 'view_single_itinerary' ) );
		add_shortcode( 'fxup_share_print_itinerary', array( $this, 'view_share_print_itinerary' ) );
		add_shortcode( 'fxup_summary_view', array( $this, 'view_summary' ) );
		add_shortcode( 'fxup_transportation_summary_view', array( $this, 'view_transportation_summary' ) );
		add_shortcode( 'fxup_grocery_search', array( $this, 'view_grocery_search' ) );

		add_action( 'wp_login', array( $this, 'record_user_last_login_timestamp' ), 10, 2 );

		add_filter( 'acf/load_field/name=fxup_event_type_auto_schedule_daily_time', array( $this, 'fxup_load_field_fxup_event_type_auto_schedule_daily_time' ), 10, 1 );
		// Overwrite all sending emails with custom FROM name and FROM email address as input into ACF options page
		add_filter( 'wp_mail_from', array( $this, 'fxup_mail_from' ), 10, 1 );
		add_filter( 'wp_mail_from_name', array( $this, 'fxup_mail_from_name' ), 10, 1 );

		add_action( 'wp', array( $this, 'test_email_service' ), 10 ); //temp
//		add_filter( 'acf/settings/remove_wp_meta_box', '__return_false' ); // temp
	}

	public function test_email_service()
	{
		if ( ! isset( $_GET['debug_email'] ) ) {
			return false;
		}

		do_action( 'fxup/process_email_queue' ); // for testing digests
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

	public function test_cron_email_notifications()
	{

//		$this->concierge_digest_report();
//		$this->guest_travel_deadline_process();
//		$this->concierge_deadline_process();
//		$this->itinerary_deadline_process();
//		$this->new_account_login_reminder();
	}

	public function setup_admin()
	{
		// Custom ACF options pages can be defined here
	}

	public function define_user()
	{
		$current_user = wp_get_current_user();
		if ( 0 === $current_user->ID ) {
			$this->user_id = 0;
			$this->user_role = 'unregistered';
		} elseif ( ( ! empty( $current_user->roles )) && in_array( 'um_concierge', $current_user->roles ) ) {
			$this->user_id = $current_user->ID;
			$this->user_role = 'concierge';
		} else {
			$this->user_id = $current_user->ID;
			$this->user_role = 'subscriber';
		}

		$this->is_concierge = ('concierge' === $this->user_role);

		$this->user_display_name = $current_user->display_name;
	}

	public function define_options()
	{
		$this->new_itinerary_form_link = site_url() . '/new-itinerary/';

		// Jump to links
		$this->jump_to_links = $this->get_field( 'fxup_jump_to_links' );

		// Link center
		$this->link_center = $this->get_field( 'fxup_link_center' );

		// Label for confirmation checkboxes on Guest Travel Details page
		$this->label_for_travel_arrangements_confirm_checkbox = $this->get_field( 'fxup_label_for_travel_arrangements_confirm_checkbox' );

		// When edit is locked - used to calculate notifications
		$this->edit_day_cutoff_interval_in_days = (int) $this->get_field( 'fxup_edit_day_cutoff_interval' );
		$this->edit_day_cutoff_interval_in_days_interval_object = new \DateInterval( "P" . $this->edit_day_cutoff_interval_in_days . "D" );

		// Advance approval warning and messages
		$this->activity_approval_warning_message = $this->get_field( 'fxup_activity_approval_warning_message' );
		$this->activity_approval_confirmed_message = $this->get_field( 'fxup_activity_approval_confirmed_message' );

		// Standard activity booking times
		$activity_booking_time_options_textfield = $this->get_field( 'fxup_activity_booking_time_options' );
		$activity_booking_time_options_array = explode( ",", $activity_booking_time_options_textfield );
		foreach ( $activity_booking_time_options_array as $untrimmed_time_option ) {
			$this->activity_booking_time_options[] = trim( $untrimmed_time_option );
		}

		// Tooltips
		$this->activity_guests_attending_tooltip = $this->get_field( 'fxup_guests_attending_tooltip' );
		$this->activity_child_guests_tooltip = $this->get_field( 'fxup_child_guests_tooltip' );
		$this->activity_booked_tooltip = $this->get_field( 'fxup_activity_booked_tooltip' );
		$this->activity_prices_tooltip = $this->get_field( 'fxup_activity_prices_tooltip' );
		$this->booking_confirmation_tooltip = $this->get_field( 'fxup_booking_confirmation_tooltip' );

		// Placeholder
		$this->activity_child_guests_names_example_placeholder = $this->get_field( 'fxup_child_guests_names_example_placeholder' );

		// WYSIWYG
		$this->above_itinerary_form_wysiwyg = $this->get_field( 'fxup_above_itinerary_form_wysiwyg' );

		// Welcome Video
		$this->welcome_video = $this->get_field( 'welcome_video' );

		// Transportation Reminder
		$this->transportation_reminder = $this->get_field( 'fxup_transportation_reminder' );
	}

	public function define_models()
	{
		$this->models = [
			'itinerary' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/models/Itinerary.php',
				'name' => '\FXUP_User_Portal\Models\Itinerary',
			],
			'villa' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/models/Villa.php',
				'name' => '\FXUP_User_Portal\Models\Villa',
			],
			'tripday' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/models/TripDay.php',
				'name' => '\FXUP_User_Portal\Models\TripDay',
			],
			'activity' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/models/Activity.php',
				'name' => '\FXUP_User_Portal\Models\Activity',
			],
			'guest' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/models/Guest.php',
				'name' => '\FXUP_User_Portal\Models\Guest',
			],
		];
	}

	public function include_models()
	{
		/*
		  include_once $this->models['itinerary']['path'];
		  include_once $this->models['guest']['path'];
		  include_once $this->models['villa']['path'];
		  include_once $this->models['tripday']['path'];
		  include_once $this->models['activity']['path'];
		 */
	}

	public function define_views()
	{
		$this->views = [
			'ui-navigation' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/partials/ui-navigation.php',
				'data' => [],
			],
			'form-itinerary' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/form-itinerary.php',
				'data' => [],
			],
			'dashboard' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/dashboard.php',
				'data' => [],
			],
			'dashboard-itinerary' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/partials/dashboard-itinerary.php',
				'data' => [],
			],
			'single-itinerary' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/single-itinerary.php',
				'data' => [],
			],
			'itinerary-trip-day' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/partials/itinerary-trip-day.php',
				'data' => [],
			],
			'itinerary-check-in' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/partials/itinerary-check-in.php',
				'data' => [],
			],
			'itinerary-check-out' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/partials/itinerary-check-out.php',
				'data' => [],
			],
			'trip-day-activity' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/partials/trip-day-activity.php',
				'data' => [],
			],
			'itinerary-event-type-flyout' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/partials/itinerary-event-type-flyout.php',
				'data' => [],
			],
			'share-print-itinerary' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/share-print-itinerary.php',
				'data' => [],
			],
			'share-print-trip-day' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/partials/share-print-trip-day.php',
				'data' => [],
			],
			'share-print-activity' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/partials/share-print-activity.php',
				'data' => [],
			],
			'summary' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/summary.php',
				'data' => [],
			],
			'summary-rooms' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/partials/summary-rooms.php',
				'data' => [],
			],
			'summary-transportation' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/partials/summary-transportation.php',
				'data' => [],
			],
			'transportation_summary' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/transportation-summary.php',
				'data' => [],
			],
			'email-concierge-newly-confirmed-activities' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/emails/email-concierge-newly-confirmed-activities.php',
				'data' => [],
			],
			'email-concierge-newly-confirmed-activity' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/emails/partials/email-concierge-newly-confirmed-activity.php',
				'data' => [],
			],
			'email-concierge-digest-report' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/emails/email-concierge-digest-report.php',
				'data' => [],
			],
			'email-concierge-digest-report-single-itinerary' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/emails/partials/email-concierge-digest-report-single-itinerary.php',
				'data' => [],
			],
			'email-concierge-deadline-check' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/emails/email-concierge-deadline-check.php',
				'data' => [],
			],
			'email-concierge-deadline-check-single-itinerary' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/emails/partials/email-concierge-deadline-check-single-itinerary.php',
				'data' => [],
			],
			'email-concierge-guest-travel-arrangements-submitted' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/emails/email-concierge-guest-travel-arrangements-submitted.php',
				'data' => [],
			],
			'email-account-creation-reminder' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/emails/email-account-creation-reminder.php',
				'data' => [],
			],
			'guest-list-edit-row' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/partials/guest-list-edit-row.php',
				'data' => [],
			],
			'itinerary-transport' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/partials/itinerary-transport.php',
				'data' => [],
			],
			'email-concierge-single-itinerary-summary-link' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/emails/partials/email-concierge-single-itinerary-summary-link.php',
				'data' => [],
			],
			'grocery-search' => [
				'path' => FXUP_USER_PORTAL()->plugin_path() . '/includes/views/grocery-search.php',
				'data' => [],
			],
		];
	}

	public function add_custom_meta_boxes()
	{
		$boxes = [
			[
				'id' => 'fxup_itinerary_bulk_activity',
				'title' => 'Bulk Add Activities',
				'callback' => array( $this, 'meta_box_itinerary_bulk_activity_display' ),
				'screen' => 'itinerary',
				'context' => 'advanced',
			],
			[
				'id' => 'fxup_itinerary_send_notifications',
				'title' => 'Send Notifications',
				'callback' => array( $this, 'meta_box_send_notifications_display' ),
				'screen' => 'itinerary',
				'context' => 'side',
			],
		];

		foreach ( $boxes as $config ) {
			add_meta_box(
				$config['id'],
				$config['title'],
				$config['callback'],
				$config['screen'],
				$config['context'],
			);
		}
	}

	public function meta_box_itinerary_bulk_activity_display( $post )
	{
		$query = new \WP_Query(
			array(
			'post_type' => array( 'activity', 'service', 'wedding' ),
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
			)
		);

		$activities = is_array( $query->posts ) ? $query->posts : array();

		$activities_ids_and_titles = wp_list_pluck( $activities, 'post_title', 'ID' ); // Value is title, key is post id.

		wp_reset_query();

		$booked_time_options = \FXUP_User_Portal\Models\Activity::getStandardBookedTimeOptions();
		$booked_time_options = is_array( $booked_time_options ) ? $booked_time_options : array();
		?>
		<label for="fxup_itinerary_bulk_activity_check">
			<input type="checkbox" id="fxup_itinerary_bulk_activity_check" name="fxup_itinerary_bulk_activity_check"/>
			Bulk Schedule?
		</label>
		<label>Activity</label>
		<select id="fxup_itinerary_bulk_activity_id" name="fxup_itinerary_bulk_activity_id">
			<option>Select one</option>
			<?php foreach ( $activities_ids_and_titles as $post_id => $post_title ) { ?>
				<option value="<?php echo $post_id; ?>"><?php echo $post_title; ?></option>
		<?php } ?>
		</select>
		<label>Time</label>
		<select id="fxup_itinerary_bulk_activity_time" name="fxup_itinerary_bulk_activity_time">
			<option>Select one</option>
			<?php foreach ( $booked_time_options as $booked_time_option ) { ?>
				<option value="<?php echo $booked_time_option; ?>"><?php echo $booked_time_option; ?></option>
		<?php } ?>
		</select>
		<?php
	}

	public function meta_box_itinerary_bulk_activity_save( $post_id )
	{

		// Checkbox and activity post_id are required
		if ( ! isset( $_POST['fxup_itinerary_bulk_activity_check'] ) || ! isset( $_POST['fxup_itinerary_bulk_activity_id'] ) ) {
			return;
		}

		// Make sure post_id is for Itinerary (should be, but you know)
		if ( get_post_type( $post_id ) !== 'itinerary' ) {
			return;
		}

		$activity_post_id = filter_var( $_POST['fxup_itinerary_bulk_activity_id'], FILTER_SANITIZE_NUMBER_INT );
		$activity_post_type = get_post_type( $activity_post_id );

		$activity_types = array( 'activity', 'service', 'wedding' );
		if ( ! in_array( $activity_post_type, $activity_types ) ) {
			return;
		}

		$fxup_itinerary_bulk_activity_time = isset( $_POST['fxup_itinerary_bulk_activity_time'] ) ? filter_var( $_POST['fxup_itinerary_bulk_activity_time'], FILTER_SANITIZE_STRING ) : ''; // Default to empty string

		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $post_id );

		// Get TripDay objects
		$TripDayObjects = $Itinerary->getTripDays();
		// Loop through each TripDay
		foreach ( $TripDayObjects as $TripDayObject ) {
			// Call addActivity
			$ActivityObject = $TripDayObject->addActivity( $activity_post_id );
			// With returned Activity, call setBooked and setTimeBooked
			$ActivityObject->setBookedTime( $fxup_itinerary_bulk_activity_time );
			$ActivityObject->setBooked( true );
		}

		// No need to save anything for the meta box fields themselves - these are intended for 1 time use only.
	}

	public function meta_box_send_notifications_display( $post )
	{
		?>
		<div class="misc-pub-section clearfix" style="min-height:30px;">
			<label>Guest Travel Reminder</label>
			<button type="button" class="button button-secondary js-fxup-send-guest-travel-reminder-notification" style="float:right" data-id="<?php echo esc_attr( $post->ID ); ?>">Send</button>
			<div class="js-fxup-send-guest-travel-reminder-notification-message"></div>
		</div>
		<script>
			( function ( $ ) {
				$( document ).on( 'click', '.js-fxup-send-guest-travel-reminder-notification', function ( e ) {
					const ID = $( e.target ).attr( 'data-id' );
					$.ajax( {
						type: 'post',
						url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
						data: {
							action: 'fxup_admin_send_guest_reminder',
							itinerary_post_id: ID,
							guest_id: false
						},
						success: function ( results ) {
							$( '.js-fxup-send-guest-travel-reminder-notification-message' ).text( 'Emails sent.' );
						}
					} )
				} );
			} )( jQuery );
		</script>
		<?php
	}

	/**
	 * Customize which time choices are available for the Time Booked select lists
	 */
	public function fxup_load_field_fxup_event_type_auto_schedule_daily_time( $field )
	{

		// Make sure it is the right field
		if ( isset( $field['key'] ) && $field['key'] === 'field_60dde7844656c' ) {

			$choices = array();
			$options = \FXUP_User_Portal\Models\Activity::getStandardBookedTimeOptions();

			$choices['Select an option'] = 'Select an option';
			foreach ( $options as $option ) {
				$choices[$option] = $option; // Key and value are same
			}

			$field['choices'] = $choices;
		}

		// return the field
		return $field;
	}

	/**
	 * The AJAX itinerary processor for sending to concierge
	 */
	public function process_submit()
	{

		parse_str( $_POST['itin_form'], $itin_form );
		if ( is_null( $itin_form ) ) {
			$itin_form = $_POST['itin_form'];
		}

		// Exit early if the form is empty
		if ( empty( $itin_form ) ) {
			echo 'Invalid Form';
			die();
		}

		$total_days = (int) $itin_form['itinerary_total_days'];
		$itin_post = (int) $itin_form['itinerary_id'];

		$itin_user = $_POST['itin_user'];

		/* BEGIN Day-Specific Data */
		$itin_form_data = $this->format_itinerary_data( $itin_form, $total_days );

		$this->store_itinerary_data( $itin_post, $itin_form_data );

		// Clear all activity repeater fields
		$this->clear_itinerary_activities( $total_days, $itin_post );

		// Format and process form data into ACF repeater fields
		$this->update_itinerary_activities( $itin_form_data, $itin_post );

		/* END Day-Specific Data */

		/* BEGIN General Itinerary Data */
		$status = 'Pending Concierge Approval';

		if ( isset( $itin_form['itin-status'] ) ) {
			$status = $itin_form['itin-status'];
		}

		$this->update_itinerary_status( $itin_post, $status );

		if ( isset( $itin_form['fxup-itinerary-client-bio-and-notes'] ) ) {
			$client_bio_and_notes = sanitize_textarea_field( $itin_form['fxup-itinerary-client-bio-and-notes'] );
			update_field( 'fxup_itinerary_client_bio_and_notes', $client_bio_and_notes, $itin_post );
		}


		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itin_post );

		if ( $this->is_concierge && (( ! $Itinerary->isEditable()) || $Itinerary->isEditableManualOverride()) ) {
			$Itinerary->setEditableManualOverride( isset( $itin_form['fxup-editable-manual-override'] ) ? true : false );
		}

		/* END General Itinerary Data */

		if ( $itin_user != 15 ) {
			$this->process_email_confirmation( $itin_user, $itin_post, $itin_form_data, $itin_form );
			exit;
		}
	}

	public function save_manual_override()
	{
		$itinerary_id = filter_input( INPUT_POST, 'itin' );
		// Exit early if the form is empty
		if ( is_null( $itinerary_id ) || empty( $itinerary_id ) ) {
			echo 'Invalid Form';
			die();
		}

		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itinerary_id );
		$Itinerary->setEditableManualOverride(  ! $Itinerary->isEditableManualOverride() );
	}

	/**
	 * Asynchronously load flyouts
	 */
	public function callback_get_event_type_flyout()
	{

		$response = array(
			'status' => null,
			'markup' => null,
		);

		if ( ! isset( $_POST['event_post_type'] ) ) {
			$response['status'] = 'error';
			echo json_encode( $response );
			wp_die();
		}

		if ( ! isset( $_POST['itinerary_post_id'] ) ) {
			$response['status'] = 'error';
			echo json_encode( $response );
			wp_die();
		}

		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( filter_var( $_POST['itinerary_post_id'], FILTER_SANITIZE_STRING ) );

		$event_post_type = filter_var( $_POST['event_post_type'], FILTER_SANITIZE_STRING ); // This is a post type
		if ( ! in_array( $event_post_type, \FXUP_User_Portal\Models\Activity::getPostActivityTypes() ) ) {
			$response['status'] = 'error';
			echo json_encode( $response );
			wp_die();
		}

		// $this->template_data['single-itinerary']['itinerary_event_post_type_flyout_path'];
		// $event_post_types = array();

		$event_type = array();
		$taxonomy = null;
		switch ( $event_post_type ) {
			case 'activity':
				$event_type['flyout_name'] = 'Activity';
				// Flyout heading
				$event_type['flyout_heading'] = 'Activities';
				// Flyout JavaScript target part
				$event_type['flyout_id'] = 'activities';
				$taxonomy = 'activity_category';
				break;
			case 'service':
				$event_type['flyout_name'] = 'Service';
				// Flyout heading
				$event_type['flyout_heading'] = 'Services';
				// Flyout JavaScript target part
				$event_type['flyout_id'] = 'services';
				$taxonomy = 'service_category';
				break;
			case 'wedding':
				$event_type['flyout_name'] = 'Venue';
				// Flyout heading
				$event_type['flyout_heading'] = 'Wedding Venues';
				// Flyout JavaScript target part
				$event_type['flyout_id'] = 'weddings';
				$taxonomy = 'wedding_category';
				break;
			default:
				$response['status'] = 'error';
				echo json_encode( $response );
				wp_die();
				break;
		}

		$activity_args = array(
			'post_type' => $event_post_type
			, 'posts_per_page' => -1
			, 'orderby' => 'ID'
			, 'order' => 'ASC'
		);
		$activity_query = new \WP_Query( $activity_args );
		$activity_posts = $activity_query->posts;
		$event_type['posts'] = $activity_posts;
		// Categories
		$activity_categories = get_terms( array( 'taxonomy' => $taxonomy ) );
		$event_type['categories'] = $activity_categories;
		// Taxonomy
		$activity_taxonomy = $taxonomy;
		$event_type['taxonomy'] = $activity_taxonomy;

		$is_concierge = $this->is_concierge;

		ob_start();

		include_once $this->views['itinerary-event-type-flyout']['path'];

		$markup = ob_get_contents();
		ob_end_clean();

		$response['status'] = 'success';
		$response['markup'] = $markup;
		echo json_encode( $response );
		wp_die();
	}

	/**
	 * The AJAX itinerary processor
	 */
	public function process()
	{
		parse_str( $_POST['itin_form'], $itin_form );
		if ( is_null( $itin_form ) ) {
			$itin_form = $_POST['itin_form'];
		}

		// Exit early if the form is empty
		if ( empty( $itin_form ) ) {
			echo 'Invalid Form';
			die();
		}

		$total_days = (int) $itin_form['itinerary_total_days'];
		$itin_post = (int) $itin_form['itinerary_id'];
		$itin_user = (int) $_POST['itin_user'];
		$concierge = (int) $_POST['concierge'];

		/* BEGIN Day-Specific Data */
		// Format form data and then save it to the post's content in case we lose it and need to version it
		$itin_form_data = $this->format_itinerary_data( $itin_form, $total_days );

		// capture activity before changes
		$before = get_field( 'itinerary_trip_days', $itin_post );
		$Logger = new Itinerary_Change_Logger();
		$Logger->capture_before( (int) $itin_post, $before );

		$this->store_itinerary_data( $itin_post, $itin_form_data );

		// Clear all activity repeater fields
		$this->clear_itinerary_activities( $total_days, $itin_post );

		// Format and process form data into ACF repeater fields
		$this->update_itinerary_activities( $itin_form_data, $itin_post );
		$status = 'Pending Client Submission';

		/* END Day-Specific Data */

		/* BEGIN Generic Itinerary Data */
		if ( isset( $itin_form['itin-status'] ) ) {
			$status = $itin_form['itin-status'];
		}

		$this->update_itinerary_status( $itin_post, $status );

		if ( isset( $itin_form['fxup-itinerary-client-bio-and-notes'] ) ) {
			$client_bio_and_notes = wp_kses_post( $itin_form['fxup-itinerary-client-bio-and-notes'] );
			update_field( 'fxup_itinerary_client_bio_and_notes', $client_bio_and_notes, $itin_post );
		}

		// capture activity after changes
		if ( function_exists( 'acf_get_store' ) ) {
			acf_get_store( 'values' )->reset( "post_{$itin_post}" );
		}
		$after = get_field( 'itinerary_trip_days', $itin_post );
		$Logger->update_after( (int) $itin_post, $after );

		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itin_post );

		if ( $concierge && (( ! $Itinerary->isEditable()) || $Itinerary->isEditableManualOverride()) ) {
			$Itinerary->setEditableManualOverride( isset( $itin_form['fxup-editable-manual-override'] ) ? true : false );
		}

		/* END Generic Itinerary Data */

		if ( $concierge ) {
			$this->send_concierge_update_email( $itin_user, $itin_post );
		}
		exit;
	}

	/**
	 * The AJAX notification processor
	 */
	public function save_notification_settings()
	{
		parse_str( filter_input( INPUT_POST, 'notification_settings' ), $notification_settings );
		if ( is_null( $notification_settings ) ) {
			$notification_settings = filter_input( INPUT_POST, 'notification_settings' );
		}

		// Exit early if the form is empty
		if ( empty( $notification_settings ) ) {
			echo 'Invalid Form';
			die();
		}

		$itin_post = (int) $notification_settings['itinerary_id'];
		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itin_post );

		$Itinerary->setDisableAllNotifications( $notification_settings['fxup-disable-all-notifications'] );

		wp_send_json_success();
	}

	/**
	 * The AJAX transportation processor
	 */
	public function process_transportation()
	{
		parse_str( filter_input( INPUT_POST, 'transport_form' ), $transport_form );
		if ( is_null( $transport_form ) ) {
			$transport_form = filter_input( INPUT_POST, 'transport_form' );
		}

		// Exit early if the form is empty
		if ( empty( $transport_form ) ) {
			echo 'Invalid Form';
			die();
		}

		$itin_post = (int) $transport_form['itinerary_id'];
		$itin_user = (int) filter_input( INPUT_POST, 'itin_user' );
		$current_user = (int) filter_input( INPUT_POST, 'current_user' );
		$concierge = (int) filter_input( INPUT_POST, 'concierge' );
		$to_delete = json_decode( $transport_form['to_delete'], true );
		$is_client_transports = ! (bool) $concierge;

		/* BEGIN Transport-Specific Data */
		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itin_post );
		$transport_arrivals = array_map( array( $this, 'json_decode_to_array' ), $transport_form['transport_arrival'] );
		$transport_departures = array_map( array( $this, 'json_decode_to_array' ), $transport_form['transport_departure'] );

		$this->update_transportation_rows( $transport_arrivals, $transport_departures, $to_delete, $Itinerary, $is_client_transports );
		/* END Transport-Specific Data */

		$Itinerary->setTransportationUpdatedBy( $current_user );

		wp_send_json( $transport_form['transport_arrival'] );
	}

	/**
	 * The AJAX transportation regenerator
	 */
	public function regenerate_transportation()
	{
		parse_str( $_POST['transport_form'], $transport_form );
		if ( is_null( $transport_form ) ) {
			$transport_form = $_POST['transport_form'];
		}

		// Exit early if the form is empty
		if ( empty( $transport_form ) ) {
			echo 'Invalid Form';
			die();
		}

		$itin_post = (int) $transport_form['itinerary_id'];
		$itin_user = (int) $_POST['itin_user'];
		$concierge = (int) $_POST['concierge'];

		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itin_post );
		$Itinerary->getTransports( true, true );

		$maybe_load_client_transports = false;
		$is_concierge = true;
		$editable = true;
		$markup = '';
		ob_start();
		include $Itinerary->transportationFormViewPath();
		$markup = ob_get_clean();

		wp_send_json( [
			'Message' => 'Success!',
			'form_markup' => $markup
		] );
	}

	public function itinerary_transport_form_view( $Itinerary )
	{
		$maybe_load_client_transports = isset( $_GET['loadfromclient'] ) ? true : false;
		ob_start();
		include $Itinerary->transportationFormViewPath();
		return ob_get_clean();
	}

	private function update_transportation_rows( $arrivals, $departures, $to_delete, $Itinerary, $is_client_transports = false )
	{
		$original_raw_transports = $is_client_transports ? $Itinerary->getClientTransports() : $Itinerary->getTransports();
		// maybe delete Transports
		foreach ( array( 'arrival', 'departure' ) as $type ) {
			if ( ! empty( $to_delete[$type] ) ) {
				$data = $to_delete[$type];
				rsort( $data );
				foreach ( $data as $i ) {
					$Transport = new \FXUP_User_Portal\Models\Transport( $i, $type, $Itinerary, false, $is_client_transports );
					$Transport->deleteFromItinerary();
				}
			}
		}

		$raw_transports = array( 'arrival' => $arrivals, 'departure' => $departures );
		if ( $is_client_transports ) {
			$Itinerary->setRawClientTransports( $raw_transports );
		} else {
			$Itinerary->setRawTransports( $raw_transports );
		}

		// rebuild Transports after setting the raw data
		$Transports = $is_client_transports ? $Itinerary->getClientTransports( true ) : $Itinerary->getTransports( true );

		// save the new data
		if ( ! empty( array_filter( array_values( $Transports ) ) ) ) {
			foreach ( array( 'arrival', 'departure' ) as $type ) {
				foreach ( $Transports[$type] as $Transport ) {
					$Transport->saveToItinerary( true );
				}
			}
		}
	}

	public function add_transport()
	{
		$transport_type = filter_input( INPUT_POST, 'transport_type' );
		// Exit early if the form is empty
		if ( empty( $transport_type ) || $transport_type == '' ) {
			echo 'Invalid Form';
			die();
		}

		$itin_post = (int) filter_input( INPUT_POST, 'itinerary' );
		$index = (int) filter_input( INPUT_POST, 'index' );
		$t = $index + 1;
		$itin_user = (int) filter_input( INPUT_POST, 'itin_user' );
		$concierge = (int) filter_input( INPUT_POST, 'concierge' );
		if ( $concierge ) {
			$is_concierge = true;
		}
		$is_client_transports = ! $concierge;

		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itin_post );
		$Transport = $Itinerary->addEmptyTransport( $t, $transport_type, $is_client_transports );
		$t ++;

		ob_start();
		include $Itinerary->transportViewPath();
		$response = ob_get_clean();

		wp_send_json( $response );
	}

	public function process_guest_list()
	{
		parse_str( $_POST['guest_list'], $guest_list );
		if ( is_null( $guest_list ) ) {
			$guest_list = $_POST['guest_list'];
		}

		// Exit early if the form is empty
		if ( empty( $guest_list ) ) {
			echo 'Invalid Form';
			die();
		}

		$errors = $this->validate_guest_list( $guest_list );
		if ( ! empty( $errors ) ) {
			$return['errors'] = $errors;
			echo json_encode( $return );
			exit;
		}

		$itin_post = (int) $guest_list['itinerary_id'];

		$formatted_guest_list = $this->format_guest_list( $guest_list );

		$this->clear_guest_list( $formatted_guest_list, $itin_post );

		$this->update_guest_list( $formatted_guest_list, $itin_post );

		$output['success'] = true;
		echo json_encode( $output );

		exit;
	}

	public function format_guest_list( $list )
	{
		$guest_data = array();

		foreach ( $list as $guest_row => $value ) {
			$field = explode( '-', $guest_row );
			if ( strpos( $guest_row, 'group-' . $i ) !== false ) {
				$guest_group = $field[1];
				if ( strpos( $guest_row, 'guest' ) !== false ) {
					$guest_number = $field[3];
					$guest_data[$guest_group]['guests'][$guest_number][$guest_row] = $value;
				} else {
					$guest_data[$guest_group][$guest_row] = $value;
				}
			}
		}

		return $guest_data;
	}

	public function update_onsite_status()
	{
		if ( ! isset( $_POST['guest_id'] ) || $_POST['guest_id'] == '' ) {
			return false;
			die;
		}

		if ( ! isset( $_POST['onsite'] ) || $_POST['onsite'] == '' ) {
			return false;
			die;
		}

		$guest_id = sanitize_text_field( $_POST['guest_id'] );
		$onsite = sanitize_text_field( $_POST['onsite'] );

		$Guest = new \FXUP_User_Portal\Models\Guest( $guest_id );
		$Guest->setOnsite( $onsite ); // String value "Yes"/"No" - not ideal.

		exit;
	}

	public function remove_user_guest_item()
	{
		if ( ! isset( $_POST['guest_id'] ) || $_POST['guest_id'] == '' ) {
			return false;
			die;
		}

		if ( ! isset( $_POST['itin_id'] ) || $_POST['itin_id'] == '' ) {
			return false;
			die;
		}

		$guest_id = sanitize_text_field( $_POST['guest_id'] );
		$itin_id = sanitize_text_field( $_POST['itin_id'] );

		if ( is_numeric( $guest_id ) && is_numeric( $itin_id ) ) {
			// email admin user to notify of guest deletion -- so they can delete in Active Campaign

			$email_data = array(
				'itinerary_id' => $itin_id,
				'itinerary_title' => get_the_title( $itin_id ),
				'guest_first_name' => get_post_meta( $guest_id, 'guest_first_name', true ),
				'guest_last_name' => get_post_meta( $guest_id, 'guest_last_name', true ),
				'guest_email' => get_post_meta( $guest_id, 'guest_email', true ),
			);
			$args = array(
				'type' => 'email-digest-concierge-guest-removed',
				'data' => $email_data,
				'is_sent' => false,
			);
			$Email_Service = FXUP_USER_PORTAL()->email( $itin_id );
			$Email = $Email_Service->create( $args );

			wp_delete_post( (int) $guest_id, false );
		}

		die;
	}

	public function clear_guest_list( $list, $pid )
	{
		$count = count( $this->get_field( 'guest_list_items', $pid ) );
		$i = 1;
		while ( $i <= $count ) {
			delete_row( 'guest_list_items', 1, $pid );
			$i ++;
		}
	}

	public function update_guest_list( $list, $pid )
	{
		$g = 1;
		foreach ( $list as $l ) {
			$group_field_parts = explode( '-', array_key_first( $l ) );
			$group_field_number = $group_field_parts[1];

			$group_name = $l['group-' . $group_field_number . '-name'];
			$group_email = $l['group-' . $group_field_number . '-email'];

			$row = array(
				'group_name' => $group_name,
				'primary_contact_email' => $group_email
			);

			add_row( 'guest_list_items', $row, $pid );

			foreach ( $l['guests'] as $guest ) {
				$guest_field_parts = explode( '-', array_key_first( $guest ) );
				$guest_field_number = $guest_field_parts[3];

				$guest_name = $guest['group-' . $group_field_number . '-guest-' . $guest_field_number . '-name'];
				$guest_passport = $guest['group-' . $group_field_number . '-guest-' . $guest_field_number . '-passport'];
				$guest_notes = $guest['group-' . $group_field_number . '-guest-' . $guest_field_number . '-notes'];
				$guest_adult_or_child = $guest['group-' . $group_field_number . '-guest-' . $guest_field_number . '-adult_or_child'];

				$sub_row = array(
					'guest_name' => $guest_name,
					'guest_passport_number' => $guest_passport,
					'guest_notes_or_allergies' => $guest_notes,
					'adult_or_child' => $guest_adult_or_child
				);

				add_sub_row( array( 'guest_list_items', $g, 'guest' ), $sub_row, $pid );
			}

			$g ++;
		}
	}

	public function maybe_add_guest_list_item()
	{
		if ( ! isset( $_POST['guest_list'] ) ) {
			echo 'Error adding guest group. Please try again.';
			die();
		}

		parse_str( $_POST['guest_list'], $guest_list );

		$last_group_parts = explode( '-', array_key_last( $guest_list ) );
		$last_group = (int) $last_group_parts[1];
		$new_group = $last_group + 1;

		$output = array();
		$output['html'] = $this->guest_group_render( $new_group );
		$output['target'] = '.js-group-' . $last_group;

		echo json_encode( $output );
		exit;
	}

	public function maybe_add_guest_item()
	{
		if ( ! isset( $_POST['guest_list'] ) || ! isset( $_POST['guest_group'] ) ) {
			echo 'Error adding guest group. Please try again.';
			die();
		}

		parse_str( $_POST['guest_list'], $guest_list );
		$formatted_guest_list = $this->format_guest_list( $guest_list );
		$guest_group = (int) $_POST['guest_group'];

		$last_guest = array_key_last( $formatted_guest_list[$guest_group]['guests'] );
		$new_guest = $last_guest + 1;

		$output = array();
		$output['html'] = $this->guest_render( $guest_group, $new_guest );
		$output['target'] = '.js-group-' . $guest_group;

		echo json_encode( $output );
		exit;
	}

	public function maybe_add_user_guest_item()
	{
		if ( ! isset( $_POST['guest_list'] ) ) {
			echo 'Error adding guest group. Please try again.';
			die();
		}

		parse_str( $_POST['guest_list'], $guest_list );

		$last_guest = array_key_last( $guest_list );
		$last_guest_parts = explode( '-', $last_guest );
		$last_guest_int = (int) $last_guest_parts[1];

		$new_guest = $last_guest_int + 1;

		$output = array();
		$output['html'] = $this->guest_user_add_render( $new_guest );

		echo json_encode( $output );
		exit;
	}

	public function maybe_insert_guest_item( $guest_list )
	{

		if ( ! isset( $_POST['guest_list'] ) || ! isset( $_POST['itinerary_id'] ) ) {
			echo 'Error adding guest group. Please try again.';
			die();
		}

		parse_str( $_POST['guest_list'], $guest_list );

		$user_formatted_guests = $this->format_user_guest_list( $guest_list );

		$errors = $this->validate_guest_list( $guest_list );
		if ( ! empty( $errors ) ) {
			$return['errors'] = $errors;
			echo json_encode( $return );
			exit;
		}

		$pid = (int) $_POST['itinerary_id'];

		$group_name = $guest_list['group-name'];
		$group_email = $guest_list['group-email'];

		$row = array(
			'group_name' => $group_name,
			'primary_contact_email' => $group_email
		);

		add_row( 'guest_list_items', $row, $pid );

		// by using add_row, we append the new guest to guest list and then we get the total so we can easily insert sub rows
		$total_row_count = count( $this->get_field( 'guest_list_items', $pid ) );

		if ( is_array( $user_formatted_guests ) && count( $user_formatted_guests ) > 0 ) {
			foreach ( $user_formatted_guests['guests'] as $guest ) {
				$first = array_key_first( $guest );
				$first_parts = explode( '-', $first );
				$g = $first_parts[1];

				$name = $guest['guest-' . $g . '-name'];
				$passport = $guest['guest-' . $g . '-passport'];
				$notes = $guest['guest-' . $g . '-notes'];
				$adult_or_child = $guest['guest-' . $g . '-adult-or-child'];

				$sub_row = array(
					'guest_name' => $name,
					'guest_passport_number' => $passport,
					'guest_notes_or_allergies' => $notes,
					'adult_or_child' => $adult_or_child
				);

				add_sub_row( array( 'guest_list_items', $total_row_count, 'guest' ), $sub_row, $pid );
			}
		}

		$output['redirect'] = site_url() . '/add-guest-thank-you';

		echo json_encode( $output );
		exit;
	}

	public function submit_user_guest_list()
	{

		if ( ! isset( $_POST['itin_id'] ) ) {
			echo 'Error submitting. Please try again.';
			die();
		}

		$itinerary_post_id = sanitize_text_field( $_POST['itin_id'] );
		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itinerary_post_id );
		$Itinerary->setGuestListSubmitted( true );
	}

	public function edit_guest_list_row()
	{
		$guest_id = filter_input( INPUT_POST, 'guest_id', FILTER_SANITIZE_NUMBER_INT );
		if ( null === $guest_id || false === $guest_id ) {
			echo 'Invalid guest id';
			die();
		}


		$Guest = new \FXUP_User_Portal\Models\Guest( $guest_id );

		ob_start();
		include $this->views['guest-list-edit-row']['path'];
		$markup = ob_get_contents();
		ob_end_clean();

		echo json_encode( [
			'Message' => 'Success!',
			'form_markup' => $markup
		] );

		exit();
	}

	public function handle_guest_list_row_edit()
	{
		$guest_id = filter_input( INPUT_POST, 'guest_id', FILTER_SANITIZE_NUMBER_INT );
		if ( null === $guest_id || false === $guest_id ) {
			echo 'Invalid guest id';
			die();
		}


		$Guest = new \FXUP_User_Portal\Models\Guest( $guest_id );

		$guest_row_form_data = array();

		parse_str( $_POST['guest_row_form_data'], $guest_row_form_data );

		foreach ( $guest_row_form_data as $key => $value ) {
			switch ( $key ) {
				case 'guest_first_name':
					// FXUP_USER_PORTAL()->debug_log('guest_first_name:', $value);
					break;
				case 'guest_last_name':
					// FXUP_USER_PORTAL()->debug_log('guest_last_name:', $value);
					break;
				case 'guest_email':
					// FXUP_USER_PORTAL()->debug_log('guest_email:', $value);
					break;
				case 'guest_children':
					// FXUP_USER_PORTAL()->debug_log('guest_children:', $value);
					break;
				case 'onsite_stay':
					// FXUP_USER_PORTAL()->debug_log('onsite_stay:', $value);
					$Guest->setOnsite( $value );
					break;
				case 'stay_location':
					// FXUP_USER_PORTAL()->debug_log('stay_location:', $value);
					$Guest->setStayLocation( $value );
					break;
				case 'villa_id':
					// FXUP_USER_PORTAL()->debug_log('villa_id:', $value);
					break;
				case 'room_name':
					// FXUP_USER_PORTAL()->debug_log('room_name:', $value);
					break;
				case 'guest_notes':
					// FXUP_USER_PORTAL()->debug_log('guest_notes:', $value);
					break;
				case 'dietary_restrictions':
					// FXUP_USER_PORTAL()->debug_log('dietary_restrictions:', $value);
					break;
				case 'guest_allergies':
					// FXUP_USER_PORTAL()->debug_log('guest_allergies:', $value);
					break;
				default:
					break;
			}
		}

		echo 'Submitted!';
		exit();
	}

	public function validate_guest_list( $list )
	{
		$errors = array();

		foreach ( $list as $key => $val ) {
			if ( ( ! isset( $val ) || $val == '') && strpos( $key, 'notes' ) == false ) {
				$errors[$key] = 'This field is required';
			}
			if ( strpos( $key, 'email' ) !== false && ! filter_var( $val, FILTER_VALIDATE_EMAIL ) ) {
				$errors[$key] = 'Please enter a valid email address';
			}

			if ( strpos( $key, 'imahuman' ) !== false && $val !== '16749697' ) {
				$errors[$key] = 'There was an error submitting the form';
			}
		}
		return $errors;
	}

	public function format_user_guest_list( $list )
	{
		$guest_data = array();

		foreach ( $list as $guest_row => $value ) {
			$field = explode( '-', $guest_row );
			if ( strpos( $guest_row, 'guest-' ) !== false ) {
				$guest_number = $field[1];
				$guest_data['guests'][$guest_number][$guest_row] = $value;
			}
		}

		return $guest_data;
	}

	public function edit_guest()
	{
		$guest_id = filter_input( INPUT_POST, 'guest_id', FILTER_SANITIZE_NUMBER_INT );
		if ( null === $guest_id || false === $guest_id ) {
			echo 'Invalid guest id';
			die();
		}

		$response = array();
		$Guest = new \FXUP_User_Portal\Models\Guest( $guest_id );
		if ( $Guest ) {
			$guest_data = Form_Handler::get_form_data( 'guest_data' );
			if ( ! empty( $guest_data ) ) {
				foreach ( $guest_data as $prop => $value ) {
					if ( is_array( $value ) && ! empty( $value ) ) {
						delete_post_meta( $Guest->getPostID(), $prop );
						foreach ( $value as $val ) {
							add_post_meta( $Guest->getPostID(), $prop, $val );
						}
					} else {
						update_post_meta( $Guest->getPostID(), $prop, $value );
					}
				}
			}
		}

		wp_send_json_success( array( 'guest_id' => $guest_id, 'Guest' => $Guest->toArray() ), 200 );
	}

	public function save_travel_details()
	{
		if ( ! isset( $_POST['travel_list'] ) ) {
			return;
		}

		parse_str( $_POST['travel_list'], $travel_list );
		if ( isset( $travel_list['guest_ids'] ) ) {
			unset( $travel_list['guest_ids'] );
		}
		if ( isset( $travel_list['guest_ids[]'] ) ) {
			unset( $travel_list['guest_ids[]'] );
		}

		$travel_list_formatted = array();

		$guest_data = array();

		// Group data by guest
		foreach ( $travel_list as $key => $val ) {
			$field_array = explode( '_', $key );
			$guest_pid = array_pop( $field_array );
			$field = implode( '_', $field_array );
			$guest_data[$guest_pid][$field] = $val;
		}

		foreach ( $guest_data as $guest_pid => $meta ) {
			if ( ! isset( $meta['fxup_guest_travel_arrangements_submitted'] ) ) {
				update_post_meta( $guest_pid, 'fxup_guest_travel_arrangements_submitted', 0 );
			}
			foreach ( $meta as $key => $val ) {
				switch ( $key ) {
					case 'fxup_guest_travel_arrangements_submitted':
						if ( '1' !== get_post_meta( $guest_pid, 'fxup_guest_travel_arrangements_submitted', true ) ) {
							$this->notify_concierge_guest_travel_arrangements_submitted( $guest_pid );
							update_post_meta( $guest_pid, 'fxup_guest_travel_arrangements_submitted', 1 );
						}
						update_post_meta( $guest_pid, $key, $val );
						break;
					default:
						update_post_meta( $guest_pid, $key, $val );
				}
			}

			if ( ! isset( $meta['requires_arrival_transportation'] ) ) {
				update_post_meta( $guest_pid, 'requires_arrival_transportation', '0' );
			}

			if ( ! isset( $meta['requires_departure_transportation'] ) ) {
				update_post_meta( $guest_pid, 'requires_departure_transportation', '0' );
			}
		}
		die;
	}

	public function save_guest_travel_details()
	{
		if ( ! isset( $_POST['guest_travel_list'] ) ) {
			return;
		}

		parse_str( $_POST['guest_travel_list'], $travel_list );

		$travel_list_formatted = array();

		foreach ( $travel_list as $key => $val ) {
			$field_array = explode( '_', $key );
			$guest_pid = array_pop( $field_array );
			$field = implode( '_', $field_array );
			switch ( $field ) {
				case 'passport_number':
					// Get the saved passport number
					$unmasked_passport_number = get_post_meta( $guest_pid, $field, true );
					// If the passport that is being sent from form is just the masked version of what is in the database, don't update the database.
					// If it is NOT the same, accept whatever the user inputs as the new passport number.
					if ( self::apply_character_mask( $unmasked_passport_number ) !== $val ) {
						update_post_meta( $guest_pid, $field, $val );
					}
					break;
				default:
					update_post_meta( $guest_pid, $field, $val );
					break;
			}
		}

		// Automatically notify the concierge
		$this->notify_concierge_guest_travel_arrangements_submitted( $guest_pid );
		// Automatically update the submitted flag to show as true
		update_post_meta( $guest_pid, 'fxup_guest_travel_arrangements_submitted', 1 );

		die;
	}

	public function notify_concierge_guest_travel_arrangements_submitted( $guest_post_id )
	{
		$itin_post = get_post_meta( $guest_post_id, 'itinerary_id', true );
		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itin_post );
		$Guest = new \FXUP_User_Portal\Models\Guest( $guest_post_id );

		$email_data = array(
			'message_body' => $this->email_concierge_guest_travel_arrangements_submitted_message,
			'itinerary_url' => $Itinerary->getPermalink(),
			'itinerary_title' => $Itinerary->getTitle(),
			'itinerary_start_date' => $Itinerary->getTripStartDate()->format( 'F j, Y' ),
			'itinerary_end_date' => $Itinerary->getTripEndDate()->format( 'F j, Y' ),
			'guest_travel_url' => $Guest->getTravelLink(),
			'guest_full_name' => $Guest->getFullName(),
			'guest_stay_length' => $Guest->getStayLength(),
			'guest_passport_number' => $Guest->getPassportNumber(),
			'guest_arrival_airline' => $Guest->getAirline(),
			'guest_arrival_flight_number' => $Guest->getFlightNumber(),
			'guest_arrival_date' => $Guest->getArrivalDate(),
			'guest_arrival_time' => $Guest->getArrivalTime(),
			'guest_departure_airline' => $Guest->getDepartureAirline(),
			'guest_departure_flight_number' => $Guest->getDepartureFlightNumber(),
			'guest_departure_date' => $Guest->getDepartureDate(),
			'guest_departure_time' => $Guest->getDepartureTime(),
			'guest_travel_notes' => $Guest->getTravelNotes(),
		);
		$args = array(
			'type' => 'email-concierge-guest-travel-arrangements-submitted',
			'data' => $email_data,
			'is_sent' => false,
		);
		$Email_Service = FXUP_USER_PORTAL()->email( $Itinerary );
		$Email = $Email_Service->create( $args );
	}

	public function setup_template_data_email_concierge_guest_travel_arrangements_submitted( $guest_post_id )
	{

		$this->template_data['email-concierge-guest-travel-arrangements-submitted'] = array();

		// Itinerary
		$itinerary_post_id = get_post_meta( $guest_post_id, 'itinerary_id', true );

		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itinerary_post_id );

		$this->template_data['email-concierge-guest-travel-arrangements-submitted']['Itinerary'] = $Itinerary;

		$Guest = new \FXUP_User_Portal\Models\Guest( $guest_post_id );

		$this->template_data['email-concierge-guest-travel-arrangements-submitted']['Guest'] = $Guest;
	}

	public function save_room_details()
	{
		if ( ! isset( $_POST['room_list'] ) ) {
			return;
		}

		parse_str( $_POST['room_list'], $room_list );

		$itinerary_post_id = sanitize_text_field( $room_list['itinerary'] );
		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itinerary_post_id );

		// TODO: Update JavaScript to submit a bool here instead of 'submitted'.
		if ( $_POST['submit_final'] == 'submitted' ) {
			$Itinerary->setRoomArrangementsSubmitted( true );
		}

		// Iterate over Rooms
		foreach ( $Itinerary->getVilla()->getRooms() as $Room ) {

			$Room->setSelfSaveItinerary( false ); // Disable auto saving on the Room model for better performance.
			$room_form_key = $Room->getFormKey(); // Red_Room-102

			if ( isset( $room_list['room-' . $room_form_key . '-bed-config'] ) ) {
				$Room->setBedConfiguration( $room_list['room-' . $room_form_key . '-bed-config'] );
			}

			if ( isset( $room_list['room-' . $room_form_key . '-pack-and-play'] ) ) {
				$Room->setPackAndPlay( true );
			} else {
				$Room->setPackAndPlay( false );
			}

			if ( isset( $room_list['room-' . $room_form_key . '-additional-guest'] ) ) {
				$Room->setAdditionalGuest( true );
			} else {
				$Room->setAdditionalGuest( false );
			}

			if ( isset( $room_list['room-' . $room_form_key . '-special-requests'] ) ) {
				$Room->setSpecialRequests( $room_list['room-' . $room_form_key . '-special-requests'] );
			}

			// Iterate over guests
			for ( $i = 1; $i <= $Room->getTotalAllowedGuests(); ++ $i ) {

				// If the guest is a valid Guest post type
				if ( ( ! empty( $room_list['room-' . $room_form_key . '-guest-' . $i] )) && get_post( $room_list['room-' . $room_form_key . '-guest-' . $i] ) ) {
					$Guest = new \FXUP_User_Portal\Models\Guest( $room_list['room-' . $room_form_key . '-guest-' . $i] );
					$Room->setGuest( $Guest, $i );
				} else {
					$Room->removeGuest( $i ); // Make sure no Guest is saved in that slot.
				}

				if ( isset( $room_list['room-' . $room_form_key . '-guest-' . $i . '-child'] ) ) {
					$Room->setGuestChild( (bool) $room_list['room-' . $room_form_key . '-guest-' . $i . '-child'], $i ); // Comes as either a 1 or a 0. Method requires bool.
				} else {
					$Room->setGuestChild( false, $i );
				}

				if ( isset( $room_list['room-' . $room_form_key . '-guest-' . $i . '-child-name'] ) ) {
					$Room->setGuestChildName( $room_list['room-' . $room_form_key . '-guest-' . $i . '-child-name'], $i );
				}
			}
			$Room->setSelfSaveItinerary( true ); // Allow row to be updated
			$inserted_row = $Room->saveToItinerary(); // Update row
		}


		die;
	}

	public function insert_room_details( $itin, $t_rooms, $room_list )
	{

		$r = 1;

		while ( $r <= $t_rooms ) {
			$room_name = isset( $room_list['room-' . $r . '-name'] ) ? $room_list['room-' . $r . '-name'] : '';
			$bed_config = isset( $room_list['room-' . $r . '-bed-config'] ) ? $room_list['room-' . $r . '-bed-config'] : '';
			$pack_and_play = isset( $room_list['room-' . $r . '-pack-and-play'] ) ? 1 : 0;
			$guest_one = isset( $room_list['room-' . $r . '-guest-1'] ) && $room_list['room-' . $r . '-guest-1'] !== 'Select a guest' ? $room_list['room-' . $r . '-guest-1'] : '';
			$guest_one_child = isset( $room_list['room-' . $r . '-guest-1-child'] ) ? $room_list['room-' . $r . '-guest-1-child'] : '';
			$guest_one_child_name = isset( $room_list['room-' . $r . '-guest-1-child-name'] ) ? $room_list['room-' . $r . '-guest-1-child-name'] : '';
			$guest_two = isset( $room_list['room-' . $r . '-guest-2'] ) && $room_list['room-' . $r . '-guest-2'] !== 'Select a guest' ? $room_list['room-' . $r . '-guest-2'] : '';
			$guest_two_child = isset( $room_list['room-' . $r . '-guest-2-child'] ) ? $room_list['room-' . $r . '-guest-2-child'] : '';
			$guest_two_child_name = isset( $room_list['room-' . $r . '-guest-2-child-name'] ) ? $room_list['room-' . $r . '-guest-2-child-name'] : '';
			$guest_three = isset( $room_list['room-' . $r . '-guest-3'] ) && $room_list['room-' . $r . '-guest-3'] !== 'Select a guest' ? $room_list['room-' . $r . '-guest-3'] : '';
			$guest_three_child = isset( $room_list['room-' . $r . '-guest-3-child'] ) ? $room_list['room-' . $r . '-guest-3-child'] : '';
			$guest_three_child_name = isset( $room_list['room-' . $r . '-guest-3-child-name'] ) ? $room_list['room-' . $r . '-guest-3-child-name'] : '';
			$guest_four = isset( $room_list['room-' . $r . '-guest-4'] ) && $room_list['room-' . $r . '-guest-4'] !== 'Select a guest' ? $room_list['room-' . $r . '-guest-4'] : '';
			$guest_four_child = isset( $room_list['room-' . $r . '-guest-4-child'] ) ? $room_list['room-' . $r . '-guest-4-child'] : '';
			$guest_four_child_name = isset( $room_list['room-' . $r . '-guest-4-child-name'] ) ? $room_list['room-' . $r . '-guest-4-child-name'] : '';
			$additional_guest = isset( $room_list['room-' . $r . '-additional-guest'] ) ? $room_list['room-' . $r . '-additional-guest'] : '';
			$special_request = isset( $room_list['room-' . $r . '-special-requests'] ) ? $room_list['room-' . $r . '-special-requests'] : '';
			$room_guests_villa_id = isset( $room_list['room-' . $r . '-villa'] ) ? $this->validate_room_guests_villa_id( $room_list['room-' . $r . '-villa'] ) : false;

			$row = array(
				'room_name' => $room_name,
				'bed_configuration' => $bed_config,
				'pack_and_play' => $pack_and_play,
				'guest_1' => $guest_one,
				'guest_1_child' => $guest_one_child,
				'guest_1_child_name' => $guest_one_child_name,
				'guest_2' => $guest_two,
				'guest_2_child' => $guest_two_child,
				'guest_2_child_name' => $guest_two_child_name,
				'guest_3' => $guest_three,
				'guest_3_child' => $guest_three_child,
				'guest_3_child_name' => $guest_three_child_name,
				'guest_4' => $guest_four,
				'guest_4_child' => $guest_four_child,
				'guest_4_child_name' => $guest_four_child_name,
				'additional_guest' => $additional_guest,
				'special_requests' => $special_request
			);

			if ( false !== $room_guests_villa_id ) {
				$row['room_guests_villa_id'] = $room_guests_villa_id;
			}

			add_row( 'room_guests', $row, $itin );
			$r ++;
		}
	}

	// Returns filtered, valid post ID if valid - else returns false
	public function validate_room_guests_villa_id( $input )
	{
		// Default to false
		$valid_post_id = false;
		// Filter input and cast to int
		$filtered_post_id = (int) filter_var( $input, FILTER_SANITIZE_NUMBER_INT );
		// Get all posts of type villa with input ID
		$villa_post_type_query = new \WP_Query(
			array(
			'post_type' => 'villa',
			'p' => $filtered_post_id,
			'posts_per_page' => -1,
			)
		);
		// Check for results
		if ( 0 < $villa_post_type_query->found_posts ) {
			$valid_post_id = $filtered_post_id;
		}
		return $valid_post_id;
	}

	public function clear_room_details( $itin, $t_rooms )
	{
		$r = 0;
		while ( delete_row( 'room_guests', 1, $itin ) ) {
			$r ++;
		}
	}

	public function store_itinerary_data( $pid, $form_data )
	{
		$formatted = '';
		$i = 1;
		foreach ( $form_data as $day ) {
			$a = 0;
			$formatted .= 'Day ' . $i . ':' . "\r\n";

			foreach ( $day as $activity ) {
				$formatted .= "\r\n" . 'Activity ' . $a . ': ' . "\r\n";

				foreach ( $activity as $key => $value ) {
					if ( is_array( $value ) ) {
						$value = implode( ', ', $value );
					}
					if ( strpos( $key, 'activity_title' ) !== false ) {
						$act_title = get_the_title( $value );
						$formatted .= $key . ': ' . $act_title . "\r\n";
					} else {
						$formatted .= $key . ': ' . $value . "\r\n";
					}
				}

				$a ++;
			}
			$formatted .= "\r\n";

			$i ++;
		}
		wp_update_post( array( 'ID' => $pid, 'post_content' => $formatted ) );
	}

	public function clear_itinerary_activities( $tdays, $pid )
	{

		$this->cache_itinerary_activities( $pid );

		$d = 1;
		while ( $d <= $tdays ) {
			$r = 0;
			while ( delete_sub_row( array( 'itinerary_trip_days', $d, 'trip_day_activities' ), 1, $pid ) ) {
				$r ++;
			}
			$d ++;
		}
	}

	public function cache_itinerary_activities( $pid )
	{
		$activities = array();
		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $pid );
		$TripDays = $Itinerary->getTripDays();
		if ( ! empty( $TripDays ) ) {
			foreach ( $TripDays as $TripDay ) {
				$activities[$TripDay->getRowNumber()] = $TripDay->getRawActivities();
			}
		}
		$this->cached_activities = $activities;
	}

	public function clear_trip_day_previous_activities( $tdays, $pid )
	{
		$d = 1;
		while ( $d <= $tdays ) {
			delete_sub_field( array( 'itinerary_trip_days', $d, 'trip_day_previous_activities' ), $pid );
			$d ++;
		}
	}

	public function format_itinerary_data( $form_data, $tdays )
	{
		$itin_form_data = array();
		$i = 1;
		while ( $i <= $tdays ) {
			foreach ( $form_data as $itin_row => $value ) {
				if ( strpos( $itin_row, '-day-' . $i ) !== false ) {
					// Sample input: 'celebration-day-1-activity-12'
					$expression = '/-([0-9]+)$/';
					$matches = array();
					preg_match( $expression, $itin_row, $matches );
					if ( isset( $matches[1] ) ) {
						$act_day = $matches[1];
						$itin_form_data[$i][$act_day][$itin_row] = $value;
					}
				}
			}
			$i ++;
		}

		return $itin_form_data;
	}

	private function get_trip_day_array()
	{
		return array(
			'activity_title' => '',
			'activity_adults' => '',
			'activity_children' => '',
			'guests' => '',
			'activity_comments' => '',
			'staff_notes' => '',
			'activity_final_cost' => '',
			'adult_cost' => '',
			'child_cost' => '',
			'activity_time_booked' => '',
			'activity_exact_time_booked' => '',
			'keep_private' => '',
			'activity_booked' => '',
			'activity_no_conflict' => '',
			'message_private' => '',
			'custom_activity_title' => '',
			'custom_activity_time' => '',
			'celebration' => '',
			'specific_guests' => '',
			'child_guests' => '',
			'fxup_activity_client_confirmed' => '',
			'fxup_event_message' => '',
			'fxup_private_tour_requested' => '',
			'fxup_hide_share_price' => '',
			'fxup_created_by' => '',
			'fxup_updated_by' => '',
		);
	}

	public function update_itinerary_activities( $form_data, $pid )
	{
		// Dynamically build array representing newly-approved activites
		$newly_confirmed_activities = array();
		$raw_trip_days = get_field( 'itinerary_trip_days', $pid );

		foreach ( $form_data as $day_key => $day ) {
			foreach ( $day as $act_key => $activities ) {
				$fxup_activity_client_confirmed = isset( $activities['fxup_activity_client_confirmed-day-' . $day_key . '-activity-' . $act_key] ) ? 1 : 0;
				$fxup_activity_client_confirmed_already = (isset( $activities['fxup_activity_client_confirmed_already-day-' . $day_key . '-activity-' . $act_key] ) && '1' === $activities['fxup_activity_client_confirmed_already-day-' . $day_key . '-activity-' . $act_key]) ? 1 : 0;
				$activity = isset( $activities['activity_title-day-' . $day_key . '-activity-' . $act_key] ) ? $activities['activity_title-day-' . $day_key . '-activity-' . $act_key] : '';
				$act_adults = isset( $activities['activity_adults-day-' . $day_key . '-activity-' . $act_key] ) ? $activities['activity_adults-day-' . $day_key . '-activity-' . $act_key] : '0';
				$act_children = isset( $activities['activity_children-day-' . $day_key . '-activity-' . $act_key] ) ? $activities['activity_children-day-' . $day_key . '-activity-' . $act_key] : '0';
				$act_guests = isset( $activities['guests-day-' . $day_key . '-activity-' . $act_key] ) ? $activities['guests-day-' . $day_key . '-activity-' . $act_key] : '';
				$act_comments = isset( $activities['activity_comments-day-' . $day_key . '-activity-' . $act_key] ) ? filter_var( $activities['activity_comments-day-' . $day_key . '-activity-' . $act_key], FILTER_SANITIZE_STRING ) : '';
				$act_final_cost = isset( $activities['exact_final_cost-day-' . $day_key . '-activity-' . $act_key] ) ? sanitize_text_field( $activities['exact_final_cost-day-' . $day_key . '-activity-' . $act_key] ) : '';
				$act_adult_cost = isset( $activities['adult_cost-day-' . $day_key . '-activity-' . $act_key] ) ? sanitize_text_field( $activities['adult_cost-day-' . $day_key . '-activity-' . $act_key] ) : '';
				$act_child_cost = isset( $activities['child_cost-day-' . $day_key . '-activity-' . $act_key] ) ? sanitize_text_field( $activities['child_cost-day-' . $day_key . '-activity-' . $act_key] ) : '';
				$act_time_booked = isset( $activities['activity_time_booked-day-' . $day_key . '-activity-' . $act_key] ) ? $activities['activity_time_booked-day-' . $day_key . '-activity-' . $act_key] : '';
				$act_exact_time_booked = isset( $activities['exact_time_booked-day-' . $day_key . '-activity-' . $act_key] ) ? $activities['exact_time_booked-day-' . $day_key . '-activity-' . $act_key] : '';
				$keep_private = isset( $activities['keep_private-day-' . $day_key . '-activity-' . $act_key] ) ? $activities['keep_private-day-' . $day_key . '-activity-' . $act_key] : '';
				$act_booked = isset( $activities['activity_booked-day-' . $day_key . '-activity-' . $act_key] ) ? $activities['activity_booked-day-' . $day_key . '-activity-' . $act_key] : '';
				;
				$act_no_conflict = isset( $activities['activity_no_conflict-day-' . $day_key . '-activity-' . $act_key] ) ? $activities['activity_no_conflict-day-' . $day_key . '-activity-' . $act_key] : '0';
				$message_private = isset( $activities['message_private-day-' . $day_key . '-activity-' . $act_key] ) ? $activities['message_private-day-' . $day_key . '-activity-' . $act_key] : '0';
				$custom_activity = isset( $activities['custom_activity_title-day-' . $day_key . '-activity-' . $act_key] ) ? $activities['custom_activity_title-day-' . $day_key . '-activity-' . $act_key] : '';
				$custom_activity_time = isset( $activities['custom_activity_time-day-' . $day_key . '-activity-' . $act_key] ) ? $activities['custom_activity_time-day-' . $day_key . '-activity-' . $act_key] : '';
				$activity_staff_notes = isset( $activities['activity_staff_notes-day-' . $day_key . '-activity-' . $act_key] ) ? filter_var( $activities['activity_staff_notes-day-' . $day_key . '-activity-' . $act_key], FILTER_SANITIZE_STRING ) : '';
				$celebration = isset( $activities['celebration-day-' . $day_key . '-activity-' . $act_key] ) ? $activities['celebration-day-' . $day_key . '-activity-' . $act_key] : '';
				$specific_guests = (isset( $activities['guest-toggle-day-' . $day_key . '-activity-' . $act_key] ) && $activities['guest-toggle-day-' . $day_key . '-activity-' . $act_key] == 'specific') ? 1 : 0;
				$child_guests = isset( $activities['child-guests-day-' . $day_key . '-activity-' . $act_key] ) ? $activities['child-guests-day-' . $day_key . '-activity-' . $act_key] : '';
				$fxup_event_message = isset( $activities['activity_message-day-' . $day_key . '-activity-' . $act_key] ) ? filter_var( $activities['activity_message-day-' . $day_key . '-activity-' . $act_key], FILTER_SANITIZE_STRING ) : '';

				$fxup_private_tour_requested = isset( $activities['fxup_private_tour_requested-day-' . $day_key . '-activity-' . $act_key] ) ? 1 : 0;

				$fxup_hide_share_price = isset( $activities['activity_hide_share_price-day-' . $day_key . '-activity-' . $act_key] ) ? 1 : 0;

				$fxup_created_by = isset( $activities['fxup_created_by-day-' . $day_key . '-activity-' . $act_key] ) ? $activities['fxup_created_by-day-' . $day_key . '-activity-' . $act_key] : '';
				$fxup_updated_by = isset( $activities['fxup_updated_by-day-' . $day_key . '-activity-' . $act_key] ) ? $activities['fxup_updated_by-day-' . $day_key . '-activity-' . $act_key] : '';

				$act_guests_string = '';
				if ( is_array( $act_guests ) ) {
					$act_guests_string = implode( ',', $act_guests );
				}

				// Prepare to send email notifying concierge that there has been a change in activity confirmation
				if ( 1 === $fxup_activity_client_confirmed && 0 === $fxup_activity_client_confirmed_already ) {
					$newly_confirmed_activity_info = array();
					$event_type_post_id = $activity;
					$newly_confirmed_activity_info['event_type_post_id'] = $event_type_post_id; // The title (Wedding, Canopy Tours, Horseback Riding, etc.)
					$newly_confirmed_activity_info['event_type_title'] = get_the_title( $event_type_post_id );
					$newly_confirmed_activity_info['day_number'] = $day_key; // The number of the day
					// Push onto array to use for mailing
					$newly_confirmed_activities[] = $newly_confirmed_activity_info;
				}

				$row = array(
					'activity_title' => $activity,
					'activity_adults' => $act_adults,
					'activity_children' => $act_children,
					'guests' => $act_guests_string,
					'activity_comments' => $act_comments, // Custom Activity Description and Standard Activity Special Requests are the same field
					'staff_notes' => $activity_staff_notes,
					'activity_final_cost' => $act_final_cost,
					'adult_cost' => $act_adult_cost,
					'child_cost' => $act_child_cost,
					'activity_time_booked' => $act_time_booked,
					'activity_exact_time_booked' => $act_exact_time_booked,
					'keep_private' => $keep_private,
					'activity_booked' => $act_booked,
					'activity_no_conflict' => $act_no_conflict,
					'message_private' => $message_private,
					'custom_activity_title' => $custom_activity,
					'custom_activity_time' => $custom_activity_time,
					'celebration' => $celebration,
					'specific_guests' => $specific_guests,
					'child_guests' => $child_guests,
					'fxup_activity_client_confirmed' => $fxup_activity_client_confirmed,
					'fxup_event_message' => $fxup_event_message,
					'fxup_private_tour_requested' => $fxup_private_tour_requested,
					'fxup_hide_share_price' => $fxup_hide_share_price,
					'fxup_created_by' => $fxup_created_by,
					'fxup_updated_by' => $fxup_updated_by,
				);

				$i = add_sub_row( array( 'itinerary_trip_days', $day_key, 'trip_day_activities' ), $row, $pid );
			}

			// save old versions for digest email reporting
//			$old_activities = $raw_trip_days[$day_key - 1]['trip_day_previous_activities'];
//			if ( ! is_array( $old_activities ) ) {
//				$old_activities = array();
//			}
//			if ( ! (end( $old_activities ) == $this->cached_activities[$day_key]) ) {
//				$old_activities[] = $this->cached_activities[$day_key];
//				update_sub_field( array( 'itinerary_trip_days', $day_key, 'trip_day_previous_activities' ), $old_activities, $pid );
//			}
		}

		$this->cached_activities = null;

		// Notify concierge if activities were newly confirmed by customer
//        if (!empty($newly_confirmed_activities) && !$this->is_concierge) {
		if ( ! empty( $newly_confirmed_activities ) ) {
			$this->notify_concierge_newly_confirmed_activities( $pid, $newly_confirmed_activities ); // Pass itinerary post_id and newly confirmed activities
		}
	}

	public function notify_concierge_newly_confirmed_activities( $pid, $newly_confirmed_activities = array() )
	{
		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $pid );
		$itinerary_user_object = get_user_by( 'ID', $Itinerary->getUserID() );
		$itinerary_user_display_name = $itinerary_user_object->display_name;
		$email_concierge_newly_confirmed_activity_template_path = $this->views['email-concierge-newly-confirmed-activity']['path'];

		$email_data = array(
			'itinerary_user_display_name' => $itinerary_user_display_name,
			'newly_confirmed_activities' => $newly_confirmed_activities,
			'email_concierge_newly_confirmed_activity_template_path' => $email_concierge_newly_confirmed_activity_template_path,
		);
		$args = array(
//			'type' => 'email-concierge-newly-confirmed-activities',
			'type' => 'email-digest-concierge-newly-confirmed-activities',
			'data' => $email_data,
			'is_sent' => false,
		);
		$Email_Service = FXUP_USER_PORTAL()->email( $Itinerary );
		$Email = $Email_Service->create( $args );
//		$Email_Service->send( $Email );
	}

	public function update_itinerary_status( $pid, $status )
	{
		update_field( 'approval_status', $status, $pid );
	}

	public function update_transportation_status( $pid, $status )
	{
		update_field( 'transportation_approval_status', $status, $pid );
	}

	public function maybe_add_activity()
	{

		if ( ! isset( $_POST['selected_days'] ) ) {
			echo 'Error adding activities. Please try again.';
			die();
		}

		$itin = $_POST['itin'];
		$selected = $_POST['selected_days'];
		$pid = $_POST['pid'] ?? null;
		$celebration = $_POST['celebration'] ?? null; // When it is present, will come in as string === 'false' or 'true';
		$concierge = $_POST['user'];

		$event_type = null;
		$event_type_post_id = null;

		if ( ! empty( $pid ) ) {
			$event_type_post_id = $pid;
			// Leave the event_type as null
		} elseif ( ! empty( $celebration ) && $celebration !== 'false' ) {
			$event_type = 'celebration';
			// Leave the event_type_post_id as null
		} else {
			$event_type = 'custom';
			// Leave the event_type_post_id as null
		}

		$output = array();
		$i = 0;
		foreach ( $selected as $s ) {
			$day = $s['day'];
			$act = 1;
			if ( isset( $s['activities'] ) ) {
				$act = intval( max( $s['activities'] ) ) + 1;
			}
			$output[$i]['day'] = $day;
			$output[$i]['html'] = $this->view_new_trip_day_activity( $itin, $day, $act, $event_type, $event_type_post_id );
			$output[$i]['activity'] = $act;
			$i ++;
		}

		echo json_encode( $output );
		exit;
	}

	public function view_new_trip_day_activity( $itinerary_post_id, $day_numeric_index, $activity_numeric_index, $event_type, $event_type_post_id = null )
	{
		$this->setup_template_data_new_trip_day_activity( $itinerary_post_id, $day_numeric_index, $activity_numeric_index, $event_type, $event_type_post_id );
		extract( $this->template_data['trip-day-activity'] );
		ob_start();
		include $this->views['trip-day-activity']['path'];
		$markup = ob_get_contents();
		ob_end_clean();
		return $markup;
	}

	public function setup_template_data_new_trip_day_activity( $itinerary_post_id, $day_numeric_index, $activity_numeric_index, $event_type = 'custom', $event_type_post_id = null )
	{

		$this->template_data['trip-day-activity'] = array();

		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itinerary_post_id );

		// Redirect if trying to access someone else's itinerary
		if ( $this->user_id !== $Itinerary->getUserID() && ! $this->is_concierge ) {
			wp_redirect( '/login' );
			exit;
		}

		$this->template_data['trip-day-activity']['Itinerary'] = $Itinerary;

		$editable = ($Itinerary->isEditable() || $this->is_concierge); // Will lock for regular users, but stay editable for concierge.

		$this->template_data['trip-day-activity']['editable'] = $editable;
		$this->template_data['trip-day-activity']['user_role'] = $this->user_role;
		$this->template_data['trip-day-activity']['is_concierge'] = $this->is_concierge; // True/False for concierge role
		$this->template_data['trip-day-activity']['user_display_name'] = $this->user_display_name;
		$this->template_data['trip-day-activity']['hide'] = false;
		$this->template_data['trip-day-activity']['d'] = $day_numeric_index;
		$this->template_data['trip-day-activity']['a'] = $activity_numeric_index;

		// Activity booking time options
		$this->template_data['trip-day-activity']['activity_booking_time_options'] = $this->activity_booking_time_options;

		// Early booking warning message
		$this->template_data['trip-day-activity']['activity_approval_warning_message'] = $this->activity_approval_warning_message;
		$this->template_data['trip-day-activity']['activity_approval_confirmed_message'] = $this->activity_approval_confirmed_message;

		// Tooltips
		$this->template_data['trip-day-activity']['activity_guests_attending_tooltip'] = $this->activity_guests_attending_tooltip;
		$this->template_data['trip-day-activity']['activity_child_guests_tooltip'] = $this->activity_child_guests_tooltip;
		$this->template_data['trip-day-activity']['activity_booked_tooltip'] = $this->activity_booked_tooltip;
		$this->template_data['trip-day-activity']['activity_prices_tooltip'] = $this->activity_prices_tooltip;
		$this->template_data['trip-day-activity']['booking_confirmation_tooltip'] = $this->booking_confirmation_tooltip;

		// Placeholder
		$this->template_data['trip-day-activity']['activity_child_guests_names_example_placeholder'] = $this->activity_child_guests_names_example_placeholder;

		// Day Count
		if ( ! empty( $Itinerary->getTripDays() ) ) {
			$day_count = count( $Itinerary->getTripDays() );
		} else {
			$day_count = 0;
		}
		$this->template_data['trip-day-activity']['day_count'] = $day_count;

		// Guests
		$guest_args = array(
			'post_type' => 'guest',
			'posts_per_page' => -1,
			'orderby' => array(
				'firstname_clause' => 'asc',
				'lastname_clause' => 'asc',
			),
			'meta_query' => array(
				'id_clause' => array(
					'key' => 'itinerary_id',
					'value' => $Itinerary->getPostID(),
				),
				'firstname_clause' => array(
					'key' => 'guest_first_name',
					'compare' => 'EXISTS',
				),
				'lastname_clause' => array(
					'key' => 'guest_last_name',
					'compare' => 'EXISTS',
				),
			)
		);

		$guest_query = new \WP_Query( $guest_args );
		$guest_posts = $guest_query->posts;

		$this->template_data['trip-day-activity']['guest_posts'] = $guest_posts;

		$TripDay = $Itinerary->getTripDays()[$day_numeric_index - 1]; // Get the right TripDay based on numeric index (1 based is passed in, but TripDays array is 0 based);

		if ( ! empty( $event_type_post_id ) ) {
			// $Activity->fromPostType($event_type_post_id);
			$Activity = $TripDay->addActivity( $event_type_post_id );
		} else {
			// $Activity->fromNonPostType($event_type);
			$Activity = $TripDay->addActivity( $event_type );
		}

		$this->template_data['trip-day-activity']['Activity'] = $Activity;
	}

	public function guest_group_render( $group )
	{

		ob_start();
		?>
		<div class="js-accordion-wrapper activity-accordion js-group-<?php echo $group; ?>" data-group="<?php echo $group; ?>">
			<h6 class="accordion-btn">
				<a class="js-accordion-toggle">Group <?php echo $group; ?><i class="fas fa-plus"></i></a>
			</h6>
			<div class="accordion-cont js-accordion-cont" style="display: none;">
				<div class="row">
					<div class="col-xxs-12 ">
						<h4>Group Name: </h4>
						<input type="text" name="group-<?php echo $group; ?>-name" value="">
						<h4>Primary Contact Email: </h4>
						<input type="text" name="group-<?php echo $group; ?>-email" value="">
						<div class="guest-list-wrapper">
							<div class="guest-list-item js-guest-1">
								<h4>Guest 1</h4>
								<p>Guest Name</p>
								<input type="text" name="<?php echo 'group-' . $group . '-guest-1-name'; ?>" value="">

								<p>Passport Number: </p>
								<input type="text" name="<?php echo 'group-' . $group . '-guest-1-passport'; ?>" value="">

								<p>Guest Notes or Allergies: </p>
								<textarea name="<?php echo 'group-' . $group . '-guest-1-notes'; ?>" rows="4" cols="50"></textarea>

								<p>Adult or child? </p>
								<select name="<?php echo 'group-' . $group . '-guest-1-adult-or-child'; ?>">
									<option value="adult">Adult</option>
									<option value="child">Child</option>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<a href="" class="js-delete-guest delete"><i class="fas fa-times-circle"></i> Delete Guest Group</a>
				</div>
			</div>
		</div>

		<?php
		return ob_get_clean();
	}

	public function guest_render( $group, $guest )
	{
		ob_start();
		?>

		<div class="guest-list-item clearfix js-guest-<?php echo $guest; ?>">
			<div class="col-xxs-12">
				<h3>Guest <?php echo $guest; ?></h3>

			</div>

			<div class="col-xxs-12 col-md-4 push-top">
				<label>Guest Name</label>
				<input type="text" name="<?php echo 'group-' . $group . '-guest-' . $guest . '-name'; ?>" value="">
			</div>
			<div class="col-xxs-12 col-md-4 push-top">
				<label>Passport Number: </label>
				<input type="text" name="<?php echo 'group-' . $group . '-guest-' . $guest . '-passport'; ?>" value="">
			</div>
			<div class="col-xxs-12 col-md-4 push-top">
				<label>Adult or child? </label>
				<select name="<?php echo 'group-' . $group . '-guest-' . $guest . '-adult-or-child'; ?>">
					<option value="adult">Adult</option>
					<option value="child">Child</option>
				</select>
			</div>
			<div class="col-xxs-12 push-top">
				<label>Guest Notes or Allergies: </label>
				<textarea name="<?php echo 'group-' . $group . '-guest-' . $guest . '-notes'; ?>" rows="4" cols="50"></textarea>
				<a href="" data-guest="<?php echo $guest; ?>" class="push-top js-delete-single-guest delete"><i class="fas fa-times-circle"></i> Delete Guest</a>
			</div>



		</div>

		<?php
		return ob_get_clean();
	}

	public function guest_user_add_render( $guest )
	{
		ob_start();
		?>

		<div class="guest-list-item clearfix js-guest-<?php echo $guest; ?>">
			<div class="col-xxs-12">
				<h3 class="">Guest <?php echo $guest; ?></h3>

			</div>
			<div class="col-xxs-12 col-md-4 push-top">
				<label>Guest Name</label>
				<input type="text" name="<?php echo 'guest-' . $guest . '-name'; ?>" value="">
			</div>
			<div class="col-xxs-12 col-md-4 push-top">
				<label>Passport Number: </label>
				<input type="text" name="<?php echo 'guest-' . $guest . '-passport'; ?>" value="">
			</div>
			<div class="col-xxs-12 col-md-4 push-top">
				<label>Adult or child?</label>
				<select name="<?php echo 'guest-' . $guest . '-adult-or-child'; ?>">
					<option value="adult">Adult</option>
					<option value="child">Child</option>
				</select>
			</div>
			<div class="col-xxs-12 push-top">
				<label>Guest Notes or Allergies: </label>
				<textarea name="<?php echo 'guest-' . $guest . '-notes'; ?>" rows="4" cols="50"></textarea>
				<a href="" data-guest="<?php echo $guest; ?>" class="js-user-delete-single-guest delete"><i class="fas fa-times-circle"></i> Delete Guest</a>
			</div>



		</div>

		<?php
		return ob_get_clean();
	}

	public function process_email_confirmation( $itin_user, $itin_post, $itin_form_data, $itin_form )
	{

		$concierge = get_user_by( 'ID', 15 );
		$user = get_user_by( 'ID', $itin_user );

		$user_name = $user->display_name;
		$user_email = $user->user_email;

		$trip_start_date = $this->get_field( 'trip_start_date', $itin_post );
		$trip_end_date = $this->get_field( 'trip_end_date', $itin_post );

		$trip_start = strtotime( $trip_start_date );
		$trip_end = strtotime( $trip_end_date );

		$trip_range = array();
		for ( $current_date = $trip_start; $current_date <= $trip_end; $current_date += (86400) ) {
			$cd = date( 'F j, Y', $current_date );
			$trip_range[] = $cd;
		}

		$trip_details = "Activity Details: <br> ";
		$td = 0;
		foreach ( $trip_range as $tr ) {
			$activity_index = $td + 1;

			$trip_details .= "<b>" . $tr . ": </b> <br> ";
			if ( ! empty( $itin_form_data[$activity_index] ) ) {
				$act_i = 1;

				foreach ( $itin_form_data[$activity_index] as $act_day ) {
					$custom_activity_title_key = 'custom_activity_title-day-' . trim( $activity_index ) . '-activity-' . trim( $act_i );
					$standard_activity_title_key = 'activity_title-day-' . trim( $activity_index ) . '-activity-' . trim( $act_i );
					if ( isset( $act_day[$custom_activity_title_key] ) && $act_day[$custom_activity_title_key] !== '' ) {
						$trip_details .= "Custom Activity : " . $act_day['custom_activity_title-day-' . trim( $activity_index ) . '-activity-' . trim( $act_i )] . "<br>";
					} else {
						$act_id = $act_day[$standard_activity_title_key];
						$trip_details .= get_the_title( $act_id ) . "<br> ";
					}
					$act_i ++;
				}
			}
			$td ++;
		}


		$trip_date_info = get_edit_trip_date_time( $trip_start_date, $trip_end_date );

		$trip_date_edit = "<b>Date of Final Submission Deadline: </b> <br>";

		if ( $trip_date_info['editable'] ) {
			$edit_days = $trip_date_info['edit_days_left'];
			$edit_last_date = date( 'F j, Y', strtotime( '+' . $edit_days . ' days' ) );
			$trip_date_edit .= $edit_last_date . " <br>";
		} else {
			$trip_date_edit .= "Deadline has passed. <br>";
		}

		$email_data = array(
			'message_body' => $this->email_client_itinerary_submitted_message,
			'edit_last_date' => $edit_last_date,
		);
		$args = array(
			'type' => 'email-client-itinerary-submitted',
			'data' => $email_data,
			'to' => $user_email,
			'is_sent' => false,
		);
		$Email_Service = FXUP_USER_PORTAL()->email( $itin_post );
		$Email = $Email_Service->create( $args );
		$Email_Service->send( $Email );
	}

	public function send_concierge_update_email( $itin_user, $itin_post )
	{
		$user = get_user_by( 'ID', $itin_user );
		$user_name = $user->display_name;
		$user_email = $user->user_email;

		$email_data = array(
			'message_body' => $this->email_client_itinerary_updated_message,
			'itinerary_url' => get_the_permalink( $itin_post ),
		);
		$args = array(
			'type' => 'email-client-concierge-update-digest',
			'data' => $email_data,
			'to' => $user_email,
			'is_sent' => false,
		);
		$Email_Service = FXUP_USER_PORTAL()->email( $itin_post );
		if ( empty( $Email_Service->get_unsent( 'email-client-concierge-update-digest', $user_email ) ) ) {
			$Email = $Email_Service->create( $args );
		}
	}

	public function concierge_deadline_process()
	{

		$this->setup_template_data_concierge_deadline_check();
		extract( $this->template_data['email-concierge-deadline-check'] );
		ob_start();
		include $this->views['email-concierge-deadline-check']['path'];
		$markup = ob_get_contents();
		ob_end_clean();

		$to = $this->email_concierge_notifications_to . ',' . $this->email_on_site_staff_notifications_to;
		$subject = 'Villa Punto de Vista - Upcoming Itineraries';
		$headers = array( 'Content-Type: text/html; charset=UTF-8;' );
		wp_mail( $to, $subject, $markup, $headers );

		if ( count( $itineraries ) > 0 ) {
			$this->send_itinerary_summary_links( $itineraries, $deadline_check_interval, $email_concierge_single_itinerary_summary_link_path );
		}


//		// todo
//		$email_data = array(
//			'message_body' => $this->email_guest_travel_deadline_reminder_message,
//			'travel_url' => $Guest->getTravelLink(),
//		);
//		$args = array(
//			'type' => 'email-concierge-deadline-check',
//			'data' => $email_data,
//			'is_sent' => false,
//		);
//		$Email_Service = FXUP_USER_PORTAL()->email( $Itinerary );
//		$Email = $Email_Service->create( $args );
//		$Email_Service->send( $Email );
//		
	}

	private function send_itinerary_summary_links( $itineraries, $deadline_check_interval, $email_concierge_single_itinerary_summary_link_path )
	{
		$email_data = array(
			'itineraries' => $itineraries,
			'deadline_check_interval' => $deadline_check_interval,
			'email_concierge_single_itinerary_summary_link_path' => $email_concierge_single_itinerary_summary_link_path,
		);
		$args = array(
			'type' => 'email-concierge-summary-links',
			'subject' => 'Villa Punto de Vista - Upcoming Itinerary Summaries',
			'data' => $email_data,
			'is_sent' => false,
		);
		$Email_Service = FXUP_USER_PORTAL()->email( $Itinerary );
		$Email = $Email_Service->create( $args );
		$Email_Service->send( $Email );
	}

	public function new_account_login_reminder()
	{

		$Today = new \DateTime( 'today' );
		// Get minimum waiting period as configured in WP Admin
		// Subtract that interval from Today to calculate the cutoff
		$LatestCreationDate = clone $Today;
		// Only members created on this date or before are possibly eligible for notifications
		$LatestCreationDate->sub( $this->new_account_login_interval_after_account_creation_interval_object );

		// Get all Users who have a registration date BEFORE the cutoff AND have not logged in
		$wp_date_query_format = 'Y-m-d H:i:s';

		$args = array(
			'role' => 'subscriber',
			// User account was created long enough ago that it is outside the initial waiting window.
			'date_query' => array(
				array(
					'before' => $LatestCreationDate->format( $wp_date_query_format ),
					'inclusive' => true,
				),
			),
			// User never logged in
			'meta_query' => array(
				array(
					'key' => 'fxup_user_last_login_timestamp',
					'compare' => 'NOT EXISTS'
				),
			)
		);

		$user_query = new \WP_User_Query( $args );

		// Query users
		$users = $user_query->get_results();

		// Testing
		$test_user = get_user_by( 'ID', 114 );
		$users = is_array( $users ) ? $users : array( $test_user );

		if ( is_array( $users ) ) {

			// Filter out users to whom we have sent a notification before and they are still within the reminder window before we send another.
			$users = array_filter( $users, function ( $user ) use ( &$Today ) {
				$should_notify = false;
				$raw = get_user_meta( $user->ID, 'fxup_new_account_login_reminder_sent', true );
				if ( is_numeric( $raw ) ) {
					$last_sent = (new \DateTime())->setTimestamp( $raw );
					// As long as reminders are enabled, send a notification again if we've sent one before but have now passed the reminder wait period.
					if ( $this->new_account_login_reminder_active && $Today >= $last_sent->add( $this->new_account_login_reminder_in_days_interval_object ) ) {
						$should_notify = true;
					}
				} elseif ( empty( $raw ) ) {
					// If we've never sent before, send.
					$should_notify = true;
				}
				return $should_notify;
			} );

			// Testing
			if ( ! in_array( $test_user, $users ) ) {
				$users[] = $test_user;
			}

			// Foreach User
			foreach ( $users as $user ) {

				$email_data = array(
					'message_body' => $this->email_account_creation_reminder_message,
				);
				$args = array(
					'type' => 'email-account-creation-reminder',
					'data' => $email_data,
					'to' => $user->user_email,
					'is_sent' => false,
				);
				$Email_Service = FXUP_USER_PORTAL()->email( false );
				$Email = $Email_Service->create( $args );
				$Email_Service->send( $Email );

				update_user_meta( $user->ID, 'fxup_new_account_login_reminder_sent', (new \DateTime( 'now' ))->getTimestamp() );
			}
		}
	}

	public function setup_template_data_concierge_deadline_check()
	{

		$this->template_data['email-concierge-deadline-check'] = array();
		$this->template_data['email-concierge-deadline-check']['itineraries'] = array();
		$this->template_data['email-concierge-deadline-check']['email_concierge_deadline_check_single_itinerary_path'] = $this->views['email-concierge-deadline-check-single-itinerary']['path'];
		$this->template_data['email-concierge-deadline-check']['email_concierge_single_itinerary_summary_link_path'] = $this->views['email-concierge-single-itinerary-summary-link']['path'];

		// Assign the deadline check interval to be passed to templates
		$this->template_data['email-concierge-deadline-check']['deadline_check_interval'] = $this->concierge_deadline_check_interval_in_days;

		$Today = new \DateTime( 'today' );

		// Soonest is a calculation of the most recent trip start date that should be considered within the window for sending notifications (if the trip starts today, should we send a notification?).
		$Soonest = clone $Today;

		// Furthest is a calculation of the most future trip start date that should be considered within the window for sending notifications.
		$Furthest = clone $Soonest;
		$Furthest->add( $this->concierge_deadline_check_interval_in_days_interval_object ); // Trip that starts in X number of days (as specified in WP Admin)

		$ValidDates = self::generate_datetime_range( $Soonest, $Furthest );

		$acf_formatted_dates = array_map( function ( $DateTimeObject ) {
			return $this->models['itinerary']['name']::convertToACFDatePicker( $DateTimeObject );
		}, $ValidDates ); // Converts to 'Ymd'

		$args = array(
			'post_type' => 'itinerary',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'meta_query' => array(
				array(
					'key' => 'trip_start_date', // Meta key for trip start date
					'value' => $acf_formatted_dates,
					'compare' => 'IN'
				)
			)
		);

		$query = new \WP_Query( $args );
		$posts = $query->posts;
		$posts_ids = wp_list_pluck( $posts, 'ID' );

		////////////////////////////
		foreach ( $posts_ids as $post_id ) {
			$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $post_id );
			$Itinerary->setSummaryExpiration( strtotime( "+10days" ) );
			$this->template_data['email-concierge-deadline-check']['itineraries'][] = $Itinerary;
		}
	}

	public static function generate_datetime_range( $start_date_time_object, $end_date_time_object, $interval = null )
	{
		if ( ! $interval instanceof \DateInterval ) {
			$interval = new \DateInterval( 'P1D' ); // 1 day
		}

		$start_date_clone = clone $start_date_time_object;
		$end_date_clone = clone $end_date_time_object; // Not currently necessary, but just in case this is expanded.
		$range_of_date_time_objects = array();

		// Catch the start date up to the end date, adding a new DateTime to the range for however many days it takes.
		while ( $start_date_clone <= $end_date_clone ) {
			$range_of_date_time_objects[] = clone $start_date_clone; // Clone so the object is not mutated later
			$start_date_clone->add( $interval ); // Increment
		}
		return $range_of_date_time_objects;
	}

	public static function filter_if_exists( $array, $key, $values_array )
	{

		$filtered = array();

		foreach ( $array as $item ) {
			if ( is_array( $item ) ) {
				if ( array_key_exists( $key, $array ) ) {
					// If key is set, only add to the final array if value matches one provided.
					if ( in_array( $array[$key], $values_array ) ) {
						$filtered[] = $item;
					}
				} else {
					$filtered[] = $item;
				}
			} elseif ( is_object( $item ) ) {
				if ( property_exists( $item, $key ) ) {
					if ( in_array( $item->$key, $values_array ) ) {
						$filtered[] = $item;
					}
				} else {
					$filtered[] = $item;
				}
			} else {
				$filtered[] = $item;
			}
		}

		return $filtered;
	}

	public function itinerary_deadline_process()
	{

		$Today = new \DateTime( 'today' );

		// Soonest is a calculation of the most recent trip start date that should be considered within the window for sending notifications (if the trip starts today, should we send a notification?).
		$Soonest = clone $Today;
		$Soonest->add( $this->edit_day_cutoff_interval_in_days_interval_object ); // Trip that is on its last day to be editable
		// Furthest is a calculation of the most future trip start date that should be considered within the window for sending notifications.
		$Furthest = clone $Soonest;
		$Furthest->add( $this->client_upcoming_itinerary_deadline_notification_interval_in_days_interval_object ); // Trip that is still editable, but as of today is within the warning zone specified by client in WP Admin.

		$ValidDates = self::generate_datetime_range( $Soonest, $Furthest );

		// Include the Itinerary model to use one of its static methods (will also be used below to create instances for templates)
		$acf_formatted_dates = array_map( function ( $DateTimeObject ) {
			return $this->models['itinerary']['name']::convertToACFDatePicker( $DateTimeObject );
		}, $ValidDates ); // Converts to 'Ymd'

		$args = array(
			'post_type' => 'itinerary',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'meta_query' => array(
				array(
					'key' => 'trip_start_date', // Meta key for trip start date
					'value' => $acf_formatted_dates,
					'compare' => 'IN'
				)
			)
		);

		$itinerary_query = new \WP_Query( $args );

		$itinerary_posts = $itinerary_query->posts;

		if ( ! empty( $itinerary_posts ) ) {

			$Itineraries = array_map(
				function ( $post ) {
					// Use the post id to construct an Itinerary
					$itinerary_post_id = $post->ID;

					$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itinerary_post_id );

					return $Itinerary;
				},
				$itinerary_posts
			);

			$Itineraries = array_filter( $Itineraries, function ( $Itinerary ) {
				// Notification date has to be false - meaning, never sent.
				return ($Itinerary->getClientItineraryDeadlineNotificationSent() === false);
			} );

			foreach ( $Itineraries as $Itinerary ) {
				$email_data = array(
					'message_body' => $this->email_client_itinerary_deadline_reminder_message,
					'edit_days_left' => $Itinerary->getEditDaysLeft(),
					'itinerary_url' => $Itinerary->getPermalink(),
					'guest_list_url' => $Itinerary->getGuestListLink(),
					'room_arrangements_url' => $Itinerary->getRoomArrangementsLink(),
				);
				$args = array(
					'type' => 'email-client-itinerary-deadline-reminder',
					'data' => $email_data,
					'to' => $Itinerary->getUserEmail(),
					'is_sent' => false,
				);
				$Email_Service = FXUP_USER_PORTAL()->email( $Itinerary );
				$Email = $Email_Service->create( $args );
				$Email_Service->send( $Email );

				$Itinerary->setClientItineraryDeadlineNotificationSent( new \DateTime( 'now' ) );
			}
		}

		exit;
	}

	public function concierge_digest_report()
	{
		$Logger = new Itinerary_Change_Logger();
		$from = new \DateTimeImmutable( 'yesterday 00:00:00' );
		$to = new \DateTimeImmutable( 'today 00:00:00' );
		$rows = $Logger->fetch_digest( $from, $to );
		$digest_html = $Logger->build_digest_html( $rows );

		$subject = 'Villa Punto de Vista Daily Digest';
		$headers = [ 'Content-Type: text/html; charset=UTF-8;' ];
		$to      = $this->email_concierge_notifications_to;
		
		$sent = wp_mail( $to, $subject, $digest_html, $headers );

		if ( $sent ) {
			// Clear logs after a successful send
			$Logger->clear_all();
		}

		return $sent;

//		$posts_ids = $this->setup_template_data_concierge_digest_report();
//		extract( $this->template_data['email-concierge-digest-report'] );
//		ob_start();
//		include $this->views['email-concierge-digest-report']['path'];
//		$markup = ob_get_contents();
//		ob_end_clean();
//
//		if ( ! empty( $posts_ids ) ) {
//			foreach ( $posts_ids as $pid ) {
//				$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $pid );
//				$this->clear_trip_day_previous_activities( $Itinerary->getTripDayCount(), $pid );
//			}
//		}
//
//		$to = $this->email_concierge_notifications_to; // 'alex@webfx.com, sandi@webfx.com, pdvconcierge@gmail.com';
//
//		$subject = 'Villa Punto de Vista Daily Digest';
//		$headers = array( 'Content-Type: text/html; charset=UTF-8;' );
//		return wp_mail( $to, $subject, $markup, $headers );
	}

	public function setup_template_data_concierge_digest_report()
	{
		$this->template_data['email-concierge-digest-report'] = array();
		$this->template_data['email-concierge-digest-report']['itineraries'] = array();
		$this->template_data['email-concierge-digest-report']['email_concierge_digest_report_single_itinerary_path'] = $this->views['email-concierge-digest-report-single-itinerary']['path'];

		$args = array(
			'post_type' => 'itinerary',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'date_query' => array(
				'relation' => 'OR',
				array(
					'column' => 'post_date',
					'after' => '-1 days'
				),
				array(
					'column' => 'post_modified',
					'after' => '-1 days'
				)
			)
		);

		$query = new \WP_Query( $args );
		$posts = $query->posts;
		$posts_ids = wp_list_pluck( $posts, 'ID' );

		////////////////////////////
		if ( ! empty( $posts_ids ) ) {
			foreach ( $posts_ids as $post_id ) {
				$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $post_id );
				$this->template_data['email-concierge-digest-report']['itineraries'][] = $Itinerary; // Push onto template data
			}
		}
		
		return $posts_ids;
	}

	public function reorder_activities_by_time( $value, $post_id, $field )
	{
		$order = array();

		if ( empty( $value ) ) {
			return $value;
		}

		foreach ( $value as $i => $row ) {
			$order[$i] = strtotime( $row['field_5ec2ac6ab848c'] );
		}

		array_multisort( $order, SORT_ASC, $value );

		return $value;
	}

	public function guest_travel_deadline_process()
	{

		$Today = new \DateTime( 'today' );
		// Soonest is a calculation of the most recent trip start date that should be considered within the window for sending notifications (if the trip starts today, should we send a notification?).
		$Soonest = clone $Today;

		// Furthest is a calculation of the most future trip start date that should be considered within the window for sending notifications.
		$Furthest = clone $Soonest;
		$Furthest->add( $this->guest_travel_upcoming_itinerary_deadline_notification_interval_in_days_interval_object ); // Trip that is still upcoming, but as of today is in the warning zone for sending guest notifications.

		$ValidDates = self::generate_datetime_range( $Soonest, $Furthest );

		// Include the Itinerary model to use one of its static methods (will also be used below to create instances for templates)
		$acf_formatted_dates = array_map( function ( $DateTimeObject ) {
			return $this->models['itinerary']['name']::convertToACFDatePicker( $DateTimeObject );
		}, $ValidDates ); // Converts to 'Ymd'

		$args = array(
			'post_type' => 'itinerary',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => 'trip_start_date', // Meta key for trip start date
					'value' => $acf_formatted_dates,
					'compare' => 'IN'
				)
			)
		);

		$itinerary_query = new \WP_Query( $args );
		$itinerary_posts = $itinerary_query->posts;
		if ( ! empty( $itinerary_posts ) ) {
			$Itineraries = array_map(
				function ( $post ) {
					// Use the post id to construct an Itinerary
					$itinerary_post_id = $post->ID;
					$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itinerary_post_id );

					return $Itinerary;
				},
				$itinerary_posts
			);

			foreach ( $Itineraries as $Itinerary ) {
				$Guests = $Itinerary->getGuests();
				$Guests = array_filter( $Guests, function ( $Guest ) use ( &$Today ) {
					$should_notify = false;
					$last_sent = $Guest->getGuestTravelDeadlineNotificationSent();
					// If arrangements are not finalized...
					if ( ! $Guest->isTravelFinalized() ) {
						// Notify the guest if they have never been notified before OR if X number of days since their last notification
						if ( $last_sent instanceof \DateTime ) {
							// As long as reminders are active, if today is after/equal last sent + the reminder interval, send because the reminder is due/overdue.
							if ( $this->guest_travel_upcoming_itinerary_reminder_active && $Today >= $last_sent->add( $this->guest_travel_upcoming_itinerary_reminder_in_days_interval_object ) ) {
								$should_notify = true;
							}
						} elseif ( false === $last_sent ) {
							// If notification was never sent, send it now.
							$should_notify = true;
						}
					}
					return $should_notify;
				} );

				foreach ( $Guests as $Guest ) {
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
					$Email_Service->send( $Email );

					$Guest->setGuestTravelDeadlineNotificationSent( new \DateTime( 'now' ) );
				}
			}
		}
		exit;
	}

	public function view_single_itinerary()
	{
		$this->setup_template_data_single_itinerary();
		extract( $this->template_data['single-itinerary'] );
		ob_start();
		include $this->views['single-itinerary']['path'];
		$markup = ob_get_contents();
		ob_end_clean();
		return $markup;
	}

	public function setup_template_data_single_itinerary()
	{

		$this->template_data['single-itinerary'] = array();

		$current_post_id = get_the_id(); // Should be the Itinerary ID


		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $current_post_id );

		// Redirect if trying to access someone else's itinerary
		if ( $this->user_id !== $Itinerary->getUserID() && ! $this->is_concierge ) {
			wp_redirect( '/login' );
			exit;
		}

		$this->template_data['single-itinerary']['Itinerary'] = $Itinerary;

		$editable = ($Itinerary->isEditable() || $this->is_concierge); // Will lock for regular users, but stay editable for concierge.

		$this->template_data['single-itinerary']['editable'] = $editable;
		$this->template_data['single-itinerary']['user_role'] = $this->user_role;
		$this->template_data['single-itinerary']['is_concierge'] = $this->is_concierge; // True/False for concierge role
		$this->template_data['single-itinerary']['user_display_name'] = $this->user_display_name;
		$this->template_data['single-itinerary']['ui_navigation_path'] = $this->views['ui-navigation']['path'];
		$this->template_data['single-itinerary']['itinerary_check_in_path'] = $this->views['itinerary-check-in']['path'];
		$this->template_data['single-itinerary']['itinerary_check_out_path'] = $this->views['itinerary-check-out']['path'];
		$this->template_data['single-itinerary']['itinerary_trip_day_path'] = $this->views['itinerary-trip-day']['path'];
		$this->template_data['single-itinerary']['trip_day_activity_path'] = $this->views['trip-day-activity']['path'];
		$this->template_data['single-itinerary']['itinerary_event_type_flyout_path'] = $this->views['itinerary-event-type-flyout']['path'];

		// Activity booking time options
		$this->template_data['single-itinerary']['activity_booking_time_options'] = $this->activity_booking_time_options;

		// Early booking warning message
		$this->template_data['single-itinerary']['activity_approval_warning_message'] = $this->activity_approval_warning_message;
		$this->template_data['single-itinerary']['activity_approval_confirmed_message'] = $this->activity_approval_confirmed_message;

		// Tooltips
		$this->template_data['single-itinerary']['activity_guests_attending_tooltip'] = $this->activity_guests_attending_tooltip;
		$this->template_data['single-itinerary']['activity_child_guests_tooltip'] = $this->activity_child_guests_tooltip;
		$this->template_data['single-itinerary']['activity_booked_tooltip'] = $this->activity_booked_tooltip;
		$this->template_data['single-itinerary']['activity_prices_tooltip'] = $this->activity_prices_tooltip;
		$this->template_data['single-itinerary']['booking_confirmation_tooltip'] = $this->booking_confirmation_tooltip;

		// Placeholder
		$this->template_data['single-itinerary']['activity_child_guests_names_example_placeholder'] = $this->activity_child_guests_names_example_placeholder;

		// WYSIWYG
		$this->template_data['single-itinerary']['above_itinerary_form_wysiwyg'] = $this->above_itinerary_form_wysiwyg;

		// Day Count
		if ( ! empty( $Itinerary->getTripDays() ) ) {
			$day_count = count( $Itinerary->getTripDays() );
		} else {
			$day_count = 0;
		}
		$this->template_data['single-itinerary']['day_count'] = $day_count;

		// Guests
		$guest_args = array(
			'post_type' => 'guest',
			'posts_per_page' => -1,
			'orderby' => array(
				'firstname_clause' => 'asc',
				'lastname_clause' => 'asc',
			),
			'meta_query' => array(
				'id_clause' => array(
					'key' => 'itinerary_id',
					'value' => $Itinerary->getPostID(),
				),
				'firstname_clause' => array(
					'key' => 'guest_first_name',
					'compare' => 'EXISTS',
				),
				'lastname_clause' => array(
					'key' => 'guest_last_name',
					'compare' => 'EXISTS',
				),
			)
		);
		$guest_query = new \WP_Query( $guest_args );
		$guest_posts = $guest_query->posts;

		$this->template_data['single-itinerary']['guest_posts'] = $guest_posts;

		/** EVENT FLYOUTS */
		$this->template_data['single-itinerary']['itinerary_event_type_flyout_path'];
		$event_types = array();

		/** Activities */
		$activities = array();
		// Flyout name
		$activities['flyout_name'] = 'Activity';
		// Flyout heading
		$activities['flyout_heading'] = 'Activities';
		// Flyout JavaScript target part
		$activities['flyout_id'] = 'activities';
		// Posts
		$activity_args = array(
			'post_type' => 'activity'
			, 'posts_per_page' => -1
			, 'orderby' => 'ID'
			, 'order' => 'ASC'
		);
		$activity_query = new \WP_Query( $activity_args );
		$activity_posts = $activity_query->posts;
		$activities['posts'] = $activity_posts;
		// Categories
		$activity_categories = get_terms( array( 'taxonomy' => 'activity_category' ) );
		$activities['categories'] = $activity_categories;
		// Taxonomy
		$activity_taxonomy = 'activity_category';
		$activities['taxonomy'] = $activity_taxonomy;

		$event_types['activity'] = $activities;

		/** Services */
		$services = array();
		// Flyout name
		$services['flyout_name'] = 'Service';
		// Flyout heading
		$services['flyout_heading'] = 'Services';
		// Flyout JavaScript target part
		$services['flyout_id'] = 'services';
		// Posts
		$service_args = array(
			'post_type' => 'service'
			, 'posts_per_page' => -1
			, 'orderby' => 'ID'
			, 'order' => 'ASC'
		);
		$service_query = new \WP_Query( $service_args );
		$service_posts = $service_query->posts;
		$services['posts'] = $service_posts;
		// Categories
		$service_categories = get_terms( array( 'taxonomy' => 'service_category' ) );
		$services['categories'] = $service_categories;
		// Taxonomy
		$service_taxonomy = 'service_category';
		$services['taxonomy'] = $service_taxonomy;

		$event_types['service'] = $services;

		/** weddings */
		$weddings = array();
		// Flyout name
		$weddings['flyout_name'] = 'Venue';
		// Flyout heading
		$weddings['flyout_heading'] = 'Wedding Venues';
		// Flyout JavaScript target part
		$weddings['flyout_id'] = 'weddings';
		// Posts
		$wedding_args = array(
			'post_type' => 'wedding'
			, 'posts_per_page' => -1
			, 'orderby' => 'ID'
			, 'order' => 'ASC'
		);
		$wedding_query = new \WP_Query( $wedding_args );
		$wedding_posts = $wedding_query->posts;
		$weddings['posts'] = $wedding_posts;
		// Categories
		$wedding_categories = get_terms( array( 'taxonomy' => 'wedding_category' ) );
		$weddings['categories'] = $wedding_categories;
		// Taxonomy
		$wedding_taxonomy = 'wedding_category';
		$weddings['taxonomy'] = $wedding_taxonomy;

		$event_types['wedding'] = $weddings;

		// Add to template data
		$this->template_data['single-itinerary']['event_types'] = $event_types;
	}

	public function view_summary()
	{
		$this->setup_template_data_view_summary();
		extract( $this->template_data['summary'] );

		if ( $is_summary_available ) {
			ob_start();
			include $this->views['summary']['path'];
			$markup = ob_get_contents();
			ob_end_clean();
			return $markup;
		} else {
			return 'Summary unavailable';
		}
	}

	public function setup_template_data_view_summary()
	{
		$this->template_data['summary'] = array();
		$requested_itinerary_token = $_GET['itin'] ? $_GET['itin'] : false; // Unique token that is set for Itinerary posts at time of insert
		$requested_itinerary_token = sanitize_text_field( $requested_itinerary_token );

		$args = array(
			'post_type' => 'itinerary',
			'post_status' => 'publish',
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => 'share_link_token',
					'value' => $requested_itinerary_token,
					'compare' => '='
				)
			)
		);

		$query = new \WP_Query( $args );
		$posts = $query->posts;
		$post_object = ( ! empty( $query->posts[0] )) ? $query->posts[0] : false;
		if ( false === $post_object ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			get_template_part( 404 );
			exit();
		}

		$post_id = $post_object->ID;
		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $post_id );
		$this->template_data['summary']['Itinerary'] = $Itinerary;

		$this->template_data['summary']['is_summary'] = true;
		$this->template_data['summary']['is_summary_available'] = $Itinerary->isSummaryAvailable();
		$this->template_data['summary']['is_concierge'] = $this->is_concierge; // True/False for concierge role
		$this->template_data['summary']['user_role'] = $this->user_role; // concierge, subscriber, unregistered
		$this->template_data['summary']['ui_navigation_path'] = $this->views['ui-navigation']['path'];
		$this->template_data['summary']['share_print_trip_day_path'] = $this->views['share-print-trip-day']['path'];
		$this->template_data['summary']['share_print_activity_path'] = $this->views['share-print-activity']['path'];
		$this->template_data['summary']['summary_rooms_path'] = $this->views['summary-rooms']['path'];
		$this->template_data['summary']['summary_transportation_path'] = $this->views['summary-transportation']['path'];
		$this->template_data['summary']['itinerary_check_in_path'] = $this->views['itinerary-check-in']['path'];
		$this->template_data['summary']['itinerary_check_out_path'] = $this->views['itinerary-check-out']['path'];
	}

	public function view_transportation_summary()
	{
		$this->setup_template_data_view_transportation_summary();
		extract( $this->template_data['transportation_summary'] );

		ob_start();
		include $this->views['transportation_summary']['path'];
		$markup = ob_get_contents();
		ob_end_clean();
		return $markup;
	}

	public function setup_template_data_view_transportation_summary()
	{
		$this->template_data['transportation_summary'] = array();
		$requested_itinerary_token = $_GET['itin'] ? $_GET['itin'] : false; // Unique token that is set for Itinerary posts at time of insert
		$requested_itinerary_token = sanitize_text_field( $requested_itinerary_token );

		$args = array(
			'post_type' => 'itinerary',
			'post_status' => 'publish',
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => 'share_link_token',
					'value' => $requested_itinerary_token,
					'compare' => '='
				)
			)
		);

		$query = new \WP_Query( $args );
		$posts = $query->posts;
		$post_object = ( ! empty( $query->posts[0] )) ? $query->posts[0] : false;
		if ( false === $post_object ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			get_template_part( 404 );
			exit();
		}

		$post_id = $post_object->ID;
		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $post_id );
		$this->template_data['transportation_summary']['Itinerary'] = $Itinerary;

		$this->template_data['transportation_summary']['is_summary'] = true;
		$this->template_data['transportation_summary']['is_concierge'] = $this->is_concierge; // True/False for concierge role
		$this->template_data['transportation_summary']['user_role'] = $this->user_role; // concierge, subscriber, unregistered
		$this->template_data['transportation_summary']['ui_navigation_path'] = $this->views['ui-navigation']['path'];
		$this->template_data['transportation_summary']['summary_transportation_path'] = $this->views['summary-transportation']['path'];
	}

	public function view_grocery_search()
	{
		ob_start();
		include $this->views['grocery-search']['path'];
		$markup = ob_get_contents();
		ob_end_clean();
		return $markup;
	}

	public function set_summary_expiration()
	{
		$itin_id = filter_input( INPUT_POST, 'itin' );

		// Exit early if the form is empty
		if ( ! isset( $itin_id ) || ! $itin_id ) {
			echo 'Invalid';
			die();
		}

		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itin_id );
		$summary_expiration = $Itinerary->setSummaryExpiration( strtotime( "+10days" ) );

		wp_send_json_success( array( 'expiration' => $summary_expiration ) );
	}

	public function view_share_print_itinerary()
	{
		$this->setup_template_data_share_print_itinerary();
		extract( $this->template_data['share-print-itinerary'] );
		ob_start();
		include $this->views['share-print-itinerary']['path'];
		$markup = ob_get_contents();
		ob_end_clean();
		return $markup;
	}

	public function setup_template_data_share_print_itinerary()
	{

		$this->template_data['share-print-itinerary'] = array();

		$requested_itinerary_token = $_GET['itin'] ? $_GET['itin'] : false; // Unique token that is set for Itinerary posts at time of insert

		$requested_itinerary_token = sanitize_text_field( $requested_itinerary_token );

		$args = array(
			'post_type' => 'itinerary',
			'post_status' => 'publish',
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => 'share_link_token',
					'value' => $requested_itinerary_token,
					'compare' => '='
				)
			)
		);

		$query = new \WP_Query( $args );
		$posts = $query->posts;
		$post_object = ( ! empty( $query->posts[0] )) ? $query->posts[0] : false;
		if ( false === $post_object ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			get_template_part( 404 );
			exit();
		}

		$post_id = $post_object->ID;

		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $post_id );

		$this->template_data['share-print-itinerary']['Itinerary'] = $Itinerary;

		$this->template_data['share-print-itinerary']['is_concierge'] = $this->is_concierge; // True/False for concierge role
		$this->template_data['share-print-itinerary']['user_role'] = $this->user_role; // concierge, subscriber, unregistered
		$this->template_data['share-print-itinerary']['is_share'] = true;
		$this->template_data['share-print-itinerary']['ui_navigation_path'] = $this->views['ui-navigation']['path'];
		$this->template_data['share-print-itinerary']['share_print_trip_day_path'] = $this->views['share-print-trip-day']['path'];
		$this->template_data['share-print-itinerary']['share_print_activity_path'] = $this->views['share-print-activity']['path'];
		$this->template_data['share-print-itinerary']['itinerary_check_in_path'] = $this->views['itinerary-check-in']['path'];
		$this->template_data['share-print-itinerary']['itinerary_check_out_path'] = $this->views['itinerary-check-out']['path'];
	}

	public function view_dashboard()
	{
		$this->setup_template_data_dashboard();
		extract( $this->template_data['dashboard'] );
		include $this->views['dashboard']['path'];
		$markup = ob_get_contents();
		ob_end_clean();
		return $markup;
	}

	public function setup_template_data_dashboard()
	{

		$this->template_data['dashboard'] = array();
		$this->template_data['dashboard']['is_concierge'] = $this->is_concierge;
		$this->template_data['dashboard']['ui_navigation_path'] = $this->views['ui-navigation']['path'];

		$this->template_data['dashboard']['dashboard_itinerary_include_path'] = $this->views['dashboard-itinerary']['path']; // Include path for partial

		$this->template_data['dashboard']['dashboard_user_display_name'] = $this->user_display_name;
		$this->template_data['dashboard']['new_itinerary_form_link'] = $this->new_itinerary_form_link;
		$this->template_data['dashboard']['dashboard_tutorial_video_link'] = $this->get_field( 'dashboard_tutorial_video' );

		$this->template_data['dashboard']['itineraries'] = array();

		$args = array(
			'post_type' => 'itinerary',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => 'itinerary_user',
					'value' => $this->user_id,
					'compare' => 'LIKE',
				)
			),
		);

		$query = new \WP_Query( $args );

		$itinerary_posts = $query->posts;

		$itinerary_posts_ids = wp_list_pluck( $itinerary_posts, 'ID' );

		$itineraries = array(); // unsorted
		////////////////////////////
		foreach ( $itinerary_posts_ids as $itinerary_post_id ) {

			$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itinerary_post_id );

			$itineraries[] = $Itinerary;
		}
		// Sort so most recent/future start dates are first
		usort( $itineraries, array( $this, 'sort_itineraries_by_trip_start_date_descending' ) );
		// Only pass the most recent/future start date itinerary to the dashboard view
		if ( ! empty( $itineraries[0] ) ) {
			$this->template_data['dashboard']['itineraries'][] = $itineraries[0];
		}
	}

	public function sort_itineraries_by_trip_start_date_descending( $ItineraryA, $ItineraryB )
	{
		$a = $ItineraryA->getTripStartDate();
		$b = $ItineraryB->getTripStartDate();
		// Most recent/future start dates will be first
		return ($a > $b) ? -1 : (($b > $a) ? 1 : 0);
	}

	public function view_itinerary_form()
	{
		$this->setup_template_data_itinerary_form();
		extract( $this->template_data['form-itinerary'] );
		ob_start();
		include $this->views['form-itinerary']['path'];
		$markup = ob_get_contents();
		ob_end_clean();
		return $markup;
	}

	public function setup_template_data_itinerary_form()
	{

		$this->template_data['form-itinerary'] = array();
		$this->template_data['form-itinerary']['villas'] = array();
		$this->template_data['form-itinerary']['users'] = array();

		// Get Villas
		$Villa = $this->models['villa']['name'];
		$villas_post_objects = $Villa::getAllVillasPostObjects();
		if ( ! empty( $villas_post_objects ) ) {
			$this->template_data['form-itinerary']['villas'] = wp_list_pluck( $villas_post_objects, 'ID', 'post_title' );
		}
		// Get users of type subscriber
		$user_fields = array( 'ID', 'display_name', 'user_email' );
		if ( 'concierge' === $this->user_role ) {
			$args = array(
				'role' => 'subscriber',
				'order_by' => 'display_name',
				'order' => 'ASC',
				'fields' => $user_fields,
			);
			$user_query = new \WP_User_Query( $args );
			$users = $user_query->get_results();
			if ( ! empty( $users ) ) {
				$this->template_data['form-itinerary']['users'] = $users; // wp_list_pluck($users, 'display_name', 'ID');
			}
		} else {
			$user = get_user_by( 'id', $this->user_id );
			if ( ( ! empty( $user->ID )) && ( ! empty( $user->display_name )) ) {
				$this->template_data['form-itinerary']['users'][$user->display_name] = $user->ID;
			}
		}
	}

	public function user_create_new_itinerary_callback()
	{

		$response = array();

		// Parse results into data variable array
		parse_str( $_POST['form_data'], $data );
		// Validate
		$errors = $this->user_create_new_itinerary_validate( $data );
		if ( ! empty( $errors ) ) {
			$response['errors'] = $errors;
			echo json_encode( $response );
			exit;
		}

		// Sanitize
		$data = $this->user_create_new_itinerary_sanitize( $data );

		// Instantiate
		$Itinerary = $this->create_new_itinerary_object( $data );
		if ( ! $Itinerary ) {
			$response['errors']['general'] = 'There was a problem creating this itinerary.';
			echo json_encode( $response );
			exit;
		}

		// Respond
		$response['message'] = 'Great work!';

		// Redirect
		if ( 'concierge' === $this->user_role ) {
			$response['redirect'] = 'http://login.villapuntodevista.com/concierge/'; // This could become an ACF option
		} else {
			$response['redirect'] = 'http://login.villapuntodevista.com/dashboard/'; // This could become an ACF option
		}

		echo json_encode( $response ); // Response
		exit;
	}

	public function user_create_new_itinerary_validate( $data )
	{

		$required_fields_validation_methods = array(
			'user_id' => 'validateUserID',
			'group_name' => 'validateGroupName',
			'trip_start_date' => 'validateTripStartDate',
			'trip_end_date' => 'validateTripEndDate',
		);

		// Validate data
		$errors = $this->validateFormData( $data, $required_fields_validation_methods );
		return $errors;
	}

	public function user_create_new_itinerary_sanitize( $data )
	{

		// Sanitize data (return new array where data is clean)
		$fields_sanitization_methods = array(
			'user_id' => 'sanitizeUserID',
			'group_name' => 'sanitizeGroupName',
			'trip_start_date' => 'sanitizeTripStartDate',
			'trip_end_date' => 'sanitizeTripEndDate',
		);

		$data = $this->sanitizeFormData( $data, $fields_sanitization_methods );
		return $data;
	}

	// Essentially a wrapper for FXUP_User_Portal\Models\Itinerary::create
	public function create_new_itinerary_object( $data )
	{

		$Itinerary = false;
		// Requires:
		/*
		  villa_id
		  group_name
		  trip_start_date
		  trip_end_date
		  user_id
		 */
		// Optional:
		/*
		  account_id
		 */

		$approval_status = 'Pending Client Submission'; // Default approval status

		$villa_id = $data['villa_id'];
		try {
			$Villa = new \FXUP_User_Portal\Models\Villa( $villa_id );
		} catch ( Exception $e ) {
			$Itinerary = false;
			return $Itinerary;
		}


		// Format itinerary title
		$group_name = $data['group_name'];
		$villa_post_title = $Villa->getPostObject()->post_title;
		$itinerary_post_title = $this->generateItineraryTitle( [ $group_name, $villa_post_title ] );

		$create_data = array();
		$create_data['title'] = $itinerary_post_title;
		// Format dates and create range for insert
		$create_data['trip_start_date'] = ($data['trip_start_date'] instanceof \DateTime) ? $data['trip_start_date'] : $this->parseDateStringToDateTimeObject( $data['trip_start_date'] ); // Returns DateTime object
		$create_data['trip_end_date'] = ($data['trip_end_date'] instanceof \DateTime) ? $data['trip_end_date'] : $this->parseDateStringToDateTimeObject( $data['trip_end_date'] );
		$create_data['approval_status'] = $approval_status;
		$create_data['user_id'] = $data['user_id'];
		$create_data['villa'] = $Villa;
		$create_data['group_name'] = $group_name;
		if ( isset( $data['account_id'] ) ) {
			$create_data['account_id'] = $data['account_id'];
		} // Optional

		try {
			// Construct itinerary

			$Itinerary = \FXUP_User_Portal\Models\Itinerary::create( $itinerary_post_title, $create_data );
		} catch ( Exception $e ) {
			$Itinerary = false;
			return $Itinerary;
		}

		return $Itinerary;
	}

	public static function parseDateStringToDateTimeObject( $mm_slash_dd_slash_yyyy )
	{
		$DateTimeObject = \DateTime::createFromFormat( 'm/d/Y', $mm_slash_dd_slash_yyyy );
		return $DateTimeObject;
	}

	public static function generateItineraryTitle( $array )
	{
		$hyphen_separated = implode( ' - ', $array );
		return $hyphen_separated;
	}

	public function validateFormData( $data, $required_fields_validation_methods = array() )
	{
		$errors = array();
		// Iterate through required fields
		foreach ( $required_fields_validation_methods as $field => $method ) {
			if ( empty( $data[$field] ) ) {
				$errors[$field] = 'This field is required';
			} else {
				// Returns true or an error string
				$valid_or_error = $this->$method( $data[$field] );
				if ( ! (true === $valid_or_error) ) {
					$errors[$field] = $valid_or_error;
				}
			}
		}
		return $errors;
	}

	public function validateUserID( $user_id )
	{
		$valid_or_error = true;
		$user = get_user_by( 'id', $user_id );
		if ( false === $user ) {
			$valid_or_error = 'Please choose a valid user';
		}
		return $valid_or_error;
	}

	public function validateGroupName( $group_name )
	{
		$valid_or_error = true;
		// No additional validation currently required for the group name
		return $valid_or_error;
	}

	public function validateTripStartDate( $trip_start_date )
	{
		$valid_or_error = true;
		$parsed = $this->parseDateStringToDateTimeObject( $trip_start_date );
		if ( false === $parsed ) {
			$valid_or_error = 'Please enter a valid start date';
		}
		return $valid_or_error;
	}

	public function validateTripEndDate( $trip_end_date )
	{
		$valid_or_error = true;
		$parsed = $this->parseDateStringToDateTimeObject( $trip_end_date );
		if ( false === $parsed ) {
			$valid_or_error = 'Please enter a valid end date';
		}
		return $valid_or_error;
	}

	public function sanitizeFormData( $data, $sanitization_methods = array() )
	{
		// Iterate through sanitization methods and transform data accordingly
		foreach ( $sanitization_methods as $field => $method ) {
			if ( isset( $data[$field] ) ) {
				// If a sanitization method for an item in data has not been set, the field will still be returned
				$data[$field] = $this->$method( $data[$field] );
			}
		}
		return $data;
	}

	public function sanitizeUserID( $input )
	{
		return filter_var( $input, FILTER_SANITIZE_NUMBER_INT );
	}

	public function sanitizeGroupName( $input )
	{
		return filter_var( $input, FILTER_SANITIZE_STRING );
	}

	public function sanitizeTripStartDate( $input )
	{
		return filter_var( $input, FILTER_SANITIZE_STRING );
	}

	public function sanitizeTripEndDate( $input )
	{
		return filter_var( $input, FILTER_SANITIZE_STRING );
	}

	public function renderPortalNavigation( $itinerary_post_id )
	{
		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itinerary_post_id );

		ob_start();
		?>
		<?php
		foreach ( $this->jump_to_links as $link ) {
			$slug = '';
			$query_params = '';
			if ( '/itinerary/' === $link['fxup_jump_to_links_hyperlink'] ) {
				$slug = '/itinerary/' . $Itinerary->getPostObject()->post_name . '/';
			} else {
				$slug = $link['fxup_jump_to_links_hyperlink'];
				$query_params = '?itin=' . $Itinerary->getToken();
			}

			$url = untrailingslashit( site_url() ) . $slug . $query_params;

			$requestedURI = $_SERVER['REQUEST_URI'];

			// Drop the query string if there was one
			if ( is_int( strpos( $requestedURI, '?' ) ) ) {
				$requestedURI = strstr( $requestedURI, '?', true );
			}

			$active = $requestedURI == $slug ? ' class="active"' : '';
			?>
			<a href="<?php echo $url; ?>"<?php echo $active; ?>><?php echo $link['fxup_jump_to_links_text']; ?></a>
		<?php } ?>
		<?php
		$markup = ob_get_contents();
		ob_end_clean();
		return $markup;
	}

	public function renderJumpToSelectList( $itinerary_post_id )
	{

		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itinerary_post_id );

		ob_start();
		?>
		<select class="editable-btns push--bottom js-jump-dropdown">
			<option value="">Select a page</option>
			<?php
			foreach ( $this->jump_to_links as $link ) {
				$slug = '';
				$query_params = '';
				if ( '/itinerary/' === $link['fxup_jump_to_links_hyperlink'] ) {
					$slug = '/itinerary/' . $Itinerary->getPostObject()->post_name . '/';
				} else {
					$slug = $link['fxup_jump_to_links_hyperlink'];
					$query_params = '?itin=' . $Itinerary->getToken();
				}

				$url = untrailingslashit( site_url() ) . $slug . $query_params;

				$requestedURI = $_SERVER['REQUEST_URI'];

				// Drop the query string if there was one
				if ( is_int( strpos( $requestedURI, '?' ) ) ) {
					$requestedURI = strstr( $requestedURI, '?', true );
				}

				if ( $requestedURI !== $slug ) {
					?>
					<option value="<?php echo $url; ?>"><?php echo $link['fxup_jump_to_links_text']; ?></option> 
				<?php } ?>
			<?php } ?>
		</select>
		<?php
		$markup = ob_get_contents();
		ob_end_clean();
		return $markup;
	}

	public function renderLinkCenter()
	{
		if ( ! empty( $this->link_center ) ) :
			ob_start();
			?>
			<div class="link-center">
				<h4>Link Center</h4>
				<h2>Important Links and Tools for Your Stay</h2>
				<ul class="list-unstyled">
					<?php foreach ( $this->link_center as $link ) : ?>
						<li><a href="<?php echo esc_url( $link['link'] ); ?>" target="_blank"><?php echo esc_html( $link['text'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
				<?php if ( $this->is_concierge ) : ?>
					<a href="/pre-arrival-link-center/" class="btn btn-secondary push-top">Concierge Link Center</a>
				<?php endif; ?>
			</div>
			<?php
			$markup = ob_get_contents();
			ob_end_clean();
			return $markup;
		endif;
	}

	public static function apply_character_mask( $string, $mask_character = '*' )
	{
		return str_repeat( $mask_character, strlen( $string ) ); // Can return '' if input is empty string
	}

	public function json_decode_to_array( $json )
	{
		return json_decode( $json, true );
	}

	public function record_user_last_login_timestamp( $user_login, $user )
	{
		$timestamp = (new \DateTime( 'now' ))->getTimeStamp();
		update_user_meta( $user->ID, 'fxup_user_last_login_timestamp', $timestamp );
	}

	public function fxup_mail_from( $from_email )
	{
		if ( (bool) $this->get_field( 'fxup_enable_from_address_override' ) ) {
			$from_email = (string) $this->get_field( 'fxup_all_emails_from_address' );
		}
		return $from_email;
	}

	public function fxup_mail_from_name( $from_name )
	{
		if ( (bool) $this->get_field( 'fxup_enable_from_name_override' ) ) {
			$from_name = (string) $this->get_field( 'fxup_all_emails_from_name' );
		}
		return $from_name;
	}

	public function get_field( $field_name, $object = 'option' )
	{
		if ( function_exists( 'get_field' ) ) {
			return get_field( $field_name, $object );
		} else {
			if ( is_numeric( $object ) ) {
				return get_post_meta( $object, $field_name, true );
			}
			return get_option( $field_name );
		}
	}
}

FXUP_Itinerary_Process::instance();

if ( isset( $_GET['debug_cron'] ) ) {
	FXUP_Itinerary_Process::instance()->test_cron_email_notifications();
}
