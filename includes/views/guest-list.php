<?php
$current_user = wp_get_current_user();
$concierge = in_array( 'um_concierge', $current_user->roles );
$is_concierge = $concierge;
$ui_navigation_path = \FXUP_USER_PORTAL\Controllers\FXUP_Itinerary_Process::instance()->views['ui-navigation']['path'];

$token = isset( $_GET['itin'] ) ? filter_var( $_GET['itin'], FILTER_SANITIZE_STRING ) : false;
$Itinerary = \FXUP_User_Portal\Models\Itinerary::fromToken( $token );

if ( ! $Itinerary ):
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	get_template_part( 404 );
	exit();
elseif ( $Itinerary->getUserID() !== $current_user->ID && ! $concierge ):
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	get_template_part( 404 );
	exit();
endif;

$editable = ($Itinerary->isEditable() || $concierge);
?>
<header class="dashboard-header">
	<div class="container-fluid">
		<div class="row">
			<div class="col-xs-6 col-md-offset-2 col-md-4">
				<a href="https://www.villapuntodevista.com/" class="header-logo-dashboard"><img src="<?php echo get_template_directory_uri(); ?>/assets/img/login-logo.png" class="img-responsive"></a>
			</div>
		</div>
	</div>
</header>

<section class="section-container">

	<div class="container-fluid">
		<div class="row">
			<div class="col-md-12 dashboard-container-leaf">
				<div class="dashboard-container itinerary-builder">
					<div class="dashboard-page-header">
						<h2 class="text-center squiggle-headline">Guest List - <?php echo $Itinerary->getTitle(); ?></h2>
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
					<div class="container-fluid section-padding <?php echo $concierge ? 'guest-list-concierge' : ''; ?>">
						<div class="row">
							<div class="col-md-offset-1 col-md-10">
								<div class="dashboard-page-content">
									<h2 class="">Add to My Guest List</h2>
									<span id="guest-list-submit-validation-feedback" class="guest-list-validation-text"></span>
									<div class="quicklinks--wrapper">
										<div class="editable-btns soft-bottom">
											<!-- .js-itin-submit is used for styling, .js-glist-save is used for JavaScript targeting and AJAX. -->
											<a class="js-glist-save js-itin-submit" href="#" data-itin="<?php echo $Itinerary->getPostID(); ?>"><i class="fas fa-file-import"></i> Submit Final Guest List</a>
											<a class="share-btn" href="<?php echo $Itinerary->getGuestTravelLink(); ?>"><i class="fas fa-plane"></i> Guest Travel</a>
											<a class="share-btn" href="<?php echo $Itinerary->getSimpleGuestListLink(); ?>"><i class="fas fa-clipboard-list"></i> Printable View</a>
										</div>



										<div class="jumplink-wrapper">
											<h4>Jump to: </h4>
											<?php echo \FXUP_USER_PORTAL\Controllers\FXUP_Itinerary_Process::instance()->renderJumpToSelectList( $Itinerary->getPostID() ); ?>

										</div>
									</div>

									<p class="small-notification push-bottom">
										<i class="fas fa-book-reader fa-2x"></i>
										<span>All guest information is required to be added to the list by the Group Leader or the guest as a policy. Besides pre-arrival concierge and itinerary functionality, Security guard uses this list and no one that does not appear on the list will not be permitted the property for security reasons</span>
									</p>

									<!--TABS-->
									<div class="js-tab-wrap">
										<ul class="js-tabs flexbox-sm flexbox-justify flexbox-flexgrow hidden-xs-down">
											<li class="active">
												<a class="tab-cont-header" href="#tabcont-add-guest">Add Guests Manually</a>
											</li>
											<li class="">
												<a class="tab-cont-header" href="#tabcont-guest-info">Collect Responses from Guests</a>
											</li>
											<li class="">
												<a class="tab-cont-header" href="#tabcont-import-guests">Import/Export Guests</a>
											</li>
										</ul>
									</div>

									<!--TABS Contents-->
									<h3 class="js-accordion accordion-btn hidden-sm-up active">
										<a class="icon-chevron-down" href="#tabcont-add-guest">Add Guests</a>
									</h3>
									<div class="js-tabs-cont active" id="tabcont-add-guest">
										<?php echo do_shortcode( '[gravityform id="5" title="false" description="false" ajax="true"]' ); ?>
									</div>


									<h3 class="js-accordion accordion-btn hidden-sm-up">
										<a class="icon-chevron-down" href="#tabcont-guest-info">Request Guest Info</a>
									</h3>
									<div class="js-tabs-cont" id="tabcont-guest-info">
										<p>
											Sit back and save time by sending this link below to your guests. This way, they can add themselves (and all their need-to-know info) directly to your Guest List. - They will not be allowed in the property or allowed to be added to the rooming list without being signed up.
										</p>

										<div class="flexshare push-bottom push-top js-itinerary-link-wrapper">
											<div class="itin-link col-xxs-12 col-sm-8" style="padding: 8px; border: 1px solid #ece7e5; color: #BDBDBD; text-align: center;">
												<p class="js-itin-link flush-ends"><?php echo $Itinerary->getAddNewGuestLink(); ?></p>
											</div>
											<button class="btn btn-secondary js-copy-link"><i class="fas fa-link"></i> Copy Link</button>
										</div>
									</div>

									<h3 class="js-accordion accordion-btn hidden-sm-up">
										<a class="icon-chevron-down" href="#tabcont-import-guests">Import/Export Guests</a>
									</h3>
									<div class="js-tabs-cont text-center" id="tabcont-import-guests">
										<a href="<?php echo FXUP_USER_PORTAL()->plugin_url() . '/assets/vpdv-guest-import.csv'; ?>" class="btn btn-primary" download="vpdv-guest-import.csv">Download Import Template</a>
										<form id="form-import-guests" class="js-import-guests push-bottom" data-itinerary="<?php echo $Itinerary->getPostID(); ?>">
											<div class="form-group push-top push-bottom">
												<label for="guest_import_file">Upload a CSV file</label><br />
												<input id="guest_import_file" type="file" name="fxup_guest_import_file" class="form-control js-import-guests-file">
											</div>
											<button type="submit" class="btn btn-secondary">Import</button>
										</form>

										<button type="button" class="btn btn-secondary js-export-guests" data-itinerary="<?php echo $Itinerary->getPostID(); ?>">Export Guests</button>
									</div>

									<h2 class="clearfix push-top">My Guest List</h2>
									<p><strong class="color-black">NOTE:</strong> If you need to make any updates or corrections to your client information, simply click the 'edit' button below.</p>

									<div class="meta-items">
										<div class="guest-count--wrapper">
											<p class="push-right flush-top color-black"><strong>Total Guests:</strong> <span class="js-total-guests"></span></p>
											<p class=" flush-top push-right color-black"><strong>Total Adult:</strong> <span class="js-guests"></span></p>
											<p class="flush-top color-black"><strong>Total Children:</strong> <span class="js-children"></span></p>
										</div>
										<div class="guests-list">
											<strong>Filter List:  </strong>
											<button class="js-onsite-filter">On-site Guests</button>
											<button class="js-offsite-filter">Off-site Guests</button>
											<button class="js-clear-filter">Clear</button>
										</div>
									</div>

									<!-- Removed nowrap and nowrap class from table -->
									<table id="guest_list" class="display responsive" style="width:100%">
										<thead>
											<tr>
												<th>First Name</th>
												<th>Last Name</th>
												<th>Email</th>
												<th>Guest Type</th>
												<th>Parent</th>
												<th>Onsite?</th>
												<th>Location</th>
												<th>Villa</th>
												<th>Room</th>
												<th>Dietary Restrictions</th>
												<th>Edit</th>
												<th>Remove</th>
												<th>Other Dietary Restrictions</th>
												<th>Allergies</th>
												<th>Notes</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ( $Itinerary->getGuests() as $Guest ): ?>
												<tr data-row-guest-id="<?php echo $Guest->getPostID(); ?>">
													<td class="guest-list__first-name" data-field-name="guest_first_name"><span class="field-value"><?php echo $Guest->getFirstName(); ?></span></td>
													<td class="guest-list__last-name" data-field-name="guest_last_name"><span class="field-value"><?php echo $Guest->getLastName(); ?></span></td>
													<td class="guest-list__email" data-field-name="guest_email">
														<span class="field-value"><?php echo esc_html( vup_guest_safe_email( $Guest ) ); ?></span>
													</td>
													<td class="guest-list__type" data-field-name="guest_is_child">
														<span class="field-value"><?php echo esc_html( vup_guest_type_label( $Guest ) ); ?></span>
													</td>
													<td class="guest-list__managed-by" data-field-name="guest_parent_id">
														<span class="field-value"><?php echo esc_html( vup_guest_parent_name( $Guest ) ?: '—' ); ?></span>
													</td>
													<td class="guest-list__edit" data-field-name="onsite_stay">
														<span class="field-value js-onsite-status"><?php echo $Guest->isOnsite() ? 'Yes' : 'No'; ?></span>
													</td>
													<td class="guest-list__location" data-field-name="stay_location">
														<span class="field-value">
															<?php
															if ( ! $Guest->isOnsite() ) :
																$stay_location = ! empty( $Guest->getStayLocation() ) ? $Guest->getStayLocation() : '';
																$stay_location = $stay_location == 'Other' ? $Guest->getStayLocationOther() : $stay_location;
																?>
																<?php echo $stay_location; ?>
															<?php endif; ?>
														</span>
													</td>
													<td class="guest-list__villa" data-field-name="villa_name">
														<span class="field-value"><?php echo $Guest->getAssignedRoom() ? $Guest->getAssignedRoom()->getSubVilla()->getTitle() : ''; ?></span>
													</td>
													<td class="guest-list__room" data-field-name="room_name"><span class="field-value"><?php echo $Guest->getAssignedRoom() ? $Guest->getAssignedRoom()->getRoomName() : ''; ?></span></td>
													<td class="guest-list__dietary-restrictions" data-field-name="dietary_restrictions"><span class="field-value"><?php echo ( ! empty( $Guest->getDietaryRestrictions() )) ? implode( ', ', $Guest->getDietaryRestrictions() ) : 'N/A'; ?></span></td>
													<td class="guest-list__edit">
														<button type="button" class="js-edit-guest" data-id="<?php echo $Guest->getPostID(); ?>">
															<span class="icon-edit--wrapper">
																<svg class="icon-edit">
																<use xlink:href="#pencil"></use>
																</svg>
															</span>
														</button>
													</td>
													<td class="guest-list__delete">
														<span class="js-remove-guest" data-id="<?php echo $Guest->getPostID(); ?>">
															<svg class="icon-delete">
															<use xlink:href="#delete"></use>
															</svg>
														</span>
													</td>
													<td class="guest-list__dietary-restrictions-other" data-field-name="dietary_restrictions_other"><span class="field-value"><?php echo $Guest->isOtherDietaryRestrictions() ? (string) $Guest->getOtherDietaryRestrictionsDetails() : 'N/A'; ?></span></td>
													<td class="guest-list__allergies" data-field-name="guest_allergies"><span class="field-value"><?php echo ! empty( $Guest->getAllergies() ) ? $Guest->getAllergies() : 'N/A'; ?></span></td>
													<td class="guest-list__notes" data-field-name="guest_notes"><span class="field-value"><?php echo $Guest->getNotes() ? $Guest->getNotes() : 'N/A'; ?></span></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="js-popup-edit-guest popup-confirm-wrapper" style="display:none">
		<div class="popup-confirm popup-edit-guest">
			<h3 class="push-bottom">Edit Guest</h3>
			<?php include FXUP_USER_PORTAL()->plugin_path() . '/includes/views/partials/form-edit-guest.php'; ?>
		</div>
	</div>

	<div class="js-popup-delete-guest popup-delete-wrapper" style="display:none">
		<div class="popup-delete">
			<p>Are you sure that you want to delete this group from your guest list?</p>
			<a class="btn btn-thirdary js-delete-confirm-guest" data-groupdelete="" href="#">Delete</a>
			<a class="btn btn-secondary js-delete-guest-cancel" href="#">Cancel</a>
		</div>
	</div>

	<div class="js-popup-delete-single-guest popup-delete-wrapper" style="display:none">
		<div class="popup-delete">
			<p>Are you sure that you want to delete this guest from your guest list?</p>
			<a class="btn btn-thirdary js-delete-confirm-single-guest" data-groupdelete="" data-guestdelete="" href="#">Delete</a>
			<a class="btn btn-secondary js-delete-guest-cancel" href="#">Cancel</a>
		</div>
	</div>

	<div class="js-popup-change-onsite-status popup-delete-wrapper" style="display:none">
		<div class="popup-delete">
			<p>Where will this guest be staying?</p>
			<a class="btn btn-thirdary js-update-onsite" data-onsite="1" data-guestupdate="">Onsite</a>
			<a class="btn btn-thirdary js-update-onsite" data-onsite="0" data-guestupdate="">Offsite</a>
			<a class="btn btn-secondary js-onsite-change-cancel" href="#">Cancel</a>
		</div>
	</div>

	<div class="js-itin-save-status itin-save-status unsaved" style="display:none;">
		<div class="row">
			<div class="col-md-12">
				<div class="js-reload-warning">
					You have unsaved changed, please remember to save before leaving the page.
				</div>
				<div class="js-close-status itin-save-status-close">&times;</div>
			</div>
		</div>
	</div>
	<div class="js-popup-submit-confirm popup-confirm-wrapper" style="display:none">
		<div class="popup-confirm">
			<p>Are you sure that you want to submit your guest list to the concierge?</p>
			<a class="btn btn-thirdary js-submit-confirm" href="#">Submit to Concierge</a>
			<a class=" js-itin-popup-close" href="#"><i class="fas fa-times"></i></a>
		</div>
	</div>
	<div class="js-itin-save-status-saved itin-save-status saved" style="display:none;">
		<div class="row">
			<div class="col-md-12">
				<p>You have successfully saved your guest list and submitted it for approval</p>
				<div class="js-close-status itin-save-status-close">&times;</div>
			</div>
		</div>
	</div>
</section>

<footer class="dashboard-footer">
	<div class="container-fluid">
		<div class="row">
			<div class="col-xs-12">
				<a href="https://www.villapuntodevista.com/" class="footer-logo-dashboard"><img src="<?php echo get_template_directory_uri(); ?>/assets/img/footer-logo.png" class="img-responsive"></a>
			</div>
		</div>
	</div>
</footer>

<svg width="0" height="0" class="hidden">
	<symbol xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" id="pencil">
		<title>Edit</title>
		<path d="M51.2 353.28L0 512l158.72-51.2zm35.96-36.788L336.96 66.69 445.57 175.3l-249.8 249.802zM504.32 79.36L432.64 7.68c-10.24-10.24-25.6-10.24-35.84 0l-23.04 23.04 107.52 107.52 23.04-23.04c10.24-10.24 10.24-25.6 0-35.84z"></path>
	</symbol>
	<symbol xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" id="delete">
		<title>Delete</title>
		<path d="M437.02 74.98C388.668 26.63 324.379 0 256 0S123.332 26.629 74.98 74.98C26.63 123.332 0 187.621 0 256c0 68.383 26.629 132.668 74.98 181.02C123.332 485.37 187.621 512 256 512s132.668-26.629 181.02-74.98C485.37 388.668 512 324.383 512 256c0-68.379-26.629-132.668-74.98-181.02zm-70.293 256.387c9.761 9.766 9.761 25.594 0 35.356-4.883 4.882-11.282 7.324-17.68 7.324s-12.797-2.442-17.68-7.324L256 291.355l-75.367 75.372c-4.883 4.878-11.281 7.32-17.68 7.32s-12.797-2.442-17.68-7.32c-9.761-9.766-9.761-25.594 0-35.356L220.645 256l-75.372-75.367c-9.761-9.766-9.761-25.594 0-35.356 9.766-9.765 25.594-9.765 35.356 0L256 220.645l75.367-75.368c9.766-9.761 25.594-9.765 35.356 0 9.765 9.762 9.765 25.59 0 35.356L291.355 256zm0 0"></path>
	</symbol>
</svg>
</main>    
<script>
	jQuery( document ).ready( function () {
		$filterButtonOnsite = jQuery( '.js-onsite-filter' );
		$filterButtonOffsite = jQuery( '.js-offsite-filter' );
		$filterButtonClear = jQuery( '.js-clear-filter' );

		const columnIndexType = 3;
		const columnIndexOnsite = 5;
		var table = jQuery( '#guest_list' ).DataTable( {
			dom: 'Bfrtip',
			"pageLength": 20,
			buttons: [
				'print'
			],
			drawCallback: function () {
				var api = this.api();
				var totalGuests = api.rows( { search: 'applied' } ).count();
				var childGuests = 0;

				api.column( columnIndexType, { search: 'applied' } ).data().each( function ( item ) {
					var value = jQuery( item ).text().trim().toLowerCase();
					if ( value === 'child' ) {
						childGuests ++;
					}
				} );

				var adultGuests = totalGuests - childGuests;

				jQuery( '.js-guests' ).html( adultGuests );
				jQuery( '.js-children' ).html( childGuests );
				jQuery( '.js-total-guests' ).html( totalGuests );
			}
		} );

		$filterButtonOnsite.on( 'click', function () {
			$filterButtonOffsite.removeClass( 'filter-active' );
			$filterButtonOnsite.addClass( 'filter-active' );
			var filteredGuests = table
					.columns( columnIndexOnsite )
					.search(
							'Yes', // Input string
							true, // Regex?
							false, // Smart?
							false, // Case insensitive?
							true
							)
					.draw();
		} );

		$filterButtonOffsite.on( 'click', function () {
			$filterButtonOnsite.removeClass( 'filter-active' );
			$filterButtonOffsite.addClass( 'filter-active' );
			var filteredGuests = table
					.columns( columnIndexOnsite )
					.search(
							'No', // Input string
							true, // Regex?
							false, // Smart?
							false, // Case insensitive?
							true
							)
					.draw();
		} );

		jQuery( '.js-clear-filter' ).on( 'click', function () {
			$filterButtonOnsite.removeClass( 'filter-active' );
			$filterButtonOffsite.removeClass( 'filter-active' );
			var filteredGuests = table
					.columns( columnIndexOnsite )
					.search( '' )
					.draw();
		} );

	} );
</script>