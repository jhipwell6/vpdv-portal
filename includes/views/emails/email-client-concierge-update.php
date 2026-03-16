<?php 

/* 
 * The message body is retrieved from an option in the dashboard.
 * We just need to replace the merge tags
 * option name = fxup_email_client_itinerary_updated_message
 * The email service passes these variables
 * $message_body
 * $itinerary_url
 */

$message_body = str_replace( '{{itinerary_url}}', $itinerary_url, $message_body );

echo $message_body;