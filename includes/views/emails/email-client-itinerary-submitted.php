<?php 

/* 
 * The message body is retrieved from an option in the dashboard.
 * We just need to replace the merge tags
 * option name = fxup_email_client_itinerary_submitted_message
 * The email service passes these variables
 * $message_body
 * $edit_last_date
 */

$message_body = str_replace( '{{edit_last_date}}', $edit_last_date, $message_body );

echo $message_body;