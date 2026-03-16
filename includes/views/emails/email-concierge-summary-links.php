Hello!
<br>
<br>
Below is a list of all itineraries that will begin within the next <?php echo $deadline_check_interval; ?> days:
<br>
<?php
foreach ( $itineraries as $Itinerary ) {
	include $email_concierge_single_itinerary_summary_link_path;
}