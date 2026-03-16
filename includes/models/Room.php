<?php

namespace FXUP_User_Portal\Models;

class Room {

    private static $DefaultAllowedGuests = 2;
    private $room_name;
    private $Villa;
    private $SubVilla;
    private $Itinerary;
    private $Guests;
    private static $VillaClass = 'FXUP_User_Portal\Models\Villa';
    private static $ItineraryClass = 'FXUP_User_Portal\Models\Itinerary';
    private static $GuestClass = 'FXUP_User_Portal\Models\Guest';
    private $row_number_villa;
    private $raw_row_villa;
    private $floor_location_text;
    private $room_image;
    private $floor_notes;
    private $extra_rooms;
    private $room_color;
    private $floor_plan;
    private $row_number_itinerary;
    private $raw_row_itinerary;
    private $are_guests_children;
    private $guest_child_names;
    private $bed_configuration;
    private $pack_and_play;
    private $additional_guest;
    private $has_extra_beds;
    private $is_accessible;
    private $special_requests;
    private $self_save_itinerary;

    // Room Name and SubVilla are NOT optional. This is the only way to look up the correct row in the Villa/Itinerary repeaters.
    // Villa is required if the Villa has SubVillas.
    // This is because Room Name is NOT a unique field - must pair with the "Villa" (SubVilla) field to get correct row.
    public function __construct($room_name, $SubVilla, $Villa = null, $Itinerary = null, $options = array()) {
        // Room Name and sub_villa are the keys used to find the room in the Villa repeater field
        if (!($SubVilla instanceof self::$VillaClass)) {
            throw new \Exception('Must pass an instance of Villa to Room constructor');
        }

        $this->SubVilla = $SubVilla; // Set the Subvilla.

        if ($Villa instanceof self::$VillaClass) {
            $this->Villa = $Villa;
        } else {
            // If Villa is null or not valid, assume that SubVilla and Villa are the same
            $this->Villa = $SubVilla;
        }

        $this->room_name = $room_name; // This is a very important key.

        $this->setItinerary($Itinerary); // This is optional

        return $this;
    }

    public static function create($room_name, $SubVilla, $Villa = null, $options = array())
    {
        // You would insert the Room to db here
        // $instance = new self($room_name, $SubVilla, $Villa = null, $options);

        // $instance->saveToVilla();

        // return $instance;
    }

    public function setItinerary($Itinerary) {
        if ($Itinerary instanceof self::$ItineraryClass) {
            $this->Itinerary = $Itinerary;
        } else {
            $this->Itinerary = false;
        }
        return $this->Itinerary;
    }

    public function getItinerary() {
        if (null === $this->Itinerary) {
            $this->setItinerary(false);
        }
        return $this->Itinerary;
    }

    // This is required, so just get it. Constructor will set it even if not passed.
    public function getVilla() {
        return $this->Villa;
    }

    // This is required, so just get it. Constructor will set it even if not passed.
    public function getSubVilla() {
        return $this->SubVilla;
    }

    // This is required, so just get it.
    public function getRoomName() {
        return $this->room_name;
    }

    public function getFormKey() {
        return str_replace(' ', '_' , $this->getRoomName()) . '-' . $this->getSubVilla()->getPostID(); // Example: Red_Room-102
    }

    /* BEGIN VILLA */

    public function getRowNumberVilla() {
        if (null === $this->row_number_villa) {
            $this->parseRawRowVilla(); // This method also sets raw_row
        }
        return is_numeric($this->row_number_villa) ? $this->row_number_villa : false;
    }

    // 1 based index
    public function setRowNumberVilla($row_number_villa) {
        $this->row_number_villa = $row_number_villa;
        return $this->row_number_villa;
    }

    public function getRawRowVilla() {
        if (null === $this->raw_row_villa) {
            $this->parseRawRowVilla(); // This method also sets row_number
        }
        return is_array($this->raw_row_villa) ? $this->raw_row_villa : array();
    }

    public function setRawRowVilla($raw_row_villa) {
        $this->raw_row_villa = $raw_row_villa;
        return $this->raw_row_villa;
    }

    // 1 based index
    private function parseRawRowVilla() {
        $raw_rows_villa = $this->getVilla()->getRawRooms();
        foreach ($raw_rows_villa as $index => $raw_row_villa) {
            $raw_row_room_name = isset($raw_row_villa['room_name']) ? $raw_row_villa['room_name'] : null;
            $raw_row_sub_villa_id = isset($raw_row_villa['room_villa']) ? $raw_row_villa['room_villa'] : null;
            if ($raw_row_sub_villa_id instanceof \WP_Post) {
                $raw_row_sub_villa_id = $raw_row_sub_villa_id->ID;
            }
            if ($this->getRoomName() === $raw_row_room_name && $this->getSubVilla()->getPostID() === $raw_row_sub_villa_id) {
                $this->setRowNumberVilla($index + 1); // 1 based (ACF)
                $this->setRawRowVilla($raw_row_villa);
                break;
            }
        }
    }
    
    // Never cache this
    public function toRawRowVilla() {

        $raw_row_villa = array(
            'room_name' => (string) $this->getRoomName(),
            'floor_location_text' => (string) $this->getFloorLocationText(),
            'room_image' => is_array($this->getRoomImage()) ? $this->getRoomImage() : false,
            'floor_notes' => (string) $this->getFloorNotes(),
            'extra_rooms' => (int) $this->getExtraRooms(),
            'has_extra_beds' => (int) $this->getHasExtraBeds(),
            'accessible' => (int) $this->getIsAccessible(),
            'room_villa' => (string) $this->getSubVilla()->getPostID(),
            'room_color' => (string)  $this->getRoomColor(),
            'floor_plan' => (string)  $this->getFloorPlan(),
        );

        return $raw_row_villa;
    }

    // Call this when using "setter" methods to update any of the Room fields which are attached to the Villa object.
    public function saveToVilla() {
        $to_raw_row_villa = $this->toRawRowVilla();
        $get_raw_row_villa = $this->getRawRowVilla();
        // Only update the database if there is no difference between current save and last save
        if (!($get_raw_row_villa == $to_raw_row_villa)) {
            // Persist to db
            update_row('room', $this->getRowNumberVilla(), $to_raw_row_villa, $this->getVilla()->getPostID());
            $this->setRawRowVilla($to_raw_row_villa);
        }
        return $this->getRawRowVilla(); // Updated row
    }

    public function getFloorLocationText() {
        // Cached
        if (null === $this->floor_location_text) {
            $raw_row_villa = $this->getRawRowVilla();
            $this->floor_location_text = isset($raw_row_villa['floor_location_text']) ? $raw_row_villa['floor_location_text'] : false;
        }
        return $this->floor_location_text;
    }

    public function getRoomImage() {
        // Cached
        if (null === $this->room_image) {
            $raw_row_villa = $this->getRawRowVilla();
            $this->room_image = isset($raw_row_villa['room_image']) ? $raw_row_villa['room_image'] : false;
        }
        return $this->room_image;
    }

    public function getFloorNotes() {
        // Cached
        if (null === $this->floor_notes) {
            $raw_row_villa = $this->getRawRowVilla();
            $this->floor_notes = isset($raw_row_villa['floor_notes']) ? $raw_row_villa['floor_notes'] : false;
        }
        return $this->floor_notes;
    }

    public function getExtraRooms() {
        // Cached
        if (null === $this->extra_rooms) {
            $raw_row_villa = $this->getRawRowVilla();
            $this->extra_rooms = isset($raw_row_villa['extra_rooms']) ? $raw_row_villa['extra_rooms'] : false;
        }
        return $this->extra_rooms;
    }
	
	public function getHasExtraBeds() {
        // Cached
        if (null === $this->has_extra_beds) {
            $raw_row_villa = $this->getRawRowVilla();
            $this->has_extra_beds = isset($raw_row_villa['has_extra_beds']) ? $raw_row_villa['has_extra_beds'] : false;
        }
        return $this->has_extra_beds;
    }
	
	public function getIsAccessible() {
        // Cached
        if (null === $this->is_accessible) {
            $raw_row_villa = $this->getRawRowVilla();
            $this->is_accessible = isset($raw_row_villa['accessible']) ? $raw_row_villa['accessible'] : false;
        }
        return $this->is_accessible;
    }

    public function getRoomColor() {
        // Cached
        if (null === $this->room_color) {
            $raw_row_villa = $this->getRawRowVilla();
            $this->room_color = isset($raw_row_villa['room_color']) ? $raw_row_villa['room_color'] : false;
        }
        return $this->room_color;
    }
	
	public function getFloorPlan() {
        // Cached
        if (null === $this->floor_plan) {
            $raw_row_villa = $this->getRawRowVilla();
            $this->floor_plan = isset($raw_row_villa['floor_plan']) ? $raw_row_villa['floor_plan'] : false;
        }
        return $this->floor_plan;
    }

    /* END VILLA */

    /* BEGIN ITINERARY */

    public function getRowNumberItinerary() {
        if (null === $this->row_number_itinerary) {
            $this->parseRawRowItinerary(); // This method also sets raw_row
        }
        return is_numeric($this->row_number_itinerary) ? $this->row_number_itinerary : false;
    }

    // 1 based index
    public function setRowNumberItinerary($row_number_itinerary) {
        $this->row_number_itinerary = $row_number_itinerary;
        return $this->row_number_itinerary;
    }

    public function getRawRowItinerary() {
        if (null === $this->raw_row_itinerary) {
            $this->parseRawRowItinerary(); // This method also sets row_number
        }
        return is_array($this->raw_row_itinerary) ? $this->raw_row_itinerary : array();
    }

    public function setRawRowItinerary($raw_row_itinerary) {
        $this->raw_row_itinerary = $raw_row_itinerary;
        return $this->raw_row_itinerary;
    }

    // 1 based index
    private function parseRawRowItinerary() {
        $raw_rows_itinerary = $this->getItinerary()->getRawRooms();
        foreach ($raw_rows_itinerary as $index => $raw_row_itinerary) {
            $raw_row_room_name = isset($raw_row_itinerary['room_name']) ? $raw_row_itinerary['room_name'] : null;
            $raw_row_sub_itinerary_id = isset($raw_row_itinerary['room_guests_villa_id']) ? $raw_row_itinerary['room_guests_villa_id'] : null;
            if ($raw_row_sub_itinerary_id instanceof \WP_Post) {
                $raw_row_sub_itinerary_id = $raw_row_sub_itinerary_id->ID;
            }
            if ($this->getRoomName() === $raw_row_room_name && $this->getSubVilla()->getPostID() === $raw_row_sub_itinerary_id) {
                $this->setRowNumberItinerary($index + 1); // 1 based (ACF)
                $this->setRawRowItinerary($raw_row_itinerary);
                break;
            }
        }
    }
    
    // Never cache this
    public function toRawRowItinerary() {

        $raw_row_itinerary = array(
            'room_name' => (string) $this->getRoomName(),
            'bed_configuration' => (string) $this->getBedConfiguration(),
            'pack_and_play' => (int) $this->isPackAndPlay(),
            'guest_1' => $this->getGuest(1) ? (string) $this->getGuest(1)->getPostID(): '',
            'guest_2' => $this->getGuest(2) ? (string) $this->getGuest(2)->getPostID(): '',
            'guest_3' => $this->getGuest(3) ? (string) $this->getGuest(3)->getPostID(): '',
            'additional_guest' => (int) $this->isAdditionalGuest(),
            'special_requests' => (string) $this->getSpecialRequests(),
            'guest_4' => $this->getGuest(4) ? (string) $this->getGuest(4)->getPostID(): '',
            'room_guests_villa_id' => (string) $this->getSubVilla()->getPostID(),
        );

        return $raw_row_itinerary;
    }

    public function isSelfSaveItinerary() {
        if (null === $this->self_save_itinerary) {
            $this->self_save_itinerary = true; // Default to true if not set for some reason
        }
        return $this->self_save_itinerary;
    }

    public function setSelfSaveItinerary($bool) {
        $this->self_save_itinerary = (bool) $bool;
        return $this->self_save_itinerary;
    }

    // Call this when using "setter" methods to update any of the Room fields which are attached to the Itinerary object.
    public function saveToItinerary() {
        // Only save to db if saving has not been disabled by caller.
        if ($this->isSelfSaveItinerary()) {
            $to_raw_row_itinerary = $this->toRawRowItinerary();
            $get_raw_row_itinerary = $this->getRawRowItinerary();
            // Only update the database if there is no difference between current save and last save
            if (!($get_raw_row_itinerary == $to_raw_row_itinerary)) {
                // Persist to db
                update_row('room_guests', $this->getRowNumberItinerary(), $to_raw_row_itinerary, $this->getItinerary()->getPostID());
                $this->setRawRowItinerary($to_raw_row_itinerary);
            }
        }
        return $this->getRawRowItinerary(); // Updated row
    }

    public function getBedConfiguration() {
        // Cached
        if (null === $this->bed_configuration) {
            $raw_row_itinerary = $this->getRawRowItinerary();
            // Watch for whether the return format is an array
            // $this->bed_configuration = (!empty($raw_row_itinerary['bed_configuration'][0])) ? $raw_row_itinerary['bed_configuration'] : false;
            $this->bed_configuration = isset($raw_row_itinerary['bed_configuration']) ? $raw_row_itinerary['bed_configuration'] : false;
        }
        return $this->bed_configuration;
    }

    public function setBedConfiguration($bed_configuration) {
        $this->bed_configuration = $bed_configuration;
        $this->saveToItinerary(); // Save to DB
        return $this->bed_configuration;
    }

    public function isPackAndPlay() {
        // Cached
        if (null === $this->pack_and_play) {
            $raw_row_itinerary = $this->getRawRowItinerary();
            $this->pack_and_play = isset($raw_row_itinerary['pack_and_play']) ? (bool) $raw_row_itinerary['pack_and_play'] : false;
        }
        return $this->pack_and_play; // Bool
    }

    public function setPackAndPlay($bool) {
        $this->pack_and_play = (bool) $bool;
        $this->saveToItinerary();
        return $this->pack_and_play;
    }

    public function getTotalAllowedGuests() {
        return self::getDefaultAllowedGuests() + (int) $this->getExtraRooms();
    }
	
	public function hasGuests() {
		return ! empty( $this->getGuests() ) ? true : false;
	}

    public function hasGuest($Guest) {
        if (! $Guest instanceof self::$GuestClass) {
            throw new \Exception('Must pass instance of Guest to Room::hasGuest');
        }
        $guest_index = false; // Default to false, otherwise - return the index where the Guest is.
        foreach ($this->getGuests() as $index => $RoomGuest) {
            if ($RoomGuest->getPostID() === $Guest->getPostID()) {
                $guest_index = $index;
                break;
            }
        }
        return $guest_index;
    }
	
	public function getGuestList() {
		$list = array();
		if ( $this->hasGuests() ) {
			foreach ( $this->getGuests() as $index => $RoomGuest ) {
				$list[] = $RoomGuest->getFullName();
			}
		}
		return implode( ', ', array_filter( $list ) );
	}

    public function getGuests() {
        if (!is_array($this->Guests) || count($this->Guests) < $this->getTotalAllowedGuests()) {
            //  This will be a 1 based index loop.
            for ($i = 1; $i <= $this->getTotalAllowedGuests(); ++$i) {
                $this->getGuest($i); // 1 based. Method updates internal state to add to internal array.
            }
        }
        // Array keys are preservered - so if there were "gaps", you might see ([1] => $Guest, [3] => $Guest) etc.
        return array_filter($this->Guests, function($Guest) { return (bool) $Guest; }); // Returns array stripped of any gaps.
    }

    public function getGuest($index) {
        // Cached
        if (!isset($this->Guests[$index])) {
            if (!is_array($this->Guests)) {
                $this->Guests = array();
            }
            $Guest = false; // Default to false
            $key = 'guest_' . $index; // 1 based
            $raw_row_itinerary = $this->getRawRowItinerary();
            $raw_guest_id = isset($raw_row_itinerary[$key]) ? $raw_row_itinerary[$key] : false;
            if ($raw_guest_id && get_post($raw_guest_id)) {
                $Guest = new self::$GuestClass($raw_guest_id);
            } else {
                // Backwards compatibility only - should be removed at earliest possible convenience
                $split = explode(' ', $raw_guest_id);
                if (count($split) > 1) {
                    $room_guest_first_name = trim($split[0]);
                    $room_guest_last_name = trim($split[1]);
                    // Try to get guest by name
                    $ItineraryGuests = $this->getItinerary()->getGuests();
                    foreach ($ItineraryGuests as $ItineraryGuest) {
                        $itinerary_guest_first_name = trim($ItineraryGuest->getFirstName());
                        $itinerary_guest_last_name = trim($ItineraryGuest->getLastName());
                        if ($room_guest_first_name === $itinerary_guest_first_name && $room_guest_last_name === $itinerary_guest_last_name) {
                            $Guest = $ItineraryGuest; // Match
                        }
                    }
                }
            }
            $this->Guests[$index] = $Guest;
        }
        return $this->Guests[$index];
    }

    public function setGuest($Guest, $index) {
        // $index should be 1 based
        if (! $Guest instanceof self::$GuestClass) {
            throw new \Exception('Must pass instance of Guest to Room::setGuest');
        }

        // Replace any existing guest
        if ($this->getGuest($index)) {
            $this->removeGuest($index);
        }

        $this->Guests[$index] = $Guest;
        $Guest->setAssignedRoom($this);

        $this->saveToItinerary(); // Update db

        return $index; // Return index for guest
    }

    public function removeGuest($guest_or_numeric_index) {

        $removed = false;
        // Convert to index
        if ($guest_or_numeric_index instanceof self::$GuestClass) {
            $PassedGuest = $guest_or_numeric_index;
            $index = $this->hasGuest($PassedGuest);
        } else {
            $index = $guest_or_numeric_index;
        }

        if (is_numeric($index)) {
            $RoomGuest = $this->getGuest($index);
            if ($RoomGuest instanceof self::$GuestClass) {
                $this->Guests[$index] = false; // Do not unset, because it will try to go back to the raw array from Itinerary in DB.
                // unset($this->Guests[$index]); // Remove Guest from internal state array
                $RoomGuest->setAssignedRoom(false); // Update Guest model to know it is no longer assigned.
                $removed = true;
            }
        }

        $this->saveToItinerary(); // Update db

        return $removed; // bool
    }

    public function areGuestsChildren() {
        $are_guests_children = array();
        for ($i = 1; $i <= $this->getTotalAllowedGuests(); ++$i) {
            $are_guests_children[$i] = $this->isGuestChild($i);
        }
        return $are_guests_children;
    }

    public function isGuestChild($index) {
        $Guest = $this->getGuest($index);
        if ($Guest instanceof self::$GuestClass) {
            return (bool) $Guest->isChild();
        }

        // Backwards compatibility for legacy room rows that stored child placeholders.
        // @deprecated legacy child storage
        $key = 'guest_' . $index . '_child';
        $raw_row_itinerary = $this->getRawRowItinerary();
        return isset($raw_row_itinerary[$key]) ? (bool) $raw_row_itinerary[$key] : false;
    }

    public function setGuestChild($bool, $index) {
        // Legacy no-op to preserve backwards compatibility with old save calls.
        return $index;
    }

    public function getGuestChildNames() {
        return array();
    }

    public function getGuestChildName($index) {
        return false;
    }

    public function setGuestChildName($name, $index) {
        // Legacy no-op to preserve backwards compatibility with old save calls.
        return $index;
    }

    public function isAdditionalGuest() {
        // Cached
        if (null === $this->additional_guest) {
            $raw_row_itinerary = $this->getRawRowItinerary();
            $this->additional_guest = isset($raw_row_itinerary['additional_guest']) ? (bool) $raw_row_itinerary['additional_guest'] : false;
        }
        return $this->additional_guest;
    }

    public function setAdditionalGuest($bool) {
        $bool = (bool) $bool;
        $this->additional_guest = (bool) $bool;
        if (!$bool) {
            // If just removed additional guest capability, make sure to remove any Adult guests who are assigned to those slots so that they become available for other assignment.
            for ($i = self::getDefaultAllowedGuests() + 1; $i <= $this->getTotalAllowedGuests(); ++$i) {
                $this->removeGuest($i);
            }
        }
        $this->saveToItinerary();
        return $this->additional_guest;
    }

    public function getSpecialRequests() {
        // Cached
        if (null === $this->special_requests) {
            $raw_row_itinerary = $this->getRawRowItinerary();
            $this->special_requests = isset($raw_row_itinerary['special_requests']) ? $raw_row_itinerary['special_requests'] : false;
        }
        return $this->special_requests;
    }

    public function setSpecialRequests($special_requests) {
        $this->special_requests = $special_requests;
        $this->saveToItinerary();
        return $this->special_requests;
    }



    /* END ITINERARY */

    /* STATIC */

    public static function getDefaultAllowedGuests() {
        return (int) self::$DefaultAllowedGuests;
    }
}