<br>
<table style="width: 65%;" border="1">
    <tr>
        <td style="width: 30%;">Itinerary</td>
        <td><a href="<?php echo $Itinerary::addStaffAccessQueryParam($Itinerary->getShareLink()); ?>"><?php echo $Itinerary->getTitle(); ?></a></td>
    </tr>
    <tr>
        <td style="width: 30%;">User</td>
        <td><?php echo $Itinerary->getUserDisplayName(); ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Email</td>
        <td><?php echo $Itinerary->getUserEmail(); ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Simple Guest List</td>
        <td><a href="<?php echo $Itinerary::addStaffAccessQueryParam($Itinerary->getSimpleGuestListLink()); ?>"><?php echo $Itinerary::addStaffAccessQueryParam($Itinerary->getSimpleGuestListLink()); ?></a></td>
    </tr>
    <tr>
        <td style="width: 30%;">Simple Room Arrangements</td>
        <td><a href="<?php echo $Itinerary::addStaffAccessQueryParam($Itinerary->getSimpleRoomArrangementsLink()); ?>"><?php echo $Itinerary::addStaffAccessQueryParam($Itinerary->getSimpleRoomArrangementsLink()); ?></a></td>
    </tr>
    <tr>
        <td style="width: 30%;">Start Date</td>
        <td><?php echo $Itinerary->getTripStartDate()->format('F j, Y'); ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">End Date</td>
        <td><?php echo $Itinerary->getTripEndDate()->format('F j, Y'); ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Status</td>
        <td><?php echo $Itinerary->getApprovalStatus(); ?></td>
    </tr>
    <tr>
        <td style="width: 30%;">Activities</td>
        <td>
            <table style="width: 100%;" border="1">
                <?php foreach ($Itinerary->getTripDays() as $TripDay) { ?>
                    <tr>
                        <td style="width: 30%;">
                            <?php echo $TripDay->getDateTime()->format('F j, Y'); ?>
                        </td>
                        <td>
                            <table>
                                <?php foreach($TripDay->getActivities() as $Activity) { ?>
                                    <tr>
                                        <td>
                                        <?php 
                                            if ($Activity->doesNotHaveActivityTypePost()) {
                                                echo 'Custom Activity : ' . $Activity->getDisplayTitle();
                                            } else {
                                                echo $Activity->getDisplayTitle();
                                            }   
                                        ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </table>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </td>
    </tr>
</table>
