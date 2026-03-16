<?php

namespace FXUP_User_Portal\Models;

class Guest
{
	private $post_id;
	public static $ItineraryClass = 'FXUP_User_Portal\Models\Itinerary';
	private $Itinerary;
	private $Room;
	private $related_itinerary_post_id;
	private $first_name;
	private $last_name;
	private $email;
	private $passport_number;
	private $children;
	private $is_child;
	private $notes;
	private $travel_notes;
	private $flight_number;
	private $departure_flight_number;
	private $arrival_time;
	private $departure_time;
	private $stay_length;
	private $arrival_date;
	private $departure_date;
	private $airline;
	private $departure_airline;
	private $travel_arrangements_submitted;
	private $travel_arrangements_status;
	private $requires_arrival_transportation;
	private $requires_departure_transportation;
	private $guest_travel_deadline_notification_sent;
	private $onsite; // Defaults to "Yes", so used boolean default
	private $stay_location;
	private $dietary_restrictions; // Array of checkbox values
	private $dietary_restriction_other; // Text
	private $allergies;
	private $arrival_time_hour;
	private $arrival_time_minute;
	private $arrival_time_meridiem;
	private $departure_time_hour;
	private $departure_time_minute;
	private $departure_time_meridiem;
	private static $guest_travel_hours_leading_zero;
	private static $guest_travel_minutes_leading_zero;
	private static $guest_travel_hours_validation_regex;
	private static $guest_travel_minutes_validation_regex;
	private static $guest_travel_times_validation_regex;
	private static $guest_travel_hours_options;
	private static $guest_travel_minutes_options;
	private $has_arrival_transportation = false;
	private $has_departure_transportation = false;

	public function __construct( $post_id, $options = array() )
	{
		if ( ! is_numeric( $post_id ) ) {
			throw new \Exception( 'Must pass numeric post_id to Guest constructor' );
		}

		$this->post_id = (int) $post_id;

		return $this;
	}

	public static function create( $email, $options )
	{
		//...
		// This is where you would do th magic to create a new guest - inserting and getting a post_id.
		//...
		//return new::self($post_id);
	}

	public function getPostID()
	{
		return $this->post_id;
	}

	public function hasItinerary()
	{
		return (bool) $this->getItinerary();
	}

	// Guests can be created without an Itinerary, and then the Itinerary can be set.
	public function getItinerary()
	{
		if ( null === $this->Itinerary ) {
			// This means Itinerary was not set directly via setItinerary method. Try to construct Itinerary from Guest's meta fields.
			// Validate it is a WordPress post.
			if ( $this->getRelatedItineraryPostID() && get_post( $this->getRelatedItineraryPostID() ) ) {
				try {
					$this->Itinerary = new self::$ItineraryClass( $this->getRelatedItineraryPostID() );
				} catch ( \Exception $e ) {
					throw new \Exception( 'This Guest has no valid Itinerary set' );
				}
			} else {
				$this->Itinerary = false; // Set Itinerary to false.
			}
		}
		return $this->Itinerary;
	}

	public function setItinerary( $Itinerary )
	{
		if ( ! ($Itinerary instanceof self::$ItineraryClass) ) {
			$Itinerary = new self::$ItineraryClass( $Itinerary ); // If post_id is passed, construct.
		}
		$this->Itinerary = $Itinerary;
		$this->setRelatedItineraryPostID();
		return $this->Itinerary;
	}

	// Private
	private function getRelatedItineraryPostID()
	{
		if ( null === $this->related_itinerary_post_id ) {
			$raw = get_post_meta( $this->getPostID(), 'itinerary_id', true );
			$sanitized = is_numeric( $raw ) ? $raw : false;
			$this->related_itinerary_post_id = $sanitized;
		}
		return $this->related_itinerary_post_id;
	}

	private function setRelatedItineraryPostID()
	{
		$success = true;
		// If the meta field on the Guest post does not match the Model's internal state, update the meta field.
		if ( (int) $this->getRelatedItineraryPostID() !== (int) $this->getItinerary()->getPostID() ) {
			$success = (bool) update_post_meta( $this->getPostID(), 'itinerary_id', $this->getItinerary()->getPostID() );
		}
		return $success;
	}

	// Backwards compat
	public function getItineraryID()
	{
		$itinerary_id = false;
		if ( $this->hasItinerary() ) {
			$itinerary_id = $this->getItinerary()->getPostID(); // Guest can exist apart from Itinerary
		}
		return $itinerary_id; // Will return false if no Itinerary is related
	}

	public function getFirstName()
	{
		if ( null === $this->first_name ) {
			$this->first_name = get_post_meta( $this->getPostID(), 'guest_first_name', true );
		}
		return $this->first_name;
	}

	public function getLastName()
	{
		if ( null === $this->last_name ) {
			$this->last_name = get_post_meta( $this->getPostID(), 'guest_last_name', true );
		}
		return $this->last_name;
	}

	public function getEmail()
	{
		if ( null === $this->email ) {
			$this->email = get_post_meta( $this->getPostID(), 'guest_email', true );
		}
		return $this->email;
	}

	public function canReceiveEmail()
	{
		if ( $this->isChild() ) {
			return false;
		}

		return '' !== trim( (string) $this->getEmail() );
	}

	public function setEmail( $email )
	{
		$this->email = trim( (string) $email );

		if ( $this->isChild() ) {
			$this->email = '';
		}

		update_post_meta( $this->getPostID(), 'guest_email', $this->email );

		return $this->email;
	}

	public function getChildren()
	{
		if ( null === $this->children ) {
			// @deprecated legacy child storage
			$this->children = get_post_meta( $this->getPostID(), 'guest_children', true );
		}
		return $this->children;
	}

	public function isChild()
	{
		if ( null === $this->is_child ) {
			$this->is_child = (bool) get_post_meta( $this->getPostID(), 'guest_is_child', true );
		}
		return $this->is_child;
	}

	public function setIsChild( $is_child )
	{
		$this->is_child = (bool) $is_child;
		update_post_meta( $this->getPostID(), 'guest_is_child', (int) $this->is_child );
		if ( $this->is_child ) {
			update_post_meta( $this->getPostID(), 'guest_children', 0 );
			$this->children = 0;
			$this->setEmail( '' );
		}
		return $this->is_child;
	}

	public function getNotes()
	{
		if ( null === $this->notes ) {
			$this->notes = get_post_meta( $this->getPostID(), 'guest_notes', true );
		}
		return $this->notes;
	}

	public function getPassportNumber()
	{
		if ( null === $this->passport_number ) {
			$this->passport_number = get_post_meta( $this->getPostID(), 'passport_number', true );
		}
		return $this->passport_number;
	}

	public function getTravelNotes()
	{
		if ( null === $this->travel_notes ) {
			$this->travel_notes = get_post_meta( $this->getPostID(), 'travel_notes', true );
		}
		return $this->travel_notes;
	}

	// Backwards compat
	public function getFlightNumber()
	{
		return $this->getArrivalFlightNumber();
	}

	public function getArrivalFlightNumber()
	{
		if ( null === $this->flight_number ) {
			$this->flight_number = get_post_meta( $this->getPostID(), 'flight_number', true );
		}
		return $this->flight_number;
	}

	public function getDepartureFlightNumber()
	{
		if ( null === $this->departure_flight_number ) {
			$this->departure_flight_number = get_post_meta( $this->getPostID(), 'departure_flight_number', true );
		}
		return $this->departure_flight_number;
	}

	public function getArrivalTimeHour()
	{
		if ( null === $this->arrival_time_hour ) {
			$this->arrival_time_hour = get_post_meta( $this->getPostID(), 'arrival_time_hour', true );
		}
		return $this->arrival_time_hour;
	}

	public function getArrivalTimeMinute()
	{
		if ( null === $this->arrival_time_minute ) {
			$this->arrival_time_minute = get_post_meta( $this->getPostID(), 'arrival_time_minute', true );
		}
		return $this->arrival_time_minute;
	}

	public function getArrivalTimeMeridiem()
	{
		if ( null === $this->arrival_time_meridiem ) {
			$this->arrival_time_meridiem = get_post_meta( $this->getPostID(), 'arrival_time_meridiem', true );
		}
		return $this->arrival_time_meridiem;
	}

	public function getDepartureTimeHour()
	{
		if ( null === $this->departure_time_hour ) {
			$this->departure_time_hour = get_post_meta( $this->getPostID(), 'departure_time_hour', true );
		}
		return $this->departure_time_hour;
	}

	public function getDepartureTimeMinute()
	{
		if ( null === $this->departure_time_minute ) {
			$this->departure_time_minute = get_post_meta( $this->getPostID(), 'departure_time_minute', true );
		}
		return $this->departure_time_minute;
	}

	public function getDepartureTimeMeridiem()
	{
		if ( null === $this->departure_time_meridiem ) {
			$this->departure_time_meridiem = get_post_meta( $this->getPostID(), 'departure_time_meridiem', true );
		}
		return $this->departure_time_meridiem;
	}

	public function getStayLength()
	{
		if ( null === $this->stay_length ) {
			$this->stay_length = get_post_meta( $this->getPostID(), 'stay_length', true );
		}
		return $this->stay_length;
	}

	public function getArrivalDate()
	{
		if ( null === $this->arrival_date ) {
			$this->arrival_date = get_post_meta( $this->getPostID(), 'arrival_date', true );
		}
		return $this->arrival_date;
	}

	public function getDepartureDate()
	{
		if ( null === $this->departure_date ) {
			$this->departure_date = get_post_meta( $this->getPostID(), 'departure_date', true );
		}
		return $this->departure_date;
	}

	// Backwards compat
	public function getAirline()
	{
		return $this->getArrivalAirline();
	}

	public function getArrivalAirline()
	{
		if ( null === $this->airline ) {
			$this->airline = get_post_meta( $this->getPostID(), 'airline', true );
		}
		return $this->airline;
	}

	public function getDepartureAirline()
	{
		if ( null === $this->departure_airline ) {
			$this->departure_airline = get_post_meta( $this->getPostID(), 'departure_airline', true );
		}
		return $this->departure_airline;
	}

	public function getStayLocation()
	{
		if ( null === $this->stay_location ) {
			$stay_location = false;
			$raw = get_post_meta( $this->getPostID(), 'stay_location', true );
			if ( ! empty( $raw ) ) {
				$stay_location = $raw;
			}
			$this->stay_location = $stay_location;
		}
		return $this->stay_location;
	}

	public function setStayLocation( $location_string )
	{
		update_post_meta( $this->getPostID(), 'stay_location', $location_string );
		$this->stay_location = $location_string;
		return $this->stay_location;
	}
	
	public function getStayLocationOther()
	{
		if ( null === $this->stay_location_other ) {
			$stay_location = false;
			$raw = get_post_meta( $this->getPostID(), 'stay_location_other', true );
			if ( ! empty( $raw ) ) {
				$stay_location = $raw;
			}
			$this->stay_location_other = $stay_location;
		}
		return $this->stay_location_other;
	}
	
	public function setStayLocationOther( $location_string )
	{
		update_post_meta( $this->getPostID(), 'stay_location_other', $location_string );
		$this->stay_location_other = $location_string;
		return $this->stay_location_other;
	}

	// Backwards compat
	public function getTravelArrangementsSubmitted()
	{
		return $this->isTravelArrangementsSubmitted();
	}

	public function isTravelArrangementsSubmitted()
	{
		if ( null === $this->travel_arrangements_submitted ) {
			$this->travel_arrangements_submitted = (bool) get_post_meta( $this->getPostID(), 'fxup_guest_travel_arrangements_submitted', true );
		}
		return $this->travel_arrangements_submitted;
	}

	public function getTravelArrangementsStatus()
	{
		if ( null === $this->travel_arrangements_status ) {
			$this->travel_arrangements_status = get_post_meta( $this->getPostID(), 'guest_travel_status', true );
		}
		return $this->travel_arrangements_status;
	}
	
	public function getTravelArrangementsStatusShortLabel()
	{
		$status_map = array(
			'ready' => 'Ready',
			'not_ready' => 'Not Ready',
			'waiting' => 'Waiting',
			'not_going' => 'Not Going',
		);
		
		return isset( $status_map[ $this->getTravelArrangementsStatus() ] ) ? $status_map[ $this->getTravelArrangementsStatus() ] : 'N/A';
	}
	
	public function requiresArrivalTransportation()
	{
		if ( null === $this->requires_arrival_transportation ) {
			if ( metadata_exists( 'post', $this->getPostID(), 'requires_arrival_transportation' ) ) {
				$this->requires_arrival_transportation = (int) get_post_meta( $this->getPostID(), 'requires_arrival_transportation', true );
			} else {
				$this->requires_arrival_transportation = 1;
			}
		}
		return $this->requires_arrival_transportation;
	}
	
	public function requiresDepartureTransportation()
	{
		if ( null === $this->requires_departure_transportation ) {
			if ( metadata_exists( 'post', $this->getPostID(), 'requires_departure_transportation' ) ) {
				$this->requires_departure_transportation = (int) get_post_meta( $this->getPostID(), 'requires_departure_transportation', true );
			} else {
				$this->requires_departure_transportation = 1;
			}
		}
		return $this->requires_departure_transportation;
	}
	
	public function isTravelFinalized()
	{
		if ( $this->getTravelArrangementsStatus() ) {
			return $this->getTravelArrangementsStatus() == 'ready' && (bool) $this->isTravelArrangementsSubmitted();
		}
		return (bool) $this->isTravelArrangementsSubmitted();
	}
	
	public function setHasArrivalTransportation( $value )
	{
		$this->has_arrival_transportation = (bool) $value;
	}
	
	public function hasArrivalTransportation()
	{
		return $this->has_arrival_transportation;
	}
	
	public function setHasDepartureTransportation( $value )
	{
		$this->has_departure_transportation = (bool) $value;
	}
	
	public function hasDepartureTransportation()
	{
		return $this->has_departure_transportation;
	}

	public function setGuestTravelDeadlineNotificationSent( $DateTime )
	{
		if ( $DateTime instanceof \DateTime ) {
			$timestamp = $DateTime->getTimestamp();
			update_post_meta( $this->getPostID(), 'guest_travel_deadline_notification_sent', $timestamp );
			$this->guest_travel_deadline_notification_sent = $DateTime;
		}
		return $this->getGuestTravelDeadlineNotificationSent();
	}

	public function getGuestTravelDeadlineNotificationSent()
	{
		if ( null === $this->guest_travel_deadline_notification_sent ) {
			$sanitized = false;
			$raw = get_post_meta( $this->getPostID(), 'guest_travel_deadline_notification_sent', true );
			if ( $raw instanceof \DateTime ) {
				$sanitized = $raw;
			} elseif ( is_numeric( $raw ) ) {
				// If stored as timestamp
				$sanitized = (new \DateTime() )->setTimestamp( $raw );
			}
			$this->guest_travel_deadline_notification_sent = $sanitized;
		}
		return $this->guest_travel_deadline_notification_sent;
	}

	public function isOnsite()
	{
		if ( null === $this->onsite ) {
			$onsite = true;

			$raw = get_post_meta( $this->getPostID(), 'onsite_stay', true );

			if ( $raw === 'No' ) {
				$onsite = false;
			}

			$this->onsite = $onsite;
		}
		return $this->onsite;
	}

	public function setOnsite( $input )
	{

		// Defaults to 'Yes' and true - there is no "not selected" option
		$meta_onsite = 'Yes';
		$instance_onsite = true;

		if ( 'No' === $input || false === $input ) {
			$meta_onsite = 'No';
			$instance_onsite = false;
			if ( $this->getAssignedRoom() ) {
				$this->getAssignedRoom()->removeGuest( $this ); // Remove Guest from related Room model
			}
			// $this->removeGuestFromItineraryRoomRow(); // If guest is offsite but has an itinerary room row assignment, remove that.
		}

		update_post_meta( $this->getPostID(), 'onsite_stay', $meta_onsite );
		$this->onsite = $instance_onsite;
		return $this->isOnsite();
	}

	public function getDietaryRestrictions()
	{
		if ( null === $this->dietary_restrictions ) {
			$guest_dietary_restrictions = get_post_meta( $this->getPostID(), 'guest_dietary_restrictions', false ); // Multiple meta key/value pairs stored under this meta key. Return array of all/any.
			$this->dietary_restrictions = is_array( $guest_dietary_restrictions ) ? $guest_dietary_restrictions : array(); // Always returns array
		}
		return $this->dietary_restrictions;
	}

	public function setDietaryRestrictions( $array )
	{
		// TODO: Implement to store array of values used for guest_dietary_restrictions meta checkbox - would need to validate to confirm these are the values allowed by Gravity Forms
		return false;
	}

	public function isOtherDietaryRestrictions()
	{
		// Check whether the Other checkbox has been checked and added to list of total Dietary Restrictions
		return in_array( 'Other', $this->getDietaryRestrictions() );
	}

	// BOOL
	public function setOtherDietaryRestrictions( $bool )
	{
		// TODO: Implement to update the 'guest_dietary_restrictions' to add or remove the 'Other' value from the array.
		return false;
	}

	public function getOtherDietaryRestrictionsDetails()
	{
		if ( null === $this->dietary_restriction_other ) {
			$this->dietary_restriction_other = get_post_meta( $this->getPostID(), 'guest_dietary_restriction_other', true );
		}
		return $this->dietary_restriction_other;
	}

	public function setOtherDietaryRestrictionsDetails( $text )
	{
		update_post_meta( $this->getPostID(), 'guest_dietary_restriction_other', $text );
		$this->dietary_restriction_other = $text;
		return $this->dietary_restriction_other;
	}

	public function getDietaryRestrictionsList()
	{
		return ! empty( $this->getDietaryRestrictions() ) ? implode( '|', $this->getDietaryRestrictions() ) : '';
	}
	
	public function getAllergies()
	{
		if ( null === $this->allergies ) {
			$raw = get_post_meta( $this->getPostID(), 'guest_allergies', true );
			$this->allergies = ( ! empty( $raw )) ? $raw : false; // Default to false
		}
		return $this->allergies;
	}

	public function setAllergies( $allergies )
	{
		update_post_meta( $this->getPostID(), 'guest_allergies', $allergies );
		$this->allergies = $allergies;
		return $this->allergies;
	}

	public function hasAssignedRoom()
	{
		return false !== $this->getAssignedRoom();
	}

	public function getAssignedRoom()
	{
		if ( null === $this->Room ) {
			$this->Room = false; // Default to false
			$Villa = $this->getVilla();
			if ( $Villa ) {
				$Rooms = $Villa->getRooms();
				foreach ( $Rooms as $Room ) {
					if ( $Room->hasGuest( $this ) ) {
						$this->Room = $Room;
						break;
					}
				}
			}
		}
		return $this->Room;
	}

	public function setAssignedRoom( $Room )
	{
		// Call this upon calling a Room's setGuest() method
		$this->Room = $Room;
		return $this->Room;
	}

	public function getVilla()
	{
		return $this->hasItinerary() ? $this->getItinerary()->getVilla() : false;
	}

	// Backwards compat
	public function getRoomName()
	{
		return $this->getAssignedRoomName();
	}

	public function getAssignedRoomName()
	{
		$name = ''; // Default to empty string
		if ( $this->getAssignedRoom() ) {
			$name = $this->getAssignedRoom()->getRoomName();
		}
		return $name;
	}

	public function getVillaID()
	{
		$Villa = $this->getItinerary()->getVilla();
		return $Villa->getPostID();
	}

	/* BEGIN ROOM ASSIGNMENT */

	/**
	 * REMOVE:
	 * internalGetItineraryRoomRow
	 * internalUpdateItineraryRoomRow
	 * internalRemoveGuestFromItineraryRoomRow
	 * 
	 * ADD:
	 * getAssignedRoom(): Room
	 */
	/* Virtual */

	public function getTravelLink()
	{
		$itinerary_token = $this->getItinerary()->getToken();
		$travel_link = site_url() . '/guest-travel-info/?itin=' . $itinerary_token . '&guest_id=' . $this->getPostID();
		// http://login.villapuntodevista.com/guest-travel-info/?itin=l8cfqdCzddMsSv6jBFUS&guest_id=1431
		return $travel_link;
	}

	// Virtual
	public function getFullName()
	{
		return trim( $this->getFirstName() . ' ' . $this->getLastName() );
	}

	// Do not cache
	public function getArrivalTime()
	{
		$arrival_time = null; // Return null if fail
		$arrival_time_hour = self::formatGuestTravelTimesHour( $this->getArrivalTimeHour() );
		$arrival_time_minute = self::formatGuestTravelTimesMinute( $this->getArrivalTimeMinute() );
		$arrival_time_meridiem = $this->getArrivalTimeMeridiem();
		$raw_arrival_time = "$arrival_time_hour:$arrival_time_minute $arrival_time_meridiem";
		// '/^[0-9]?[0-9]:[0-9]{2} [aAPp][mM]$/'
		$valid = preg_match( self::getGuestTravelTimesValidationRegex(), $raw_arrival_time );
		if ( $valid ) {
			$arrival_time = $raw_arrival_time;
		}
		return $arrival_time; // Returns null if fail
	}

	// Do not cache
	public function getDepartureTime()
	{
		$departure_time = null; // Return null if fail
		$departure_time_hour = self::formatGuestTravelTimesHour( $this->getDepartureTimeHour() );
		$departure_time_minute = self::formatGuestTravelTimesMinute( $this->getDepartureTimeMinute() );
		$departure_time_meridiem = $this->getDepartureTimeMeridiem();
		$raw_departure_time = "$departure_time_hour:$departure_time_minute $departure_time_meridiem";
		// '/^[0-9]?[0-9]:[0-9]{2} [aAPp][mM]$/'
		$valid = preg_match( self::getGuestTravelTimesValidationRegex(), $raw_departure_time );
		if ( $valid ) {
			$departure_time = $raw_departure_time;
		}
		return $departure_time; // Returns null if fail
	}

	/* Static */

	public static function getGuestTravelHoursOptions()
	{
		if ( null === self::$guest_travel_hours_options ) {
			self::$guest_travel_hours_options = array();
			foreach ( range( 1, 12 ) as $option ) {
				self::$guest_travel_hours_options[] = self::formatGuestTravelTimesHour( $option );
			}
		}
		return self::$guest_travel_hours_options;
	}

	public static function getGuestTravelMinutesOptions()
	{
		if ( null === self::$guest_travel_minutes_options ) {
			self::$guest_travel_minutes_options = array();
			foreach ( range( 0, 59 ) as $option ) {
				self::$guest_travel_minutes_options[] = self::formatGuestTravelTimesMinute( $option );
			}
		}
		return self::$guest_travel_minutes_options;
	}

	public static function getGuestTravelHoursLeadingZero()
	{
		if ( null === self::$guest_travel_hours_leading_zero ) {
			self::$guest_travel_hours_leading_zero = (bool) get_field( 'fxup_guest_travel_hours_leading_zero', 'option' );
		}
		return self::$guest_travel_hours_leading_zero;
	}

	public static function getGuestTravelMinutesLeadingZero()
	{
		if ( null === self::$guest_travel_minutes_leading_zero ) {
			self::$guest_travel_minutes_leading_zero = (bool) get_field( 'fxup_guest_travel_minutes_leading_zero', 'option' );
		}
		return self::$guest_travel_minutes_leading_zero;
	}

	public static function getGuestTravelHoursValidationRegex()
	{
		if ( null === self::$guest_travel_hours_validation_regex ) {
			self::$guest_travel_hours_validation_regex = self::getGuestTravelHoursLeadingZero() ? '[0-1][0-9]' : '[1]?[0-9]';
		}
		return self::$guest_travel_hours_validation_regex;
	}

	public static function getGuestTravelMinutesValidationRegex()
	{
		if ( null === self::$guest_travel_minutes_validation_regex ) {
			self::$guest_travel_minutes_validation_regex = self::getGuestTravelMinutesLeadingZero() ? '[0-5][0-9]' : '[1-5]?[0-9]';
		}
		return self::$guest_travel_minutes_validation_regex;
	}

	public static function getGuestTravelTimesValidationRegex()
	{
		if ( null === self::$guest_travel_times_validation_regex ) {
			$hours_part = self::getGuestTravelHoursValidationRegex();
			$minutes_part = self::getGuestTravelMinutesValidationRegex();
			$regex = '/^' . $hours_part . ':' . $minutes_part . ' [aAPp][mM]$/';
			self::$guest_travel_times_validation_regex = $regex;
		}
		return self::$guest_travel_times_validation_regex;
	}

	public static function formatGuestTravelTimesHour( $timepart )
	{
		if ( is_numeric( $timepart ) ) {
			$format_string = self::getGuestTravelHoursLeadingZero() ? '%02d' : '%d';
			$timepart = sprintf( $format_string, $timepart );
		}

		return $timepart;
	}

	public static function formatGuestTravelTimesMinute( $timepart )
	{
		if ( is_numeric( $timepart ) ) {
			$format_string = self::getGuestTravelMinutesLeadingZero() ? '%02d' : '%d';
			$timepart = sprintf( $format_string, $timepart );
		}

		return $timepart;
	}

	public function toArray()
	{
		return [
			'guest_id' => $this->getPostID(),
			'guest_first_name' => $this->getFirstName(),
			'guest_last_name' => $this->getLastName(),
			'guest_email' => $this->getEmail(),
			'guest_children' => $this->getChildren(),
			'guest_is_child' => $this->isChild(),
			'onsite_stay' => $this->isOnsite(),
			'stay_location' => $this->getStayLocation(),
			'stay_location_other' => $this->getStayLocationOther(),
			'guest_dietary_restrictions' => $this->getDietaryRestrictions(),
			'guest_dietary_restriction_other' => $this->getOtherDietaryRestrictionsDetails(),
			'guest_notes' => $this->getNotes(),
			'guest_allergies' => $this->getAllergies(),
		];
	}
	
	public function toExportArray()
	{
		$data = [
			'guest_id' => $this->getPostID(),
			'guest_first_name' => $this->getFirstName(),
			'guest_last_name' => $this->getLastName(),
			'guest_email' => $this->getEmail(),
			'guest_children' => $this->getChildren(),
			'guest_is_child' => $this->isChild(),
			'guest_notes' => $this->getNotes(),
			'guest_dietary_restrictions' => $this->getDietaryRestrictionsList(),
			'guest_dietary_restriction_other' => $this->getOtherDietaryRestrictionsDetails(),
			'guest_allergies' => $this->getAllergies(),
			'airline' => $this->getAirline(),
			'flight_number' => $this->getArrivalFlightNumber(),
			'arrival_date' => $this->getArrivalDate(),
			'arrival_time_hour' => $this->getArrivalTimeHour(),
			'arrival_time_minute' => $this->getArrivalTimeMinute(),
			'arrival_time_meridiem' => $this->getArrivalTimeMeridiem(),
			'requires_arrival_transportation' => $this->requiresArrivalTransportation(),
			'departure_airline' => $this->getDepartureAirline(),
			'departure_flight_number' => $this->getDepartureFlightNumber(),
			'departure_date' => $this->getDepartureDate(),
			'departure_time_hour' => $this->getDepartureTimeHour(),
			'departure_time_minute' => $this->getDepartureTimeMinute(),
			'departure_time_meridiem' => $this->getDepartureTimeMeridiem(),
			'requires_departure_transportation' => $this->requiresDepartureTransportation(),
			'passport_number' => $this->getPassportNumber(),
			'travel_notes' => $this->getTravelNotes(),
			'onsite_stay' => $this->isOnsite(),
			'stay_location' => $this->getStayLocation(),
			'stay_location_other' => $this->getStayLocationOther(),
			'group_name' => '',
			'account_id' => '',
			'itinerary_id' => '',
		];
		
		if ( $this->hasItinerary() ) {
			$Itinerary = $this->getItinerary();
			$data['group_name'] = $Itinerary->getTitle();
			$data['account_id'] = $Itinerary->getAccountID();
			$data['itinerary_id'] = $Itinerary->getPostID();
		}
		
		return $data;
	}
}
