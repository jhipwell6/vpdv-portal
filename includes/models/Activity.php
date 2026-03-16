<?php

namespace FXUP_User_Portal\Models;

class Activity
{
    private $post_id;
    private $type;
    private $TripDay;
    private static $TripDayClass = 'FXUP_User_Portal\Models\TripDay';
    private $raw_row;
    public static $auto_scheduled_activities;
    public static $standard_booked_time_options; // Will be set when static method is used to access.
    public static $post_activity_types = ['activity', 'service', 'wedding'];
    const NON_POST_ACTIVITY_TYPES = ['custom', 'celebration'];
    const WEDDING_CEREMONY_POST_ID = 1059;
    private $is_wedding;
    private $activity_type_content;
    private $activity_type_max_guests;
    private $activity_type_price_adult;
    private $activity_type_price_child;
    private $display_title;
    private $special_comments;
    private $exact_final_cost;
    private $adult_cost;
    private $child_cost;
    private $time_booked;
    private $is_booked;
    private $is_no_conflict;
    private $is_message_private;
    private $custom_activity_title;
    private $custom_activity_time;
    private $staff_notes;
    private $exact_time_booked;
    private $guests;
    private $child_guests;
    private $specific_guests;
    private $client_confirmation_required;
    private $client_confirmed;
    private $message;
    private $message_options;
    private $punctuality_reminder;
    private $requested_time_options; // Populates in dropdown for non-concierge users, and varies based on Activity type.
    private $private_tour_enabled;
    private $private_tour_price;
    private $private_tour_price_description;
    private $private_tour_requested;
	private $payment_link;
    private $hide_share_price;
	private $is_concierge_only;
	private $keep_private;
	private $created_by;
	private $updated_by;

    public function __construct($index, $TripDay, $options = array(), $raw_data = array() ) {
        // Query the $TripDays raw array based on passed index
        if (!is_numeric($index)) {
            throw new \Exception('Must provide numeric index to Activity constructor');
        }

        if (!($TripDay instanceof self::$TripDayClass)) {
            throw new \Exception('Must provide TripDay instance to Activity constructor');
        }

        $this->index = $index;
        $this->TripDay = $TripDay;
		
		if ( ! empty( $raw_data ) ) {
			$this->setRawRow( $raw_data );
		}
    }

    public static function create($post_id_or_type, $TripDay, $options = array()) {

        // TO DO: Accept options which set the values of props directly. Allow for a "no save" so each update does not trigger database update.

        if (!($TripDay instanceof self::$TripDayClass)) {
            throw new \Exception('Must provide TripDay instance to Activity constructor');
        }

        $index = $TripDay->getActivityCount(); // Returns 1 based count, but we are using 0 based index - so it is already incremented to the new index for us.
        $instance = new self($index, $TripDay, $options);

        if (is_numeric($post_id_or_type)) {
            $instance->setActivityTypePostID($post_id_or_type); // Private method, but static method of same class can access instance private method.
        } elseif ($post_id_or_type === 'celebration') {
            $instance->setActivityType('celebration'); // Private method, but static method of same class can access instance private method.
        } else {
            $instance->setActivityType('custom'); // Private method, but static method of same class can access instance private method.
        }

        // Because we want to avoid updating the database every time a setter is called, allow options to be passed in and set directly on props, bypassing setters.
        // We will save all these props when we finish this call to ::create();

        // Can still access private props/methods on instance from within static method on class.
        if (isset($options['activity_exact_time_booked'])) {
            $instance->setBookedTimeNoSave($options['activity_exact_time_booked']);
        }

        if (isset($options['activity_booked'])) {
            $instance->setBookedNoSave($options['activity_booked']);
        }

        // Activity class cannot call save() method in ::create, because not yet added to TripDay's collection - so would not be persisted along with others.

        // Return and allow TripDay to save all Activities.
        return $instance;
    }

    public function save() {
		$to_raw_row = $this->toRawRow();
        $get_raw_row = $this->getRawRow();
        // Only update the database if there is no difference between current save and last save
        if (!($get_raw_row == $to_raw_row)) {

            // Persist to db
            $this->TripDay->save(); // Call TripDay's save method (will call this instance's toRawRow)
            $this->setRawRow($to_raw_row);

        }
        return $this->getRawRow(); // Updated row
    }

    public function getIndex() {
        return $this->index;
    }

    // Private, because model does not support dynamically changing an Activity's type or post id.
    private function setActivityType($type) {
        $this->type = $type;
        return $this->type;
    }
    
    // Private, because model does not support dynamically changing an Activity's type or post id.
    private function setActivityTypePostID($post_id) {
        $this->post_id = $post_id;
        return $this->post_id;
    }
    
    public function getActivityTypePostID() {
        if (null === $this->post_id) {

            $raw_row = $this->getRawRow();
            $raw_value = isset($raw_row['activity_title']) ? $raw_row['activity_title'] : false;

            // Convert to numeric id if required
            if ($raw_value instanceof \WP_Post) {
                $raw_value = $raw_value->ID;    
            }
    
            $this->post_id = is_numeric($raw_value) ? $raw_value : false;
        }
        return $this->post_id;
    }

    public function hasActivityTypePostID() {
        return !empty($this->getActivityTypePostID());
    }

    public function getActivityType() {
        // Cached
        if (null === $this->type) {
            // All activity types are custom post types except for 'celebration' and 'custom'.
            if ($this->hasActivityTypePostID()) {
                $this->type = get_post_type( $this->getActivityTypePostID() ); // wedding, activity, service
            } else {
                $raw_row = $this->getRawRow();
                // If celebration checkbox is checked, it's a 'celebration' - otherwise, default to 'custom'.
                if(!empty($raw_row['celebration'])) {
                    $this->type = 'celebration';
                } else {
                    $this->type = 'custom';
                }
            }
        }
        // Type can also be set through setter method.
        return $this->type;
    }

    // Backwards compat
    public function hasActivityTypePost()
    {
        return $this->hasActivityTypePostID();
    }

    // Backwards compat
    public function doesNotHaveActivityTypePost()
    {
        return (! $this->hasActivityTypePostID());
    }

    public function isCustom()
    {
        return $this->getActivityType() === 'custom';
    }

    public function isCelebration()
    {
        return $this->getActivityType() === 'celebration';
    }

    public function isWedding()
    {
        // Cached. Assuming we will not be changing an Activity's type or Post ID once set. Otherwise, would need to update internal props.
        if (null === $this->is_wedding) {
            $this->is_wedding = false;
            if ($this->getActivityTypePostID() === self::WEDDING_CEREMONY_POST_ID) {
                $this->is_wedding = true;
            }
        }
        return $this->is_wedding;
    }

    public function getDisplayTitle()
    {
        // Cached. Would only change if we changed the Activity's type/post id, which is not supported. Theoretically could change on setDisplayTitle, but that would be a setter.
        if (null === $this->display_title) {
            if ($this->hasActivityTypePostID()) {
                $display_title = get_the_title( $this->getActivityTypePostID() );
            } elseif (!empty($this->getCustomDisplayTitle())) {
                // The user will not be able to save an empty '' activity title for the display title
                $display_title = $this->getCustomDisplayTitle();
            } else {
                switch ($this->getActivityType()) {
                    case 'custom':
                        $display_title = 'Custom Activity';
                        break;
                    case 'celebration':
                        $display_title = 'Celebration or Event';
                        break;
                    default:
                        $display_title = ''; // Defaults to empty string
                        break;
                }
            }
            $this->display_title = $display_title;
        }
        return $this->display_title;
    }

    public function getMessageOptions()
    {
        // Cached. Would only change if we changed the Activity's type/post id, which is not supported.
        if (null === $this->message_options) {
            $formatted_options = array();
            if ($this->hasActivityTypePostID()) {
                $raw_options = get_field('fxup_event_type_message', $this->getActivityTypePostID());
                $raw_options = is_array($raw_options) ? $raw_options : array();
                foreach ($raw_options as $raw_option) {
                    $formatted_option = array();
                    $formatted_option['title'] = $raw_option['fxup_event_type_message_title'];
                    $formatted_option['body'] = $raw_option['fxup_event_type_message_body'];
                    $formatted_options[] = $formatted_option;
                }
            } else {
                $raw_options = get_field('fxup_custom_event_message', 'options');
                $raw_options = is_array($raw_options) ? $raw_options : array();
                foreach ($raw_options as $raw_option) {
                    $formatted_option = array();
                    $formatted_option['title'] = $raw_option['fxup_custom_event_message_title'];
                    $formatted_option['body'] = $raw_option['fxup_custom_event_message_body'];
                    $formatted_options[] = $formatted_option;
                }
            }
           $this->message_options = $formatted_options;
        }
       return $this->message_options;
    }
	
	public function getPunctualityReminder()
	{
		if ( null === $this->PunctualityReminder ) {
			$this->PunctualityReminder = get_field( 'punctuality_reminder', $this->getActivityTypePostID() );
		}
		return $this->PunctualityReminder;
	}
	
	public function hasPunctualityReminder()
	{
		return (bool) $this->getPunctualityReminder();
	}
	
    public function getActivityTypeContent()
    {
        // Cached
        if (null === $this->activity_type_content && $this->hasActivityTypePostID()) {
            $this->activity_type_content = get_field( 'activity_itinerary_content', $this->getActivityTypePostID() );
        }
        return $this->activity_type_content;
    }

    public function getActivityTypeMaxGuests()
    {
        // Cached
        if (null === $this->activity_type_max_guests && $this->hasActivityTypePostID()) {
            $this->activity_type_max_guests = get_field( 'max_guests', $this->getActivityTypePostID() ) ? get_field( 'max_guests', $this->getActivityTypePostID() ) : 0;
        }
        return $this->activity_type_max_guests;
    }

    public function getActivityTypePriceAdult()
    {
        // Cached
        if (null === $this->activity_type_price_adult && $this->hasActivityTypePostID()) {
            $this->activity_type_price_adult = get_field( 'activity_price_adult', $this->getActivityTypePostID() ) ? get_field( 'activity_price_adult', $this->getActivityTypePostID() ) : 0;
        }
        return $this->activity_type_price_adult;
    }

    public function getActivityTypePriceChild()
    {
        // Cached
        if (null === $this->activity_type_price_child && $this->hasActivityTypePostID()) {
            $this->activity_type_price_child = get_field( 'activity_price_child', $this->getActivityTypePostID() ) ? get_field( 'activity_price_child', $this->getActivityTypePostID() ) : 0;
        }
        return $this->activity_type_price_child;
    }


    public function isClientConfirmationRequired()
    {
        // Cached
        if (null === $this->client_confirmation_required && $this->hasActivityTypePostID()) {
            $this->client_confirmation_required = (bool) get_field('requires_advanced_client_approval', $this->getActivityTypePostID());
        }
        return $this->client_confirmation_required;
    }

    public function getRequestedTimeOptions()
    {
        // Cached - we don't allow changing the Activity Type.
        if (null === $this->requested_time_options) {
            switch ($this->getActivityType()) {
                case 'wedding':
                    $options_array = is_array(get_field('fxup_requested_time_options_wedding', 'options')) ? get_field('fxup_requested_time_options_wedding', 'options') : array();
                    $requested_time_options = array_column($options_array, 'option_value');
                    break;
                case 'activity':
                    $options_array = is_array(get_field('fxup_requested_time_options_activity', 'options')) ? get_field('fxup_requested_time_options_activity', 'options') : array();
                    $requested_time_options = array_column($options_array, 'option_value');
                    break;
                case 'service':
                    $options_array = is_array(get_field('fxup_requested_time_options_service', 'options')) ? get_field('fxup_requested_time_options_service', 'options') : array();
                    $requested_time_options = array_column($options_array, 'option_value');
                    break;
                case 'celebration':
                    $options_array = is_array(get_field('fxup_requested_time_options_celebration', 'options')) ? get_field('fxup_requested_time_options_celebration', 'options') : array();
                    $requested_time_options = array_column($options_array, 'option_value');
                    break;
                case 'custom':
                    $options_array = is_array(get_field('fxup_requested_time_options_custom', 'options')) ? get_field('fxup_requested_time_options_custom', 'options') : array();
                    $requested_time_options = array_column($options_array, 'option_value');
                    break;
                default:
                    $requested_time_options = array();
                    break;
            }
            $this->requested_time_options = is_array($requested_time_options) ? $requested_time_options : array(); // Defaults to array
        }
        return $this->requested_time_options;
    }

    public function isPrivateTourEnabled()
    {
        // Cached
        if (null === $this->private_tour_enabled && $this->hasActivityTypePostID()) {
            $this->private_tour_enabled = (bool) get_field('fxup_private_tour_enabled', $this->getActivityTypePostID());
        }
        return $this->private_tour_enabled;
    }

    public function getPrivateTourPrice()
    {
        if (null === $this->private_tour_price && $this->hasActivityTypePostID()) {
            $raw = get_field('fxup_private_tour_price', $this->getActivityTypePostID());
            $this->private_tour_price = is_numeric($raw) ? $raw : false;
        }
        return $this->private_tour_price;
    }

    public function getPrivateTourPriceDescription()
    {
        if (null === $this->private_tour_price_description && $this->hasActivityTypePostID()) {
            $raw = get_field('fxup_private_tour_price_description', $this->getActivityTypePostID());
            $this->private_tour_price_description = !empty($raw) ? $raw : false;
        }
        return $this->private_tour_price_description;
    }

    public function getPrivateTourPriceString()
    {
        $string = '';
        if ($this->getPrivateTourPrice()) {
            // $30.00
            $string .= '$' . $this->getPrivateTourPrice();
            if ($this->getPrivateTourPriceDescription()) {
                // $30.00 per person
                $string.= ' ' . $this->getPrivateTourPriceDescription();
            }
        }
        // Example return value below:
        // $30.00 per person
        return $string;
    }
	
	public function getPaymentLink()
	{
		if (null === $this->payment_link && $this->hasActivityTypePostID()) {
            $this->payment_link = get_field('fxup_payment_link', $this->getActivityTypePostID());
        }
        return $this->payment_link;
	}
	
	public function hasPaymentLink()
	{
		return (bool) $this->getPaymentLink();
	}

    /**
     * Props pulled directly from related TripDay's ACF repeater row using this Activity's index (to lookup the correct Activity)
     * These should be safe to cache, because if they are changed we would do so via setter method that would update the private prop directly
     * On calling the setter method, we would call a separate method to update/save the TripDay's repeater row.
     * This would build a new raw array of Activity data by converting all required fields on this Activity object into the correct format from the set props.
     * */ 

    // Reads from internal instance state
    public function toRawRow() {
        return array(
            'activity_title' => $this->hasActivityTypePost() ? $this->getActivityTypePostID() : '', // Post Object, but return the post id
            'activity_adults' => (int) $this->getNumberOfAdults(), // Number
            'activity_children' => (int) $this->getNumberOfChildren(), // Number
            'activity_comments' => (string) $this->getSpecialComments(), // Text Area
            'activity_final_cost' => (string) $this->getExactFinalCost(), // Text
            'adult_cost' => (string) $this->getAdultCost(), // Text
            'child_cost' => (string) $this->getChildCost(), // Text
            'activity_time_booked' => (string) $this->getRequestedTime(), // Text
            'activity_booked' => (int) $this->isBooked(), // True/False
            'activity_no_conflict' => (int) $this->isNoConflict(), // True/False
            'message_private' => (int) $this->isMessagePrivate(), // True/False
            'concierge_only' => (int) $this->isConciergeOnly(), // True/False
            'keep_private' => (int) $this->keepPrivate(), // True/False
            'custom_activity_title' => (string) $this->getCustomActivityTitle(), // Text
            'custom_activity_time' => (string) $this->getCustomActivityTime(), // Text
            'staff_notes' => (string) $this->getStaffNotes(), // Text Area
            'activity_exact_time_booked' => (string) $this->getBookedTime(), // Text
            'guests' => implode(',', $this->getGuests()), // Text
            'celebration' => (int) $this->isCelebration(), // True/False 
            'child_guests' => implode(',', $this->getChildGuests()), // Text
            'specific_guests' => (int) $this->isSpecificGuests(), // True/False
            'fxup_activity_client_confirmation_required' => (int) $this->isClientConfirmationRequired(), // True/False
            'fxup_activity_client_confirmed' => (int) $this->isClientConfirmed(), // True/False
            'fxup_activity_client_confirmation_due' => '', // Date Picker
            'fxup_event_message' => (string) $this->getMessage(), // Text Area
            'fxup_private_tour_requested' => (int) $this->isPrivateTourRequested(), // True/False
            'fxup_hide_share_price' => (int) $this->isHideSharePrice(),
            'fxup_created_by' => (int) $this->getCreatedBy(), // User ID
            'fxup_updated_by' => (int) $this->getUpdatedBy(), // User ID
        );
    }

    // Reads from TripDay
    public function getRawRow() {
        // Cached
        if (null === $this->raw_row) {
            $raw_activities = $this->TripDay->getRawActivities();
            $this->raw_row = isset($raw_activities[$this->getIndex()]) ? $raw_activities[$this->getIndex()] : array(); // Default to empty array
        }
        return $this->raw_row;
    }

    private function setRawRow($raw_row) {
        $this->raw_row = $raw_row;
        return $this->raw_row;
    }

    public function getSpecialComments()
    {
        // Cached
        if (null === $this->special_comments) {
            $raw_row = $this->getRawRow();
            $this->special_comments = isset($raw_row['activity_comments']) ? $raw_row['activity_comments'] : false;
        }
        return $this->special_comments;
    }

    public function getExactFinalCost()
    {
        // Cached
        if (null === $this->exact_final_cost) {
            $raw_row = $this->getRawRow();
            $this->exact_final_cost = (isset($raw_row['activity_final_cost']) && is_numeric($raw_row['activity_final_cost'])) ? $raw_row['activity_final_cost'] : false;
        }
        return $this->exact_final_cost;
    }
	
	public function getAdultCost()
    {
        // Cached
        if (null === $this->adult_cost) {
            $raw_row = $this->getRawRow();
            $this->adult_cost = (isset($raw_row['adult_cost']) && is_numeric($raw_row['adult_cost'])) ? $raw_row['adult_cost'] : false;
        }
        return $this->adult_cost;
    }
	
	public function getChildCost()
    {
        // Cached
        if (null === $this->child_cost) {
            $raw_row = $this->getRawRow();
            $this->child_cost = (isset($raw_row['child_cost']) && is_numeric($raw_row['child_cost'])) ? $raw_row['child_cost'] : false;
        }
        return $this->child_cost;
    }

    public function getRequestedTime()
    {
        // Cached
        if (null === $this->time_booked) {
            $raw_row = $this->getRawRow();
            $this->time_booked = isset($raw_row['activity_time_booked']) ? trim($raw_row['activity_time_booked']) : false;
        }
        return $this->time_booked;
    }

    public function isPrivateTourRequested()
    {
        // Cached
        if (null === $this->private_tour_requested) {
            $raw_row = $this->getRawRow();
            $this->private_tour_requested = isset($raw_row['fxup_private_tour_requested']) ? (bool) $raw_row['fxup_private_tour_requested'] : false;
        }
        return $this->private_tour_requested;
    }

    public function isHideSharePrice()
    {
        // Cached
        if (null === $this->hide_share_price) {
            $raw_row = $this->getRawRow();
            $this->hide_share_price = isset($raw_row['fxup_hide_share_price']) ? (bool) $raw_row['fxup_hide_share_price'] : false;
        }
        return $this->hide_share_price;
    }

    public function isNoConflict()
    {
        // Cached
        if (null === $this->is_no_conflict) {
            $raw_row = $this->getRawRow();
            $this->is_no_conflict = isset($raw_row['activity_no_conflict']) ? (bool) $raw_row['activity_no_conflict'] : false;
        }
        return $this->is_no_conflict;
    }

    public function isMessagePrivate()
    {
        // Cached
        if (null === $this->is_message_private) {
            $raw_row = $this->getRawRow();
            $this->is_message_private = (bool) isset($raw_row['message_private']) ? $raw_row['message_private'] : false;
        }
        return $this->is_message_private;
    }
	
	public function isConciergeOnly()
    {
        // Cached
        if (null === $this->is_concierge_only) {
            $raw_row = $this->getRawRow();
            $this->is_concierge_only = (bool) isset($raw_row['concierge_only']) ? $raw_row['concierge_only'] : false;
        }
        return $this->is_concierge_only;
    }
	
	public function keepPrivate()
    {
        // Cached
        if (null === $this->keep_private ) {
            $raw_row = $this->getRawRow();
            $this->keep_private = (bool) isset($raw_row['keep_private']) ? $raw_row['keep_private'] : false;
        }
        return $this->keep_private;
    }
	
	public function getCreatedBy()
    {
        // Cached
        if (null === $this->created_by ) {
            $raw_row = $this->getRawRow();
            $this->created_by = (bool) isset($raw_row['fxup_created_by']) ? $raw_row['fxup_created_by'] : false;
        }
        return $this->created_by;
    }
	
	public function getCreatedByName()
	{
		if ( $this->getCreatedBy() ) {
			$user = get_userdata( $this->getCreatedBy() );
			return ( in_array( 'um_concierge', $user->roles ) ) ? 'Concierge' : $user->display_name;
		}
		return 'N/A';
	}
	
	public function getUpdatedBy()
    {
        // Cached
        if (null === $this->updated_by ) {
            $raw_row = $this->getRawRow();
            $this->updated_by = (bool) isset($raw_row['fxup_updated_by']) ? $raw_row['fxup_updated_by'] : false;
        }
        return $this->updated_by;
    }
	
	public function getUpdatedByName()
	{
		if ( $this->getUpdatedBy() ) {
			$user = get_userdata( $this->getUpdatedBy() );
			return ( in_array( 'um_concierge', $user->roles ) ) ? 'Concierge' : $user->display_name;
		}
		return 'N/A';
	}

    // Backwards compat
    public function getCustomDisplayTitle() {
        return $this->getCustomActivityTitle();
    }

    public function getCustomActivityTitle()
    {
        if (null === $this->custom_activity_title) {
            $raw_row = $this->getRawRow();
            $this->custom_activity_title = isset($raw_row['custom_activity_title']) ? $raw_row['custom_activity_title'] : false;
        }
        return $this->custom_activity_title;
    }

    public function getCustomActivityTime()
    {
        if (null === $this->custom_activity_time) {
            $raw_row = $this->getRawRow();
            $this->custom_activity_time = isset($raw_row['custom_activity_time']) ? $raw_row['custom_activity_time'] : false;
        }
        return $this->custom_activity_time;
    }

    public function getStaffNotes()
    {
        if (null === $this->staff_notes) {
            $raw_row = $this->getRawRow();
            $this->staff_notes = isset($raw_row['staff_notes']) ? $raw_row['staff_notes'] : false;
        }
        return $this->staff_notes;
    }

    public function isBooked()
    {
        // Cached - setter method will update after cached from raw data.
        if (null === $this->is_booked) {
            $raw_row = $this->getRawRow();
            $this->is_booked = isset($raw_row['activity_booked']) ? (bool) $raw_row['activity_booked'] : false;
        }
        return $this->is_booked;
    }

    // Saves to db
    public function setBooked($bool) {
        $is_booked = $this->setBookedNoSave($bool);
        $this->save(); // Notifies TripDay for save
        return $is_booked;
    }

    // Does not save to db - only updates instance state.
    private function setBookedNoSave($bool) {
        $this->is_booked = (bool) $bool;
        return $this->is_booked;
    }

    public function getBookedTime()
    {
        if (null === $this->exact_time_booked) {
            $raw_row = $this->getRawRow();
            $this->exact_time_booked = (isset($raw_row['activity_exact_time_booked']) && 'Select a time' !== $raw_row['activity_exact_time_booked']) ? $raw_row['activity_exact_time_booked'] : false;
        }
        return $this->exact_time_booked;
    }

    // Saves to db
    public function setBookedTime($time) {
        $exact_time_booked = $this->setBookedTimeNoSave($time); // Does not save.
        $this->save(); // Notifies TripDay for save
        return $exact_time_booked;
    }

    // Does not save to db - only updates instance state.
    private function setBookedTimeNoSave($time) {
        if (in_array($time, $this->getBookedTimeOptions())) {
            $this->exact_time_booked = $time;
        } else {
            $this->exact_time_booked = false;
        }
        return $this->exact_time_booked;
    }

    public function getGuests()
    {
        if (null === $this->guests) {
            $raw_row = $this->getRawRow();
            $raw_guests = (isset($raw_row['guests']) && is_string($raw_row['guests'])) ? $raw_row['guests'] : false;
            $this->guests = (!empty($raw_guests)) ? explode(',', $raw_guests) : array(); // Always defaults to empty array
        }
        return $this->guests; // Always returns array
    }


    public function getChildGuests()
    {
        // Should be safe to cache, because if the child guests changes, we would update this internal prop via a setter method.
        if (null === $this->child_guests) {
            $this->child_guests = array(); // init to empty array
            $raw_row = $this->getRawRow();
            $raw_child_guests = (isset($raw_row['child_guests']) && is_string($raw_row['child_guests'])) ? $raw_row['child_guests'] : false;
            $untrimmed_child_guests = (!empty($raw_child_guests)) ? explode(',', $raw_child_guests) : array();
            // Remove any surrounding whitespace
            foreach ($untrimmed_child_guests as $untrimmed_child_guest) {
                $child_guest = trim($untrimmed_child_guest);
                if (!empty($child_guest)) {
                    $this->child_guests[] = $child_guest;
                }
            }
        }
        return $this->child_guests; // Always returns array.
    }

    public function isSpecificGuests()
    {
        // Cached
        if (null === $this->specific_guests) {
            $raw_row = $this->getRawRow();
            $this->specific_guests = isset($raw_row['specific_guests']) ? (bool) $raw_row['specific_guests'] : false;
        }
        return $this->specific_guests;
    }

    public function isClientConfirmed()
    {
        // Cached
        if (null === $this->client_confirmed) {
            $raw_row = $this->getRawRow();
            $this->client_confirmed = isset($raw_row['fxup_activity_client_confirmed']) ? (bool) $raw_row['fxup_activity_client_confirmed'] : false;
        }
        return $this->client_confirmed;
    }

    public function getMessage()
    {
        if (null === $this->message) {
            $raw_row = $this->getRawRow();
            $this->message = isset($raw_row['fxup_event_message']) ? $raw_row['fxup_event_message'] : false;
        }
        return $this->message;
    }

    /* Internal Price Logic - none of these fields should be cached. They should always recalculate (because they depend on other set fields) */
    public function getTotalCostAdults()
    {
        return $this->getNumberOfAdults() * $this->getActivityTypePriceAdult();
    }

    public function getTotalCostChildren()
    {
        return $this->getNumberOfChildren() * $this->getActivityTypePriceChild();
    }

    public function getTotalCost()
    {
        return $this->getTotalCostAdults() + $this->getTotalCostChildren();
    }
    
    public function getSortTime()
    {
        // Do not cache - depends on BookedTime and RequestedTime, which can be set. Otherwise, you could update the SortTime whenever those setters are called.
        // Always store as a DateTime or null
        $sort_time = null;

        // h:i A
        // 8:00 AM
        $format = 'h:i A';

        $raw_time_string = '';

        // If is booked
        if ($this->isBooked()) {
            $raw_time_string = $this->getBookedTime();
        } else {
            // If not is booked
            $raw_time_string = $this->getRequestedTime();
        }

        // Try to create a DateTime from what user has selected
        $attempt = \DateTime::createFromFormat($format, $raw_time_string);

        if ($attempt instanceof \DateTime) {
            // If is a valid formatted date string
            $sort_time = $attempt;
        } else {
            // If user input not a valid formatted date string

            // Try to get lookup table for specific activity type

            // Identify which table to use
            $field_name = null;

            switch ($this->getActivityType()) {
                case 'wedding':
                    $field_name = 'fxup_requested_time_options_wedding';
                    break;
                case 'activity':
                    $field_name = 'fxup_requested_time_options_activity';
                    break;
                case 'service':
                    $field_name = 'fxup_requested_time_options_service';
                    break;
                case 'celebration':
                    $field_name = 'fxup_requested_time_options_celebration';
                    break;
                case 'custom':
                    $field_name = 'fxup_requested_time_options_custom';
                    break;
                default:
                    break;
            }

            if (!empty($field_name)) {
                // Get lookup table
                $lookup_table = get_field($field_name, 'option');
                $lookup_table = (is_array($lookup_table) && !empty($lookup_table)) ? $lookup_table : array();
                // Try to find raw string in lookup table
                foreach ($lookup_table as $row) {
                    // Option must match user input for time
                    if (isset($row['option_value']) && $row['option_value'] === $raw_time_string) {
                        if (isset($row['custom_sorting']) && is_string($row['custom_sorting'])) {
                            // If a custom sorting option exists, try to parse it
                            $attempt = \DateTime::createFromFormat($format, $row['custom_sorting']);
                            if ($attempt instanceof \DateTime) {
                                // If is a valid formatted date string
                                $sort_time = $attempt;
                            }
                        }
                        // Don't bother continuing upon finding an exact match for the user input
                        break;
                    }
                }
            }
        }
        // Return
        return $sort_time;
    }

    /* Virtual fields */

    public function getNumberOfAdults()
    {
        // Do not cache, because number of adults can be changed dynamically on the Itinerary

        // If no specific guests have been set, that means ALL the guests going to the Itinerary are attending
        if (false === $this->isSpecificGuests()) {
            $TripDay = $this->TripDay;
            $Itinerary = $TripDay->getItinerary();
            $number_of_adults = count($Itinerary->getGuests());
        } else {
            $number_of_adults = count($this->getGuests());
        }
        return $number_of_adults;
    }

    public function getNumberOfChildren()
    {
        // Do not cache, because depends on other field which can be set.
        $number_of_children = count($this->getChildGuests());
        return $number_of_children;
    }

    public function getBookedTimeOptions() {
        return self::getStandardBookedTimeOptions(); // Right now, there is no variance between the activity types.
    }

    public static function getStandardBookedTimeOptions() {
        if (null === self::$standard_booked_time_options) {
            $standard_booked_time_options = array();
            $start = '00:00';
            $end = '23:45';
            $t_start = strtotime($start);
            $t_end = strtotime($end);
            $t_now = $t_start;
            while ($t_now <= $t_end) {
                $standard_booked_time_options[] = date("g:i A", $t_now);
                $t_now = strtotime('+15 minutes', $t_now); // This is what will break the loop
            }
            self::$standard_booked_time_options = $standard_booked_time_options;
        }
        return self::$standard_booked_time_options; // Keep this cached - there are so many Activities, that we don't want to be running above calculation repeatedly.
    }

    /* Auto Scheduled */

    public static function getAutoScheduledActivities() {

        // Cache on static
        if (null === self::$auto_scheduled_activities) {

            $query = new \WP_Query( 
                array( 
                    'post_type' => array('activity', 'service', 'wedding'),
                    'posts_per_page' => -1,
                    'meta_query' => array( // meta_query MUST contain nested arrays, even if only one query expected.
                        array(
                            'key'	  	=> 'fxup_event_type_auto_schedule_daily', // field_60dc777a26319 is meta key for ACF field fxup_event_type_auto_schedule_daily
                            'value'	  	=> '1',
                            'compare' 	=> '=',
                        )
                    ),
                )
            );
    
            $posts = is_array($query->posts) ? $query->posts : array();
    
            wp_reset_query();
    
            $auto_scheduled_activities =  wp_list_pluck( $posts, 'ID'); // IDs only
            
            // Cache on static
            self::$auto_scheduled_activities = is_array($auto_scheduled_activities) ? $auto_scheduled_activities : array();
        }

        return self::$auto_scheduled_activities;
    
    }

    public static function getPostActivityTypes() {
        return self::$post_activity_types;
    }
	
	public function recentUpdateCount()
	{
		$total_updates = 0;
		$match = array();
		if ( ! empty( $this->TripDay->getPreviousActivities() ) ) {
			// check all updates to see if this one has an update
			foreach ( $this->TripDay->getPreviousActivities() as $TripDay_Activities ) {
				if ( isset( $TripDay_Activities[ $this->index ] ) ) {
					$matches[] = $TripDay_Activities[ $this->index ];
				}
			}
		}
		
		if ( ! empty( $matches ) ) {
			$raw_row = $this->toRawRow();
			foreach ( $matches as $i => $update ) {
				$new_instance = new self( $i, $this->TripDay, array(), $update );
				if ( $raw_row != $new_instance->toRawRow() ) {
					$total_updates++;
				}
			}
		}
		
		FXUP_USER_PORTAL()->debug_log( 'recentUpdateCount:', 'update count', $total_updates );
		return $total_updates;
	}
	
	public function hasRecentUpdate()
	{
		return $this->recentUpdateCount() > 0;
	}
	
	public function getTitleForEmail()
	{
		return ( $this->hasRecentUpdate() ) ? '<span style="background-color:yellow;">' . $this->getDisplayTitle() . '</span>' : $this->getDisplayTitle();
	}
}
