<main id="page-body" <?php post_class('page-body shared-temp summary-view'); ?> >
    <header class="dashboard-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xs-6 col-md-offset-2 col-md-4">
                    <a href="https://www.villapuntodevista.com/" class="header-logo-dashboard">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/img/login-logo.png" class="img-responsive">
					</a>
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
                            <h2 class="text-center squiggle-headline"><?php echo $Itinerary->getTitle(); ?></h2>
                        </div>
                        <div class="container-fluid section-padding">
                            <div class="row">
                                <div class="col-md-offset-1 col-md-10">
                                    <div class="dashboard-page-content">
                                        <h2>Trip Summary</h2>
                                        <p class="font-large flush-bottom soft-bottom">
											<?php echo $Itinerary->getTripStartDate()->format('l, m/d/Y') . ' - ' .  $Itinerary->getTripEndDate()->format('l, m/d/Y'); ?>
										</p>
										
										<div class="accordion sec-itinerary-accordion">
											
											<!-- Summary: Itinerary -->
											<div class="js-accordion-wrapper summary-accordion">
												<div class="accordion-btn js-accordion-toggle">
													<h3>Itinerary<//h3>
												</div>
												<div class="accordion-cont js-accordion-cont" style="display: none;">
													<div class="itinerary-section">
														<div class="itinerary-form-client-bio">
															<label for="fxup-itinerary-client-bio-and-notes">Client Bio and Notes</label>
															<div class="fxup-itinerary-client-bio-and-notes" id="fxup-itinerary-client-bio-and-notes">
																<?php echo wpautop( $Itinerary->getClientBioAndNotes() ); ?>
															</div>
														</div>
														<div class="row intinerary-row">
														<!-- FOREACH ITINERARY TRIPDAYS AS NUMERIC_INDEX TRIPDAY -->
														<?php
															foreach ( $Itinerary->getTripDays() as $trip_day_numeric_index => $TripDay ) {
																$d = $trip_day_numeric_index + 1;
																include $share_print_trip_day_path;
															}
														?>
														</div>
													</div>
												</div>
											</div>
											
											<!-- Summary: Rooms -->
											<div class="js-accordion-wrapper summary-accordion">
												<div class="accordion-btn js-accordion-toggle">
													<h3>Room Assignments<//h3>
												</div>
												<div class="accordion-cont js-accordion-cont" style="display: none;">
													<?php include $summary_rooms_path; ?>
												</div>
											</div>
											<?php
												$password = filter_input( INPUT_POST, 'fxup_pass' );
												if ( isset( $password ) && $password == 'villapuntodevista' ) : ?>
											<!-- Summary Guest List/Travel -->
											<div class="js-accordion-wrapper summary-accordion page-template-page-guest-list-simple">
												<div class="accordion-btn js-accordion-toggle">
													<h3>Guest List/Travel Details</h3>
												</div>
												<div class="accordion-cont js-accordion-cont" style="display: none;">
													<?php foreach ($Itinerary->getGuests() as $Guest): ?>
													<h6 class="accordion-btn active">
														<?php echo $Guest->getFullName(); ?>
													</h6>
													<div class="accordion-cont accordion-active print-wrapper">
														<div class="print-col">
															<h4>Guest Details:</h4>
															<p><b>Number of Children:</b> <?php echo $Guest->getChildren(); ?></p>
															<p><b>Guest Notes:</b> <?php echo !empty($Guest->getNotes()) ? $Guest->getNotes() : 'N/A'; ?></p>
															<p><b>Guest Dietary Restrictions:</b> <?php echo (!empty($Guest->getDietaryRestrictions())) ? implode(', ', $Guest->getDietaryRestrictions()) : 'N/A'; ?></p>
															<?php if ($Guest->isOtherDietaryRestrictions()) { ?>
																<!-- Only display this if the guest has checked the 'Other' dietary restrictions option on the add guest form -->
																<p><b>Guest Other Dietary Restrictions:</b> <?php echo $Guest->getOtherDietaryRestrictionsDetails() ? $Guest->getOtherDietaryRestrictionsDetails() : ''; ?></p>
															<?php } ?>
															<p><b>Guest Allergies:</b> <?php echo !empty($Guest->getAllergies()) ? $Guest->getAllergies() : 'N/A'; ?></p>
															<p><b>Will they be staying on-site?</b> <?php echo $Guest->isOnsite() ? 'Yes' : 'No'; ?> </p>
															<p><b>Location:</b> <?php echo !empty($Guest->getStayLocation()) ? $Guest->getStayLocation() : ''; ?></p>
															<p><b>Villa:</b> <?php echo $Guest->getAssignedRoom() ? $Guest->getAssignedRoom()->getSubVilla()->getTitle() : ''; ?></p>
															<p><b>Room:</b> <?php echo $Guest->getAssignedRoom() ? $Guest->getAssignedRoom()->getRoomName() : ''; ?></p>
														</div>
														<div class="print-col">
															<h4>Guest Travel Details: </h4>
															<div class="print-two">
															<p><b>Stay Length:</b> <?php echo $Guest->getStayLength(); ?></p>
															<p><b>Arrival Airline:</b> <?php echo $Guest->getArrivalAirline(); ?></p>
															<p><b>Arrival Flight Number:</b> <?php echo $Guest->getArrivalFlightNumber(); ?></p>
															<p><b>Arrival Date:</b> <?php echo $Guest->getArrivalDate(); ?></p>
															<p><b>Arrival Time:</b> <?php echo $Guest->getArrivalTime(); ?></p>
															<p><b>Requires Arrival Transportation:</b> <?php echo $Guest->requiresArrivalTransportation(); ?></p>
															<p><b>Departure Airline:</b> <?php echo $Guest->getDepartureAirline(); ?></p>
															<p><b>Departure Flight Number:</b> <?php echo $Guest->getDepartureFlightNumber(); ?></p>
															<p><b>Departure Date:</b> <?php echo $Guest->getDepartureDate(); ?></p>
															<p><b>Departure Time:</b> <?php echo $Guest->getDepartureTime(); ?></p>
															<p><b>Requires Departure Transportation:</b> <?php echo $Guest->requiresDepartureTransportation(); ?></p>
															<p><b>Travel Notes:</b> <?php echo $Guest->getTravelNotes(); ?></p>
															</div>
														</div>
													</div>
													<?php endforeach; ?>
												</div>
											</div>
											
											<!-- Summary: Transportation -->
											<div class="js-accordion-wrapper summary-accordion">
												<div class="accordion-btn js-accordion-toggle">
													<h3>Transportation<//h3>
												</div>
												<div class="accordion-cont js-accordion-cont" style="display: none;">
													<h3>Arrival Transportation</h3>
													<?php
														foreach ( $Itinerary->getArrivalTransportation() as $numeric_index => $Transport ) {
															$t = $numeric_index + 1;
															include $summary_transportation_path;
														}
													?>
													<h3>Departure Transportation</h3>
													<?php
														foreach ( $Itinerary->getDepartureTransportation() as $numeric_index => $Transport ) {
															$t = $numeric_index + 1;
															include $summary_transportation_path;
														}
													?>
												</div>
											</div>
											<?php ; else : ?>
											<br />
											<br />
											<div class="row">
												<div class="col-sm-6 col-sm-offset-3">
													<h4>View Additional Details</h4>
													<form action="" method="post">
														<input type="password" name="fxup_pass" />
														<br />
														<button type="submit" class="btn btn-thirdary">Login</button>
													</form>
												</div>
											</div>
											<?php endif; ?>
										</div>	
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <footer class="dashboard-footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xs-12">
                    <a href="https://www.villapuntodevista.com/" class="footer-logo-dashboard">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/img/footer-logo.png" class="img-responsive">
					</a>
                </div>
            </div>
        </div>
    </footer>
</main>
