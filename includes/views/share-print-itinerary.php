<main id="page-body" <?php post_class('page-body shared-temp'); ?> >
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
                            <h2 class="text-center squiggle-headline"><?php echo $Itinerary->getTitle(); ?></h2>
                        </div>
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-12">
                                    <!-- Back to dashboard link should not display for users who are not logged in (user id of 0) -->
                                    <div class="backtodash soft-top" style="<?php echo ($user_role === 'unregistered') ? 'display: none;' : ''; ?>">
                                        <a href="<?php echo $Itinerary->getPermalink(); ?>"><i class="fas fa-arrow-left"></i> Edit Itinerary</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="container-fluid section-padding">
                            <div class="row">

                                <div class="col-md-offset-1 col-md-10">
                                    <div class="dashboard-page-content">
                                        <h2>Trip Itinerary</h2>
                                        <p class="font-large flush-bottom soft-bottom"><?php echo $Itinerary->getTripStartDate()->format('l, m/d/Y') . ' - ' .  $Itinerary->getTripEndDate()->format('l, m/d/Y'); ?></p>
                                        <div class="quicklinks--wrapper">
                                            <div class="editable-btns">
                                                <a href="<?php echo $Itinerary->getShareLink(); ?>" class="share-btn js-copy-share"><i class="fas fa-share"></i> Share</a>
                                                <a href="mailto:%20?subject=<?php echo $Itinerary->getTitle(); ?>&body=<?php echo $Itinerary->getShareLink(); ?>" target="_blank" class="share-btn"><i class="fas fa-envelope"></i> Email</a>
                                                <a href="" class="share-btn" onclick="window.print();return false;"><i class="fas fa-print"></i> Print</a>


                                                <input type="text" value="" id="js-share-value" class="hidden" name="js-share-value">
                                            </div>
                                            <div class="jumplink-wrapper">
                                                <h4>Jump to: </h4>
                                                <?php echo $this->renderJumpToSelectList($Itinerary->getPostID()); ?>
                                            </div>
                                        </div>

                                        <div class="itinerary-section">
											
											<?php if ($is_concierge) { ?>
                                                <!-- Only output the client bio field if the current user is a concierge -->
                                                <div class="itinerary-form-client-bio">
                                                    <label for="fxup-itinerary-client-bio-and-notes">Client Bio and Notes</label>
                                                    <div class="fxup-itinerary-client-bio-and-notes" id="fxup-itinerary-client-bio-and-notes" ><?php echo wpautop($Itinerary->getClientBioAndNotes()); ?></div>
                                                </div>
                                            <?php } ?>
											
                                            <div class="row intinerary-row">
                                            <!-- FOREACH ITINERARY TRIPDAYS AS NUMERIC_INDEX TRIPDAY -->
                                            <?php
                                                foreach ($Itinerary->getTripDays() as $trip_day_numeric_index => $TripDay) {
                                                    $d = $trip_day_numeric_index + 1;
                                                    
                                                    include $share_print_trip_day_path;
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
                    <a href="https://www.villapuntodevista.com/" class="footer-logo-dashboard"><img src="<?php echo get_template_directory_uri(); ?>/assets/img/footer-logo.png" class="img-responsive"></a>
                </div>
            </div>
        </div>
    </footer>
</main>
