<?php

namespace FXUP_User_Portal\Models;

use \NumberFormatter;

class Itinerary
{
	private static $print_view_post_id = 791; // Used to store some ACF meta fields which should apply to all Itinerary posts.
	private $post_id;
	private $post_object;
	private $title;
	private $approval_status;
	private $pending_client_submission_tooltip;
	private $pending_concierge_approval_tooltip;
	private $approved_tooltip;
	private $user_id;
	private $user_display_name;
	private $user_email;
	private $Villa;
	private $VillaClass = 'FXUP_User_Portal\Models\Villa';
	private $TripDayClass = 'FXUP_User_Portal\Models\TripDay';
	private $PreviousTripDayClass = 'FXUP_User_Portal\Models\PreviousTripDay';
	private $ActivityClass = 'FXUP_User_Portal\Models\Activity';
	private $GuestClass = 'FXUP_User_Portal\Models\Guest';
	private static $TransportClass = 'FXUP_User_Portal\Models\Transport';
	private static $RoomClass = 'FXUP_User_Portal\Models\Room';
	private $raw_rooms;
	private $Rooms;
	private $guest_objects;
	private $trip_start_date;
	private $trip_end_date;
	private $edit_day_cutoff_interval;
	private $editable_manual_override;
	private $guest_list_submitted;
	private $room_arrangements_submitted;
	private $wedding_date;
	private $raw_trip_days;
	private $trip_days;
	private $trip_day_objects;
	private $raw_previous_trip_days;
	private $previous_trip_days;
	private $group_name;
	private $token;
	private $account_id;
	private $client_itinerary_deadline_notification_sent;
	private $client_bio_and_notes;
	private $transportation_approval_status;
	private $raw_transports;
	private $raw_client_transports;
	private $Transports;
	private $transport_objects;
	private $transportation_updated_by;
	private static $transport_max_wait = 90;
	private static $transport_max_guests = 19; // set this to one less than the max
	private $summary_expiration;
	private $edit_lock;
	private $disable_all_notifications;
	private $payment_link;

	public function __construct( $post_object_or_id, $options = array() )
	{
		if ( is_numeric( $post_object_or_id ) ) {
			$this->post_id = $post_object_or_id;
			// Post Object
			$args = array(
				'post_type' => 'itinerary',
				'p' => $this->post_id
			);
			$query = new \WP_Query( $args );
			$post_object = ( ! empty( $query->posts[0] )) ? $query->posts[0] : false;
			if ( false === $post_object ) {
				throw new \Exception( "No Itinerary post found with ID: $this->post_id" );
			}
			$this->post_object = $post_object;
			return $this->post_object;
		} else {
			if ( ! $post_object_or_id instanceof \WP_Post ) {
				throw new \Exception( "Invalid post object passed to Itinerary constructor: $post_object_or_id" );
			}
			$this->post_object = $post_object_or_id;
			$this->post_id = $this->post_object->ID;
		}

		return $this;
	}

	public static function create( $title, $options = array() )
	{
		// CREATE NEW POST
		if ( ! is_string( $title ) || empty( $title ) ) {
			throw new \Exception( "Invalid title passed to Itinerary create method." );
		}
		// Create an itinerary for the user at signup and associate their account with it
		$itin_post = array(
			'post_title' => $title,
			'post_status' => 'publish',
			'post_author' => 9,
			'post_type' => 'itinerary'
		);

		// Save new itinerary and update relevant fields
		$post_id = wp_insert_post( $itin_post );

		// Check whether call was successful
		if ( ( ! is_int( $post_id )) || 0 === $post_id ) {
			throw new \Exception( 'Invalid post ID returned' );
		}

		$instance = new self( $post_id ); // Create new instance of self to return
		$instance->setToken( self::generateToken( 20 ) ); // This method MUST be called when Itinerary is created.
		// Accept setting other meta data on create.

		if ( isset( $options['trip_start_date'] ) ) {
			$instance->setTripStartDate( $options['trip_start_date'] );
		}

		if ( isset( $options['trip_end_date'] ) ) {
			$instance->setTripEndDate( $options['trip_end_date'] );
		}

		if ( isset( $options['approval_status'] ) ) {
			$instance->setApprovalStatus( $options['approval_status'] );
		}

		if ( isset( $options['user_id'] ) ) {
			$instance->setUserID( $options['user_id'] );
		}

		if ( isset( $options['villa'] ) ) {
			$instance->setVilla( $options['villa'] ); // Should accept either post_id, post_object and instantiate a new Villa model. OR accept Villa model and just set it (check instanceof).
		}

		if ( isset( $options['group_name'] ) ) {
			$instance->setGroupName( $options['group_name'] );
		}

		if ( isset( $options['account_id'] ) ) {
			$instance->setAccountID( $options['account_id'] );
		}

		return $instance;
	}

	public static function fromToken( $token )
	{
		$instance = false; // Default to false

		$args = array(
			'post_type' => 'itinerary',
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => 'share_link_token',
					'value' => $token,
					'compare' => '='
				)
			)
		);

		$query = new \WP_Query( $args );

		wp_reset_query();

		if ( ! empty( $query->posts[0] ) ) {
			$post_id = $query->posts[0]->ID;
			$instance = new self( $post_id );
		}

		return $instance;
	}

	public function getPostID()
	{
		return $this->post_id;
	}

	public function getPostObject()
	{
		return $this->post_object;
	}

	public function getEditDayCutoffInterval()
	{
		if ( null === $this->edit_day_cutoff_interval ) {
			$edit_day_cutoff_interval_raw = (int) get_field( 'fxup_edit_day_cutoff_interval', 'option' );
			$cutoff_date_interval_format_string = "P" . $edit_day_cutoff_interval_raw . "D"; // Days to subtract
			$this->edit_day_cutoff_interval = new \DateInterval( $cutoff_date_interval_format_string );
		}
		return $this->edit_day_cutoff_interval;
	}

	public function getWeddingDate()
	{
		if ( null === $this->wedding_date ) {
			$wedding_date_string = get_field( 'wedding_date', $this->post_id );
			$this->wedding_date = self::convertFromACFDatePicker( $wedding_date_string );
		}
		return $this->wedding_date;
	}

	/* BEGIN time sensitive functions which really should not be cached or would need to be lumped together into subscriber pattern to refresh when exposed fields are set */

	public function isUpcoming()
	{

		$interval_to_start_date = $this->getIntervalToStartDate();
		$upcoming = (0 === $interval_to_start_date->invert && 0 !== $interval_to_start_date->days); // If today is the "first day", trip is not upcoming

		return $upcoming;
	}

	public function getIntervalToStartDate()
	{
		// Get current date and time
		$current_date = new \DateTime();
		$interval_to_start_date = $current_date->diff( $this->getTripStartDate() );
		return $interval_to_start_date;
	}

	public function getIntervalToEndDate()
	{
		// Get current date and time
		$current_date = new \DateTime();
		$interval_to_end_date = $current_date->diff( $this->getTripEndDate() );
		return $interval_to_end_date;
	}

	public function getDaysUntilStart()
	{
		$interval_to_start_date = $this->getIntervalToStartDate();
		// Set how many days until trip starts
		$days_until_start = ($this->isUpcoming()) ? $interval_to_start_date->days : 0 - $interval_to_start_date->days; // Get days integer from DateInterval - change to negative if invert.
		return $days_until_start;
	}

	public function isTripOver()
	{
		$interval_to_end_date = $this->getIntervalToEndDate();
		$trip_over = (1 === $interval_to_end_date->invert && 0 !== $interval_to_end_date->days); // If today is the "last day", trip is not over
		return $trip_over;
	}

	public function getLastEditDay()
	{
		// Calculate last day this trip can be edited
		$trip_start_date = $this->getTripStartDate(); // Cloned so as not to mess up the actual trip_start_date
		$last_edit_day = $trip_start_date->sub( $this->getEditDayCutoffInterval() ); // Subtract days as pre-defined
		return $last_edit_day;
	}

	public function getIntervalToLastEditDay()
	{
		// Get current date and time
		$current_date = new \DateTime();
		$interval_to_last_edit_day = $current_date->diff( $this->getLastEditDay() );
		return $interval_to_last_edit_day;
	}

	public function getEditDaysLeft()
	{
		$interval_to_last_edit_day = $this->getIntervalToLastEditDay();
		// Calculate how many days left to edit
		$edit_days_left = ($this->isEditable()) ? $interval_to_last_edit_day->days : 0 - $interval_to_last_edit_day->days; // Get days integer from DateIntervall - change to negative if invert.    
		return $edit_days_left;
	}

	public function getIntervalToWeddingDate()
	{
		$interval_to_wedding_date = false; // Default to false in case wedding date is not set and calculation cannot be made.
		$current_date = new \DateTime();
		$wedding_date = $this->getWeddingDate();
		// Calculate whether wedding is today
		if ( $wedding_date instanceof \DateTime ) {
			$interval_to_wedding_date = $current_date->diff( $wedding_date );
		}
		return $interval_to_wedding_date;
	}

	public function isWeddingToday()
	{
		$wedding_today = (0 === $this->getIntervalToWeddingDate()); // Only if today is the wedding, set to true
		return $wedding_today;
	}

	/* END time sensitive functions which really should not be cached or would need to be lumped together into subscriber pattern to refresh when exposed fields are set */

	public function getApprovalStatus()
	{
		if ( null === $this->approval_status ) {
			$this->approval_status = get_field( 'approval_status', $this->getPostID() );
		}
		return $this->approval_status;
	}

	public function setApprovalStatus( $approval_status )
	{
		update_field( 'approval_status', $approval_status, $this->getPostID() );
		$this->approval_status = $approval_status;
		return $this->approval_status;
	}

	public function getTransportationApprovalStatus()
	{
		if ( null === $this->transportation_approval_status ) {
			$this->transportation_approval_status = get_field( 'transportation_approval_status', $this->getPostID() );
		}
		return $this->transportation_approval_status;
	}

	public function setTransportationApprovalStatus( $transportation_approval_status )
	{
		update_field( 'transportation_approval_status', $transportation_approval_status, $this->getPostID() );
		$this->transportation_approval_status = $transportation_approval_status;
		return $this->transportation_approval_status;
	}

	public function isTransportationApproved()
	{
		return $this->getTransportationApprovalStatus() == 'Approved' ? true : false;
	}
	
	public function getTransportationUpdatedBy()
	{
		if ( null === $this->transportation_updated_by ) {
			$this->transportation_updated_by = get_field( 'transportation_updated_by', $this->getPostID() );
		}
		return $this->transportation_updated_by;
	}

	public function setTransportationUpdatedBy( $transportation_updated_by )
	{
		update_field( 'transportation_updated_by', $transportation_updated_by, $this->getPostID() );
		$this->transportation_updated_by = $transportation_updated_by;
		return $this->transportation_updated_by;
	}

	public function getTransportationUpdatedByName()
	{
		if ( $this->getTransportationUpdatedBy() ) {
			$user = $this->getTransportationUpdatedBy();
			return ( in_array( 'um_concierge', $user->roles ) ) ? 'Concierge' : $user->display_name;
		}
		return 'N/A';
	}
	
	public function getTooltipPendingClientSubmission()
	{
		if ( null === $this->pending_client_submission_tooltip ) {
			$this->pending_client_submission_tooltip = get_field( 'pending_client_submission_tooltip', 'option' );
		}
		return $this->pending_client_submission_tooltip;
	}

	public function getTooltipPendingConciergeApproval()
	{
		if ( null === $this->pending_concierge_approval_tooltip ) {
			$this->pending_concierge_approval_tooltip = get_field( 'pending_concierge_approval_tooltip', 'option' );
		}
		return $this->pending_concierge_approval_tooltip;
	}

	public function getTooltipApproved()
	{
		if ( null === $this->approved_tooltip ) {
			$this->approved_tooltip = get_field( 'approved_tooltip', 'option' );
		}
		return $this->approved_tooltip;
	}

	/* BEGIN approval status related functions which really should not be cached or would need to be lumped together into subscriber pattern to refresh when exposed fields are set */

	public function getIcon()
	{
		// Icon and color
		if ( 'Pending Client Submission' === $this->getApprovalStatus() ) {
			$icon = 'fa-square';
		} else {
			$icon = 'fa-check-square';
		}
		return $icon;
	}

	public function getColor()
	{
		// Icon and color
		if ( 'Pending Client Submission' === $this->getApprovalStatus() ) {
			$color = '';
		} else {
			$color = 'style="color: #00A6A0"';
		}
		return $color;
	}

	public function getStatusTooltip()
	{
		// Status Tooltip
		switch ( $this->getApprovalStatus() ) {
			case 'Pending Client Submission':
				$status_tooltip = $this->getTooltipPendingClientSubmission();
				break;
			case 'Pending Concierge Approval':
				$status_tooltip = $this->getTooltipPendingConciergeApproval();
				break;
			case 'Approved':
				$status_tooltip = $this->getTooltipApproved();
				break;
			default:
				$status_tooltip = '';
				break;
		}

		return $status_tooltip;
	}

	/* END approval status related functions which really should not be cached or would need to be lumped together into subscriber pattern to refresh when exposed fields are set */

	public function setToken( $token )
	{
		update_field( 'share_link_token', $token, $this->getPostID() );
		$this->token = $token;
		return $this->token;
	}

	public function getToken()
	{
		if ( null === $this->token ) {
			$this->token = get_field( 'share_link_token', $this->getPostID() );
		}
		return $this->token;
	}

	public static function generateToken( $length )
	{
		$token = "";
		$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
		$codeAlphabet .= "0123456789";
		$max = strlen( $codeAlphabet );

		for ( $i = 0; $i < $length; $i ++ ) {
			$token .= $codeAlphabet[random_int( 0, $max - 1 )];
		}

		return $token;
	}
	
	public function getDisableAllNotifications()
	{
		if ( null === $this->disable_all_notifications ) {
			$this->disable_all_notifications = (bool) get_field( 'disable_all_notifications', $this->getPostID() );
		}
		return $this->disable_all_notifications;
	}
	
	public function setDisableAllNotifications( $value )
	{
		update_field( 'disable_all_notifications', $value, $this->getPostID() );
		$this->disable_all_notifications = $value;
		return $this->disable_all_notifications;
	}
	
	public function getPaymentLink()
	{
		if ( null === $this->payment_link ) {
			$this->payment_link = get_field( 'payment_link', $this->getPostID() );
		}
		return $this->payment_link;
	}
	
	public function hasPaymentLink()
	{
		return (bool) $this->getPaymentLink();
	}

	/* BEGIN calculated links which depend on other fields which are theoretically changeable. Do not cache these unless you are planning to do subscriber pattern to update these when token is changed. */

	public function getPermalink()
	{
		$permalink = get_permalink( $this->getPostID() );
		return $permalink;
	}

	public function getGuestListLink()
	{
		$guest_list_link = site_url() . '/edit-guest-list/?itin=' . $this->getToken();
		return $guest_list_link;
	}

	public function getSimpleGuestListLink()
	{
		$simple_guest_list_link = site_url() . '/simple-guest-list/?itin=' . $this->getToken();
		return $simple_guest_list_link;
	}

	public function getGuestTravelLink()
	{
		$guest_travel_link = site_url() . '/edit-guest-travel/?itin=' . $this->getToken();
		return $guest_travel_link;
	}

	public function getRoomArrangementsLink()
	{
		$room_arrangements_link = site_url() . '/room-arrangements/?itin=' . $this->getToken();
		return $room_arrangements_link;
	}

	public function getSimpleRoomArrangementsLink()
	{
		$simple_room_arrangements_link = site_url() . '/simple-room-arrangements/?itin=' . $this->getToken();
		return $simple_room_arrangements_link;
	}

	public function getShareLink()
	{
		$share_link = site_url() . '/share-itinerary/?itin=' . $this->getToken();
		return $share_link;
	}

	public function getSummaryLink()
	{
		return site_url() . '/summary/?itin=' . $this->getToken();
	}

	public function setSummaryExpiration( $value )
	{
		update_field( 'summary_expiration', $value, $this->getPostID() );
		$this->summary_expiration = $value;
		return $this->summary_expiration;
	}

	public function getSummaryExpiration()
	{
		if ( null === $this->summary_expiration ) {
			$this->summary_expiration = get_field( 'summary_expiration', $this->getPostID() );
		}
		return $this->summary_expiration;
	}

	public function isSummaryAvailable()
	{
		return $this->getSummaryExpiration() && $this->getSummaryExpiration() > time();
	}
	
	public function getTransportationSummaryLink()
	{
		return site_url() . '/transportation-summary/?itin=' . $this->getToken();
	}

	public function setEditLock( $value )
	{
		update_post_meta( $this->getPostID(), 'fxup_edit_lock', $value );
		$this->edit_lock = $value;
		return $this->edit_lock;
	}

	public function getEditLock()
	{
		if ( null === $this->edit_lock ) {
			$this->edit_lock = get_post_meta( $this->getPostID(), 'fxup_edit_lock', true );
		}
		return $this->edit_lock;
	}

	public function isEditLocked()
	{
		$lock = $this->getEditLock();
		$lock = explode( ':', $lock );
		$time = $lock[0];
		$user_id = $lock[1];

		if ( get_current_user_id() == $user_id ) {
			return false;
		}
	}

	public function removeEditLock()
	{
		delete_post_meta( $this->getPostID(), 'fxup_edit_lock' );
		$this->edit_lock = null;
		return $this->edit_lock;
	}

	public function getAddNewGuestLink()
	{
		$add_new_guest_link = site_url() . '/add-new-guest/?itin=' . $this->getToken();
		return $add_new_guest_link;
	}

	/* END calculated links which depend on other fields which are theoretically changeable. Do not cache these unless you are planning to do subscriber pattern to update these when token is changed. */

	public function getTitle()
	{
		if ( null === $this->title ) {
			$this->title = get_the_title( $this->getPostID() );
		}
		return $this->title;
	}

	public function getGuests()
	{
		if ( null === $this->guest_objects ) {
			$guest_objects = array(); // Init to empty array;
			// Guests
			$guest_args = array(
				'post_type' => 'guest',
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
						'key' => 'itinerary_id',
						'value' => $this->getPostID(),
					)
				)
			);
			$guest_query = new \WP_Query( $guest_args );
			$guest_posts = $guest_query->posts;
			$guest_post_ids = wp_list_pluck( $guest_posts, 'ID' );
			foreach ( $guest_post_ids as $guest_post_id ) {
				$Guest = new $this->GuestClass( $guest_post_id );
				$guest_objects[] = $Guest;
			}
			// Sort alphabetically
			if ( ! empty( $guest_objects ) ) {
				usort( $guest_objects, function ( $GuestOne, $GuestTwo ) {
					return $GuestOne->getFullName() < $GuestTwo->getFullName() ? -1 : ($GuestOne->getFullName() > $GuestTwo->getFullName() ? 1 : 0);
				} );
			}
			$this->guest_objects = $guest_objects; // Set to empty array so we won't run the call to the database again (no longer null) because we know we already queried and found nothing.
		}
		return is_array( $this->guest_objects ) ? $this->guest_objects : array(); // Will always return array
	}
	
	public function getGuestsForJson()
	{
		return array_map( function( $Guest ) {
			return $Guest->toArray();
		}, $this->getGuests() );
	}

	public function getGuestsOnsite()
	{
		return array_filter( $this->getGuests(), function ( $Guest ) {
			return $Guest->isOnsite();
		} );
	}

	public function getAdultGuests()
	{
		return array_filter( $this->getGuests(), function ( $Guest ) {
			return ! $Guest->isChild();
		} );
	}

	public function getChildGuests()
	{
		return array_filter( $this->getGuests(), function ( $Guest ) {
			return $Guest->isChild();
		} );
	}

	// Adults only
	public function getGuestsCount()
	{
		return count( $this->getAdultGuests() );
	}

	public function getChildrenCount()
	{
		$entity_children_count = count( $this->getChildGuests() );
		if ( $entity_children_count > 0 ) {
			return $entity_children_count;
		}

		// Backwards compatibility for legacy rows that still track child count on adults.
		$total_children = 0;
		foreach ( $this->getAdultGuests() as $Guest ) {
			$total_children += intval( $Guest->getChildren() );
		}
		return $total_children;
	}

	// Adults and children
	public function getTotalGuestsCount()
	{
		return ($this->getGuestsCount() + $this->getChildrenCount());
	}


	public function migrateLegacyChildrenToGuestEntities()
	{
		$created = 0;
		foreach ( $this->getAdultGuests() as $Guest ) {
			$legacy_children_count = intval( $Guest->getChildren() );
			if ( $legacy_children_count <= 0 ) {
				continue;
			}

			for ( $index = 1; $index <= $legacy_children_count; $index++ ) {
				$child_name = trim( $Guest->getFirstName() . ' Child ' . $index . ' ' . $Guest->getLastName() );
				$already_exists = false;
				foreach ( $this->getChildGuests() as $ChildGuest ) {
					if ( strtolower( trim( $ChildGuest->getFullName() ) ) === strtolower( $child_name ) ) {
						$already_exists = true;
						break;
					}
				}
				if ( $already_exists ) {
					continue;
				}

				$child_post_id = wp_insert_post( array(
					'post_type' => 'guest',
					'post_status' => 'publish',
					'post_title' => $this->getTitle() . ' - ' . $child_name,
				) );

				if ( ! $child_post_id || is_wp_error( $child_post_id ) ) {
					continue;
				}

				update_post_meta( $child_post_id, 'itinerary_id', $this->getPostID() );
				update_post_meta( $child_post_id, 'guest_first_name', trim( $Guest->getFirstName() . ' Child ' . $index ) );
				update_post_meta( $child_post_id, 'guest_last_name', $Guest->getLastName() );
				update_post_meta( $child_post_id, 'guest_is_child', 1 );
				update_post_meta( $child_post_id, 'guest_children', 0 );
				update_post_meta( $child_post_id, 'guest_parent_id', $Guest->getPostID() );
				$created++;
			}

			if ( $legacy_children_count > 0 ) {
				update_post_meta( $Guest->getPostID(), 'guest_children', 0 );
			}
		}

		if ( $created > 0 ) {
			$this->guest_objects = null;
		}

		return $created;
	}

	public function transportationFormViewPath()
	{
		return FXUP_USER_PORTAL()->plugin_path() . '/includes/views/form-transportation.php';
	}

	public function transportViewPath()
	{
		return FXUP_USER_PORTAL()->plugin_path() . '/includes/views/partials/itinerary-transport.php';
	}

	public function getRawTransports()
	{
		if ( null === $this->raw_transports ) {
			$arrival_transportation = get_field( 'arrival_transportation', $this->getPostID() );
			$departure_transportation = get_field( 'departure_transportation', $this->getPostID() );
			$this->raw_transports = array( 'arrival' => $arrival_transportation, 'departure' => $departure_transportation );
		}
		return is_array( $this->raw_transports ) ? $this->raw_transports : array(); // Always array
	}
	
	public function getRawClientTransports()
	{
		if ( null === $this->raw_client_transports ) {
			$arrival_transportation = get_field( 'arrival_transportation_client', $this->getPostID() );
			$departure_transportation = get_field( 'departure_transportation_client', $this->getPostID() );
			$this->raw_client_transports = array( 'arrival' => $arrival_transportation, 'departure' => $departure_transportation );
		}
		return is_array( $this->raw_client_transports ) ? $this->raw_client_transports : array(); // Always array
	}
	
	public function hasClientTransports()
	{
		$raw_transports = $this->getRawClientTransports();
		return ! empty( array_filter( array_values( $raw_transports ) ) );
	}

	public function setRawTransports( $raw_transports )
	{
		$this->raw_transports = $raw_transports;
		return $this->raw_transports;
	}
	
	public function setRawClientTransports( $raw_client_transports )
	{
		$this->raw_client_transports = $raw_client_transports;
		return $this->raw_client_transports;
	}

	public function getTransports( $force_update = false, $force_regenerate = false, $is_client_transports = false )
	{
		if ( null === $this->Transports || $force_update ) {
			$is_generated = false;
			$raw_transports = $is_client_transports ? $this->getRawClientTransports() : $this->getRawTransports();
//			if ( ! $is_client_transports ) {
				if ( empty( array_filter( array_values( $raw_transports ) ) ) || $force_regenerate ) {
					$raw_transports = $this->generateTransportation();
					$is_generated = true;
				}
//			}
			$Transports = array(
				'arrival' => array(),
				'departure' => array(),
			);
			foreach ( array( 'arrival', 'departure' ) as $type ) {
				if ( isset( $raw_transports[$type] ) ) {
					foreach ( $raw_transports[$type] as $index => $raw_transport ) {
						// acf 1 based index
						if ( $is_generated ) {
							$Transport = new self::$TransportClass( $index, $type, $this, $raw_transport, $is_client_transports );
						} else {
							$Transport = new self::$TransportClass( $index, $type, $this, false, $is_client_transports );
						}
						$Transports[$type][] = $Transport;
					}
				}
			}

			$this->Transports = $Transports;
			return $this->Transports;
		}

		return is_array( $this->Transports ) ? $this->Transports : array(); // Will always return array
	}
	
	public function getClientTransports( $force_update = false )
	{
		return $this->getTransports( $force_update, false, true );
	}

	public function generateTransportation()
	{
		// group the guests based on flight dates
		$arrivals = $this->groupGuestsBy( 'Arrival' );
		$departures = $this->groupGuestsBy( 'Departure' );

		// set the transports based on flight times
		$this->generateRawTransports( $arrivals, 'arrival' );
		$this->generateRawTransports( $departures, 'departure' );

		return $this->raw_transports;
	}

	public function getTransportationCount( $is_client_transports = false )
	{
		return count( $this->getArrivalTransportation( $is_client_transports ) + count( $this->getDepartureTransportation( $is_client_transports ) ) );
	}

	public function getArrivalTransportation( $is_client_transports = false )
	{
		$transports = $is_client_transports ? $this->getClientTransports() : $this->getTransports();
		return $transports['arrival'];
	}

	public function getDepartureTransportation( $is_client_transports = false )
	{
		$transports = $is_client_transports ? $this->getClientTransports() : $this->getTransports();
		return $transports['departure'];
	}

	public function getTransportGuests( $is_client_transports = false )
	{
		$has_arrival_transport = array();
		$has_departure_transport = array();
		$TransportGuests = array();
		$transports = $is_client_transports ? $this->getClientTransports() : $this->getTransports();
		foreach ( array( 'arrival', 'departure' ) as $type ) {
			if ( isset( $transports[$type] ) ) {
				foreach ( $transports[$type] as $index => $Transport ) {
					$Guests = $Transport->getGuestObjects();
					if ( ! empty( $Guests ) ) {
						foreach ( $Guests as $Guest ) {
							if ( $type == 'arrival' ) {
								$has_arrival_transport[] = $Guest->getPostID();
							}
							if ( $type == 'departure' ) {
								$has_departure_transport[] = $Guest->getPostID();
							}
							$TransportGuests[$type][$index][] = $this->toTransportJson( $Guest );
						}
					}
				}
			}
		}
		
		if ( $this->getGuests() ) {
			foreach ( $this->getGuests() as $Guest ) {
				if ( ! $Guest->isTravelFinalized() ) {
					continue;
				}
				
				if ( ! in_array( $Guest->getPostID(), $has_arrival_transport ) && $Guest->requiresArrivalTransportation() ) {
					$TransportGuests['missing']['arrival'][] = $this->toTransportJson( $Guest );
				}
				
				if ( ! in_array( $Guest->getPostID(), $has_departure_transport ) && $Guest->requiresDepartureTransportation() ) {
					$TransportGuests['missing']['departure'][] = $this->toTransportJson( $Guest );
				}
			}
		}
	
		return $TransportGuests;
	}

	public function getTransportGuestsJson( $is_client_transports = false )
	{
		return json_encode( $this->getTransportGuests( $is_client_transports ) );
	}

	private function toTransportJson( $Guest )
	{
		return array(
			'ID' => $Guest->getPostID(),
			'first_name' => $Guest->getFirstName(),
			'last_name' => $Guest->getLastName(),
			'full_name' => $Guest->getFullName(),
			'arrival_flight' => $Guest->getArrivalAirline(),
			'arrival_time' => $Guest->getArrivalTime(),
			'departure_flight' => $Guest->getDepartureAirline(),
			'departure_time' => $Guest->getDepartureTime(),
			'is_child' => $Guest->isChild(),
			'villa_name' => $Guest->getAssignedRoom() ? $Guest->getAssignedRoom()->getSubVilla()->getTitle() : '',
		);
	}

	public function addEmptyTransport( $index, $type, $is_client_transports )
	{
		return new self::$TransportClass( $index, $type, $this, false, $is_client_transports );
	}
	
	public function getFilteredArrivalTransports()
	{
		$transports = $this->getArrivalTransportation();
		return $this->getFilteredTransports( $transports );
	}
	
	public function getFilteredDepartureTransports()
	{
		$transports = $this->getDepartureTransportation();
		return $this->getFilteredTransports( $transports );
	}
	
	private function getFilteredTransports( $transports )
	{
		$company = filter_input( INPUT_GET, 'transportCompany' );
		if ( $company ) {
			$transports = array_filter( $transports, function( $Transport ) use ( $company ) {
				return $Transport->getCompany() == $company;
			} );
		}
		
		$guests = (array) $_GET['transportGuests'];
		if ( $guests ) {
			$transports = array_filter( $transports, function( $Transport ) use ( $guests ) {
				$filtered_guests = array_filter( $Transport->getGuestObjects(), function( $Guest ) use ( $guests ) {
					return in_array( $Guest->getPostID(), $guests );
				} );
				return ! empty( $filtered_guests );
			} );
		}
		
		return $transports;
	}

	public function getTransportCompanies()
	{
		$TransportCompanies = array();
		$transports = $this->getTransports();
		foreach ( array( 'arrival', 'departure' ) as $type ) {
			if ( isset( $transports[$type] ) ) {
				foreach ( $transports[$type] as $index => $Transport ) {
					$company = $Transport->getCompany();
					if ( $company && ! in_array( $company, $TransportCompanies ) ) {
						$TransportCompanies[] = $company;
					}
				}
			}
		}
		return $TransportCompanies;
	}
	
	public function getMissingTravelDetails()
	{
		return array_filter( $this->getGuests(), function ( $Guest ) {
			return ! $Guest->isTravelFinalized();
//			return ! $Guest->isTravelArrangementsSubmitted();
		} );
	}

	public function getMissingTravelDetailsCount()
	{
		return count( $this->getMissingTravelDetails() );
	}

	private function groupGuestsBy( $Arrival_or_Departure )
	{
		$grouping = array();
		foreach ( $this->getGuests() as $Guest ) {
			if ( $Arrival_or_Departure == 'Arrival' && ! $Guest->requiresArrivalTransportation() ) {
				continue;
			}
			
			if ( $Arrival_or_Departure == 'Departure' && ! $Guest->requiresDepartureTransportation() ) {
				continue;
			}
			
			$guest_id = $Guest->getPostID();
			$date_getter = "get{$Arrival_or_Departure}Date";
			$hour_getter = "get{$Arrival_or_Departure}TimeHour";
			$minute_getter = "get{$Arrival_or_Departure}TimeMinute";
			$meridiem_getter = "get{$Arrival_or_Departure}TimeMeridiem";

			$date = $Guest->{$date_getter}();
			$hour = (int) $Guest->{$hour_getter}();
			if ( strtolower( $Guest->{$meridiem_getter}() ) == 'pm' && $hour < 12 ) {
				$hour += 12;
			}
			$minute = $Guest->{$minute_getter}();
			$time = join( ':', array( $hour, $minute ) );

			// group guests based on date
			if ( $date != '' && $time != '' ) {
				$timestamp = strtotime( join( ' ', array( $date, $time ) ) );
				$grouping[$guest_id]['guest_id'] = $guest_id;
				$grouping[$guest_id]['date'] = $date;
				$grouping[$guest_id]['time'] = $hour . $minute;
				$grouping[$guest_id]['timestamp'] = $timestamp;
			}
		}

		usort( $grouping, array( $this, 'sortByTime' ) );

		return $grouping;
	}

	private function generateRawTransports( $data, $type )
	{

		$max_wait_in_seconds = self::$transport_max_wait * 60;
		$start_time = 0;
		$index = 0;
		foreach ( $data as $guest ) {
			$date = $guest['date'];
			$time = $guest['time'];
			$timestamp = $guest['timestamp'];
			if ( $time === '' ) {
				continue;
			}

			if ( $timestamp - $start_time > $max_wait_in_seconds || ! isset( $raw_transport ) || ( isset( $raw_transport ) && count( $raw_transport['guests'] ) > self::$transport_max_guests ) ) {
				// add the previous raw_transport
				if ( isset( $raw_transport ) ) {
					$this->raw_transports[$type][$index] = $raw_transport;
					$index ++;
				}

				$start_time = $timestamp;

				$raw_transport = array(
					'date' => $date,
					'company' => '',
					'guests' => array(),
				);
			}

			$raw_transport['guests'][] = $guest['guest_id'];
		}

		// add the final raw_transport
		$this->raw_transports[$type][$index] = $raw_transport;
	}

	private function sortByTime( $a, $b )
	{
		return $a['timestamp'] <=> $b['timestamp'];
	}

	public function setVilla( $Villa )
	{
		// Make sure to convert Villa to instance of the Villa model class
		if ( ! ($Villa instanceof $this->VillaClass) ) {
			$Villa = new $this->VillaClass( $Villa, $this ); // Pass to Villa constructor
		}
		$villa_post_object = $Villa->getPostObject(); // Villa model should adhere to interface with method for getPostObject()
		update_field( 'villa_option', $villa_post_object, $this->getPostID() );
		$this->Villa = $Villa; // Set instance of Villa on this Itinerary instance
		return $this->Villa;
	}

	public function getVilla()
	{
		if ( ! ($this->Villa instanceof $this->VillaClass) ) {
			$villa_post_object = get_field( 'villa_option', $this->getPostID() );
			if ( is_array( $villa_post_object ) && ( ! empty( $villa_post_object )) ) {
				// Get the first item if an array
				$villa_post_object = $villa_post_object[0];
			}
			// Make sure Villa is a post, then call the constructor (accepts post object or ID, but the point here is to return null if there is not a valid Villa set in the db)
			if ( $villa_post_object instanceof \WP_Post ) {
				$this->Villa = new $this->VillaClass( $villa_post_object, $this ); // Construct empty Villa and add as property of this Itinerary instance
			}
		}
		return $this->Villa;
	}

	/* BEGIN ROOMS */

	public function addRoom( $name )
	{
		// Suggested implementation below...
		/*
		  $Room = self::$RoomClass::create($room_name, $SubVilla, $Villa = $this, $options = array());

		  if (! is_array($this->Rooms)) {
		  $this->Rooms = array();
		  }
		  $this->Rooms[] = $Room;
		  return $Room;
		 */
	}

	public function getRawRooms()
	{
		if ( null === $this->raw_rooms ) {
			$raw_rooms = get_field( 'room_guests', $this->getPostID() );
			$this->raw_rooms = $raw_rooms;
		}
		return is_array( $this->raw_rooms ) ? $this->raw_rooms : array(); // Always array
	}

	public function getRooms()
	{
		if ( null === $this->Rooms ) {

			$raw_rooms = $this->getRawRooms();
			$Rooms = array();
			foreach ( $raw_rooms as $index => $raw_room ) {

				$room_name = $raw_room['room_name'];
				$sub_villa_id = $raw_room['room_guests_villa_id'];
				if ( $sub_villa_id instanceof \WP_Post ) {
					$sub_villa_id = $sub_villa_id->ID;
				}
				$SubVilla = new self::$VillaClass( $sub_villa_id, $this );
				// public function __construct($room_name, $SubVilla, $Villa = null, $Itinerary = null, $options = array())
				$Room = new self::$RoomClass( $room_name, $SubVilla, $this->getVilla(), $this ); // Constructor accepts Model Instances.
				$Rooms[] = $Room;
			}

			$this->Rooms = $Rooms; // Set to empty array so we won't run the call to the database again (no longer null) because we know we already queried and found nothing.
			return $this->Rooms;
		}

		return is_array( $this->Rooms ) ? $this->Rooms : array(); // Will always return array
	}

	/* END ROOMS */

	public function setUserID( $user_id )
	{
		update_field( 'itinerary_user', $user_id, $this->getPostID() );
		$this->user_id = $user_id;
		return $this->user_id;
	}

	public function getUserID()
	{
		if ( null === $this->user_id ) {
			$user_id = get_field( 'itinerary_user', $this->getPostID() );
			// Could be instance of class - in which case, use methods to simplify to ID.
			if ( $user_id instanceof \WP_User ) {
				$user_id = $user_id->ID;
			}
			$this->user_id = $user_id;
		}
		return $this->user_id;
	}

	public function getUserDisplayName()
	{
		if ( null === $this->user_display_name ) {
			$user = get_user_by( 'ID', $this->getUserID() );
			$user_display_name = $user->display_name;
			$this->user_display_name = $user_display_name;
		}
		return $this->user_display_name;
	}

	public function getUserEmail()
	{
		if ( null === $this->user_email ) {
			$user = get_user_by( 'ID', $this->getUserID() );
			$user_email = $user->user_email;
			$this->user_email = $user_email;
		}
		return $this->user_email;
	}

	public function setGroupName( $group_name )
	{
		update_field( 'group_name', $group_name, $this->post_id );
		$this->group_name = $group_name;
		return $this->group_name;
	}

	public function getGroupName()
	{
		if ( null === $this->group_name ) {
			$this->group_name = get_field( 'group_name', $this->post_id );
		}
		return $this->group_name;
	}

	public function setTripStartDate( $DateTimeObject )
	{
		$formatted_for_ACF_datepicker = self::convertToACFDatePicker( $DateTimeObject );
		update_field( 'trip_start_date', $formatted_for_ACF_datepicker, $this->getPostID() );
		$this->trip_start_date = $DateTimeObject;
		$this->onTripDateSet();
		return $this->trip_start_date;
	}

	public function getTripStartDate()
	{
		if ( null === $this->trip_start_date ) {
			$acf_formatted_start_date = get_field( 'trip_start_date', $this->post_id );
			$this->trip_start_date = self::convertFromACFDatePicker( $acf_formatted_start_date );
		}
		return clone $this->trip_start_date; // So DateTime object instance won't be messed up
	}

	public function setTripEndDate( $DateTimeObject )
	{
		$formatted_for_ACF_datepicker = self::convertToACFDatePicker( $DateTimeObject );
		update_field( 'trip_end_date', $formatted_for_ACF_datepicker, $this->getPostID() );
		$this->trip_end_date = $DateTimeObject;
		$this->onTripDateSet();
		return $this->trip_end_date;
	}

	public function getTripEndDate()
	{
		if ( null === $this->trip_end_date ) {
			$acf_formatted_end_date = get_field( 'trip_end_date', $this->post_id );
			$this->trip_end_date = self::convertFromACFDatePicker( $acf_formatted_end_date );
		}
		return $this->trip_end_date;
	}

	public function onTripDateSet()
	{
		// If both bookend dates have not yet been set, exit.
		if ( empty( $this->trip_start_date ) || empty( $this->trip_end_date ) ) {
			return; // Exit
		}

		// If there is already data in database for the trip day repeater rows, do not do anything.
		if ( ! empty( $this->trip_day_objects ) ) {
			// This could be improved in the future - we could allow user to dynamically change TripDays range (start/end date of Itinerary).
			// Idea: Add a ->delete() method to TripDay model, iterate through TripDays and identify any which are outside the trip start/end and call TripDay->delete();
			// Then, iterate through trip days date range and compare to $this->trip_day_objects to find days which are missing the model/repeater rows - call TripDay::create() for those days.
			return; // Exit
		}

		// Based on above return conditions, we are assuming that trip day objects have NOT yet been persisted to db. So, will persist them here and now.
		$trip_day_range = self::generateDayRange( $this->getTripStartDate(), $this->getTripEndDate() );
		foreach ( $trip_day_range as $DateTimeDay ) {
			$this->addTripDay( $DateTimeDay );
		}
	}

	public function addTripDay( $DateTimeObject )
	{
		$TripDayObject = $this->TripDayClass::create( $DateTimeObject, $this );
		/*
		  $TripDayObject = new $this->TripDayClass;
		  $TripDayObject->setItinerary($this); // TripDay belongs to an Itinerary and must have knowledge of it.
		  $TripDayObject->setActivityClass($this->ActivityClass);
		  $data = array();
		  $data['DateTime'] = $DateTimeObject; // TripDay needs a DateTime to correctly add itself as a repeater row
		  $TripDayObject->create($data);
		  $auto_scheduled_activities_daily = $this->ActivityClass::getAutoScheduledActivities();
		  if (is_array($auto_scheduled_activities_daily)) {
		  foreach ($auto_scheduled_activities_daily as $activity_post_id) {
		  $fxup_event_type_auto_schedule_daily_time = get_field('fxup_event_type_auto_schedule_daily_time', $activity_post_id);
		  $fxup_event_type_auto_schedule_daily_time = !empty($fxup_event_type_auto_schedule_daily_time) ? $fxup_event_type_auto_schedule_daily_time : '';
		  // Execute here
		  $ActivityObject = $TripDayObject->addActivity($activity_post_id);
		  $ActivityObject->setBookedTime($fxup_event_type_auto_schedule_daily_time);
		  $ActivityObject->setBooked(true);
		  }
		  }
		 */
		if ( ! is_array( $this->trip_day_objects ) ) {
			$this->trip_day_objects = array();
		}
		$this->trip_day_objects[] = $TripDayObject;
		return $TripDayObject;
	}

	public function getRawTripDays( $force_update = false )
	{
		if ( null === $this->raw_trip_days || $force_update ) {
			$raw_trip_days = get_field( 'itinerary_trip_days', $this->getPostID() );

			$this->raw_trip_days = $raw_trip_days;
			// $this->parseRawTripDays($this->raw_trip_days); // Parse to the normalized forms
		}
		return is_array( $this->raw_trip_days ) ? $this->raw_trip_days : array(); // Always array
	}

	public function getTripDays( $force_update = false )
	{
		if ( null === $this->trip_day_objects || $force_update ) {

			$raw_trip_days = $this->getRawTripDays( $force_update );
			$trip_days = array();
			$trip_day_objects = array();
			foreach ( $raw_trip_days as $index => $raw_trip_day ) {
				$trip_day = self::formatInternalTripDayFieldFromACF( $raw_trip_day );
				$trip_days[] = $trip_day;
				$DateTimeObject = \DateTime::createFromFormat( 'F j, Y', $raw_trip_day['trip_day'] );
				$TripDayObject = new $this->TripDayClass( $DateTimeObject, $this ); // Constructor accepts DateTime and Itinerary instances.
				/*
				  $TripDayObject = new $this->TripDayClass;
				  $TripDayObject->setItinerary($this);
				  $TripDayObject->setActivityClass($this->ActivityClass);
				  $TripDayObject->setRowNumber($index + 1); // ACF is 1 based index, loop index will be 0 based.
				  $TripDayObject->setRawTripDayArray($raw_trip_day); // Hopefully this can be optimized in the future.
				  $TripDayObject->read($DateTimeObject);
				 */
				$trip_day_objects[] = $TripDayObject;
			}

			$this->trip_days = $trip_days;
			$this->trip_day_objects = $trip_day_objects; // Set to empty array so we won't run the call to the database again (no longer null) because we know we already queried and found nothing.
			return $this->trip_day_objects;
		}

		return is_array( $this->trip_day_objects ) ? $this->trip_day_objects : array(); // Will always return array
	}

	public function getRawPreviousTripDays()
	{
		if ( null === $this->raw_previous_trip_days ) {
			$this->raw_previous_trip_days = maybe_unserialize( get_field( 'itinerary_previous_trip_days', $this->getPostID() ) );
		}
		return is_array( $this->raw_previous_trip_days ) ? $this->raw_previous_trip_days : array(); // Always array
	}

	public function getPreviousTripDays()
	{
		if ( null === $this->previous_trip_days ) {
			$raw_previous_trip_days = $this->getRawPreviousTripDays();
			$previous_trip_days = array();
			// todo
			$this->previous_trip_days = $previous_trip_days;
		}
		return is_array( $this->previous_trip_days ) ? $this->previous_trip_days : array(); // Will always return array 
	}

	public function setPreviousTripDays( $value )
	{
		
	}

	private function getPreviousTripDaysSerialized()
	{
		$raw_data = array();
		if ( ! empty( $this->getPreviousTripDays() ) ) {
			$raw_data = array_map( function ( $PreviousTripDay ) {
				return $PreviousTripDay->toRawRow();
			}, $this->getPreviousTripDays() );
		}
		return serialize( $raw_data );
	}

	public function savePreviousTripDays( $value = null )
	{
		if ( $value ) {
			$this->setPreviousTripDays( $value );
		}
		update_field( 'itinerary_previous_trip_days', $this->getPreviousTripDaysSerialized(), $this->getPostID() );
	}

	public function getTripDayObjects()
	{
		// Backwards compat
		return $this->getTripDays();
	}

	public static function getVideoLinkTop( $queried_object )
	{
		$fields_required = array(
			'title' => null,
			'url' => null,
			'target' => null,
		);
		$fields_raw = null;
		if ( $queried_object instanceof \WP_Post ) {
			// If the query was for an Itinerary post, then redirect it to the static print view page/post which can house ACF inputs for all Itinerary posts.
			if ( 'itinerary' === $queried_object->post_type ) {
				$fields_raw = get_field( 'fxup_page_videos_top_video', self::getPrintViewPostID() );
			} else {
				$fields_raw = get_field( 'fxup_page_videos_top_video', $queried_object->ID );
			}
		}
		// Merge to return a safe array
		$fields_array = is_array( $fields_raw ) ? $fields_raw : array();
		$fields_safe = shortcode_atts( $fields_required, $fields_array );
		return $fields_safe;
	}

	public static function getPrintViewPostID()
	{
		return self::$print_view_post_id;
	}

	public static function formatInternalTripDayFieldFromACF( $trip_day )
	{
		$DateTimeObject = \DateTime::createFromFormat( 'F j, Y', $trip_day['trip_day'] );
		$activities = ( ! empty( $trip_day['trip_day_activities'] )) ? $trip_day['trip_day_activities'] : array();
		return [
			'DateTime' => $DateTimeObject,
			'activities' => $activities,
		];
	}

	public static function generateDayRange( $start_date_time_object, $end_date_time_object )
	{
		$start_date_clone = clone $start_date_time_object;
		$end_date_clone = clone $end_date_time_object; // Not currently necessary, but just in case this is expanded.
		$range_of_date_time_objects = array();

		$interval = new \DateInterval( 'P1D' ); // 1 day
		$safety = 90; // Only allow for up to 90 days max to prevent guests from accidentally inserting a ton of rows.
		// Catch the start date up to the end date, adding a new DateTime to the range for however many days it takes.
		while ( $start_date_clone <= $end_date_clone && $safety > 0 ) {
			$range_of_date_time_objects[] = clone $start_date_clone; // Clone so the object is not mutated later
			$start_date_clone->add( $interval ); // Increment by 1
			-- $safety;
		}
		return $range_of_date_time_objects;
	}

	public function getTotalCost()
	{
		$total_cost = 0;
		$trip_day_objects = is_array( $this->trip_day_objects ) ? $this->trip_day_objects : array();
		foreach ( $trip_day_objects as $TripDay ) {
			$total_cost += $TripDay->getTotalCost();
		}
		return $total_cost;
	}
	
	public function getTotalCostFormatted()
	{
		$formatter = new NumberFormatter( 'en_US', NumberFormatter::CURRENCY );
		return $formatter->format( $this->getTotalCost() );
	}

	public function getTripDayCount()
	{
		$trip_day_objects = is_array( $this->trip_day_objects ) ? $this->trip_day_objects : array();
		return count( $trip_day_objects );
	}

	public function isEditableManualOverride()
	{
		if ( null === $this->editable_manual_override ) {
			$this->editable_manual_override = (bool) get_field( 'fxup_editable_manual_override', $this->post_id );
		}
		return $this->editable_manual_override;
	}

	public function isEditable()
	{
		// Determine whether trip is still editable
		$editable = (0 === $this->getIntervalToLastEditDay()->invert || 0 === $this->getIntervalToLastEditDay()->days); // If today is the "last edit day", trip is still editable
		// Allow for manual override so activities can be added.
		if ( $this->isEditableManualOverride() ) {
			$editable = true;
		}
		return $editable;
	}

	public function setEditableManualOverride( $bool )
	{
		$bool = (bool) $bool;
		update_field( 'fxup_editable_manual_override', $bool, $this->getPostID() );
		$this->editable_manual_override = $bool;
		return $this->editable_manual_override;
	}

	public function getGuestListSubmitted()
	{
		// Backwards compat
		return $this->isGuestListSubmitted();
	}

	public function isGuestListSubmitted()
	{
		if ( null === $this->guest_list_submitted ) {
			$this->guest_list_submitted = (bool) get_field( 'guest_list_submitted', $this->getPostID() ); // Will be set to bool
		}
		return $this->guest_list_submitted;
	}

	public function setGuestListSubmitted( $bool )
	{
		$bool = (bool) $bool;
		update_field( 'guest_list_submitted', $bool, $this->getPostID() );
		$this->guest_list_submitted = $bool;
		return $this->guest_list_submitted;
	}

	public function isRoomArrangementsSubmitted()
	{
		if ( null === $this->room_arrangements_submitted ) {
			$this->room_arrangements_submitted = (bool) get_field( 'guest_room_arrangements_submitted', $this->getPostID() ); // Will be set to bool
		}
		return $this->room_arrangements_submitted;
	}

	public function setRoomArrangementsSubmitted( $bool )
	{
		$bool = (bool) $bool;
		update_field( 'guest_room_arrangements_submitted', $bool, $this->getPostID() );
		$this->room_arrangements_submitted = $bool;
		return $this->room_arrangements_submitted;
	}

	public function setAccountID( $account_id )
	{
		update_field( 'account_id', $account_id, $this->getPostID() );
		$this->account_id = $account_id;
		return $this->account_id;
	}

	public function getAccountID()
	{
		if ( null === $this->account_id ) {
			$this->account_id = get_field( 'account_id', $this->getPostID() );
		}
		return $this->account_id;
	}

	// Don't cache, because calculated
	public function isAllActivitiesBooked()
	{
		$is_all_activities_booked = true;
		$trip_day_objects = is_array( $this->trip_day_objects ) ? $this->trip_day_objects : array();
		foreach ( $trip_day_objects as $TripDay ) {
			if ( true !== $TripDay->isAllActivitiesBooked() ) {
				$is_all_activities_booked = false;
				break;
			}
		}
		$this->is_all_activities_booked = $is_all_activities_booked;
		return $this->is_all_activities_booked;
	}

	public function setClientItineraryDeadlineNotificationSent( $DateTime )
	{
		if ( $DateTime instanceof \DateTime ) {
			$timestamp = $DateTime->getTimestamp();
		}
		update_post_meta( $this->getPostID(), 'client_itinerary_deadline_notification_sent', $timestamp );
		$this->client_itinerary_deadline_notification_sent = $DateTime;
		return $this->client_itinerary_deadline_notification_sent;
	}

	public function getClientItineraryDeadlineNotificationSent()
	{
		if ( null === $this->client_itinerary_deadline_notification_sent ) {
			$sanitized = false;
			$raw = get_post_meta( $this->getPostID(), 'client_itinerary_deadline_notification_sent', true );
			if ( $raw instanceof \DateTime ) {
				$sanitized = $raw;
			} elseif ( is_numeric( $raw ) ) {
				// If stored as timestamp
				$sanitized = (new \DateTime() )->setTimestamp( $raw );
			}
			$this->client_itinerary_deadline_notification_sent = $sanitized;
		}
		return $this->client_itinerary_deadline_notification_sent;
	}

	public function getClientBioAndNotes()
	{
		if ( null === $this->client_bio_and_notes ) {
			$this->client_bio_and_notes = wp_kses_post( (string) get_field( 'fxup_itinerary_client_bio_and_notes', $this->getPostID() ) );
		}
		return $this->client_bio_and_notes;
	}

	public function setVillaClass( $className )
	{
		$this->VillaClass = $className;
	}

	public function setGuestClass( $className )
	{
		$this->GuestClass = $className;
	}

	public function setTripDayClass( $className )
	{
		$this->TripDayClass = $className;
	}

	public function setActivityClass( $className )
	{
		$this->ActivityClass = $className;
	}

	public static function convertFromACFDatePicker( $date_picker_return_value )
	{
		return \DateTime::createFromFormat( 'Ymd', $date_picker_return_value );
	}

	public static function convertToACFDatePicker( $DateTimeObject )
	{
		return $DateTimeObject->format( 'Ymd' );
	}

	public static function addStaffAccessQueryParam( $url )
	{
		return add_query_arg( 'vpdvstaff', 'true', $url );
	}

	public static function hasStaffAccessQueryParam()
	{
		return isset( $_REQUEST['vpdvstaff'] );
	}

}
