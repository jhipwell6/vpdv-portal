<?php

namespace FXUP_User_Portal\Migrations;

class ChildGuestMigration
{
	private $stats = array();

	public function __construct()
	{
		$this->stats = $this->emptyStats();
	}

	public function runMigration()
	{
		$this->stats = $this->emptyStats();

		$query = new \WP_Query( array(
			'post_type' => 'itinerary',
			'post_status' => 'any',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'no_found_rows' => true,
		) );

		$itinerary_ids = is_array( $query->posts ) ? $query->posts : array();
		$this->stats['itineraries_scanned'] = count( $itinerary_ids );

		foreach ( $itinerary_ids as $itinerary_id ) {
			$this->migrateItineraryChildren( (int) $itinerary_id );
		}

		return $this->stats;
	}

	public function migrateItineraryChildren( $itinerary_id )
	{
		$itinerary_id = (int) $itinerary_id;
		if ( $itinerary_id <= 0 || ! get_post( $itinerary_id ) ) {
			return $this->stats;
		}

		$legacy = $this->detectLegacyChildren( $itinerary_id );
		if ( ! $legacy['has_legacy'] ) {
			return $this->stats;
		}

		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itinerary_id );
		$this->migrateGuestChildrenCounts( $Itinerary );
		$this->migrateRoomChildren( $itinerary_id );

		return $this->stats;
	}

	public function migrateRoomChildren( $itinerary_id )
	{
		$itinerary_id = (int) $itinerary_id;
		$raw_rooms = get_field( 'room_guests', $itinerary_id );
		if ( ! is_array( $raw_rooms ) || empty( $raw_rooms ) ) {
			return 0;
		}

		$rows_updated = 0;
		$itinerary_child_guests = $this->getChildGuestsForItinerary( $itinerary_id );

		foreach ( $raw_rooms as $row_index => $raw_room ) {
			$row_changed = false;
			$guest_indexes = $this->extractGuestIndexesFromRow( $raw_room );

			foreach ( $guest_indexes as $guest_index ) {
				$guest_key = 'guest_' . $guest_index;
				$legacy_child_key = 'guest_' . $guest_index . '_child';
				$legacy_child_name_key = 'guest_' . $guest_index . '_child_name';

				$legacy_child_flag = ! empty( $raw_room[ $legacy_child_key ] );
				$legacy_child_name = isset( $raw_room[ $legacy_child_name_key ] ) ? trim( (string) $raw_room[ $legacy_child_name_key ] ) : '';

				if ( ! $legacy_child_flag && '' === $legacy_child_name ) {
					continue;
				}

				$current_slot_guest_id = isset( $raw_room[ $guest_key ] ) ? (int) $raw_room[ $guest_key ] : 0;
				if ( $current_slot_guest_id > 0 ) {
					$current_slot_guest = get_post( $current_slot_guest_id );
					if ( $current_slot_guest ) {
						// Keep existing valid entity assignment and only clear legacy placeholders.
						unset( $raw_room[ $legacy_child_key ], $raw_room[ $legacy_child_name_key ] );
						$row_changed = true;
						$this->stats['legacy_fields_cleared'] += 2;
						continue;
					}
				}

				$ChildGuest = $this->findMatchingChildGuest( $itinerary_child_guests, $legacy_child_name );
				if ( ! $ChildGuest && '' !== $legacy_child_name ) {
					$ChildGuest = $this->createChildGuestFromName( $itinerary_id, $legacy_child_name );
					if ( $ChildGuest ) {
						$itinerary_child_guests[] = $ChildGuest;
						$this->stats['child_guests_created']++;
					}
				}

				if ( $ChildGuest ) {
					$raw_room[ $guest_key ] = $ChildGuest->getPostID();
					$this->stats['room_placeholders_converted']++;
					$row_changed = true;
				}

				unset( $raw_room[ $legacy_child_key ], $raw_room[ $legacy_child_name_key ] );
				$row_changed = true;
				$this->stats['legacy_fields_cleared'] += 2;
			}

			if ( $row_changed ) {
				$raw_rooms[ $row_index ] = $raw_room;
				$rows_updated++;
			}
		}

		if ( $rows_updated > 0 ) {
			update_field( 'room_guests', $raw_rooms, $itinerary_id );
		}

		return $rows_updated;
	}

	public function detectLegacyChildren( $itinerary_id )
	{
		$itinerary_id = (int) $itinerary_id;
		$result = array(
			'has_legacy' => false,
			'legacy_guest_children' => false,
			'legacy_room_children' => false,
		);

		$guest_query = new \WP_Query( array(
			'post_type' => 'guest',
			'post_status' => 'any',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'no_found_rows' => true,
			'meta_query' => array(
				array(
					'key' => 'itinerary_id',
					'value' => $itinerary_id,
					'compare' => '=',
				),
			),
		) );

		foreach ( $guest_query->posts as $guest_id ) {
			$is_child = (bool) get_post_meta( $guest_id, 'guest_is_child', true );
			if ( $is_child ) {
				continue;
			}

			$legacy_child_count = (int) get_post_meta( $guest_id, 'guest_children', true );
			if ( $legacy_child_count > 0 ) {
				$result['has_legacy'] = true;
				$result['legacy_guest_children'] = true;
				break;
			}
		}

		$raw_rooms = get_field( 'room_guests', $itinerary_id );
		if ( is_array( $raw_rooms ) ) {
			foreach ( $raw_rooms as $raw_room ) {
				foreach ( $raw_room as $key => $value ) {
					if ( false !== strpos( $key, '_child' ) && ! empty( $value ) ) {
						$result['has_legacy'] = true;
						$result['legacy_room_children'] = true;
						break 2;
					}
				}
			}
		}

		return $result;
	}

	public function getStats()
	{
		return $this->stats;
	}

	private function migrateGuestChildrenCounts( $Itinerary )
	{
		foreach ( $Itinerary->getAdultGuests() as $Guest ) {
			$legacy_children_count = (int) $Guest->getChildren();
			if ( $legacy_children_count <= 0 ) {
				continue;
			}

			$existing_children_for_parent = array_filter( $Itinerary->getChildGuests(), function ( $ChildGuest ) use ( $Guest ) {
				return (int) get_post_meta( $ChildGuest->getPostID(), 'guest_parent_id', true ) === (int) $Guest->getPostID();
			} );

			$children_to_create = max( 0, $legacy_children_count - count( $existing_children_for_parent ) );
			for ( $index = 1; $index <= $children_to_create; $index++ ) {
				$child_display_index = count( $existing_children_for_parent ) + $index;
				$child_full_name = trim( $Guest->getFirstName() . ' Child ' . $child_display_index . ' ' . $Guest->getLastName() );
				$ChildGuest = $this->findChildGuestByName( $Itinerary->getPostID(), $child_full_name );

				if ( ! $ChildGuest ) {
					$ChildGuest = $this->createChildGuestFromName( $Itinerary->getPostID(), $child_full_name, $Guest->getPostID() );
					if ( $ChildGuest ) {
						$this->stats['child_guests_created']++;
					}
				}

				if ( $ChildGuest ) {
					$existing_children_for_parent[] = $ChildGuest;
				}
			}

			update_post_meta( $Guest->getPostID(), 'guest_children', 0 );
			$this->stats['legacy_fields_cleared']++;
		}
	}

	private function getChildGuestsForItinerary( $itinerary_id )
	{
		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itinerary_id );
		return $Itinerary->getChildGuests();
	}

	private function findMatchingChildGuest( $child_guests, $child_full_name )
	{
		$needle = strtolower( trim( (string) $child_full_name ) );
		if ( '' === $needle ) {
			return false;
		}

		foreach ( $child_guests as $ChildGuest ) {
			if ( strtolower( trim( $ChildGuest->getFullName() ) ) === $needle ) {
				return $ChildGuest;
			}
		}

		return false;
	}

	private function findChildGuestByName( $itinerary_id, $child_full_name )
	{
		$itinerary_id = (int) $itinerary_id;
		$child_full_name = trim( (string) $child_full_name );
		if ( '' === $child_full_name ) {
			return false;
		}

		$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $itinerary_id );
		return $this->findMatchingChildGuest( $Itinerary->getChildGuests(), $child_full_name );
	}

	private function createChildGuestFromName( $itinerary_id, $child_full_name, $parent_guest_id = 0 )
	{
		$child_full_name = trim( (string) $child_full_name );
		if ( '' === $child_full_name ) {
			return false;
		}

		$name_parts = preg_split( '/\s+/', $child_full_name );
		$first_name = array_shift( $name_parts );
		$last_name = implode( ' ', $name_parts );

		$new_guest_id = wp_insert_post( array(
			'post_type' => 'guest',
			'post_status' => 'publish',
			'post_title' => get_the_title( $itinerary_id ) . ' - ' . $child_full_name,
		) );

		if ( ! $new_guest_id || is_wp_error( $new_guest_id ) ) {
			return false;
		}

		update_post_meta( $new_guest_id, 'itinerary_id', (int) $itinerary_id );
		update_post_meta( $new_guest_id, 'guest_first_name', $first_name );
		update_post_meta( $new_guest_id, 'guest_last_name', $last_name );
		update_post_meta( $new_guest_id, 'guest_is_child', 1 );
		if ( $parent_guest_id > 0 ) {
			update_post_meta( $new_guest_id, 'guest_parent_id', (int) $parent_guest_id );
		}

		return new \FXUP_User_Portal\Models\Guest( $new_guest_id );
	}

	private function extractGuestIndexesFromRow( $raw_room )
	{
		$indexes = array();
		foreach ( $raw_room as $key => $value ) {
			if ( preg_match( '/^guest_(\d+)(?:_child(?:_name)?)?$/', $key, $matches ) ) {
				$indexes[] = (int) $matches[1];
			}
		}
		$indexes = array_unique( $indexes );
		sort( $indexes );
		return $indexes;
	}

	private function emptyStats()
	{
		return array(
			'itineraries_scanned' => 0,
			'child_guests_created' => 0,
			'room_placeholders_converted' => 0,
			'legacy_fields_cleared' => 0,
		);
	}
}

