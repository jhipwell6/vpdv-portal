Hello!
<br>
<br>
<?php if (0 < count($itineraries)) { ?>
Below is a list of all itineraries that will begin within the next <?php echo $deadline_check_interval; ?> days:
<br>
<?php
    foreach ($itineraries as $Itinerary) {
        include $email_concierge_deadline_check_single_itinerary_path;
    }
?>
<?php } else { ?>
    No itineraries begin within the next <?php echo $deadline_check_interval; ?> days
<?php } ?>