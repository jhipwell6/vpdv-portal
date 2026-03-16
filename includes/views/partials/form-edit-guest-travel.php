<div class="row push-bottom">
	<label>Stay Length *</label>
	<input id="guest-trip-length-<?php echo $i; ?>" type="text" name="stay_length_<?php echo $Guest->getPostID(); ?>" value="<?php echo $Guest->getStayLength(); ?>">
</div>
<div class="row push-bottom">
	<label>Passport Number <small>optional</small></label>
	<input type="text" name="passport_number_<?php echo $Guest->getPostID(); ?>" value="<?php echo $Guest->getPassportNumber(); ?>">
</div>
<div class="row push-bottom">
	<label>Arrival Airline *</label>
	<input type="text" name="airline_<?php echo $Guest->getPostID(); ?>" value="<?php echo $Guest->getArrivalAirline(); ?>">
</div>
<div class="row push-bottom">
	<label>Arrival Flight Number *</label>
	<input type="text" name="flight_number_<?php echo $Guest->getPostID(); ?>" value="<?php echo $Guest->getArrivalFlightNumber(); ?>">
</div>
<div class="row push-bottom">
	<label>Requires transportation to the Villa upon arrival?</label>
	<div class="flex-row">
		<input type="radio" name="requires_arrival_transportation_<?php echo $Guest->getPostID(); ?>" <?php echo $Guest->requiresArrivalTransportation() ? 'checked' : ''; ?> value="1"> <label class="push-right">Yes</label>
		<input type="radio" name="requires_arrival_transportation_<?php echo $Guest->getPostID(); ?>" <?php echo ! $Guest->requiresArrivalTransportation() ? 'checked' : ''; ?> value="0"> <label class="push-right">No</label>
	</div>
</div>
<!-- BEGIN HIDDEN FIELD -->
<div class="row push-bottom arrival-date-<?php echo $i; ?> hidden">
	<label>Arrival Date *</label>
	<input type="text" name="arrival_date_<?php echo $Guest->getPostID(); ?>" value="<?php echo $Guest->getArrivalDate(); ?>">
</div>
<!-- END HIDDEN FIELD -->
<div class="row push-bottom inline-time">
	<label>Arrival (Costa Rica Time) *</label>
	<div class="select-grid">
		<select name="arrival_time_hour_<?php echo $Guest->getPostID(); ?>">
			<!-- Default value -->
			<option value="">Hour</option>
			<?php foreach ( $Guest::getGuestTravelHoursOptions() as $option ): ?>
				<option value="<?php echo $option; ?>" <?php echo $option === $Guest->getArrivalTimeHour() ? 'selected' : ''; ?>><?php echo $option; ?></option>
			<?php endforeach; ?>
		</select>
		<select name="arrival_time_minute_<?php echo $Guest->getPostID(); ?>">
			<!-- Default value -->
			<option value="">Minute</option>
			<?php foreach ( $Guest::getGuestTravelMinutesOptions() as $option ): ?>
				<option value="<?php echo $option; ?>" <?php echo $option === $Guest->getArrivalTimeMinute() ? 'selected' : ''; ?>><?php echo $option; ?></option>
			<?php endforeach; ?>
		</select>
		<select name="arrival_time_meridiem_<?php echo $Guest->getPostID(); ?>">
			<!-- Default value -->
			<!--<option value="" disabled<?php echo $option == '' ? ' selected' : ''; ?>>AM/PM</option>-->
			<?php foreach ( [ 'AM', 'PM' ] as $option ) : ?>
				<option value="<?php echo $option; ?>" <?php echo $option === $Guest->getArrivalTimeMeridiem() ? 'selected' : ''; ?>><?php echo $option; ?></option>
			<?php endforeach; ?>
		</select>
	</div>
</div>
<div class="row push-bottom">
	<label>Departure Airline *</label>
	<input type="text" name="departure_airline_<?php echo $Guest->getPostID(); ?>" value="<?php echo $Guest->getDepartureAirline(); ?>">
</div>
<div class="row push-bottom">
	<label>Departure Flight Number *</label>
	<input type="text" name="departure_flight_number_<?php echo $Guest->getPostID(); ?>" value="<?php echo $Guest->getDepartureFlightNumber(); ?>">
</div>
<div class="row push-bottom">
	<label>Requires transportation from the Villa upon departure?</label>
	<div class="flex-row">
		<input type="radio" name="requires_departure_transportation_<?php echo $Guest->getPostID(); ?>" <?php echo $Guest->requiresDepartureTransportation() ? 'checked' : ''; ?> value="1"> <label class="push-right">Yes</label>
		<input type="radio" name="requires_departure_transportation_<?php echo $Guest->getPostID(); ?>" <?php echo ! $Guest->requiresDepartureTransportation() ? 'checked' : ''; ?> value="0"> <label class="push-right">No</label>
	</div>
</div>
<!-- BEGIN HIDDEN FIELD -->
<div class="row push-bottom departure-date-<?php echo $i; ?> hidden">
	<label>Departure Date *</label>
	<input type="text" name="departure_date_<?php echo $Guest->getPostID(); ?>" value="<?php echo $Guest->getDepartureDate(); ?>">
</div>
<!-- END HIDDEN FIELD -->
<div class="row push-bottom inline-time">
	<label>Departure (Costa Rica Time) *</label>
	<div class="select-grid">
		<select name="departure_time_hour_<?php echo $Guest->getPostID(); ?>">
			<!-- Default value -->
			<option value="">Hour</option>
			<?php foreach ( $Guest::getGuestTravelHoursOptions() as $option ): ?>
				<option value="<?php echo $option; ?>" <?php echo $option === $Guest->getDepartureTimeHour() ? 'selected' : ''; ?>><?php echo $option; ?></option>
			<?php endforeach; ?>
		</select>
		<select name="departure_time_minute_<?php echo $Guest->getPostID(); ?>">
			<!-- Default value -->
			<option value="">Minute</option>
			<?php foreach ( $Guest::getGuestTravelMinutesOptions() as $option ): ?>
				<option value="<?php echo $option; ?>" <?php echo $option === $Guest->getDepartureTimeMinute() ? 'selected' : ''; ?>><?php echo $option; ?></option>
			<?php endforeach; ?>
		</select>
		<select name="departure_time_meridiem_<?php echo $Guest->getPostID(); ?>">
			<!-- Default value -->
			<!--<option value="">AM/PM</option>-->
			<?php foreach ( [ 'AM', 'PM' ] as $option ) : ?>
				<option value="<?php echo $option; ?>" <?php echo $option === $Guest->getDepartureTimeMeridiem() ? 'selected' : ''; ?>><?php echo $option; ?></option>
			<?php endforeach; ?>
		</select>
	</div>
</div>
<div class="row push-bottom">
	<label>Additional Travel Notes</label> <small>*If you are coming to the Villa with a company other than the Villa please put the company phone number here</small>
	<textarea name="travel_notes_<?php echo $Guest->getPostID(); ?>" rows="4" cols="50"><?php echo $Guest->getTravelNotes(); ?></textarea>
</div>
<!-- guest_travel_status -->
<div class="row push-bottom">
	<label>Travel Status</label>
	<select name="guest_travel_status_<?php echo $Guest->getPostID(); ?>">
		<option value="ready" <?php echo 'ready' === $Guest->getTravelArrangementsStatus() ? 'selected' : ''; ?>>Ready</option>
		<option value="not_ready" <?php echo 'not_ready' === $Guest->getTravelArrangementsStatus() ? 'selected' : ''; ?>>Not Ready</option>
		<option value="waiting" <?php echo 'waiting' === $Guest->getTravelArrangementsStatus() ? 'selected' : ''; ?>>Waiting</option>
		<option value="not_going" <?php echo 'not_going' === $Guest->getTravelArrangementsStatus() ? 'selected' : ''; ?>>Not Going</option>
	</select>
</div>
<?php if ( ! isset( $hide_submit ) ) : ?>
<!-- save -->
<div class="row push-bottom">
	<button type="submit" class="btn" value="<?php echo $Guest->getPostID(); ?>" name="guest_id">Save</button>
	<div class="alert mt-2">
		<small><u>*Your information will not be saved until all required fields are completed.</u></small>
	</div>
</div>
<?php endif; ?>