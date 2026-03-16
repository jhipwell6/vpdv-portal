<form class="itinerary-section" id="transport-form" autocomplete="off">
	<input type="hidden" name="itinerary_id" value="<?php echo $Itinerary->getPostID(); ?>">
	<input type="hidden" name="current_user" value="<?php echo get_current_user_id(); ?>">
	<input type="hidden" name="to_delete" value="<?php echo esc_attr( json_encode( array( 'arrival' => array(), 'departure' => array() ) ) ); ?>">
	<div class="itinerary-header">
		<div class="row">
			<div class="col-xs-12">
				<div class="quicklinks--wrapper">
					<div class="editable-btns">
						<?php if ( $is_concierge ) : ?>
							<a class="js-transport-save" href="#"><i class="far fa-save"></i> Save</a>
						<?php endif; ?>
						<?php if ( $editable ) : ?>
							<a class="js-transport-regenerate" href="#"><i class="fas fa-sync"></i> Regenerate</a>
						<?php endif; ?>
						<?php if ( $is_concierge && $Itinerary->hasClientTransports() ) : ?>
							<a class="load-from-client" href="<?php echo add_query_arg( 'loadfromclient', 1 ); ?>"><i class="fas fa-download"></i> Load Client Transportation</a>
						<?php endif; ?>
						<?php if ( $maybe_load_client_transports ) : ?>
							<a class="load-from-client" href="<?php echo remove_query_arg( 'loadfromclient' ); ?>"><i class="fas fa-download"></i> Load Concierge Transportation</a>
						<?php endif; ?>	
							<a href="#" class="share-btn js-share-transportation"><i class="fas fa-share"></i> Share</a>
					</div>
					<div class="jumplink-wrapper">
						<h4>Jump to: </h4>
						<?php echo \FXUP_USER_PORTAL\Controllers\FXUP_Itinerary_Process::instance()->renderJumpToSelectList($Itinerary->getPostID()); ?>
					</div>
				</div>
			</div>
		</div>
		<p class="small-notification push-bottom">
			<i class="fas fa-book-reader fa-2x fa-fw"></i>
			<span>Transportation is automatically assigned based on your guests' arrival and departure times.</span>
		</p>
		<?php if ( $Itinerary->getMissingTravelDetailsCount() > 0 ) : ?>
		<p class="small-notification push-bottom">
			<i class="fas fa-exclamation-triangle fa-2x fa-fw"></i>
			<span>There are <?php echo $Itinerary->getMissingTravelDetailsCount(); ?> guests missing travel details.  All guest travel details are required before transportation can be finalized. Please add that information on the <a href="<?php echo $Itinerary->getGuestTravelLink(); ?>">Guest Travel Details</a> page.</span>
		</p>
		<?php endif; ?>
	</div>
	<?php if ( $Itinerary->getMissingTravelDetailsCount() != $Itinerary->getGuestsCount() ) : ?>
	<div class="row intinerary-row">
		<div class="col-xs-12">
			<h2 class="trip-itinerary-title">Arrival Transportation <?php if ( $is_concierge ) : ?><button type="button" class="btn btn-secondary js-transport-add" data-type="arrival">Add Transport</button><?php endif; ?></h2>
		</div>
		<?php
			foreach ( $Itinerary->getArrivalTransportation( $maybe_load_client_transports ) as $numeric_index => $Transport ) {
				if ( ! $Transport->hasValidDates() ) {
					continue;
				}
				$t = $numeric_index + 1;
				include $Itinerary->transportViewPath();
			}		
		?>
		<div class="col-xs-12">
			<h2 class="trip-itinerary-title">Departure Transportation <?php if ( $is_concierge ) : ?><button type="button" class="btn btn-secondary js-transport-add" data-type="departure">Add Transport</button><?php endif; ?></h2>
		</div>
		<?php
			foreach ( $Itinerary->getDepartureTransportation( $maybe_load_client_transports ) as $numeric_index => $Transport ) {
				if ( ! $Transport->hasValidDates() ) {
					continue;
				}
				$t = $numeric_index + 1;
				include $Itinerary->transportViewPath();
			}
		?>
		<?php if ( $is_concierge ) : ?>
		<script>
			var FXUPTransportGuests = <?php echo $Itinerary->getTransportGuestsJson( $maybe_load_client_transports ); ?>;
		</script>
		<?php endif; ?>
	</div>
	<?php ; else : ?>
	<div class="row intinerary-row text-center">
		<h3 class="push-top push-bottom">No guest travel information available.</h3>
		<a href="<?php echo $Itinerary->getGuestTravelLink(); ?>" class="btn btn-primary">Guest Travel Details</a>
	</div>
	<?php endif; ?>
	<p>Last updated by: <?php echo $Itinerary->getTransportationUpdatedByName(); ?></p>
</form>

<div class="js-popup-share-transportation popup-confirm-wrapper" style="display:none">
	<div class="popup-confirm">
		<form id="share-transportation">
			<div class="form-heading push-bottom">
				<h3>Share Transportation</h3>
			</div>
			<div class="form-group push-bottom">
				<label>Filter by company</label>
				<select name="transportCompany">
					<option value="">All</option>
					<?php
						if ( ! empty( $Itinerary->getTransportCompanies() ) ) :
							foreach ( $Itinerary->getTransportCompanies() as $company ) :
					?>
					<option value="<?php echo $company; ?>"><?php echo $company; ?></option>
					<?php endforeach; endif; ?>
				</select>
			</div>
			<div class="form-group push-bottom">
				<label>Filter by guest</label>
				<select name="transportGuests[]" class="js-disable-mobile multi-select push-half-bottom" multiple>
					<?php
						if ( ! empty( $Itinerary->getGuests() ) ) :
							foreach ( $Itinerary->getGuests() as $Guest ) :
					?>
					<option value="<?php echo $Guest->getPostID(); ?>"><?php echo $Guest->getFullName(); ?></option>
					<?php endforeach; endif; ?>
				</select>
				<small>defaults to all guests</small>
			</div>
			<div class="form-actions">
				<button type="submit" id="submit-share-transportation" class="btn btn-thirdary">Get Share Link</button>
			</div>
			<div class="input-group d-flex align-items-stretch js-share-transportation-link-wrap push-top" style="display:none;">
				<input type="text" class="form-control js-share-transportation-link" value="" />
				<div class="input-group-append d-flex">
					<button class="btn btn-outline-secondary js-copy-value" type="button">copy</button>
				</div>
			</div>
		</form>
		<a class="js-itin-popup-close" href="#"><i class="fas fa-times"></i></a>
	</div>
</div>