<?php

namespace FXUP_User_Portal\Models;

class TripDay
{
	private $Itinerary;
	private $DateTime;
	private $ItineraryClass = 'FXUP_User_Portal\Models\Itinerary';
	private $ActivityClass = 'FXUP_User_Portal\Models\Activity';
	private $raw_row;
	private $row_number;
	private $activity_objects;
	private $previous_activities;
	private static $day_counter = 1;

	public function addActivity( $activity_id_or_type, $options = array() )
	{

		// Returns instance of Activity
		// Activity class cannot call save method in ::create, because not yet added to TripDay's collection - so would not be persisted along with others.
		$ActivityObject = $this->ActivityClass::create( $activity_id_or_type, $this, $options );

		$this->activity_objects[] = $ActivityObject;

		// When bulk adding Activities, may want to delay calling internal save() method until all are added to reduce db calls.
		if ( ! empty( $options['no_save'] ) ) {
			$this->save();
		}

		return $ActivityObject;
	}

	public function getRawActivities()
	{
		$raw_row = $this->getRawRow();
		$raw_activities = ( ! empty( $raw_row['trip_day_activities'] ) && is_array( $raw_row['trip_day_activities'] )) ? $raw_row['trip_day_activities'] : array();
		return $raw_activities; // Defaults to empty array
	}

	public function getActivities()
	{
		// Cached
		if ( null === $this->activity_objects ) {
			$this->activity_objects = array(); // Init to array so this is cached even if no results.
			// Hopefully, this can be improved upon sometime in the future.
			foreach ( $this->getRawActivities() as $numeric_index_key => $raw_activity ) {

				// $index, $TripDay, $options = array()
				$ActivityObject = new $this->ActivityClass( $numeric_index_key, $this ); // Uses numeric index to read from raw repeater array.
				$this->activity_objects[] = $ActivityObject;
			}
			$this->sortActivities(); // Sort on output
		}
		return is_array( $this->activity_objects ) ? $this->activity_objects : array(); // Always return array
	}

	/* BEGIN FINISHED */

	public function __construct( $DateTime, $Itinerary, $options = array() )
	{
		if ( ! $DateTime instanceof \DateTime ) {
			throw new \Exception( "Must provide instance of DateTime as first arg to TripDay constructor" );
		}
		if ( ! $Itinerary instanceof $this->ItineraryClass ) {
			throw new \Exception( "Must provide instance of Itinerary as second arg to TripDay constructor" );
		}
		$this->DateTime = $DateTime;
		$this->Itinerary = $Itinerary;
		// Turn DateTimeObject into string to be used for lookup in repeater row
		// Get the row number

		return $this;
	}

	public static function create( $DateTime, $Itinerary, $options = array() )
	{
		$instance = new self( $DateTime, $Itinerary, $options );

		// Set Activities (automated ones)
		$instance->autoScheduleActivities();
		self::$day_counter ++;

		$instance->save();

		return $instance;
	}

	public function save()
	{
		$to_raw_row = $this->toRawRow();
		$get_raw_row = $this->getRawRow();
		// Only update the database if there is no difference between current save and last save
		if ( ! ($get_raw_row == $to_raw_row) ) {
			// Persist to db
			update_row( 'itinerary_trip_days', $this->getRowNumber(), $to_raw_row, $this->getItinerary()->getPostID() );
			$this->setRawRow( $to_raw_row );
		}
		return $this->getRawRow(); // Updated row
	}

	public function activitiesToArray()
	{
		// Sort Activities before saving to db.
		$this->sortActivities(); // Sort on insert
		$raw_activities = array();
		foreach ( $this->getActivities() as $Activity ) {
			$raw_activities[] = $Activity->toRawRow();
		}
		return $raw_activities;
	}

	// Never cache this
	public function toRawRow()
	{

		$raw_row = array(
			'trip_day' => $this->getDateStringKey(), // This should be a string in this format: January 20, 2022
			'trip_day_activities' => $this->activitiesToArray(), // Empty array, would be able to use methods to add activities onto here
//            'trip_day_previous_activities' => $this->rawActivitiesToSerializedArray(), // Empty array, would be able to use methods to add activities onto here
		);

		return $raw_row;
	}

	public function getRawRow()
	{
		if ( null === $this->raw_row ) {
			$this->parseRawRow(); // This method also sets row_number
		}
		return is_array( $this->raw_row ) ? $this->raw_row : array();
	}

	public function setRawRow( $raw_row )
	{
		$this->raw_row = $raw_row;
		return $this->raw_row;
	}

	public function getRowNumber()
	{
		if ( null === $this->row_number ) {
			$this->parseRawRow(); // This method also sets raw_row
		}
		return is_numeric( $this->row_number ) ? $this->row_number : null;
	}

	// 1 based index
	public function setRowNumber( $row_number )
	{
		$this->row_number = $row_number;
		return $this->row_number;
	}

	// 1 based index
	private function parseRawRow()
	{
		$raw_itinerary_trip_days = $this->getItinerary()->getRawTripDays();
		foreach ( $raw_itinerary_trip_days as $index => $raw_row ) {
			if ( isset( $raw_row['trip_day'] ) && $raw_row['trip_day'] === $this->getDateStringKey() ) {
				$this->setRowNumber( $index + 1 ); // 1 based
				$this->setRawRow( $raw_row );
				break;
			}
		}
	}

	public function getPreviousActivities()
	{
		if ( null === $this->previous_activities ) {
			$raw_row = $this->getRawRow();
			$this->previous_activities = ( ! empty( $raw_row['trip_day_previous_activities'] ) && is_array( $raw_row['trip_day_previous_activities'] )) ? $raw_row['trip_day_previous_activities'] : array();
		}
		return $this->previous_activities; // Defaults to empty array
	}

	private function sortActivities()
	{
		// Maintain the numerical index keys
		if ( is_array( $this->activity_objects ) ) {
			uasort( $this->activity_objects, function ( $a_activity, $b_activity ) {

				// If user has not selected an activity, getSortTime will return null. null is < DateTime in comparison.
				$a_datetime = $a_activity->getSortTime();
				$b_datetime = $b_activity->getSortTime();

				return ($a_datetime < $b_datetime) ? -1 : (($a_datetime > $b_datetime) ? 1 : 0);
			} );
		}
	}

	private function autoScheduleActivities()
	{
		$auto_scheduled_activities_daily = $this->ActivityClass::getAutoScheduledActivities();
		if ( is_array( $auto_scheduled_activities_daily ) ) {
			foreach ( $auto_scheduled_activities_daily as $activity_post_id ) {
				$fxup_event_type_auto_schedule_daily_time = get_field( 'fxup_event_type_auto_schedule_daily_time', $activity_post_id );
				$fxup_event_type_auto_schedule_daily_time = ! empty( $fxup_event_type_auto_schedule_daily_time ) ? $fxup_event_type_auto_schedule_daily_time : '';
				$options = array(
					'activity_exact_time_booked' => $fxup_event_type_auto_schedule_daily_time,
					'activity_booked' => true,
					'no_save' => true, // Used by TripDay method to delay saving
				);

				// Execute here
//                if ( ( self::$day_counter > $this->getItinerary()->getTripDayCount() ) || ( self::$day_counter === 1 && $activity_post_id === 4210 ) ) { // don't add breakfast on the first day
				if ( self::$day_counter === 1 && $activity_post_id === 4210 ) {
					// do nothing
					continue;
				} else {
					$ActivityObject = $this->addActivity( $activity_post_id, $options ); // Pass as many options as possible into the ::create() method to minimize calls to db. 
				}
			}
			$this->save(); // Wait to save until all Activities have been added.
		}
	}

	public function getDateTime()
	{
		return $this->DateTime;
	}

	public function getDateStringKey()
	{
		return self::formatDateStringKey( $this->getDateTime() );
	}

	public static function formatDateStringKey( $DateTimeObject )
	{
		return $DateTimeObject->format( 'F j, Y' );
	}

	public function getItinerary()
	{
		return $this->Itinerary;
	}

	public function getActivityCount()
	{
		$activities = $this->getActivities();
		return count( $activities );
	}

	public function getPublicActivityCount()
	{
		$count = 0;
		$activities = $this->getActivities();
		foreach ( $activities as $Activity ) {
			if ( $Activity->keepPrivate() ) {
				continue;
			}
			$count ++;
		}
		return $count;
	}

	public function isWeddingDay()
	{
		$is_wedding_day = false;

		$wedding_date = $this->getItinerary()->getWeddingDate();

		if ( 'object' === gettype( $wedding_date ) && 'DateTime' === get_class( $wedding_date ) ) {
			$interval_to_wedding_date = $this->getDateTime()->diff( $wedding_date );
			$is_wedding_day = (0 === $interval_to_wedding_date->days); // Only if today is the wedding, set to true
		}
		return $is_wedding_day;
	}

	public function hasWeddingCeremony()
	{
		$has_wedding_ceremony = false;
		$activities = $this->getActivities();
		foreach ( $activities as $Activity ) {
			if ( $Activity->isWedding() ) {
				$has_wedding_ceremony = true;
				break;
			}
		}
		return $has_wedding_ceremony;
	}

	public function hasCelebration()
	{
		$has_celebration = false;
		$activities = $this->getActivities();
		foreach ( $activities as $Activity ) {
			if ( $Activity->isCelebration() ) {
				$has_celebration = true;
				break;
			}
		}
		return $has_celebration;
	}

	public function hasCustom()
	{
		$has_custom = false;
		$activities = $this->getActivities();
		foreach ( $activities as $Activity ) {
			if ( $Activity->doesNotHaveActivityTypePost() ) {
				$has_custom = true;
				break;
			}
		}
		return $has_custom;
	}

	public function isAllActivitiesBooked()
	{
		$is_all_activities_booked = false;
		$activities = $this->getActivities();
		foreach ( $activities as $Activity ) {
			$is_all_activities_booked = true;
			if ( true !== $Activity->isBooked() ) {
				$is_all_activities_booked = false;
				break;
			}
		}
		return $is_all_activities_booked;
	}

	public function getTotalCost()
	{
		$total_cost = 0;
		$activities = $this->getActivities();
		foreach ( $activities as $Activity ) {
			// $total_cost += $Activity->getTotalCost(); // Use this if you want to calculate based on number of guests and activity pricing
			if ( true === $Activity->isBooked() ) {
				$total_cost += (is_numeric( $Activity->getExactFinalCost() ) ? $Activity->getExactFinalCost() : 0); // This is manually keyed in by concierge
			}
		}
		return $total_cost;
	}

	public function setItineraryClass( $ItineraryClass )
	{
		$this->ItineraryClass = $ItineraryClass;
	}

	public function setActivityClass( $ActivityClass )
	{
		$this->ActivityClass = $ActivityClass;
	}

}
