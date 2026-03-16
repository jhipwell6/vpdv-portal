<br>
<table style="width: 65%;" border="1">
    <tr>
        <td style="width: 30%;">Itinerary</td>
        <td><a href="<?php echo $Itinerary::addStaffAccessQueryParam($Itinerary->getSummaryLink()); ?>"><?php echo $Itinerary->getTitle(); ?></a></td>
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
        <td style="width: 30%;">Summary</td>
        <td><a href="<?php echo $Itinerary->getSummaryLink(); ?>"><?php echo $Itinerary->getSummaryLink(); ?></a></td>
    </tr>
</table>
