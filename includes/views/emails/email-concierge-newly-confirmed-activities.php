<h1>Activities were approved for this itinerary!</h1>
<table style="width: 65%;" border="1">
    <tr>
        <td style="width: 30%;">Title</td>
        <td><?php echo $Itinerary->getTitle(); ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Link</td>
        <td><?php echo $Itinerary->getPermalink(); ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">User</td>
        <td><?php echo $itinerary_user_display_name; ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Trip Start Date</td>
        <td><?php echo $Itinerary->getTripStartDate()->format('F j, Y'); ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Trip End Date</td>
        <td><?php echo $Itinerary->getTripEndDate()->format('F j, Y'); ?></td>
    </tr>
</table>
<br>
<table style="width: 65%;" border="1">
    <tr>
        <th style="width: 30%;">Day</th>
        <th>Activity</th>
    </tr>
    <?php
        foreach($newly_confirmed_activities as $numeric_index => $activity) {
            include $email_concierge_newly_confirmed_activity_template_path;
        }
    ?>
</table>