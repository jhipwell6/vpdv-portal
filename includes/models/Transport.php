<?php

namespace FXUP_User_Portal\Models;

class Transport
{
    private $type;
	private $date;
    private $status;
    private $company;
    private $pickup_time;
    private $pickup_location;
    private $dropoff_location;
    private $mode;
    private $notes;
    private $cost;
	private $guests = array();
    private static $GuestClass = 'FXUP_User_Portal\Models\Guest';
	private $guest_objects;
	private $Itinerary;
	private static $ItineraryClass = 'FXUP_User_Portal\Models\Itinerary';
	private $row_number_itinerary;
    private $raw_row_itinerary;
	private $self_save_itinerary;
	private $is_client_transports;
	
	public function __construct( $index, $type, $Itinerary, $raw_row_itinerary = false, $is_client_transports = false ) {

		$this->setType( $type );
		$this->setRowNumberItinerary( $index );
		$this->setItinerary( $Itinerary );
		$this->setIsClientTransports( $is_client_transports );
		
		if ( $raw_row_itinerary ) {
			$this->setRawRowItinerary( $raw_row_itinerary );
		}
		
        return $this;
    }
	
	public function getType()
    {
        return $this->type;
    }
	
	public function setType( $type )
    {
        $this->type = $type;
		return $this->type;
    }
	
	public function setIsClientTransports( $is_client_transports )
	{
		$this->is_client_transports = $is_client_transports;
		return $this->is_client_transports;
	}
	
	public function getDate()
    {
        // Cached
        if ( null === $this->date ) {
            $raw_row_itinerary = $this->getRawRowItinerary();
            // Watch for whether the return format is an array
            $date = isset( $raw_row_itinerary['date'] ) ? $raw_row_itinerary['date'] : false;
			if ( ! ( $date instanceof \DateTime ) ) {
				$date = new \DateTime( $date );
			}
			$this->date = $date;
        }
        return $this->date;
    }
	
	public function setDate( $date )
    {
        $this->date = new \DateTime( $date );
		return $this->date;
    }
	
	public function getStatus()
    {
        // Cached
        if ( null === $this->status ) {
            $raw_row_itinerary = $this->getRawRowItinerary();
            // Watch for whether the return format is an array
            $this->status = isset( $raw_row_itinerary['status'] ) ? $raw_row_itinerary['status'] : false;
        }
        return $this->status;
    }
	
	public function setStatus( $status )
    {
        $this->status = $status;
		return $this->status;
    }
	
	public function isApproved()
	{
		return $this->getStatus() == 'Approved' ? true : false;
	}
	
	public function getCompany()
    {
        // Cached
        if ( null === $this->company ) {
            $raw_row_itinerary = $this->getRawRowItinerary();
            // Watch for whether the return format is an array
            $this->company = isset( $raw_row_itinerary['company'] ) ? $raw_row_itinerary['company'] : false;
        }
        return $this->company;
    }
	
	public function setCompany( $company )
    {
        $this->company = $company;
		return $this->company;
    }
	
	public function getPickupTime()
    {
        // Cached
        if ( null === $this->pickup_time ) {
            $raw_row_itinerary = $this->getRawRowItinerary();
            // Watch for whether the return format is an array
            $this->pickup_time = isset( $raw_row_itinerary['pickup_time'] ) ? $raw_row_itinerary['pickup_time'] : false;
        }
        return $this->pickup_time;
    }
	
	public function setPickupTime( $pickup_time )
    {
        $this->pickup_time = $pickup_time;
		return $this->pickup_time;
    }
	
	public function getPickupLocation()
    {
        // Cached
        if ( null === $this->pickup_location ) {
            $raw_row_itinerary = $this->getRawRowItinerary();
            // Watch for whether the return format is an array
            $this->pickup_location = isset( $raw_row_itinerary['pickup_location'] ) ? $raw_row_itinerary['pickup_location'] : false;
        }
        return $this->pickup_location;
    }
	
	public function setPickupLocation( $pickup_location )
    {
        $this->pickup_location = $pickup_location;
		return $this->pickup_location;
    }
	
	public function getDropoffLocation()
    {
        // Cached
        if ( null === $this->dropoff_location ) {
            $raw_row_itinerary = $this->getRawRowItinerary();
            // Watch for whether the return format is an array
            $this->dropoff_location = isset( $raw_row_itinerary['dropoff_location'] ) ? $raw_row_itinerary['dropoff_location'] : false;
        }
        return $this->dropoff_location;
    }
	
	public function setDropoffLocation( $dropoff_location )
    {
        $this->dropoff_location = $dropoff_location;
		return $this->dropoff_location;
    }
	
	public function getMode()
    {
        // Cached
        if ( null === $this->mode ) {
            $raw_row_itinerary = $this->getRawRowItinerary();
            // Watch for whether the return format is an array
            $this->mode = isset( $raw_row_itinerary['mode'] ) ? $raw_row_itinerary['mode'] : false;
        }
        return $this->mode;
    }
	
	public function setMode( $mode )
    {
        $this->mode = $mode;
		return $this->mode;
    }
	
	public function getNotes()
    {
        // Cached
        if ( null === $this->notes ) {
            $raw_row_itinerary = $this->getRawRowItinerary();
            // Watch for whether the return format is an array
            $this->notes = isset( $raw_row_itinerary['notes'] ) ? $raw_row_itinerary['notes'] : false;
        }
        return $this->notes;
    }
	
	public function setNotes( $notes )
    {
        $this->notes = $notes;
		return $this->notes;
    }
	
	public function getCost()
    {
        // Cached
        if ( null === $this->cost ) {
            $raw_row_itinerary = $this->getRawRowItinerary();
            // Watch for whether the return format is an array
            $this->cost = isset( $raw_row_itinerary['cost'] ) ? $raw_row_itinerary['cost'] : false;
        }
        return $this->cost;
    }
	
	public function setCost( $cost )
    {
        $this->cost = $cost;
		return $this->cost;
    }
	
	public function getGuests()
	{
		// Cached
        if ( empty( $this->guests ) ) {
            $raw_row_itinerary = $this->getRawRowItinerary();
            // Watch for whether the return format is an array
            $this->guests = isset( $raw_row_itinerary['guests'] ) ? $raw_row_itinerary['guests'] : [];
			$key = array_search( 0, $this->guests );
			if ( $key !== false ) {
				unset( $this->guests[ $key ] );
			}
        }
        return $this->guests;
	}
	
	public function getGuestObjects( $orderby_method = 'getLastName' )
	{
		if ( null === $this->guest_objects || $orderby_method != 'getLastName' ) {
			$guest_ids = $this->getGuests();
			if ( ! empty( $guest_ids ) ) {
				$this->guest_objects = [];
				foreach ( $guest_ids as $guest_id ) {
					$Guest = new self::$GuestClass( $guest_id );
					$this->guest_objects[] = $Guest;
				}
				$this->sortGuestsByName( $orderby_method );
			}
		}
		return $this->guest_objects;
	}
	
	public function addGuest( $guest_id )
	{
		if ( ! $this->hasThisGuest( $guest_id ) ) {
			// add to guest id array
			$this->guests[] = $guest_id;
			
			// add to guest_objects
			$Guest = new self::$GuestClass( $guest_id );
			$this->guest_objects[] = $Guest;
		}
	}
	
	public function removeGuest( $guest_id )
	{
		$guests = $this->getGuests();

		// remove from guest id array
		if ( ( $key = array_search( $guest_id, $guests ) ) !== false ) {
			unset( $guests[ $key ] );
			$this->guests = $guests;
		}

		// remove from guest_objects
		$guest_objects = $this->getGuestObjects();
		if ( $this->hasThisGuestObject() && ( $key = array_usearch( $guest_objects, function( $Guest ) use( $guest_id ) {
			return $Guest->getPostID() == $guest_id;
		} ) ) !== false ) {
			unset( $guest_objects[ $key ] );
			$this->guests_objects = $guest_objects;
		}
	}
	
	public function hasThisGuest( $this_Guest_ID )
	{
		$hasGuest = array_filter( $this->getGuests(), function( $guest_id ) use ( $this_guest_ID ) {
			return $guest_id == $this_guest_ID;
		});
		
		return ! empty( $hasGuest ) ? true : false;
	}
	
	public function hasThisGuestObject( $this_Guest )
	{
		$hasGuest = array_filter( $this->getGuestObjects(), function( $Guest ) use ( $this_Guest ) {
			return $Guest->getPostID() == $this_Guest->getPostID();
		});
		
		return ! empty( $hasGuest ) ? true : false;
	}
	
	public function hasValidDates()
	{
		$valid = false;
		if ( ! empty( $this->getGuestObjects() ) ) {
			foreach ( $this->getGuestObjects() as $Guest ) {
				if ( $Guest->isTravelFinalized() ) {
					if ( $this->getType() == 'arrival' && (bool) $Guest->getArrivalTime() ) {
						$valid = true;
						break;
					}
					
					if ( $this->getType() == 'departure' && (bool) $Guest->getDepartureTime() ) {
						$valid = true;
						break;
					}
				}
			}
		}
		
		return $valid;
	}
	
	public function getGuestCount()
	{
		$count = 0;
		if ( empty( $this->getGuests() ) ) {
			return $count;
		}
		
		foreach ( $this->getGuestObjects() as $Guest ) {
			$count++;
		}
		
		return $count;
	}
	
	private function sortGuestsByName( $orderby_method = 'getLastName' )
	{
		// Sort alphabetically
		if ( ! empty( $this->guest_objects ) && $this->getGuestCount() > 1 ) {
			usort( $this->guest_objects, function( $GuestOne, $GuestTwo ) use ( $orderby_method ) {
				return $GuestOne->{$orderby_method}() < $GuestTwo->{$orderby_method}() ? -1 : ( $GuestOne->{$orderby_method}() > $GuestTwo->{$orderby_method}() ? 1 : 0 );
			});
		}
	}
	
	/* BEGIN ITINERARY */
	
	public function setItinerary( $Itinerary )
	{
        if ( $Itinerary instanceof self::$ItineraryClass ) {
            $this->Itinerary = $Itinerary;
        } else {
            $this->Itinerary = false;
        }
        return $this->Itinerary;
    }

    public function getItinerary()
	{
        if ( null === $this->Itinerary ) {
            $this->setItinerary( false );
        }
        return $this->Itinerary;
    }

    public function getRowNumberItinerary()
	{
        return is_numeric( $this->row_number_itinerary ) ? $this->row_number_itinerary : false;
    }

    // 1 based index
    public function setRowNumberItinerary( $row_number_itinerary )
	{
        $this->row_number_itinerary = $row_number_itinerary;
        return $this->row_number_itinerary;
    }

    public function getRawRowItinerary()
	{
        if ( null === $this->raw_row_itinerary ) {
            $this->parseRawRowItinerary();
        }
        return is_array( $this->raw_row_itinerary ) ? $this->raw_row_itinerary : array();
    }

    public function setRawRowItinerary( $raw_row_itinerary )
	{
        $this->raw_row_itinerary = $raw_row_itinerary;
        return $this->raw_row_itinerary;
    }

    // 1 based index
    private function parseRawRowItinerary()
	{
        $raw_rows_itinerary = $this->getItinerary()->getRawTransports();
		$raw_row_itinerary = $raw_rows_itinerary[ $this->getType() ][ $this->getRowNumberItinerary() ];
		$this->setRawRowItinerary( $raw_row_itinerary );
    }
    
    // Never cache this
    public function toRawRowItinerary() {

        $raw_row_itinerary = array(
            'date' => (string) $this->getDate()->format('m/d/Y'),
            'status' => (string) $this->getStatus(),
			'company' => (string) $this->getCompany(),
            'pickup_time' => $this->getPickupTime(),
            'pickup_location' => (string) $this->getPickupLocation(),
            'dropoff_location' => (string) $this->getDropoffLocation(),
            'mode' => (string) $this->getMode(),
            'guests' => (array) $this->getGuests(),
            'notes' => (string) $this->getNotes(),
            'cost' => (string) $this->getCost(),
        );

        return $raw_row_itinerary;
    }
	
	// Never cache this
	public function toJsonRawRowItinerary() {
		
		return json_encode( $this->toRawRowItinerary() );
	}

    public function isSelfSaveItinerary() {
        if ( null === $this->self_save_itinerary ) {
            $this->self_save_itinerary = true; // Default to true if not set for some reason
        }
        return $this->self_save_itinerary;
    }

    public function setSelfSaveItinerary( $bool ) {
        $this->self_save_itinerary = (bool) $bool;
        return $this->self_save_itinerary;
    }

    // Call this when using "setter" methods to update any of the Transport fields which are attached to the Itinerary object.
    public function saveToItinerary( $force_update = false ) {
        // Only save to db if saving has not been disabled by caller.
        if ( $this->isSelfSaveItinerary() ) {
            $to_raw_row_itinerary = $this->toRawRowItinerary();
            $get_raw_row_itinerary = $this->getRawRowItinerary();
            // Only update the database if there is no difference between current save and last save
            if ( ! ( $get_raw_row_itinerary == $to_raw_row_itinerary ) || $force_update ) {
                // Persist to db
				$field_name = $this->is_client_transports ? $this->getType() . '_transportation_client' : $this->getType() . '_transportation';
                update_row( $field_name, $this->getRowNumberItinerary() + 1, $to_raw_row_itinerary, $this->getItinerary()->getPostID() );
                $this->setRawRowItinerary( $to_raw_row_itinerary );
            }
        }
        return $this->getRawRowItinerary(); // Updated row
    }
	
	public function deleteFromItinerary()
	{
		$field_name = $this->is_client_transports ? $this->getType() . '_transportation_client' : $this->getType() . '_transportation';
		delete_row( $field_name, $this->getRowNumberItinerary() + 1, $this->getItinerary()->getPostID() );
	}
}