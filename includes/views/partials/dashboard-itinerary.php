<div class="row unique-itinerary-container">
    <div class="col-md-8 col-xxs-12" style="overflow: hidden;">
        <div class="dashboard-page-content">
            <!-- BEGIN ITINERARY -->
            <h2 class="soft-bottom">Your stay at <?php echo $Villa->getTitle(); ?> <span class="itinerary-date font-large flush-bottom break-sm"><?php echo $Itinerary->getTripStartDate()->format('M j, l') . ' - ' . $Itinerary->getTripEndDate()->format('M j, l'); ?></span></h2>
            <div class="itinerary-block villa-smbtn sec-bg-secondary" href="http://luxuryvillacostarica.com/villa/villa-punto-de-vista-estate/">
                <div class="row flex-row no-flex-wrap">
                    <div class="col-xxs-12 col-xs-4 flex-item flex-image">
                        <img class="img-responsive" src="<?php echo $Villa->getImageLink(); ?>">
                    </div>
                    <div class="col-xxs-12 col-xs-8 flex-item flex-text">
                        <div class="villa-smbtn-desc">
                            <h5 class="villa-smbtn-title h3 font-black">
                                <b><?php echo $Villa->getTitle(); ?></b>
                            </h5>
                            <div class="villa-smbtn-details"><?php echo $Villa->getBedrooms(); ?> bedrooms    |    Sleeps up to <?php echo $Villa->getSleeps(); ?> People</div>
                            <a class="btn btn-thirdary" href="https://www.villapuntodevista.com/villa-overview/" target="_blank">Learn More About Your Stay</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="trip-alerts hidden-md-up">
                <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/img/sidebar-img.png">
                <div class="trip-alerts-content">
                    <h2 class="flush-top push-bottom">Action Items for You</h2>
                    <p class="text-center flush-top">Each of these items needs to be completed before your stay with us, if you have any questions, please contact us below.</p>
                    <?php if ($Itinerary->isEditable()) : ?>
                        <?php if ($Itinerary->isGuestListSubmitted()) : ?>
                            <p class="flush-bottom"><i class="far fa-check-square" style="color: #00A6A0"></i> <span class="icon-left">Your guest list has been submitted. <a href="<?php echo $Itinerary->getGuestListLink(); ?>">Update Guest List <i class="icon-arrow-right"></i></a></span> </p>
                            <hr />
                        <?php else : ?>
                            <p class="flush-bottom"><i class="far fa-square"></i> <span class="icon-left">Please <a href="<?php echo $Itinerary->getGuestListLink(); ?>">add details</a> for all adults in your party.</span> </p>
                            <hr />
                        <?php endif; ?>
                        <?php if ($Itinerary->isRoomArrangementsSubmitted()) : ?>
                            <p class="flush-bottom"><i class="far fa-check-square" style="color: #00A6A0;"></i> <span class="icon-left">Your room arrangements have been submitted. <a href="<?php echo $Itinerary->getRoomArrangementsLink(); ?>">Update Room Arrangements <i class="icon-arrow-right"></i></a> </span></p>

                        <?php else : ?>
                            <p class="flush-bottom"><i class="far fa-square"></i> <span class="icon-left">Please confirm the <a href="<?php echo $Itinerary->getRoomArrangementsLink(); ?>">Room Arrangements</a> for your party. </span></p>
                        <?php endif; ?>
                        <hr /><?php /* ?>
                        <p class=" flush-bottom"><i class="far fa-square"></i> <span class="icon-left">Please ensure you've <a href="<?php echo get_permalink(1162) .'?itin='.get_field( 'share_link_token', $Itinerary->getPostID() ); ?>">Added Travel Details</a> for all adults in your party.</span> </p>
                        <hr /><?php */ ?>
                        <p class="flush-bottom"><i class="far <?php echo $Itinerary->getIcon(); ?>" <?php echo $Itinerary->getColor(); ?>></i> <span class="icon-left">You have <?php echo $Itinerary->getEditDaysLeft(); ?> days to <a href="<?php echo $Itinerary->getPermalink(); ?>">Edit Your Itinerary <i class="icon-arrow-right"></i></a>.</span></p>

                        <p class="arrive-time flush-bottom">You are set to arrive in <?php echo $Itinerary->getDaysUntilStart(); ?> days.</p>
                    <?php elseif ($Itinerary->isTripOver()) : ?>
                        <p class="flush-bottom">It looks like your trip is over. We hope to see you again soon! </p>
                    <?php else : ?>
                        <p class="flush-bottom">Your trip is almost here! Your itinerary has been locked, please contact us for any last minute request to change your itinerary.</p>
                        <p class="flush-bottom">You are set to arrive in <?php echo $Itinerary->getDaysUntilStart(); ?> days.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php
				// $video = FXUP_Itinerary_Process::instance()->welcome_video;
				$video = $Villa->getVideoURL();
                $video_thumbnail = $Villa->getVideoThumbnailURL();
                $video_iframe = $Villa->getVideoEmbedURL(true);
                $video_title = $Villa->getVideoTitle();
				$video_iframe = ( ! $video_iframe || $video_iframe == '' ) && $Villa->getVideoURL() != '' ? '<iframe src="' . $Villa->getVideoURL() . '"></iframe>': $video_iframe;
            ?>
			<?php if (!empty($video_iframe)) { ?>
            <div class="introduction-video section-padding soft-top" data-video="<?php echo esc_attr( $video ); ?>">
                <div class="video-wrapper">
                    <?php if( $video_title) : ?><h2 class="push-bottom"><?php echo $video_title; ?></h2><?php endif; ?>

                    <div class="video-player">
                        <div class="embed-responsive embed-responsive-16by9"><?php echo $video_iframe; ?></div>
                    </div>
                </div>
            </div>
            <?php } ?>
            <div class="itinerary-section dash-itinerary section-top-padding">
                <div class="itinerary-header">
                    <div class="row flex-row">
                        <div class="col-xs-7 flex-item">
                            <h2>Your Trip Itinerary</h2>
                            <p class="font-large flush-bottom soft-bottom"><?php $Itinerary->getTripStartDate()->format('F j, Y') . ' - ' .   $Itinerary->getTripEndDate()->format('F j, Y'); ?></p>

                        </div>
                        <div class="col-xs-5 flex-item text-right">
                            <a class="itinerary-view-btn btn btn-thirdary" href="<?php echo $Itinerary->getPermalink(); ?>">View/Edit Itinerary <i class="icon-chevron-right"></i></a>
                        </div>

                    </div>
					<?php if ( $Itinerary->hasPaymentLink() ) : ?>
					<div class="row flex-row push-bottom">
                        <div class="col-xs-7 flex-item">
                            <h2>Pay for your trip</h2>
                        </div>
                        <div class="col-xs-5 flex-item text-right">
                            <a class="btn btn-secondary" href="<?php echo $Itinerary->getPaymentLink(); ?>" target="_blank">Pay Now</a>
                        </div>
                    </div>
					<?php endif; ?>
                    <div class="itinerary-status">
                        <h5>Itinerary Status: <?php echo $Itinerary->getApprovalStatus(); ?><i class="itin-tooltip far fa-question-circle" ><div class="itin-tooltip-text hidden">
                                <span class="arrow-up"></span>
                                <?php echo $Itinerary->getStatusTooltip(); ?>
                            </div></i>

                        </h5>
                    </div>

                    <p class="small-notification">Use our Trip Itinerary Planner to plan your stay at the Villa. Choose from our list of activities and services available, or create your own event. Once you have submitted your Itinerary, you can share it with your party. If you have any questions, reach out to us at any time.</p>
                </div>

                <div class="row">
                    <?php $i = 1; foreach ($Itinerary->getTripDays() as $TripDay) { ?>
                    <div class="col-xxs-12 col-xs-12 js-itin-cards">
                        <div class="intinerary-date-cards">
                            <div class="row flex-row">
                                <?php

                                $trip_day_date_time = $TripDay->getDateTime();
                                $activities = $TripDay->getActivities();

                                ?>
                                <div class="col-xxs-4 flex-item date ">
                                    <div class="flex-date">
                                        <span class="date-day">Day <?php echo $i; ?></span>
                                    </div>
                                </div>
                                <div class="col-xxs-8 flex-item flex-text">
                                    <div class="intinerary-date-content <?php echo $TripDay->isWeddingDay() ? 'wedding-day' : ''; ?>">
                                        <p class="flush-ends"><img src="https://www.villapuntodevista.com/content/uploads/2019/08/wedding.png"> Wedding Day</p>
                                        <p class="itin-date flush-ends"><?php echo $TripDay->getDateTime()->format('d F, l'); ?></p>
                                        <?php
                                            $activity_text = '';
                                            if ($TripDay->getActivityCount() >= 1) {
                                                $activity_text = ($TripDay->getActivityCount() === 1)  ? '1 Activity' : $TripDay->getActivityCount() . ' Activities';
                                            }
                                        ?>
                                        <p class="flush-ends"><span class="activity-text"><?php echo $activity_text; ?></span> <a href="<?php echo $Itinerary->getPermalink(); ?>" class="btn-accent uppercase">Edit Itinerary</a></p>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $i++; } ?>
                </div>
            </div>
            <div class="push-bottom js-itinerary-link-wrapper push-top">
                <h2 class="push-bottom">Share Itinerary</h2>
                <div class="flexshare push-bottom">
                    <div class="itin-link col-xxs-12 col-sm-8 " style="padding: 8px; border: 1px solid #ece7e5; color: #BDBDBD; text-align: center;">
                        <p class="js-itin-link" style="margin: 0px;"><?php echo $Itinerary->getShareLink(); ?></p>
                    </div>
                    <button class="btn btn-secondary js-copy-link"><i class="fas fa-link"></i> Copy Link</button>
                </div>
            </div>
            <!-- END ITINERARY -->
            <!-- BEGIN FAQS -->
            <div class="section-top--padding accordion sec-faq-accordion flush-top hidden-sm-down">
                <h2 class="push-bottom">Traveling Tips & FAQs</h2>
                <div class="accordion sec-faq-accordion flush-top">
                    <?php
                    $c = 0;
                    // check if the repeater field has rows of data
                    foreach ($Villa->getFAQs() as $faq) {
                        ++$c;
                    ?>
                    <h6 class="accordion-btn js-accordion">
                        <a class="icon-chevron-down" href="#sec-faq-<?php echo $c; ?>"><?php echo $faq['question'];?></a>
                    </h6>
                    <div class="accordion-cont" id="sec-faq-<?php echo $c; ?>">
                        <?php echo $faq['answer'];?>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <!-- END FAQS -->
        </div>
    </div>
    <!-- BEGIN SIDEBAR -->
    <div class="col-md-4 col-xxs-12">
        <div class="dashboard-sidebar">
            <!-- BEGIN JUMP TO LINKS -->
            <?php echo \FXUP_USER_PORTAL\Controllers\FXUP_Itinerary_Process::instance()->renderJumpToSelectList($Itinerary->getPostID()); ?>
            <!-- END JUMP TO LINKS -->
            <div class="trip-alerts hidden-sm-down">
                <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/img/sidebar-img.png">
                <div class="trip-alerts-content">
                    <h2 class="flush-top push-bottom">Action Items for You</h2> 
                    <p class="text-center flush-top">Each of these items needs to be completed before your stay with us, if you have any questions, please contact us below.</p>
                    <?php if ($Itinerary->isEditable()) : ?>
                        <?php if ($Itinerary->isGuestListSubmitted()) : ?>
                            <p class="flush-bottom"><i class="far fa-check-square" style="color: #00A6A0"></i> <span class="icon-left">Your guest list has been submitted. <a href="<?php echo $Itinerary->getGuestListLink(); ?>">Update Guest List <i class="icon-arrow-right"></i></a></span> </p>
                            <hr />
                        <?php else : ?>
                            <p class="flush-bottom"><i class="far fa-square"></i> <span class="icon-left">Please <a href="<?php echo $Itinerary->getGuestListLink(); ?>">add details</a> for all adults in your party.</span> </p>
                            <hr />
                        <?php endif; ?>

                        <?php if ($Itinerary->isRoomArrangementsSubmitted()) : ?>
                            <p class="flush-bottom"><i class="far fa-check-square" style="color: #00A6A0;"></i> <span class="icon-left">Your room arrangements have been submitted. <a href="<?php echo $Itinerary->getRoomArrangementsLink(); ?>">Update Room Arrangements <i class="icon-arrow-right"></i></a> </span></p>
                        <?php else : ?>
                            <p class="flush-bottom"><i class="far fa-square"></i> <span class="icon-left">Please confirm the <a href="<?php echo $Itinerary->getRoomArrangementsLink(); ?>">Room Arrangements</a> for your party. </span></p>
                        <?php endif; ?>
                        <hr /><?php /* ?>
                        <p class=" flush-bottom"><i class="fas fa-exclamation-circle"></i> <span class="icon-left">Please ensure you've <a href="<?php echo get_permalink(1162) .'?itin='.get_field( 'share_link_token', $Itinerary->getPostID() ); ?>">Added Travel Details</a> for all adults in your party.</span> </p>
			<hr /><?php */ ?>
<p class="flush-bottom"><i class="far <?php echo $Itinerary->getIcon(); ?>"  <?php echo $Itinerary->getColor(); ?>></i> 
<?php if($Itinerary->isEditableManualOverride()) { ?>
<span class="icon-left">Your itinerary is editable <a href="<?php echo $Itinerary->getPermalink(); ?>">here</a>.</span>
<?php } else { ?>
<span class="icon-left">You have <?php echo $Itinerary->getEditDaysLeft(); ?> days to <a href="<?php echo $Itinerary->getPermalink(); ?>">Edit Your Itinerary <i class="icon-arrow-right"></i></a>.</span>
<?php } ?>
</p>
                        <p class="arrive-time flush-bottom">You are set to arrive in <?php echo $Itinerary->getDaysUntilStart(); ?> days.</p>
                    <?php elseif ($Itinerary->isTripOver()) : ?>
                        <p class="flush-bottom">It looks like your trip is over. We hope to see you again soon! </p>
                    <?php else : ?>
                        <p class="flush-bottom">Your trip is almost here! Your itinerary has been locked, please contact us for any last minute request to change your itinerary.</p>
                        <p class="flush-bottom">You are set to arrive in <?php echo $Itinerary->getDaysUntilStart(); ?> days.</p>
                    <?php endif; ?>

                </div>
            </div>
            <?php if (0 === $itineraryIndex) { ?>
                <div class="contact-info text-center">
					<?php echo \FXUP_USER_PORTAL\Controllers\FXUP_Itinerary_Process::instance()->renderLinkCenter(); ?>
                    <h2>Call Us</h2>
                    <p><a href="https://calendly.com/gloriela/" target="_blank">Schedule a Call <i class="icon-arrow-right"></i></a>
                    </p>
                    <h2>Email</h2>
                    <p><a href="mailto:concierge@villapuntodevista.com">concierge@villapuntodevista.com</a></p>
                    <img class="img-responsive" src="<?php echo get_stylesheet_directory_uri(); ?>/assets/img/rough-sidebar.png">
                </div>
                <div class="contact-form">
                    <h2 class="text-center">send us a message</h2>
                    <?php echo do_shortcode('[gravityform id=2 title=false description=false ajax=true tabindex=49]');?>

                    <hr class="form-spacer" />

                    <div class="text-center sometimes-hidden-form form-hidden">
                        <h3 class="prompt">We'd love to hear from you!</h3>
                        <button type="button" class="btn btn-secondary js-submit-your-feedback">Submit Your Feedback</button>
                        <?php echo do_shortcode('[gravityform id="6" title="false" description="false" ajax="true" tabindex="49"]'); ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
    <!-- END SIDEBAR -->
    <!-- BEGIN FAQS -->
    <!-- This is not displaying on desktop -->
    <div class="dashboard-page-content">
        <div class="col-xxs-12 push-top hidden-md-up">
            <div class=" sec-faq-accordion">
                <h2 class="push-bottom">Traveling Tips & FAQs</h2>
                <div class="accordion sec-faq-accordion flush-top">
                <?php
                    $c = 0;
                    // check if the repeater field has rows of data
                    foreach ($Villa->getFAQs() as $faq) {
                        ++$c;
                    ?>
                    <h6 class="accordion-btn js-accordion">
                        <a class="icon-chevron-down" href="#sec-faq-<?php echo $c; ?>"><?php echo $faq['question'];?></a>
                    </h6>
                    <div class="accordion-cont" id="sec-faq-<?php echo $c; ?>">
                        <?php echo $faq['answer'];?>
                    </div>
                <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <!-- END FAQS -->
</div>
