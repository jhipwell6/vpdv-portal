<header class="dashboard-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-xs-6 col-md-offset-2 col-md-4">
                <a href="https://www.villapuntodevista.com/" class="header-logo-dashboard"><img src="<?php echo get_template_directory_uri(); ?>/assets/img/login-logo.png" class="img-responsive"></a>
            </div>
            <div class="col-xs-6 col-md-4">
                <div class="dashboard-user-header">
                    <input type="hidden" name="itin-user" value="<?php echo $Itinerary->getUserID(); ?>">
                    <input type="hidden" name="concierge-user" value="<?php echo $is_concierge; ?>">
                    <span class="hello">Hello,</span> <span class="name">
						<?php if ( 'concierge' === $user_role || current_user_can( 'administrator' ) ): ?>
							Villa Concierge
						<?php else: // If user is not admin or concierge ?>
							<?php echo $user_display_name; ?>
						<?php endif; ?>
							<a href="javascript:;" class="js-notification-settings<?php echo $Itinerary->getDisableAllNotifications() ? ' text-red' : ''; ?>"><i class="fas fa-bell"></i></a>
					</span> <a href="<?php echo site_url() . '/logout'; ?>">Log Out <i class="icon-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</header>

<section class="section-container">
    <div class="container-fluid mobile-container-fluid">
        <div class="row">
            <div class="col-md-12 dashboard-container-leaf">
                <div class="dashboard-container itinerary-builder">
                    <div class="dashboard-page-header">
                        <h2 class="text-center squiggle-headline">Villa Punto de Vista Itinerary Builder<br><?php $Itinerary->getPostObject()->title; ?></h2>

                    </div>
                    <?php include $ui_navigation_path; ?>
                    <div class="row dashboard-top-options-container">
                        <div class="col-md-12 col-xxs-12 dashboard-top-option">
                            <div class="dashboard-page-content">
                                <p class="dashboard-video-link">
                                    <!--Video: &nbsp;<a href="https://youtu.be/D9TzvRDe564" class="btn btn-secondary html5lightbox"><i class="fab fa-youtube"></i> How to use your dashboard</a>-->
                                    Video: <a href="<?php echo \FXUP_User_Portal\Models\Itinerary::getVideoLinkTop( get_queried_object() )['url']; ?>" class="btn btn-secondary html5lightbox"><i class="fab fa-youtube"></i> How to use this page</a>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="container-fluid section-padding">
                        <div class="row">

                            <div class="col-md-offset-1 col-md-10">
                                <div class="dashboard-page-content">
                                    <form class="itinerary-section" id="itinerary-form" autocomplete="off">
                                        <input type="hidden" name="itinerary_total_days" value="<?php echo $Itinerary->getTripDayCount(); ?>">
                                        <input type="hidden" name="itinerary_id" value="<?php echo $Itinerary->getPostID(); ?>">
                                        <div class="itinerary-header">
                                            <div class="row">
                                                <div class="col-xs-12 dashboard-top-option">
                                                    <h2><?php echo $Itinerary->getUserDisplayName(); ?></h2>
                                                    <h2 class="trip-itinerary-title">Trip Itinerary <span class="font-large flush-bottom soft-bottom break-sm"><?php echo $Itinerary->getTripStartDate()->format( 'l, m/d/Y' ) . ' - ' . $Itinerary->getTripEndDate()->format( 'l, m/d/Y' ); ?></span></h2>
                                                    <div>
														<?php echo $above_itinerary_form_wysiwyg; ?>
                                                    </div>
                                                </div>

												<div class="col-xs-12">
													<div class="quicklinks--wrapper">
														<div class="editable-btns">
															<?php if ( $editable ): ?>
																<a class="js-itin-save" href="#"><i class="far fa-save"></i> Save</a>
																<?php if ( ! $is_concierge ): ?>
																	<?php
																	if ( $Itinerary->getApprovalStatus() == 'Pending Concierge Approval' ) {
																		$fa_class = 'fa-check';
																	} else {
																		$fa_class = 'fa-file-import';
																	}
																	?>
																	<a class="js-itin-submit" disabled href="#"><i class="fas <?php echo $fa_class; ?>"></i> Submit Itinerary to Concierge</a>
																<?php endif; ?>

	                                                                        <!-- <a href="" class="js-add-itinerary-item" data-day="0"><i class="fas fa-plus"></i> Add Activities</a> -->
															<?php endif; ?>
															<!--<a href="<?php echo $Itinerary->getShareLink(); ?>" class="share-btn js-copy-share"><i class="fas fa-share"></i> Share</a>-->
															<!--<input type="text" value="" id="js-share-value" class="hidden" name="js-share-value">-->
															<a class="share-btn" href="<?php echo $Itinerary->getShareLink(); ?>"><i class="fas fa-share"></i> Share Itinerary</a>
															<?php if ( $is_concierge ): ?><a href="javascript:;" class="js-share-summary share-btn"><i class="fas fa-clipboard-list"></i> Onsite Concierge Summary</a><?php endif; ?>
														</div>
														<div class="jumplink-wrapper">
															<h4>Jump to: </h4>
															<?php echo \FXUP_USER_PORTAL\Controllers\FXUP_Itinerary_Process::instance()->renderJumpToSelectList( $Itinerary->getPostID() ); ?>
														</div>
													</div>
												</div>
                                            </div>
                                            <div class="itinerary-status">
                                                <h5>Itinerary Status: <?php echo $Itinerary->getApprovalStatus(); ?> <i class="itin-tooltip far fa-question-circle"><div class="itin-tooltip-text hidden">
															<span class="arrow-up"></span>
															<span class="tooltip"><?php echo $Itinerary->getStatusTooltip(); ?></span>
														</div></i></h5>

                                            </div>

											<?php if ( ! $editable ): ?>
												<div class="push-bottom">
													<p class="small-notification"><i class="fas fa-lock"></i> Your trip is almost here! Your itinerary has been locked, please contact us for any last minute request to change your itinerary.</p>
												</div>
											<?php endif; ?>
											<?php if ( $is_concierge ): ?>
												<div class=" small-notification push-bottom">
													<h4>Update Itinerary Status</h4>
													<select name="itin-status" class="js-itin-status">
														<option value="Approved" <?php echo $Itinerary->getApprovalStatus() == 'Approved' ? 'selected' : ''; ?>>Approved</option>
														<option value="Pending Client Submission" <?php echo $Itinerary->getApprovalStatus() == 'Pending Client Submission' ? 'selected' : ''; ?>>Pending Client Submission</option>
														<option value="Pending Concierge Approval" <?php echo $Itinerary->getApprovalStatus() == 'Pending Concierge Approval' ? 'selected' : ''; ?>>Pending Concierge Approval</option>
													</select>
												</div>
											<?php endif; ?>
											<?php /* if( $is_concierge && ((!$Itinerary->isEditable()) ||  $Itinerary->isEditableManualOverride())): */ ?>
											<?php /* if ( $is_concierge && ( ! $Itinerary->isEditable() ) ) : */ ?>
											<?php if ( $is_concierge ) : ?>
												<div class="push-bottom">
													<label for="fxup-editable-manual-override">
														<input id="fxup-editable-manual-override" name="fxup-editable-manual-override" type="checkbox" <?php echo $Itinerary->isEditableManualOverride() ? 'checked' : ''; ?>/> Unlock Itinerary
													</label>
												</div>
											<?php endif; ?>
                                        </div>

										<?php if ( $is_concierge ) { ?>
											<!-- Only output the client bio field if the current user is a concierge -->
											<div class="itinerary-form-client-bio  push-bottom">
												<label for="fxup-itinerary-client-bio-and-notes">Client Bio and Notes</label>
												<?php wp_editor( $Itinerary->getClientBioAndNotes(), 'fxup-itinerary-client-bio-and-notes', array( 'quicktags' => false, 'textarea_rows' => 10 ) ); ?>
												<!--<textarea id="fxup-itinerary-client-bio-and-notes" name="fxup-itinerary-client-bio-and-notes" style="resize: vertical;"><?php echo $Itinerary->getClientBioAndNotes(); ?></textarea>-->
											</div>
										<?php } ?>

                                        <div class="row intinerary-row">
											<?php
											foreach ( $Itinerary->getTripDays() as $numeric_index => $TripDay ) {
												$d = $numeric_index + 1;
												include $itinerary_trip_day_path;
											}
											?>
                                        </div>

                                    </form>
                                    <!-- BEGIN IF CONCIERGE OR ALL ACTIVITIES ARE BOOKED -->
                                    <!-- Estimated total unless all activities have been booked -->
                                    <h4 class="activity-total estimated-total" style="<?php echo ($is_concierge && ! $Itinerary->isAllActivitiesBooked()) ? '' : 'display: none;'; ?>">Estimated Total: <span class="js-total-itinerary-price"><span class="js-itinerary-price-value"><?php echo $Itinerary->getTotalCostFormatted(); ?></span></span></h4>
                                    <h4 class="activity-total final-total" style="<?php echo ($Itinerary->isAllActivitiesBooked()) ? '' : 'display: none;'; ?>">Total: <span class="js-total-itinerary-price"><span class="js-itinerary-price-value"><?php echo $Itinerary->getTotalCostFormatted(); ?></span></span></h4>
                                    <!-- END IF CONCIERGE OR ALL ACTIVITIES ARE BOOKED -->
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="js-itin-save-status itin-save-status unsaved" style="display:none;">
        <div class="row">
            <div class="col-md-12">

                <div class="js-reload-warning">
                    You have unsaved changed, please remember to save before leaving the page. <a class="push-left colordarkred js-itin-save" href="#"><i class="far fa-save"></i> Save</a>
                </div>
                <div class="js-close-status itin-save-status-close">&times;</div>
            </div>
        </div>
    </div>

    <div class="js-itin-save-status-saved itin-save-status saved" style="display:none;">
        <div class="row">
            <div class="col-md-12">

				<?php if ( $is_concierge ): ?>
					<p>You have successfully saved this itinerary</p>
<?php else: ?>
					<div class="status-flex-wrapper">
						<p>You have successfully saved your itinerary, but it has not been submitted for approval</p>
	                    <a class="btn btn-thirdary js-itin-submit" href="#">Save & Submit to Concierge</a>
					</div>

<?php endif; ?>
                <div class="js-close-status itin-save-status-close">&times;</div>
            </div>
        </div>
    </div>

    <div class="js-itin-save-status-submitted itin-save-status saved" style="display:none;">
        <div class="row">
            <div class="col-md-12">
                <div class="js-close-status itin-save-status-close">&times;</div>
                <p>You have successfully saved your itinerary and submitted it for approval</p>
            </div>
        </div>
    </div>

    <!-- <div class="js-popup-confirm popup-confirm-wrapper" style="display:none">
        <div class="popup-confirm">
	<?php /* if( $is_concierge ): ?>
	  <p>You have successfully saved this itinerary</p>
	  <?php else:  ?>
	  <p>You have successfully saved your itinerary, but it has not been submitted for approval</p>
	  <a class="btn btn-thirdary js-itin-submit" href="#">Save & Submit to Concierge</a>
	  <?php endif; */ ?>
            <a class="btn btn-secondary js-itin-popup-close" href="#">Close</a>
        </div>
    </div> -->

    <!-- <div class="js-popup-exit popup-confirm-wrapper" style="display:none">
        <div class="popup-confirm">
            <p>Did you forget to save or submit your itinerary?</p>
            <a class="btn js-itin-save" href="#">Save</a>
            <a class="btn btn-thirdary js-itin-submit" href="#">Save & Submit to Concierge</a>
            <a class="btn btn-secondary js-itin-popup-close" href="#">Close</a>
        </div>
    </div> -->

    <div class="js-popup-submit-confirm popup-confirm-wrapper" style="display:none">
        <div class="popup-confirm">
            <p>Are you sure that you want to submit your itinerary to the concierge?</p>
            <a class="btn btn-thirdary js-submit-confirm" href="#">Submit to Concierge</a>
            <a class=" js-itin-popup-close" href="#"><i class="fas fa-times"></i></a>
        </div>
    </div>

    <div class="js-popup-submit-confirmed popup-confirm-wrapper" style="display:none">
        <div class="popup-confirm">
            <p>You have successfully saved your itinerary and submitted it for approval</p>
            <a class=" js-itin-popup-close" href="#"><i class="fas fa-times"></i></a>
        </div>
    </div>

    <div class="js-popup-delete popup-delete-wrapper" style="display:none">
        <div class="popup-delete">
            <p>Are you sure that you want to delete this activity from your itinerary?</p>
            <a class="btn btn-thirdary js-delete-confirm" data-actdelete="" href="#">Delete</a>
            <!-- <a class=" js-delete-cancel" href="#">Cancel</a> -->
            <a class="icon-close  js-delete-cancel js-itin-popup-close" href="#"></a>
        </div>
    </div>

	<div class="js-popup-share-summary popup-confirm-wrapper" style="display:none">
        <div class="popup-confirm">
            <p>This summary is for internal use only and will expire in 10 days.</p>
            <a class="btn btn-thirdary js-share-summary-confirm" href="#">Get Summary Link</a>
            <a class="js-itin-popup-close" href="#"><i class="fas fa-times"></i></a>
        </div>
    </div>

	<div class="js-popup-share-summary-confirmed popup-confirm-wrapper" style="display:none">
        <div class="popup-confirm">
            <p>Summary link:</p>
			<div class="input-group d-flex align-items-stretch">
				<input type="text" class="form-control" value="<?php echo $Itinerary->getSummaryLink(); ?>" />
				<div class="input-group-append d-flex">
					<button class="btn btn-outline-secondary js-copy-value" type="button">copy</button>
				</div>
			</div>
            <a class=" js-itin-popup-close" href="#"><i class="fas fa-times"></i></a>
        </div>
    </div>
	
	<div class="js-popup-notification-settings popup-confirm-wrapper" style="display:none">
        <div class="popup-confirm">
            <form id="edit-notification-settings">
				<div class="form-heading push-bottom">
					<h3>Notification Settings</h3>
				</div>
				<div class="form-group push-bottom">
					<label for="fxup-disable-all-notifications">
						<input id="fxup-disable-all-notifications" name="fxup-disable-all-notifications" type="checkbox" <?php echo $Itinerary->getDisableAllNotifications() ? 'checked' : ''; ?>/>
						Disable All Client & Guest Notifications
					</label>
				</div>
				<input type="hidden" name="itinerary_id" value="<?php echo $Itinerary->getPostID(); ?>">
				<div class="form-actions">
					<button type="submit" id="save-notification-settings" class="btn btn-thirdary">Save Settings</button>
				</div>
			</form>
            <a class="js-itin-popup-close" href="#"><i class="fas fa-times"></i></a>
        </div>
    </div>

    <div class="itin-add-popup popup-confirm-wrapper" style="display:none;">
        <div class="popup-confirm">
            <p>What kind of item would you like to add?</p>
            <a class="btn btn-thirdary btn-block js-add-activity" href="#">Activities & Tours<span class="small-btn">Ziplining, Horseback Riding, Rafting, Snorkeling and more.</span></a>
            <a class="btn btn-thirdary btn-block js-add-service" href="#">Villa Services<span class="small-btn">Yoga, Spa & Wellness, Private Chefs, Entertainment, and more</span></a>
            <a class="btn btn-thirdary btn-block js-add-wedding" href="#">Wedding Day<span class="small-btn">Plan Your Day: Ceremony, Cocktail Hour, Reception, etc.</span></a>



            <div class="js-activity-row">
                <a href="#" class="btn btn-thirdary btn-block js-add-activity-item">
                    Celebrations & Events <span class="small-btn">Birthdays, Anniversaries, Milestones, etc.</span>
                </a>
                <div class="activity-confirm-days" style="display:none; position: fixed; width: 300px; left: 50%; margin-left: -150px; top: 30%; max-height: 410px; overflow: hidden; overflow-y: scroll;">
                    <p class="activity-confirm-days-close flush">

                        <button type="button" class="icon-close js-toggle-addclass" data-target=""></button>
                    </p>
                    <p>Select a Date to add an Activity:</p>
					<?php
					foreach ( $Itinerary->getTripDays() as $numeric_index => $TripDay ) {
						$d = $numeric_index + 1;
						?>

						<input type="checkbox" name="selected_days" value="<?php echo $d; ?>" id="day-<?php echo $d; ?>"><label for="day-<?php echo $d; ?>"><?php echo 'Day ' . $d . ' (' . $TripDay->getDateTime()->format( 'F d' ) . ')'; ?></label><br>

						<?php
					}
					?>
                    <a href="#" class="push-top flex-button btn btn-secondary js-add-activity-confirm" data-custom="true" data-celebration="true">
                        Add to Itinerary
                    </a>
                </div>
            </div>
            <div class="js-activity-row">
                <a href="#" class="btn btn-thirdary btn-block js-add-activity-item">
                    Custom Activity <span class="small-btn">Add a custom activity to your schedule</span>
                </a>
                <div class="activity-confirm-days" style="display:none; position: fixed; width: 300px; left: 50%; margin-left: -150px; top: 30%; max-height: 410px; overflow: hidden; overflow-y: scroll;">
                    <p class="activity-confirm-days-close flush">

                        <button type="button" class="icon-close js-toggle-addclass" data-target=""></button>
                    </p>
                    <p>Select a Date to add an Activity:</p>
					<?php
					foreach ( $Itinerary->getTripDays() as $numeric_index => $TripDay ) {
						$d = $numeric_index + 1;
						?>

						<input type="checkbox" name="selected_days" value="<?php echo $d; ?>" id="day-<?php echo $d; ?>"><label for="day-<?php echo $d; ?>"><?php echo 'Day ' . $d . ' (' . $TripDay->getDateTime()->format( 'F d' ) . ')'; ?></label><br>

						<?php
					}
					?>
                    <a href="#" class="push-top flex-button btn btn-secondary js-add-activity-confirm" data-custom="true">
                        Add to Itinerary
                    </a>
                </div>
            </div>

            <a class="icon-close js-itin-popup-close" href="#"></a>
        </div>
    </div>
</section>

<footer class="dashboard-footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-xs-12">
                <a href="https://www.villapuntodevista.com/" class="footer-logo-dashboard "><img src="<?php echo get_template_directory_uri(); ?>/assets/img/footer-logo.png" class="img-responsive"></a>
            </div>
        </div>

    </div>
</footer>

<div id="flyouts-container">
	<?php
	// foreach ($event_types as $event_type) {
	//     include $itinerary_event_type_flyout_path;
	// }
	?>
</div>
<div class="ajax-loading">
    <div class="ajax-loading-image">
        <div class="fa-5x"><i class="fas fa-spinner fa-spin"></i></div>
    </div>
</div>
