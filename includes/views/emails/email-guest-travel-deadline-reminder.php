<?php 

/* 
 * The message body is retrieved from an option in the dashboard.
 * We just need to replace the merge tags
 * option name = fxup_email_guest_travel_deadline_reminder_message
 * The email service passes these variables
 * $message_body
 * $travel_url
 */

$message_body = str_replace( '{{travel_url}}', $travel_url, $message_body );

echo $message_body;