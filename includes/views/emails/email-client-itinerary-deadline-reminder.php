<?php 

/* 
 * The message body is retrieved from an option in the dashboard.
 * We just need to replace the merge tags
 * option name = fxup_email_client_itinerary_deadline_reminder_message
 * The email service passes these variables
 * $message_body
 * $edit_days_left
 * $itinerary_url
 * $guest_list_url
 * $room_arrangements_url
 */

$message_body = str_replace( '{{edit_days_left}}', $edit_days_left, $message_body );
$message_body = str_replace( '{{itinerary_url}}', $itinerary_url, $message_body );
$message_body = str_replace( '{{guest_list_url}}', $guest_list_url, $message_body );
$message_body = str_replace( '{{room_arrangements_url}}', $room_arrangements_url, $message_body );

echo $message_body;