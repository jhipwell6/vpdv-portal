<!-- Flyout Content -->
<div class="flyout" id="flyout-<?php echo $event_type['flyout_id']; ?>" style="-webkit-transition: none; -moz-transition: none; -ms-transition: none; -o-transition: none; transition: none;">
    <p class="flyout-close flush">
        <button class="icon-close js-toggle-addclass" data-target="">Close</button>
    </p>
    <div class="row flyout-cont">
        <div class="col-sm-6 col-sm-push-6 flyout-text">
            <div class="flyout-text-cont ajaxload">
                <h2 class="squiggle-headline font-black">Available <?php echo $event_type['flyout_heading']; ?></h2>
                <select class="js-act-filter">
                    <option value="All">Filter By Category</option>
                    <?php foreach( $event_type['categories'] as $event_type_category): ?>
                        <option value="<?php echo $event_type_category->term_id; ?>"><?php echo $event_type_category->name; ?></option>
                    <?php endforeach; ?>
						<?php if ( $is_concierge ) : ?><option value="concierge-only">Concierge Only</option><?php endif; ?>
                </select>
                <?php foreach ($event_type['posts'] as $event_type_post_object) { ?>
                    <?php
					
					$is_concierge_only_event = get_field('concierge_only', $event_type_post_object->ID);
					if ( $is_concierge_only_event && ! $is_concierge ) {
						continue;
					}
					
                    $event_type_terms = wp_get_post_terms( $event_type_post_object->ID, $event_type['taxonomy'] );
                    $event_type_category_classes = '';

                    foreach( $event_type_terms as $event_type_term ) {
                        $event_type_category_classes .= ' js-act-cat' . $event_type_term->term_id;
                    }
					
					if ( $is_concierge_only_event ) {
						$event_type_category_classes .= ' js-act-catconcierge-only';
					}
                    ?>
                    <div class="villa-smbtn sec-bg-secondary addto-itinerary-card js-activity-row<?php echo $event_type_category_classes; ?>">
                        <div class="row flex-row">
                            <div class="col-xs-3 flex-item flex-image">
                                <?php if( has_post_thumbnail($event_type_post_object->ID) ): ?>
                                    <?php echo get_the_post_thumbnail($event_type_post_object->ID, 'full', array( 'class' => 'img-responsive' ) ); ?>
                                <?php else: ?>
                                    <img class="img-responsive" src="<?php echo get_stylesheet_directory_uri(); ?>/assets/img/villa-default.jpg">
                                <?php endif; ?>
                            </div>
                            <div class="col-xs-6 flex-item flex-text">
                                <div class="villa-smbtn-desc">
									<h5 class="villa-smbtn-title h3 font-black"><b><?php if ( $is_concierge_only_event ) { echo '<i class="fas fa-user-lock"></i> '; }; ?><?php echo get_the_title($event_type_post_object->ID); ?></b></h5>
                                    <span class="view-details activity-toggle">View <?php echo $event_type['flyout_name']; ?> Details <i class="fas fa-chevron-down"></i></span><!-- <i class="fas fa-plus activity-toggle"></i> -->
                                </div>
                            </div>
                            <div class="col-xs-3 flex-item button">
                                <a href="#" class="flex-button btn btn-thirdary js-add-activity-item" data-pid="<?php echo $event_type_post_object->ID; ?>">
                                    Add to Itinerary
                                </a>
                                <div class="activity-confirm-days" style="display:none;">
                                    <p class="activity-confirm-days-close flush">
                                        <button class="icon-close js-toggle-addclass" data-target="">Close</button>
                                    </p>
                                    <?php
                                        foreach ($Itinerary->getTripDays() as $numeric_index => $TripDay) {
                                            $d = $numeric_index + 1;
                                     ?>
    
                                        <input type="checkbox" name="selected_days" value="<?php echo $d; ?>" id="day-<?php echo $d; ?>"><label for="day-<?php echo $d; ?>"><?php echo 'Day ' . $d . ' (' . $TripDay->getDateTime()->format('F d') . ')'; ?></label><br>
                                
                                    <?php 
                                        }
                                    ?>
                                    <a href="#" class="push-top flex-button btn btn-secondary js-add-activity-confirm" data-pid="<?php echo $event_type_post_object->ID; ?>">
                                        Add to Itinerary
                                    </a>
                                </div>

                            </div>
                        </div>
                        <div class="flex-row row">
                            <div class="col-xs-12 flex-item flex-text">
                                <div class="js-activity-description activity-description" style="display: none;">
                                    <?php echo get_field('activity_itinerary_content', $event_type_post_object->ID); ?>
                                    <?php /* echo apply_filters( 'the_content', $event_type_post_object->post_content ); */ ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>