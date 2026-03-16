<?php 

/* 
 * The message body is retrieved from an option in the dashboard.
 * We just need to replace the merge tags
 * option name = email_concierge_guest_travel_arrangements_submitted_message
 * The email service passes these variables
 * $message_body
 * $itinerary_url			
 * $itinerary_title
 * $itinerary_start_date
 * $itinerary_end_date
 * $guest_travel_url
 * $guest_full_name
 * $guest_stay_length
 * $guest_passport_number
 * $guest_arrival_airline
 * $guest_arrival_flight_number
 * $guest_arrival_date
 * $guest_arrival_time
 * $guest_departure_airline
 * $guest_departure_flight_number
 * $guest_departure_date
 * $guest_departure_time
 * $guest_travel_notes
 */

//$message_body = str_replace( '{{itinerary_url}}', $itinerary_url, $message_body );
//$message_body = str_replace( '{{itinerary_title}}', $itinerary_title, $message_body );
//$message_body = str_replace( '{{itinerary_start_date}}', $itinerary_start_date, $message_body );
//$message_body = str_replace( '{{itinerary_end_date}}', $itinerary_end_date, $message_body );
//$message_body = str_replace( '{{guest_travel_url}}', $guest_travel_url, $message_body );
//$message_body = str_replace( '{{guest_full_name}}', $guest_full_name, $message_body );
//$message_body = str_replace( '{{guest_stay_length}}', $guest_stay_length, $message_body );
//$message_body = str_replace( '{{guest_passport_number}}', $guest_passport_number, $message_body );
//$message_body = str_replace( '{{guest_arrival_airline}}', $guest_arrival_airline, $message_body );
//$message_body = str_replace( '{{guest_arrival_flight_number}}', $guest_arrival_flight_number, $message_body );
//$message_body = str_replace( '{{guest_arrival_date}}', $guest_arrival_date, $message_body );
//$message_body = str_replace( '{{guest_arrival_time}}', $guest_arrival_time, $message_body );
//$message_body = str_replace( '{{guest_departure_airline}}', $guest_departure_airline, $message_body );
//$message_body = str_replace( '{{guest_departure_flight_number}}', $guest_departure_flight_number, $message_body );
//$message_body = str_replace( '{{guest_departure_date}}', $guest_departure_date, $message_body );
//$message_body = str_replace( '{{guest_departure_time}}', $guest_departure_time, $message_body );
//$message_body = str_replace( '{{guest_travel_notes}}', $guest_travel_notes, $message_body );
//
//echo $message_body;
if ( $Email->get_data() && ! empty( $Email->get_data() ) ) :
?>
<h1>Guest travel arrangements have been updated!</h1>
<?php foreach ( $Email->get_data() as $data ) : ?>
<table style="width: 65%;" border="1">
    <tr>
        <td style="width: 30%;">Itinerary</td>
        <td><a href="<?php echo $data['itinerary_url']; ?>"><?php echo $data['itinerary_title']; ?></a></td>
    </tr>
    <tr>
        <td style="width: 30%;">Trip Start Date</td>
        <td><?php echo $data['itinerary_start_date']; ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Trip End Date</td>
        <td><?php echo $data['itinerary_end_date']; ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Guest</td>
        <td><a href="<?php echo $data['guest_travel_url']; ?>"><?php echo $data['guest_full_name']; ?></a></td>
    </tr>
    <tr>
        <td style="width: 30%;">Stay Length</td>
        <td><?php echo $data['guest_stay_length']; ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Passport Number</td>
        <td><?php echo $data['guest_passport_number']; ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Arrival Airline</td>
        <td><?php echo $data['guest_arrival_airline']; ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Arrival Flight Number</td>
        <td><?php echo $data['guest_stay_length']; ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Arrival Date</td>
        <td><?php echo $data['guest_arrival_date']; ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Arrival Time</td>
        <td><?php echo $data['guest_arrival_time']; ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Departure Airline</td>
        <td><?php echo $data['guest_departure_airline']; ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Departure Flight Number</td>
        <td><?php echo $data['guest_departure_flight_number']; ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Departure Date</td>
        <td><?php echo $data['guest_departure_date']; ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Departure Time</td>
        <td><?php echo $data['guest_departure_time']; ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Travel Notes</td>
        <td><?php echo $data['guest_travel_notes']; ?></td>
    </tr>
</table>
<?php endforeach; ?>
<?php endif; ?>