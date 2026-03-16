Hello!
<br>
<br>
<?php if ( $itineraries && 0 < count($itineraries)) { ?>
Below is a list of all itineraries updated or created within the last 24 hours:
<br>
<?php
    foreach ($itineraries as $Itinerary) {
        include $email_concierge_digest_report_single_itinerary_path;
    }
?>
<?php } else { ?>
    No itineraries were updated!
<?php } ?>