<main id="page-body" <?php post_class( 'page-body shared-temp summary-view' ); ?> >
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
                                        <h2>Transportation Summary</h2>
                                        <p class="font-large flush-bottom soft-bottom">
											<?php echo $Itinerary->getTripStartDate()->format( 'l, m/d/Y' ) . ' - ' . $Itinerary->getTripEndDate()->format( 'l, m/d/Y' ); ?>
										</p>
										<!-- Summary: Transportation -->
										<div class="js-accordion-wrapper summary-accordion">
											<div class="accordion-cont js-accordion-cont" style="display: block;">
												<h3>Arrival Transportation</h3>
												<?php
												foreach ( $Itinerary->getFilteredArrivalTransports() as $numeric_index => $Transport ) {
													$t = $numeric_index + 1;
													include $summary_transportation_path;
												}
												?>
												<h3>Departure Transportation</h3>
												<?php
												foreach ( $Itinerary->getFilteredDepartureTransports() as $numeric_index => $Transport ) {
													$t = $numeric_index + 1;
													include $summary_transportation_path;
												}
												?>
											</div>
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
