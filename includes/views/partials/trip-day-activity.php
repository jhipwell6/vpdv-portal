<div class="js-accordion-wrapper activity-accordion<?php echo $is_concierge ? ' is-concierge' : ''; ?> js-" data-activity="day-<?php echo $d . '-activity-' . $a; ?>">
    <input type="hidden" name="activity_counter" value="<?php echo $a; ?>">
    <input type="hidden" name="celebration-day-<?php echo $d . '-activity-' . $a; ?>" value="<?php echo $Activity->isCelebration() ? '1' : ''; ?>">
    <input type="hidden" name="activity_title-day-<?php echo $d . '-activity-' . $a; ?>" value="<?php echo ( ! $Activity->doesNotHaveActivityTypePost()) ? $Activity->getActivityTypePostID() : ''; ?>">

	<input type="hidden" name="fxup_created_by-day-<?php echo $d . '-activity-' . $a; ?>" value="<?php echo $Activity->getCreatedBy() ? $Activity->getCreatedBy() : get_current_user_id(); ?>">
	<input type="hidden" name="fxup_updated_by-day-<?php echo $d . '-activity-' . $a; ?>" value="<?php echo get_current_user_id(); ?>">

    <!-- BEGIN ACTIVITY EDIT BUTTONS -->
    <h6 class="accordion-btn">
		<?php
		// Default to empty string
		$title_time_prefix = '';
		$title_time_separator = ' - ';
		// Figure out which time to display
		if ( $Activity->isBooked() && ! empty( $Activity->getBookedTime() ) ) {
			$title_time_prefix = $Activity->getBookedTime();
		} elseif ( ! empty( $Activity->getRequestedTime() ) ) {
			$title_time_prefix = $Activity->getRequestedTime(); // If Activity has been booked, but concierge did not choose an "official" booked time yet, display the client's requested time.
		}

		// If there is a time to display at all, separate it from the title with a ' - '
		if ( ! empty( $title_time_prefix ) ) {
			#$title_time_prefix .= ' - ';
		}
		?>
		<span class="activity--title">
			<span class="activity--title-time">
				<span class="activity--title-timePrefix"><?php echo $title_time_prefix; /* Might be an empty string - see above */ ?></span>
				<span class="activity--title-timeSeparator" style="<?php echo empty( $title_time_prefix ) ? 'display: none;' : ''; ?>"><?php echo $title_time_separator; ?></span>
			</span>
			<span class="activity--title-name" data-id="<?php echo esc_attr( $Activity->getActivityTypePostID() ); ?>"><?php echo $Activity->getDisplayTitle(); ?></span>
			<span class="activity-booked activity-booked-memo" style="<?php echo $Activity->isBooked() ? '' : 'display: none;' ?>">Booked</span>
        </span>
        <span class="activity--buttons <?php echo $hide ? '' : 'active'; ?>">
            <a class="js-accordion-toggle btn btn-secondary">
                Edit Activity <i class="fas fa-chevron-down"></i>
            </a>
			<?php if ( $editable ) : ?>
				<a href="" class="js-delete delete btn btn-secondary">Delete Activity</a>
			<?php endif; ?>
			<?php if ( $Activity->isBooked() && $Activity->hasPaymentLink() ) : ?>
				<a href="<?php echo esc_url( $Activity->getPaymentLink() ); ?>" target="_blank" class="btn btn-primary">Pay Now</a>
			<?php endif; ?>
        </span>
    </h6>
    <!-- END ACTIVITY EDIT BUTTONS -->
    <!-- BEGIN ALL ACCORDION CONTENT -->
    <div class="accordion-cont js-tab-group js-accordion-cont" style="<?php echo $hide ? 'display: none;' : 'display: block;'; ?>">
		
		<?php if ( $is_concierge ) { ?>
		<div class="tab-nav">
			<button class="js-tab-button active" data-tab="edit">Edit</button>
			<button class="js-tab-button" data-tab="details">Details</button>
		</div>
		<?php } ?>
		<div class="tabs">
		
			<div class="tab-content" data-tab="details">
        <!-- BEGIN IF CLIENT CONFIRMATION REQUIRED -->
		<?php if ( $Activity->isClientConfirmationRequired() ) { ?>

			<div class="fxup-tooltip-container">
				<label class="checkbox-label activity-approval-label" for="fxup_activity_client_confirmed-day-<?php echo $d . '-activity-' . $a; ?>" >
					<!-- Warning message shows if customer has not confirmed yet -->
					<span class="activity-approval-warning-message" style="<?php echo ($Activity->isClientConfirmed()) ? 'display: none;' : ''; ?>"><?php echo $activity_approval_warning_message; ?></span>
					<!-- Confirmed message shows if customer has confirmed -->
					<span class="activity-approval-confirmed-message" style="<?php echo ( ! $Activity->isClientConfirmed()) ? 'display: none;' : ''; ?>"><?php echo $activity_approval_confirmed_message; ?></span>
				</label>
				<!-- Checkbox always shows unless user is customer and has already confirmed -->

				<div class="fxup-tooltip-container-checkbox-label-flex soft-bottom" style="<?php echo (( ! $is_concierge) && $Activity->isClientConfirmed()) ? 'display: none;' : ''; ?>">
					<label class="checkbox-label">
						<input class="activity-approval-confirm" id="fxup_activity_client_confirmed-day-<?php echo $d . '-activity-' . $a; ?>" type="checkbox" value="1" <?php echo $Activity->isClientConfirmed() ? 'checked' : ''; ?> name="fxup_activity_client_confirmed-day-<?php echo $d . '-activity-' . $a; ?>">
						&nbsp; I am ready to book this item.
					</label>
					<i class="far fa-question-circle fxup-tooltip-icon fxup-tooltip-container-checkbox-label-flex-item"></i>
				</div>
				<span class="fxup-tooltip-text fxup-tooltip-container-checkbox-label-flex-item"><?php echo $booking_confirmation_tooltip; ?></span>

				<!-- Always hidden input sets confirmed status -->
				<input type="hidden" class="activity-approval-already-confirm" value="<?php echo $Activity->isClientConfirmed() ? '1' : '0' ?>" name="fxup_activity_client_confirmed_already-day-<?php echo $d . '-activity-' . $a; ?>">
			</div>
		<?php } ?>
        <!-- END IF CLIENT CONFIRMATION REQUIRED -->
        <!-- BEGIN IF ACTIVITY BOOKED -->
        <!-- Put summary info here - no inputs -->
        <div class="activity-booked-info push-top" style="<?php echo $Activity->isBooked() ? '' : 'display: none;'; ?>">
			<!-- BEGIN IF EXACT FINAL COST HAS BEEN SET -->
            <p class="fxup-tooltip-container exact-final-cost-memo">
                <b>Exact/Final Cost: </b>
                <span class="total-price">
                    <!-- Only output a $ amount if the price is numeric (AKA, set by the concierge) -->
					<?php if ( is_numeric( $Activity->getExactFinalCost() ) ) { ?>
	                    $<span class="exact-final-cost-memo-value"><?php echo $Activity->getExactFinalCost(); ?></span>
					<?php } ?>
                </span>
                <i class="far fa-question-circle fxup-tooltip-icon"></i>
                <span class="fxup-tooltip-text">The prices are subject to change</span>
            </p>
            <!-- END IF EXACT FINAL COST HAS BEEN SET -->
            <!-- BEGIN IF EXACT TIME BOOKED HAS BEEN SET -->
            <p class="exact-time-booked-memo" style="<?php /* echo (!empty($Activity->getBookedTime())) ? '' : 'display: none;' ; */ ?>">
                <b>Time Booked:</b> <span class="exact-time-booked-memo-value"><?php echo ! empty( $Activity->getBookedTime() ) ? $Activity->getBookedTime() : 'Time not specified'; ?></span>
            </p>
            <!-- END IF EXACT TIME BOOKED HAS BEEN SET -->
        </div>
        <!-- END IF ACTIVITY BOOKED -->
        <!-- BEGIN FLEX WRAPPER INTRO -->
        <div class="accordion-flex-wrapper-intro <?php echo $Activity->doesNotHaveActivityTypePost() ? '' : 'with-image'; ?>">
            <!-- BEGIN IF STANDARD ACTIVITY -->
			<?php if ( ! $Activity->doesNotHaveActivityTypePost() ) : ?>

				<!-- BEGIN THUMBNAIL -->
				<?php if ( has_post_thumbnail( $Activity->getActivityTypePostID() ) ) : ?>
					<?php echo get_the_post_thumbnail( $Activity->getActivityTypePostID(), 'full', array( 'class' => 'img-responsive' ) ); ?>
				<?php else : ?>
					<img class="img-responsive" src="<?php echo get_stylesheet_directory_uri(); ?>/assets/img/villa-default.jpg">
				<?php endif; ?>
				<!-- END THUMBNAIL -->

				<!-- BEGIN PRICES AND ACTIVITY DESCRIPTION PARAGRAPH -->
				<div class="">
					<!-- BEGIN IF PRICE IS NUMERIC AND NOT 0 -->
					<?php if ( $Activity->getActivityTypePriceAdult() > 0 || $Activity->getActivityTypePriceChild() > 0 ): ?>
						<!-- BEGIN STANDARD PRICES -->
						<div class="prices-flex--wrapper fxup-tooltip-container">
							<label class="prices-title">Prices</label>
							<p class="activity-prices fxup-tooltip-label"><?php echo '$<span class="js-price-adult">' . $Activity->getActivityTypePriceAdult() . '</span>/Adult | $<span class="js-price-child">' . $Activity->getActivityTypePriceChild() . '</span>/Child'; ?></p>
							<i class="far fa-question-circle fxup-tooltip-icon"></i>
							<p class="fxup-tooltip-text inline"><?php echo $activity_prices_tooltip; ?></p>
						</div>
					<?php endif; ?>
					<!-- END IF PRICE IS NUMERIC AND NOT 0 -->
					<!-- END STANDARD PRICES -->
					<!-- BEGIN ACTIVITY DESCRIPTION PARAGRAPH -->
					<?php echo $Activity->getActivityTypeContent(); ?>
					<!-- END ACTIVITY DESCRIPTION PARAGRAPH -->
				</div>
				<!-- END PRICES AND ACTIVITY DESCRIPTION PARAGRAPH -->

			<?php endif; ?>
            <!-- END IF STANDARD ACTIVITY -->
        </div>
        <!-- END FLEX WRAPPER INTRO -->
			</div>
			<div class="tab-content active" data-tab="edit">
        <!-- BEGIN EVERYTHING ELSE -->
        <div class="row push-top">
            <div class="col-xxs-12 ">
                <div class="col-xxs-12">
                    <h3 class="uppercase">Edit Your Activity Details</h3>
                </div>
				<div class="col-xxs-12 push-bottom">
					<?php if ( $Activity->getCreatedBy() ) { ?>
						<p style="margin:0">
							<small>Added by: <?php echo $Activity->getCreatedByName(); ?></small><br />
							<small>Updated by: <?php echo $Activity->getUpdatedByName(); ?></small>
						</p>
					<?php } ?>
				</div>
                <!-- BEGIN CUSTOM ACTIVITIES ONLY -->
				<?php if ( $Activity->doesNotHaveActivityTypePost() ) { ?>
	                <div class="col-xxs-12 col-md-6 push-bottom">
	                    <label>Activity Title*</label>
	                    <input type="text" name="custom_activity_title-day-<?php echo $d . '-activity-' . $a; ?>" value="<?php echo $Activity->getCustomActivityTitle(); ?>">
	                </div>
				<?php } ?>
                <!-- END CUSTOM ACTIVITIES ONLY -->
                <div class="col-xxs-12 col-md-6 push-bottom">
                    <label>Requested Time*</label>
                    <select name="activity_time_booked-day-<?php echo $d . '-activity-' . $a; ?>"  <?php echo $editable ? '' : 'disabled'; ?>>
                        <option value="">Select a time</option>
						<?php foreach ( $Activity->getRequestedTimeOptions() as $time ) : ?>
							<option value="<?php echo $time; ?>" <?php echo $time === $Activity->getRequestedTime() ? 'selected' : ''; ?>><?php echo $time; ?></option>
						<?php endforeach; ?>
                    </select>
                </div>
                <!-- This no conflict checkbox is in the template 2x, for both Custom and Standard activities -->
                <div class="col-xxs-12 push-bottom">
                    <label class="checkbox-label">
                        <input type="checkbox" value="1" name="activity_no_conflict-day-<?php echo $d . '-activity-' . $a; ?>" <?php echo $Activity->isNoConflict() ? 'checked' : ''; ?> <?php echo $editable ? '' : 'disabled'; ?>>
                        Please do not schedule other activities over this event
                    </label>
                </div>
                <!-- BEGIN IF PRIVATE TOUR ENABLED -->
				<?php if ( $Activity->isPrivateTourEnabled() ) : ?>
	                <div class="col-xxs-12 push-bottom">
	                    <label class="checkbox-label">
	                        <input type="checkbox" value="1" name="fxup_private_tour_requested-day-<?php echo $d . '-activity-' . $a; ?>" <?php echo $Activity->isPrivateTourRequested() ? 'checked' : ''; ?> <?php echo $editable ? '' : 'disabled'; ?>>
	                        Private Tour<?php echo $Activity->getPrivateTourPriceString() ? ' - ' . $Activity->getPrivateTourPriceString() : ''; ?>
	                    </label>
	                    <i class="itin-tooltip far fa-question-circle"><div class="itin-tooltip-text hidden">
								<span class="arrow-up"></span>
								<span class="tooltip">Private tours must be booked ahead of time and have an additional cost. We recommend private tours for the most intimate experience. </span>
							</div></i>
	                </div>
				<?php endif; ?>
                <!-- END IF PRIVATE TOUR ENABLED -->
                <!-- Custom Activity Description and Standard Activity Special Requests are the same field, in the template 2x -->
                <div class="col-xxs-12 push-bottom">
                    <label><?php echo $Activity->doesNotHaveActivityTypePost() ? 'Description' : 'Special Requests'; ?></label>
                    <textarea name="activity_comments-day-<?php echo $d . '-activity-' . $a; ?>" rows="4" cols="50"><?php echo $Activity->getSpecialComments(); ?></textarea>
                </div>
                <!-- Privacy checkbox is in the template 2x, for both Custom and Standard activities -->
                <div class="col-xxs-12 push-bottom">
                    <label class="checkbox-label"><input type="checkbox" value="1" name="message_private-day-<?php echo $d . '-activity-' . $a; ?>" <?php echo $Activity->isMessagePrivate() ? 'checked' : ''; ?> <?php echo $editable ? '' : 'disabled'; ?>>Please keep this comment private</label>
                </div>
                <!-- BEGIN GUESTS -->
                <div class="col-xxs-12">
                    <h4>Guests Attending</h4>
                    <div class="js-guest-wrap wrap-margin">
                        <div class="guest--radio">
                            <div>
                                <input type="radio" class="js-guest-check" name="guest-toggle-day-<?php echo $d; ?>-activity-<?php echo $a; ?>" id="day-<?php echo $d; ?>-activity-<?php echo $a; ?>-guest-specific" value="specific"<?php echo $Activity->isSpecificGuests() ? ' checked' : ''; ?>><label>Specific Guests</label>
                            </div>
                            <div>
                                <input type="radio" class="js-guest-check" name="guest-toggle-day-<?php echo $d; ?>-activity-<?php echo $a; ?>" id="day-<?php echo $d; ?>-activity-<?php echo $a; ?>-guest-all" value="all"<?php echo $Activity->isSpecificGuests() ? '' : ' checked'; ?>><label>All Guests (<?php echo $Itinerary->getTotalGuestsCount(); ?>)</label>
                            </div>
                        </div>
                        <!-- BEGIN IF ITINERARY GUESTS EXIST -->
						<?php if ( ! empty( $guest_posts ) ) : ?>
							<div class="push-ends js-specific-guests" <?php echo $Activity->isSpecificGuests() ? '' : 'style="display: none;"'; ?>>
								<div class="push-top fxup-tooltip-container">
									<label>Guests</label>
									<i class="far fa-question-circle fxup-tooltip-icon"></i>
									<span class="fxup-tooltip-text"><?php echo $activity_guests_attending_tooltip; ?></span>
									<!-- BEGIN IF STANDARD ACTIVITY AND MAX GUESTS -->
									<?php if ( $Activity->getActivityTypeMaxGuests() ) : ?>
										<p>Max Guests: <?php echo $Activity->getActivityTypeMaxGuests(); ?></p>
									<?php endif; ?>
									<?php if ( $Activity->isSpecificGuests() ) : ?>
										<p>Guests Attending: <?php echo count( $Activity->getGuests() ); ?></p>
									<?php endif; ?>
									<!-- END IF STANDARD ACTIVITY AND MAX GUESTS -->
									<select class="js-disable-mobile multi-select js-guests" name="guests-day-<?php echo $d . '-activity-' . $a; ?>[]"  <?php echo $editable ? '' : 'disabled'; ?> multiple="multiple">
										<?php foreach ( $guest_posts as $guest_post_object ) { ?>
											<?php $name = get_post_meta( $guest_post_object->ID, 'guest_first_name', true ) . ' ' . get_post_meta( $guest_post_object->ID, 'guest_last_name', true ); ?>
											<option value="<?php echo $name; ?>" <?php echo in_array( $name, $Activity->getGuests() ) ? 'selected' : ''; ?>><?php echo $name; ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="push-top fxup-tooltip-container">
									<label class="soft-top">Child Guests (Under 18)</label>
									<i class="far fa-question-circle fxup-tooltip-icon"></i>
									<span class="fxup-tooltip-text"><?php echo $activity_child_guests_tooltip; ?></span>
									<input type="text" placeholder="<?php echo $activity_child_guests_names_example_placeholder; ?>"name="child-guests-day-<?php echo $d . '-activity-' . $a; ?>" value="<?php echo implode( ', ', $Activity->getChildGuests() ); ?>">
								</div>
							</div>
						<?php endif; ?>
                        <!-- END IF ITINERARY GUESTS EXIST -->
                    </div>
                </div>
                <div class="col-xxs-12 push-bottom">
                    <label class="checkbox-label">
                        <input type="checkbox" value="1" name="activity_hide_share_price-day-<?php echo $d . '-activity-' . $a; ?>" <?php echo $Activity->isHideSharePrice() ? 'checked' : ''; ?> <?php echo $editable ? '' : 'disabled'; ?>>
                        Please do not share this activity's price with guests.
                    </label>
                </div>
                <!-- END GUESTS -->
                <!-- BEGIN IF MESSAGE SET OR STAFF -->
                <div class="col-xxs-12 push-top" style="<?php echo (( ! empty( $Activity->getMessage() )) || $is_concierge) ? '' : 'display: none;'; ?>">
                    <h4>Messages from Staff</h4>
                </div>
                <!-- END IF MESSAGE SET OR STAFF -->
                <!-- BEGIN IF MESSAGE OPTIONS EXIST AND STAFF -->
				<?php if ( ! empty( $Activity->getMessageOptions() ) ) { ?>
	                <div class="col-xxs-12 push-bottom fxup-concierge-only-field-label activity-message-options-container" style="<?php echo $is_concierge ? '' : 'display: none;'; ?>">
	                    <label>Concierge Recommendation/Concierge Message Options</label>
	                    <select class="activity-message-options" name="activity_message_options-day-<?php echo $d . '-activity-' . $a; ?>">
	                        <option value="">Select an option</option>
							<?php foreach ( $Activity->getMessageOptions() as $message_option ) { ?>
		                        <option value="<?php echo $message_option['body']; ?>" <?php echo ($message_option['body'] === $Activity->getMessage()) ? 'selected' : ''; ?>><?php echo $message_option['title']; ?></option>
							<?php } ?>
	                    </select>
	                </div>
				<?php } ?>
                <!-- END IF MESSAGE OPTIONS EXIST AND STAFF -->
                <!-- BEGIN IF MESSAGE SET OR CONCIERGE -->
                <div class="col-xxs-12 push-bottom activity-message-container" style="<?php echo (( ! empty( $Activity->getMessage() )) || $is_concierge) ? '' : 'display: none;'; ?>">
                    <!-- Label and textarea only shows for concierge, but whatever they type here will display to customer -->
                    <label style="<?php echo ( ! $is_concierge) ? 'display: none;' : ''; ?>" class="fxup-concierge-only-field-label">Concierge Recommendation/Concierge Message</label>
                    <textarea class="activity-message" name="activity_message-day-<?php echo $d . '-activity-' . $a; ?>" rows="4" cols="50" style="<?php echo ( ! $is_concierge) ? 'display: none;' : ''; ?>"><?php echo $Activity->getMessage(); ?></textarea>
                    <!-- Paragraph text only shows for customer -->
                    <p style="<?php echo ($is_concierge) ? 'display: none;' : ''; ?>"><?php echo $Activity->getMessage(); ?></p>
                </div>
                <!-- END IF MESSAGE SET OR CONCIERGE -->
                <!-- BEGIN STAFF ONLY -->
				<?php if ( ! $is_concierge ) { ?><div style="display:none !important;"><?php } ?>
					<div class="col-xxs-12 push-top">
						<h4 class="fxup-concierge-only-field-label">Staff Options</h4>
					</div>
					<div class="col-xxs-12 push-bottom fxup-tooltip-container fxup-tooltip-container-checkbox-label-flex ">
						<label class="checkbox-label fxup-concierge-only-field-label">
							<input type="checkbox" class="keep-private-checkbox" name="keep_private-day-<?php echo $d . '-activity-' . $a; ?>" value="1" <?php echo $Activity->keepPrivate() ? 'checked' : ''; ?>>
							Keep Private
						</label>
						<?php /* ?>
						  <i class="far fa-question-circle fxup-tooltip-icon fxup-tooltip-container-checkbox-label-flex-item"></i>
						  <span class="fxup-tooltip-text fxup-tooltip-container-checkbox-label-flex-item"><?php echo $activity_booked_tooltip; ?></span>
						  <?php */ ?>
					</div>
					<div class="col-xxs-12 push-bottom">
						<label class="fxup-concierge-only-field-label">Time Booked</label>
						<select class="exact-time-booked-select" name="exact_time_booked-day-<?php echo $d . '-activity-' . $a; ?>" id="time">
							<option <?php echo ! in_array( $Activity->getBookedTime(), $Activity->getBookedTimeOptions() ) ? 'selected' : ''; ?>>Select a time</option>
							<?php foreach ( $Activity->getBookedTimeOptions() as $booked_time_option ) { ?>
								<option <?php echo ($booked_time_option === $Activity->getBookedTime()) ? 'selected' : ''; ?> value="<?php echo $booked_time_option ?>"><?php echo $booked_time_option; ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="col-xxs-12 col-xs-12 col-sm-4 push-bottom">
						<h4 class="fxup-concierge-only-field-label">Exact/Final Cost</h4>
						<span class="currencyinput">$<input type="number" class="exact-final-cost-input" name="exact_final_cost-day-<?php echo $d . '-activity-' . $a; ?>" value="<?php echo $Activity->getExactFinalCost(); ?>"></span>
					</div>
					<div class="col-xxs-12 col-xs-12 col-sm-4 push-bottom">
						<h4 class="fxup-concierge-only-field-label">Adult Cost</h4>
						<span class="currencyinput">$<input type="number" class="adult-cost-input" name="adult_cost-day-<?php echo $d . '-activity-' . $a; ?>" value="<?php echo $Activity->getAdultCost(); ?>"></span>
					</div>
					<div class="col-xxs-12 col-xs-12 col-sm-4 push-bottom">
						<h4 class="fxup-concierge-only-field-label">Child Cost</h4>
						<span class="currencyinput">$<input type="number" class="child-cost-input" name="child_cost-day-<?php echo $d . '-activity-' . $a; ?>" value="<?php echo $Activity->getChildCost(); ?>"></span>
					</div>
					<div class="col-xxs-12 push-bottom fxup-tooltip-container fxup-tooltip-container-checkbox-label-flex ">
						<label class="checkbox-label fxup-concierge-only-field-label">
							<input type="checkbox" class="activity-booked-checkbox" name="activity_booked-day-<?php echo $d . '-activity-' . $a; ?>" value="1" <?php echo $Activity->isBooked() ? 'checked' : ''; ?>>
							Activity Booked
						</label>
						<i class="far fa-question-circle fxup-tooltip-icon fxup-tooltip-container-checkbox-label-flex-item"></i>
						<span class="fxup-tooltip-text fxup-tooltip-container-checkbox-label-flex-item"><?php echo $activity_booked_tooltip; ?></span>
					</div>
					<div class="col-xxs-12 push-top fxup-concierge-only-field-label">
						<label>Staff Notes</label>
						<textarea name="activity_staff_notes-day-<?php echo $d . '-activity-' . $a; ?>" rows="4" cols="50"><?php echo $Activity->getStaffNotes(); ?></textarea>
					</div>
				<?php if ( ! $is_concierge ) { ?></div><?php } ?>
                <!-- END STAFF ONLY -->
            </div>
        </div>
		</div>
		</div>
        <!-- END EVERYTHING ELSE -->
    </div>
    <!-- END ALL ACCORDION CONTENT -->
</div>
