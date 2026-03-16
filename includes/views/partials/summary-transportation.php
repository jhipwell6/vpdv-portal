<div class="row mb-5">
	<div class="col-12">
		<div class="row">
			<div class="col-sm-2">
				<h4><strong>Transport <?php echo $t; ?></strong></h4>
			</div>
			<div class="col-sm-10">
				<h4><?php echo $Transport->getDate()->format('F d, l'); ?></h4>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-2">
				<h4><strong>Company</strong></h4>
			</div>
			<div class="col-sm-10">
				<h4><?php echo $Transport->getCompany(); ?></h4>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-2">
				<h4><strong>Pickup Location</strong></h4>
			</div>
			<div class="col-sm-10">
				<h4><?php echo $Transport->getPickupLocation(); ?></h4>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-2">
				<h4><strong>Dropoff Location</strong></h4>
			</div>
			<div class="col-sm-10">
				<h4><?php echo $Transport->getDropoffLocation(); ?></h4>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-2">
				<h4><strong>Mode</strong></h4>
			</div>
			<div class="col-sm-10">
				<h4><?php echo $Transport->getMode(); ?></h4>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-2">
				<h4><strong>Cost</strong></h4>
			</div>
			<div class="col-sm-10">
				<h4><?php echo $Transport->getCost(); ?></h4>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-2">
				<h4><strong>Guest Count</strong></h4>
			</div>
			<div class="col-sm-10">
				<h4><?php echo $Transport->getGuestCount(); ?></h4>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-2">
				<h4><strong>Notes</strong></h4>
			</div>
			<div class="col-sm-10">
<p class="flush-top white-space-pre"><?php echo $Transport->getNotes(); ?>

<?php echo \FXUP_USER_PORTAL\Controllers\FXUP_Itinerary_Process::instance()->transportation_reminder; ?>
</p>
			</div>
		</div>
		<div class="row">
			<div class="col-12">
				<table id="transport_list_<?php echo $Transport->getType(); ?>_<?php echo $t; ?>" class="display responsive transport-html-table" style="width:100%">
					<thead>
						<tr>
							<th>First Name</th>
							<th>Last Name</th>
							<th>Airline/Flight</th>
							<th>Time</th>
							<th>Villa</th>
						</tr>
					</thead>
					<tbody>
						<?php
							foreach( $Transport->getGuestObjects( 'getFirstName' ) as $Guest ) :
								$type = ucwords( $Transport->getType() );
								$airline_getter = "get{$type}Airline";
								$flight_number_getter = "get{$type}FlightNumber";
								$time_getter = "get{$type}Time";
						?>
							<tr
								data-row-guest-id="<?php echo $Guest->getPostID(); ?>"
								data-type="<?php echo $Transport->getType(); ?>"
								data-row="<?php echo $Transport->getRowNumberItinerary(); ?>"
								data-table="transport_list_<?php echo $Transport->getType(); ?>_<?php echo $t; ?>"
								>
								<td class="guest-list__first-name" data-field-name="guest_first_name"><span class="field-value"><?php echo $Guest->getFirstName(); ?></span></td>
								<td class="guest-list__last-name" data-field-name="guest_last_name"><span class="field-value"><?php echo $Guest->getLastName(); ?></span></td>
								<td class="guest-list__flight" data-field-name="guest_flight"><span class="field-value"><?php echo $Guest->$airline_getter() . ' ' . $Guest->$flight_number_getter(); ?></span></td>
								<td class="guest-list__time" data-field-name="guest_time"><span class="field-value"><?php echo $Guest->$time_getter(); ?></span></td>
								<td class="guest-list__villa" data-field-name="villa_name">
									<?php if ( $Guest->isOnsite() && $Guest->getAssignedRoom() ) : ?>
									<span class="field-value"><?php echo $Guest->getAssignedRoom()->getSubVilla()->getTitle(); ?></span>
									<?php ; elseif( $Guest->getStayLocation() && $Guest->getStayLocation() != 'Other' ) : ?>
									<span class="field-value"><?php echo $Guest->getStayLocation(); ?></span>
									<?php ; elseif( $Guest->getStayLocationOther() && $Guest->getStayLocation() == 'Other' ) : ?>
									<span class="field-value"><?php echo $Guest->getStayLocationOther(); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

		