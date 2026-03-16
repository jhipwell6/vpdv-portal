<main id="page-body" <?php post_class('page-body'); ?>>
	<header class="dashboard-header">
		<div class="container-fluid">
			<div class="row">
				<div class="col-xs-6 col-md-offset-2 col-md-4">
					<a href="https://www.villapuntodevista.com/" class="header-logo-dashboard">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/img/login-logo.png" class="img-responsive">
					</a>
				</div>
				<div class="col-xs-6 col-md-4">
					<div class="dashboard-user-header">
						<span class="hello">Hello,</span> <span class="name"><?php echo $dashboard_user_display_name; ?></span> <?php if ( isset( $_GET['tour'] ) ) : ?><a href="javascript:;" class="js-start-tour">Tour</a><?php endif; ?><a href="<?php echo site_url() . '/logout'; ?>">Log Out <i class="icon-arrow-right"></i></a>
					</div>
				</div>
			</div>
		</div>
	</header>
	<section class="section-container">
		<div class="container-fluid mobile-container-fluid ">
			<div class="row">
				<div class="col-md-12 dashboard-container-leaf">
					<div class="dashboard-container">
						<div class="dashboard-page-header">
							<h2 class="text-center squiggle-headline">Villa Punto de Vista Travel Dashboard</h2>
						</div>
						<div class="container-fluid section-padding">
							<div class="row dashboard-top-options-container">
								<div class="col-md-12 col-xxs-12 dashboard-top-option">
									<p class="dashboard-video-link">
										Video: &nbsp<a href="<?php echo \FXUP_User_Portal\Models\Itinerary::getVideoLinkTop(get_queried_object())['url']; ?>" class="btn btn-secondary html5lightbox"><i class="fab fa-youtube"></i> How to use your dashboard</a>
									</p>
								</div>
							</div>
							<?php
								$itineraryIndex = 0;
								foreach ($itineraries as $Itinerary) {
									$Villa = $Itinerary->getVilla();
									include $dashboard_itinerary_include_path;
									++$itineraryIndex;
								}
							?>
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
