<?php
 // $guest_posts is equal to posts from WP_Query passed in from controller.
 // Would be better if this could also be a model passed in, or part of the Itinerary model.
$count_getter = $is_concierge ? 'getActivityCount' : 'getPublicActivityCount';
?>
<div class="itinerary-day-wrapper js-day-<?php echo $d; ?>">
    <div class="col-xs-12 itinerary-day-heading">
        <div class="intinerary-date-cards">
            <div class="row flex-row">
                <?php
                $activity_text = '';
                if ($TripDay->{$count_getter}() < 1) {
                    if ($editable) {
                        $activity_text = 'No Activities Added Yet';
                    } else {
                        $activity_text = 'No Activities have been added for this day. Please contact your concierge to book last minute activities.';
                    }
                } else {
                    $activity_text = ($TripDay->{$count_getter}() === 1)  ? '1 Activity' : $TripDay->{$count_getter}() . ' Activities';
                }
                ?>
                <div class="col-xs-4 flex-item date <?php echo $TripDay->hasWeddingCeremony() ? 'wedding-day' : ''; ?> <?php echo $TripDay->hasCelebration() ? 'celebration-day' : ''; ?> <?php echo $TripDay->hasCustom() ? 'custom' : ''; ?>">
                    <div class="flex-date">
                        <!-- <span class="date-number"><?php echo $TripDay->getDateTime()->format('d'); ?></span>
                        <span class="date-month"><?php echo $TripDay->getDateTime()->format('F'); ?></span> -->
                        <span class="date-day">Day <?php echo $d; ?></span>
                    </div>
                </div>
                <div class="col-xs-12 flex-item flex-text">
                    <div class="intinerary-date-content <?php echo $TripDay->hasWeddingCeremony() ? 'wedding-day' : ''; ?> <?php echo $TripDay->hasCelebration() ? 'celebration-day' : ''; ?> <?php echo $TripDay->hasCustom() ? 'custom' : ''; ?>">
                        <div class="row flex-row">
                            <div class="col-xs-12 flex-item ">
                                <h2 class="flush-ends flex-row--title"><!--Day <?php echo $d; ?>--><?php echo $TripDay->getDateTime()->format('F d, l'); ?> <p class="flush-ends wedding" ><img src="https://www.villapuntodevista.com/content/uploads/2019/08/wedding.png"> Wedding Day</p><p class="flush-ends hidden" ><img src="http://login.villapuntodevista.com/wp-content/uploads/2020/06/celebration.png">Celebration/Event Day</p></h2>
                                <div class="activities-add--wrapper" >
                                    <span class="flush-ends activity-count js-act-count push-right <?php echo $TripDay->{$count_getter}() > 0 ? '' : 'activity-count-no-activities'; ?>"><?php echo $activity_text; ?> </span>
                                    <?php if( $editable ): ?>
                                        <a href="" class="js-add-itinerary-item hard-left" data-day="<?php echo $d; ?>" style="font-size: 14px;padding: 4px;">Add Activities <i class="fas fa-plus"></i></a>
                                    <?php endif; ?>
                                </div>
                                <?php if( $editable ): ?>
                                    <button type="button" class="js-itinerary-toggle push-bottom">View/Edit Day Details <i class="fas fa-chevron-down"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xs-12 itinerary-day-activities" style="display:none;">
        <div class="accordion sec-itinerary-accordion">
            <?php
                $first = false; // First is never set to true?
                $hide = true;
                foreach($TripDay->getActivities() as $activity_numeric_index => $Activity) {
					
//					if ( $d == 1 && $activity_numeric_index == 0 && stripos( $Activity->getDisplayTitle(), 'check in' ) === false ) {
//						include $itinerary_check_in_path;
//					}
					
					if ( $Activity->keepPrivate() && ! $is_concierge ) {
						continue;
					}
					
                    $a = $activity_numeric_index + 1;
                    include $trip_day_activity_path;
                    $first = false;
                }
				
//				if ( $d == 1 && $TripDay->getActivityCount() == 0 ) {
//					include $itinerary_check_in_path;
//				}
				
//				if ( $d == $Itinerary->getTripDayCount() ) {
//					include $itinerary_check_out_path;
//				}
            ?>
        </div>
    </div>
</div>