<?php 

/* 
 * The message body is retrieved from an option in the dashboard.
 * We just need to replace the merge tags
 * option name = email_concierge_guest_removed_message
 * The email service passes these variables
 * $message_body
 * $itinerary_id
 * $itinerary_title
 * $guest_first_name
 * $guest_last_name
 * $guest_email
 */

$message_body = str_replace( '{{itinerary_id}}', $itinerary_id, $message_body );
$message_body = str_replace( '{{itinerary_title}}', $itinerary_title, $message_body );
$message_body = str_replace( '{{guest_first_name}}', $guest_first_name, $message_body );
$message_body = str_replace( '{{guest_last_name}}', $guest_last_name, $message_body );
$message_body = str_replace( '{{guest_email}}', $guest_email, $message_body );

echo $message_body;