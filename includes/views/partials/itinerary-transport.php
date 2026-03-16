<?php
 // $guest_posts is equal to posts from WP_Query passed in from controller.
 // Would be better if this could also be a model passed in, or part of the Itinerary model.
?>
<div class="itinerary-day-wrapper js-day-<?php echo $t; ?> js-transport-wrapper" data-type="<?php echo $Transport->getType(); ?>" data-row="<?php echo $Transport->getRowNumberItinerary(); ?>">
	<input type="hidden" name="transport_<?php echo $Transport->getType(); ?>[]" data-row="<?php echo $Transport->getRowNumberItinerary(); ?>" value="<?php echo esc_attr( $Transport->toJsonRawRowItinerary() ); ?>" />
    <div class="col-xs-12 itinerary-day-heading">
        <div class="intinerary-date-cards">
            <div class="row flex-row">
                <?php
                $guest_text = '';
                if ( $Transport->getGuestCount() < 1 ) {
                    if ( $editable ) {
                        $guest_text = 'No Guests Added Yet';
                    } else {
                        $guest_text = 'No Guests have been added for this transport. Please contact your concierge to book last minute transports.';
                    }
                } else {
                    $guest_text = ( $Transport->getGuestCount() === 1 )  ? '1 Guest' : $Transport->getGuestCount() . ' Guests';
                }
                ?>
                <div class="col-xs-4 flex-item date transport">
                    <div class="flex-date">
                        <span class="date-day">Transport <?php echo $t; ?></span>
                    </div>
                </div>
                <div class="col-xs-12 flex-item flex-text">
                    <div class="intinerary-date-content">
                        <div class="row flex-row">
                            <div class="col-xs-12 flex-item ">
                                <h2 class="flush-ends flex-row--title"><?php echo $Transport->getDate()->format('F d, l'); ?></h2>
                                <div class="activities-add--wrapper" >
                                    <span class="flush-ends activity-count js-act-count js-transport-count push-right <?php echo $Transport->getGuestCount() > 0 ? '' : 'activity-count-no-activities'; ?>"><?php echo $guest_text; ?> </span>
									<?php if ( $is_concierge ) : ?>
									<button type="button" class="js-transport-remove" data-type="<?php echo $Transport->getType(); ?>" data-row="<?php echo $Transport->getRowNumberItinerary(); ?>">Remove Transport <i class="fas fa-minus-circle"></i></button>
									<?php endif; ?>
                                </div>
                                    <button type="button" class="js-itinerary-toggle push-bottom">View Transport Details <i class="fas fa-chevron-down"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xs-12 itinerary-day-activities" style="display:none;">
        <div class="accordion sec-itinerary-accordion">
			<?php if ( $is_concierge ) : ?>
			<div class="row">
				<div class="col-xxs-12 col-md-6 push-bottom">
					<label>Date</label>
					<input type="text" class="js-transport-date" value="<?php echo $Transport->getDate()->format('m/d/Y'); ?>" data-field="date" data-type="<?php echo $Transport->getType(); ?>" data-row="<?php echo $Transport->getRowNumberItinerary(); ?>" />
				</div>
				<div class="col-xxs-12 col-md-6 push-bottom">
					<label>Status</label>
					<select class="js-transport-status" data-type="<?php echo $Transport->getType(); ?>" data-field="status" data-row="<?php echo $Transport->getRowNumberItinerary(); ?>">
						<option value="Pending Concierge Approval" <?php echo $Transport->getStatus() == 'Pending Concierge Approval' ? 'selected' : ''; ?>>Pending Concierge Approval</option>
						<option value="Approved" <?php echo $Transport->getStatus() == 'Approved' ? 'selected' : ''; ?>>Approved</option>
					</select>
				</div>
			</div>
			<div class="row">
				<div class="col-xxs-12 col-md-6 push-bottom">
					<label>Company</label>
					<input type="text" class="js-transport-company" value="<?php echo $Transport->getCompany(); ?>" data-field="company" data-type="<?php echo $Transport->getType(); ?>" data-row="<?php echo $Transport->getRowNumberItinerary(); ?>" />
				</div>
				<div class="col-xxs-12 col-md-6 push-bottom">
					<label>Pickup Time</label>
					<input type="text" class="js-transport-pickup-time" value="<?php echo $Transport->getPickupTime(); ?>" data-field="pickup_time" data-type="<?php echo $Transport->getType(); ?>" data-row="<?php echo $Transport->getRowNumberItinerary(); ?>"  />
				</div>
			</div>
			<div class="row">
				<div class="col-xxs-12 col-md-6 push-bottom">
					<label>Mode</label>
					<select class="js-transport-mode" data-type="<?php echo $Transport->getType(); ?>" data-field="mode" data-row="<?php echo $Transport->getRowNumberItinerary(); ?>">
						<option value="">Select a mode</option>
						<option value="Ground" <?php echo $Transport->getMode() == 'Ground' ? 'selected' : ''; ?>>Ground</option>
						<option value="Private Air" <?php echo $Transport->getMode() == 'Private Air' ? 'selected' : ''; ?>>Private Air</option>
						<option value="Public Air" <?php echo $Transport->getMode() == 'Public Air' ? 'selected' : ''; ?>>Public Air</option>
					</select>
					<small>The client is responsible for booking public air directly with the company.</small>
				</div>
				<div class="col-xxs-12 col-md-6 push-bottom">
					<label>Notes</label>
					<input type="text" class="js-transport-notes" value="<?php echo $Transport->getNotes(); ?>" data-field="notes" data-type="<?php echo $Transport->getType(); ?>" data-row="<?php echo $Transport->getRowNumberItinerary(); ?>"  />
				</div>
			</div>
			<?php if ( $is_concierge ) : ?>
			<div class="row">
				<div class="col-xxs-12 col-md-6 push-bottom">
					<label>Cost</label>
					<input type="text" class="js-transport-cost" value="<?php echo $Transport->getCost(); ?>" data-field="cost" data-type="<?php echo $Transport->getType(); ?>" data-row="<?php echo $Transport->getRowNumberItinerary(); ?>"  />
				</div>
			</div>
			<?php endif; ?>
			<div class="row">
				<div class="col-xxs-12 col-md-4 push-bottom">
					<label>Pickup Location</label>
					<input type="text" class="js-transport-pickup-location" value="<?php echo $Transport->getPickupLocation(); ?>" data-field="pickup_location" data-type="<?php echo $Transport->getType(); ?>" data-row="<?php echo $Transport->getRowNumberItinerary(); ?>"  />
				</div>
				<div class="col-xxs-12 col-md-4 push-bottom">
					<label>Dropoff Location</label>
					<input type="text" class="js-transport-dropoff-location" value="<?php echo $Transport->getDropoffLocation(); ?>" data-field="dropoff_location" data-type="<?php echo $Transport->getType(); ?>" data-row="<?php echo $Transport->getRowNumberItinerary(); ?>"  />
				</div>
				<div class="col-xxs-12 col-md-4 push-bottom">
					<label>Add A Guest <i class="itin-tooltip far fa-question-circle"><div class="itin-tooltip-text hidden">
                                <span class="arrow-up"></span>
                                If you aren't seeing someone you expect, they may already be added to a transport
                            </div></i></label>
					<select class="js-transport-add-guest" data-type="<?php echo $Transport->getType(); ?>" data-table="transport_list_<?php echo $Transport->getType(); ?>_<?php echo $t; ?>" data-row="<?php echo $Transport->getRowNumberItinerary(); ?>">
						<option value="-1">Select a guest to add</option>
						<?php /*
							foreach( $Itinerary->getMissingTravelDetails() as $Guest ) :
								if ( $Transport->getType() == 'arrival' && ! $Guest->requiresArrivalTransportation() ) {
									continue;
								}

								if ( $Transport->getType() == 'departure' && ! $Guest->requiresDepartureTransportation() ) {
									continue;
								}
								
								if ( $Transport->getType() == 'arrival' && $Guest->hasArrivalTransportation() ) {
									continue;
								}
								
								if ( $Transport->getType() == 'departure' && $Guest->hasDepartureTransportation() ) {
									continue;
								}
						?>
						<option value="<?php echo $Guest->getPostID(); ?>"><?php echo $Guest->getFullName(); ?></option>
						<?php endforeach; */ ?>
					</select>
				</div>
			</div>
			<?php endif; ?>
			<?php // if ( ! empty( $Transport->getGuestObjects() ) ) : ?>
			<!-- Removed nowrap and nowrap class from table -->
			<table id="transport_list_<?php echo $Transport->getType(); ?>_<?php echo $t; ?>" class="display responsive" style="width:100%">
				<thead>
					<tr>
						<th>First Name</th>
						<th>Last Name</th>
						<th>Airline/Flight</th>
						<th>Time</th>
						<th>Guest Type</th>
						<th>Villa</th>
						<?php if ( $is_concierge ) : ?><th class="text-center">Remove</th><?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<?php
						foreach( $Transport->getGuestObjects() as $Guest ) :
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
							<td class="guest-list__children" data-field-name="guest_is_child"><span class="field-value"><?php echo $Guest->isChild() ? 'Child' : 'Adult'; ?></span></td>
							<td class="guest-list__villa" data-field-name="villa_name">
								<span class="field-value"><?php echo $Guest->getAssignedRoom() ? $Guest->getAssignedRoom()->getSubVilla()->getTitle() : ''; ?></span>
							</td>
							<?php if ( $is_concierge ) : ?><td class="guest-list__remove text-center" data-field-name="guest_remove"><span class="field-value"><button class="js-transport-remove-guest"><i class="fas fa-times-circle"></i></button></span></td><?php endif; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php // endif; ?>
        </div>
    </div>
</div>