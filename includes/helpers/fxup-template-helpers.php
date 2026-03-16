<?php 



function get_edit_trip_date_time( $trip_start, $trip_end ) {

    $edit_day_cutoff_interval_in_days = (int) get_field('fxup_edit_day_cutoff_interval', 'option');

    // Set default timezone to Costa Rica time
    date_default_timezone_set('America/Costa_Rica');
    
    $trip_date_info = array();

    $current_date = date('F j, Y');
    $current_date_formatted = date_create($current_date);
    $trip_start_date = date_create($trip_start);
    $trip_end_date = date_create($trip_end);
    $interval = date_diff( $trip_start_date, $current_date_formatted );
    $trip_date_info['difference'] = $interval->format('%a');
    $trip_date_info['edit_days_left'] = (int) $interval->format('%a') - $edit_day_cutoff_interval_in_days;
    
    // Difference is always positive so we need to see if the date has passed too
    $upcoming = $trip_start_date > $current_date_formatted;
    $trip_date_info['trip_over'] = $current_date_formatted > $trip_end_date;

    // User can edit itinerary up to 28 days (1 month) before trip start date
    $trip_date_info['editable'] = ( $upcoming && (int) $interval->format('%a') >= $edit_day_cutoff_interval_in_days );

    return $trip_date_info;
}

function get_days_to_trip( $trip_start ) {
    // Set default timezone to Costa Rica time
    date_default_timezone_set('America/Costa_Rica');
    
    $trip_date_info = array();
    $current_date = date('F j, Y');
    $current_date_formatted = date_create($current_date);
    $trip_start_date = date_create($trip_start);
    $interval = date_diff( $trip_start_date, $current_date_formatted );
    $trip_date_info['days_to_notify'] = (int) $interval->format('%a') - 3;
    $trip_date_info['upcoming'] = $trip_start_date > $current_date_formatted;

    return $trip_date_info;
}

function generate_concierge_itinerary_list( $id ) {
    $itin_args = array(
        'post_type' => 'itinerary',        
        'p' => $id
    );

    $itin = new WP_Query( $itin_args );
    if( $itin->have_posts() ): while( $itin->have_posts() ): $itin->the_post(); 
        ob_start(); ?>
        <p><b>Trip Dates: </b><?php echo get_field( 'trip_start_date' ) . ' - ' . get_field( 'trip_end_date' ); ?></p>
        <?php $i = 1; ?>
        <?php if( have_rows( 'itinerary_trip_days' ) ): while( have_rows( 'itinerary_trip_days' ) ): the_row(); ?>
            <?php
                $trip_day = strtotime( get_sub_field( 'trip_day' ) );
                $day = date( 'd', $trip_day );
                $month = date( 'F', $trip_day );
                $activities = get_sub_field( 'trip_day_activities');
            ?>
            <p><?php echo $day. ' ' .$month; ?> - Day <?php echo $i; ?></p>
            <?php if( have_rows( 'trip_day_activities' ) ): 
                while( have_rows( 'trip_day_activities' ) ): the_row(); ?>
                    <?php $activity = get_sub_field( 'activity_title' ); ?>
                    <p><b>Activity Title: </b><?php echo get_the_title($activity) ?></p>
                    <p><b>Activity Details: <b></p>
                    <p><?php echo '<b>Adults:</b> ' . get_sub_field( 'activity_adults' ) . ' <b>Children:</b> ' . get_sub_field( 'activity_children' ); ?></p>
                    <p><b>Requested Time: </b><?php echo get_sub_field( 'activity_time_booked' ); ?></p>
                    <?php if ( get_sub_field( 'activity_comments' ) && ! get_sub_field( 'message_private' ) ) : ?>
                        <p><b>Special Requests/Comments:</b></p>
                        <p><?php echo get_sub_field( 'activity_comments' ); ?></p>
                    <?php endif; ?>
                    --------------------------------<br>
                <?php endwhile;
            else: ?>
                <p>No activities have been scheduled for this day</p>
                --------------------------------<br>
            <?php endif; ?>
            <?php $i++; ?>
        <?php endwhile; endif; ?>
    <?php endwhile; endif; ?>
    
    <?php return ob_get_clean();
}

function generate_concierge_guest_list( $id ) {
    $guest_args = array(
        'post_type' => 'guest',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'itinerary_id',
                'value' => $id
            )
        )
    );

    $guest_list = new WP_Query($guest_args);
    ob_start();

    if( $guest_list->have_posts() ): while( $guest_list->have_posts() ): $guest_list->the_post(); ?>
        <p><b>Guest Name: </b><?php echo get_post_meta( get_the_ID(), 'guest_first_name', true ); ?> <?php echo get_post_meta( get_the_ID(), 'guest_last_name', true ); ?></p>
        <p><b>Guest Email: </b><?php echo get_post_meta( get_the_ID(), 'guest_email', true ); ?></p>
        <p><b>Passport Number: </b><?php echo get_post_meta( get_the_ID(), 'passport_number', true ) ?></p>
        <p><b>Number of Children: </b><?php echo get_post_meta( get_the_ID(), 'guest_children', true ); ?></p>
        <p><b>Guest Notes: </b><br><?php echo get_post_meta( get_the_ID(), 'guest_notes', true ) ? get_post_meta( get_the_ID(), 'guest_notes', true ) : 'N/A'; ?></p>
        --------------------------------<br>
    <?php endwhile; wp_reset_postdata(); endif; ?>

    <?php return ob_get_clean();
}

function generate_concierge_room_arrangements( $itinerary ) {
    $villa_option = get_field( 'villa_option', $itinerary );
    $villa_option_id = $villa_option[0]->ID;

    $room = 1;
    $room_guest = 0;
    ob_start();

    if( have_rows( 'room', $villa_option_id ) ): while( have_rows( 'room', $villa_option_id ) ): the_row();
            $room_name_value = get_post_meta( $itinerary, 'room_guests_' . $room_guest . '_room_name' );
            $bed_config_value = get_post_meta( $itinerary, 'room_guests_' . $room_guest . '_bed_configuration' );
            $guest_one_value = get_post_meta( $itinerary, 'room_guests_' . $room_guest . '_guest_1' );
            $guest_one_child_value = get_post_meta( $itinerary, 'room_guests_' . $room_guest . '_guest_1_child' );
            $guest_one_child_name = get_post_meta( $itinerary, 'room_guests_' . $room_guest . '_guest_1_child_name' );
            $guest_two_value = get_post_meta( $itinerary, 'room_guests_' . $room_guest . '_guest_2' );
            $guest_two_child_value = get_post_meta( $itinerary, 'room_guests_' . $room_guest . '_guest_2_child' );
            $guest_two_child_name = get_post_meta( $itinerary, 'room_guests_' . $room_guest . '_guest_2_child_name' );
            $guest_three_value = get_post_meta( $itinerary, 'room_guests_' . $room_guest . '_guest_3' );
            $guest_three_child_value = get_post_meta( $itinerary, 'room_guests_' . $room_guest . '_guest_3_child' );
            $guest_three_child_name = get_post_meta( $itinerary, 'room_guests_' . $room_guest . '_guest_3_child_name' );
            $add_guest_value = get_post_meta( $itinerary, 'room_guests_' . $room_guest . '_additional_guest' );
            $special_requests_value = get_post_meta( $itinerary, 'room_guests_' . $room_guest . '_special_requests' );
       
        ?>
         <?php echo get_sub_field('room_name'); ?> - <?php echo get_sub_field('floor_location_text'); ?> - <?php echo get_the_title($villa_option_id); ?>
            <p><b>Room Configuration:</b> <?php echo $bed_config_value[0] == 'king' ? 'King' : 'Twin'; ?></p>
            <p><b>Guest 1 Age:</b> <?php echo $guest_one_child_value[0] ? 'Child' : 'Adult'; ?></p>
            <?php if( $guest_one_child_value[0] ): ?>
                <p><b>Guest 1 Name:</b> <?php echo $guest_one_child_name[0] ? $guest_one_child_name[0] : 'N/A'; ?></p>
            <?php else: ?>
                <p><b>Guest 1 Name:</b> <?php echo $guest_one_value[0] ? $guest_one_value[0] : 'N/A'; ?></p>
            <?php endif; ?>
            <p><b>Guest 2 Age:</b> <?php echo $guest_two_child_value[0] ? 'Child' : 'Adult'; ?></p>
            <?php if( $guest_two_child_value[0] ): ?>
                <p><b>Guest 2 Name:</b> <?php echo $guest_two_child_name[0] ? $guest_two_child_name[0] : 'N/A'; ?></p>
            <?php else: ?>
                <p><b>Guest 2 Name:</b> <?php echo $guest_two_value[0] ? $guest_two_value[0] : 'N/A'; ?></p>
            <?php endif; ?>
            <p><b>Additional Guest?</b> <?php echo $add_guest_value[0] ? 'Yes' : 'No'; ?></p>
            <?php if( $add_guest_value[0] ): ?>
                <p><b>Guest 3 Age:</b> <?php echo $guest_three_child_value[0] ? 'Child' : 'Adult'; ?></p>
                <?php if( $guest_three_child_value[0] ): ?>
                    <p><b>Guest 3 Name:</b> <?php echo $guest_three_child_name[0] ? $guest_three_child_name[0] : 'N/A'; ?></p>
                <?php else: ?>
                    <p><b>Guest 3 Name:</b> <?php echo $guest_three_value[0] ? $guest_three_value[0] : 'N/A'; ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        --------------------------------<br>
        <?php $room++; $room_guest++; ?>
    <?php endwhile; endif; ?>

    <?php return ob_get_clean();
}

if ( ! function_exists( 'get_youtube_id' ) ) {
    function get_youtube_id( $data ) {
        if ( 11 === strlen( $data ) ) {
            return $data;
        }
        preg_match( '/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/', $data, $matches );
        return isset( $matches[2] ) ? $matches[2] : false;
    }
}

function fxup_sideload_image( $url ) {

    // Need to require these files
    if ( ! function_exists( 'media_handle_upload' ) ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
    }

    $tmp = download_url( $url );

    $file_array = array();

    // Set variables for storage
    // fix file filename for query strings
    preg_match( '/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches );
    $file_array['name']     = basename( $matches[0] );
    $file_array['tmp_name'] = $tmp;

    // If error storing temporarily, unlink
    if ( is_wp_error( $tmp ) ) {
        @unlink( $file_array['tmp_name'] );
        $file_array['tmp_name'] = '';
    }

    return media_handle_sideload( $file_array, 0 );
}

function generate_youtube_embed_url( $youtube_url, $iframe = false ) {
    if ( !$youtube_url ) return '';

    $video_id = get_youtube_id( $youtube_url );

    if ( $video_id && !$iframe ) return 'https://www.youtube.com/embed/' . $video_id;
    elseif ( $video_id && $iframe ) return '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . $video_id . '" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
    else return '';
}

if ( ! function_exists('array_usearch') ) {
	function array_usearch( array $array, Closure $test ) {
		$found = false;
		$iterator = new \ArrayIterator($array);

		while ($found === false && $iterator->valid()) {
			if ($test($iterator->current())) {
				$found = $iterator->key();
			}
			$iterator->next();
		}

		return $found;
	}
}

/**
 * Get an item from an array or object using "dot" notation.
 *
 * @param  mixed   $target
 * @param  string  $key
 * @param  mixed   $default
 * @return mixed
 */
if ( ! function_exists( 'data_get' ) ) {

	function data_get( $target, $key, $default = null )
	{
		if ( is_null( $key ) )
			return $target;
		foreach ( explode( '.', $key ) as $segment ) {
			if ( is_array( $target ) ) {
				if ( ! array_key_exists( $segment, $target ) ) {
					return $default;
				}
				$target = $target[$segment];
			} elseif ( $target instanceof ArrayAccess ) {
				if ( ! isset( $target[$segment] ) ) {
					return $default;
				}
				$target = $target[$segment];
			} elseif ( is_object( $target ) ) {
				if ( ! isset( $target->{$segment} ) ) {
					return $default;
				}
				$target = $target->{$segment};
			} else {
				return $default;
			}
		}
		return $target;
	}

}

if ( ! function_exists( 'data_set' ) ) {

	/**
	 * Set an array item to a given value using "dot" notation.
	 *
	 * If no key is given to the method, the entire array will be replaced.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return array
	 */
	function data_set( &$array, $key, $value, $separator = '.' )
	{
		if ( is_null( $key ) )
			return $array = $value;

		$keys = explode( $separator, $key );

		while ( count( $keys ) > 1 ) {
			$key = array_shift( $keys );

			// If the key doesn't exist at this depth, we will just create an empty array
			// to hold the next value, allowing us to create the arrays to hold final
			// values at the correct depth. Then we'll keep digging into the array.
			if ( ! isset( $array[$key] ) || ! is_array( $array[$key] ) ) {
				$array[$key] = array();
			}

			$array = & $array[$key];
		}

		$array[array_shift( $keys )] = $value;

		return $array;
	}

}

/**
 * Return the first element in an array passing a given truth test.
 *
 * @param  array  $array
 * @param  callable  $callback
 * @param  mixed  $default
 * @return mixed
 */
if ( ! function_exists( 'array_first' ) ) {

	function array_first( $array, $callback = null, $default = null )
	{
		if ( is_null( $callback ) ) {
			return count( $array ) > 0 ? reset( $array ) : null;
		}
		foreach ( $array as $key => $value ) {
			if ( call_user_func( $callback, $key, $value ) )
				return $value;
		}
		return value( $default );
	}

}

/**
 * Return the default value of the given value.
 *
 * @param  mixed  $value
 * @return mixed
 */
if ( ! function_exists( 'value' ) ) {

	function value( $value )
	{
		return $value instanceof Closure ? $value() : $value;
	}

}