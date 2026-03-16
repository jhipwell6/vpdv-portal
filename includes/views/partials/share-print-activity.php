<?php
// Default to empty string
$title_time_prefix = '';

// Figure out which time to display
if ( $Activity->isBooked() && ! empty( $Activity->getBookedTime() ) ) {
	$title_time_prefix = $Activity->getBookedTime();
} elseif ( ! empty( $Activity->getRequestedTime() ) ) {
	$title_time_prefix = $Activity->getRequestedTime(); // If Activity has been booked, but concierge did not choose an "official" booked time yet, display the client's requested time.
}

// If there is a time to display at all, separate it from the title with a ' - '
if ( ! empty( $title_time_prefix ) ) {
	$title_time_prefix .= ' - ';
}

if ( ( isset( $_GET['staffonly'] ) && $_GET['staffonly'] == 1 ) || ( isset( $is_summary ) && $is_summary ) ) {
	$is_concierge = true;
}
?>
<div class="js-accordion-wrapper">
	<h6 class="accordion-btn active">
		<span class="activity--title-name"><?php echo $title_time_prefix; /* Might be an empty string - see above */ ?><?php echo $Activity->getDisplayTitle(); ?></span>
	</h6>
	<div class="accordion-cont accordion-active">
		<h6 class="print-hidden">Activity Details: </h6>
		<!-- Only display this row if the activity requires client confirmation -->
		<?php if ( $Activity->isClientConfirmationRequired() ) { ?>
	        <p>
	            <b>I am ready to book this item: </b><?php echo $Activity->isClientConfirmed() ? "Yes" : "No"; ?>
	        </p>
		<?php } ?>
		<?php if ( $Activity->isBooked() ) { ?>
			<p>
				<b>Booked: </b><?php echo $Activity->isBooked() ? "Yes" : "No"; ?>
			</p>
			<?php if ( ( ! $Activity->isHideSharePrice()) || $is_concierge || get_current_user_id() === (int) $Itinerary->getUserID() ) { ?>
				<!-- If the user is not the concierge or the owner of the itinerary (client), and the "Please do not show price" box has been checked - hide the exact/final pricing -->
				<p>
					<b>Exact/Final Cost: </b><?php echo is_numeric( $Activity->getExactFinalCost() ) ? '$' . $Activity->getExactFinalCost() : ''; ?>
				</p>
			<?php } ?>
	        <p>
	            <b>Exact Time Booked: </b><?php echo ! empty( $Activity->getBookedTime() ) ? $Activity->getBookedTime() : 'Time not specified'; ?>
	        </p>
		<?php } else { ?>
	        <p>
	            <b>Requested Time: </b><?php echo ! empty( $Activity->getRequestedTime() ) ? $Activity->getRequestedTime() : 'Time not specified'; ?>
	        </p>
		<?php } ?>
		<!-- Only display for standard activities -->
		<?php if ( $Activity->hasActivityTypePost() ) { ?>
			<?php if ( ( ! $Activity->isHideSharePrice()) || $is_concierge || get_current_user_id() === (int) $Itinerary->getUserID() ) { ?>
				<!-- If the user is not the concierge or the owner of the itinerary (client), and the "Please do not show price" box has been checked - hide the standard pricing -->
				<?php if ( $Activity->getActivityTypePriceAdult() ) { ?>
				<p>
					<b>Adult Price: </b><?php echo '$' . $Activity->getActivityTypePriceAdult(); ?>
				</p>
				<?php } ?>
				<?php if ( $Activity->getActivityTypePriceChild() ) { ?>
				<p>
					<b>Child Price: </b><?php echo '$' . $Activity->getActivityTypePriceChild(); ?>
				</p>
				<?php } ?>
			<?php } ?>
		<?php } ?>

		<?php if ( $Activity->isNoConflict() ): ?>
			<p>
				<b>Please do not schedule other activities over this event: </b><?php echo $Activity->isNoConflict() ? 'Yes' : 'No'; ?>
			</p>
		<?php endif; ?>

		<?php if ( $Activity->isPrivateTourEnabled() ) { ?>
			<p>
				<b>Private Tour: </b><?php echo $Activity->isPrivateTourRequested() ? 'Yes' : 'No'; ?>
			</p>
			<?php if ( ( ! $Activity->isHideSharePrice()) || $is_concierge || get_current_user_id() === (int) $Itinerary->getUserID() ) { ?>
				<!-- If the user is not the concierge or the owner of the itinerary (client), and the "Please do not show price" box has been checked - hide the private tour price -->
				<?php if ( $Activity->getPrivateTourPrice() ) { ?>
				<p>
					<b>Private Tour Price: </b><?php echo $Activity->getPrivateTourPriceString(); ?>
				</p>
				<?php } ?>
			<?php } ?>
		<?php } ?>
		<p>
			<b>Adults: </b><?php echo $Activity->getNumberOfAdults(); ?>
			<b>Children: </b><?php echo $Activity->getNumberOfChildren(); ?>
		<p>	
			<?php if ( $Activity->isSpecificGuests() ): ?>
				<b>Guests: </b><?php echo $Activity->getGuests() ? implode( ', ', $Activity->getGuests() ) : 'No guests selected'; ?>
			<?php else: ?>
				<b>Guests: </b> All Guests
			<?php endif; ?>
		</p>

		<?php if ( $Activity->getChildGuests() ): ?>
			<p>
				<b>Child Guests: </b><?php echo $Activity->getChildGuests() ? implode( ', ', $Activity->getChildGuests() ) : ''; ?>
			</p>
		<?php endif; ?>
			
		<p>
			<b>Guest Count: </b><?php echo $Activity->isSpecificGuests() ? count( $Activity->getGuests() ) : $Itinerary->getTotalGuestsCount(); ?>
		</p>

		<?php if ( ( $is_concierge || get_current_user_id() === (int) $Itinerary->getUserID() ) && $Activity->isHideSharePrice() ) { ?>
	        <!-- If user is concierge or the owner (client) of the itinerary, display their feedback for the "Please do not share this activity's price with guests." option -->
	        <p>
	            <b>Please do not share this activity's price with guests: </b><?php echo $Activity->isHideSharePrice() ? 'Yes' : 'No'; ?>
	        </p>
		<?php } ?>
		<?php if ( ( ! $Activity->isMessagePrivate()) || $is_concierge || get_current_user_id() === (int) $Itinerary->getUserID() ) { ?>
	        <!-- If the user is not the concierge or the owner of the itinerary (client), and the "Please keep this comment private" box has been checked - hide the comment -->
			<?php if ( $Activity->getSpecialComments() ): ?>
				<p>
					<b>Special Requests/Comments: </b>
				</p>

				<p>
					<?php echo $Activity->getSpecialComments(); ?>
				</p>
			<?php endif; ?>
		<?php } ?>
		<?php if ( ( $is_concierge ) && $Activity->isMessagePrivate() ) { ?>
	        <!-- If user is concierge or the owner (client) of the itinerary, display their feedback for the "Please keep this comment private" option -->
	        <p>
	            <b>Please keep this comment private: </b><?php echo $Activity->isMessagePrivate() ? 'Yes' : 'No'; ?>
	        </p>
		<?php } ?>

		<?php if ( $Activity->getMessage() ): ?>
			<p>
				<b>Concierge Recommendation/Concierge Message:</b>
			</p>
<p class="white-space-pre">
<?php echo trim( $Activity->getMessage() ); ?>
	
<?php if ( $Activity->hasPunctualityReminder() ) : ?>
<?php echo $Activity->getPunctualityReminder(); ?>
<?php endif; ?>
</p>
		<?php endif; ?>
			
		<?php if ( $Activity->getCreatedBy() && is_user_logged_in() ) { ?>
	        <p>
	            <b>Created by: </b><?php echo $Activity->getCreatedByName(); ?>
	        </p>
		<?php } ?>
			
		<?php if ( $Activity->getUpdatedBy() && is_user_logged_in() ) { ?>
	        <p>
	            <b>Updated by: </b><?php echo $Activity->getUpdatedByName(); ?>
	        </p>
		<?php } ?>

		<?php if ( $is_concierge ) { ?>
	        <p>
	            <b>Staff Notes: </b>
	        </p>
<p class="white-space-pre">
<?php echo trim( $Activity->getStaffNotes() ); ?>
</p>
		<?php } ?>
	</div>
</div>
