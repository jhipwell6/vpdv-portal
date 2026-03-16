<?php $count_getter = $is_concierge ? 'getActivityCount' : 'getPublicActivityCount'; ?>
<div class="js-day-<?php echo $d; ?>">
<div class="col-xs-12">
		<div class="intinerary-date-cards">
			<div class="row flex-row">
				<div class="col-xs-4 flex-item date <?php echo ($TripDay->isWeddingDay()) ? 'wedding-day' : ''; ?>">
					<div class="flex-date">
						<!-- <span class="date-number"><?php echo $TripDay->getDateTime()->format('d'); ?></span>
						<span class="date-month"><?php echo $TripDay->getDateTime()->format('F');; ?></span> -->
						<span class="date-day">Day <?php echo $d; ?></span>
					</div>
				</div>
				<div class="col-xs-12 flex-item flex-text">
					<div class="intinerary-date-content">
						<div class="row flex-row">
							<div class="col-xs-7 flex-item ">
								<h2 class="flush-ends"><!--Day <?php echo $trip_day_numeric_index + 1; // 0 based index to 1 based index ?>--><?php echo $TripDay->getDateTime()->format('F d, l'); ?></h2>
								<p class="flush-ends" style="display: block !important;"><?php echo ($TripDay->{$count_getter}() > 0 ) ? $TripDay->{$count_getter}() . ' Itinerary Items' : 'No Activites Scheduled'; ?> </p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-xs-12 itinerary-day-wrapper">
		<!-- IF TRIPDAY HAS ACTIVITIES -->
		<?php if ($TripDay->{$count_getter}() > 0) { ?>
			<div class="accordion sec-itinerary-accordion">
				<!-- FOREACH TRIPDAY'S ACTIVITIES AS $numeric_index => ACTIVITY -->
				<?php
				foreach ($TripDay->getActivities() as $activity_numeric_index => $Activity) {
//					if ( $d == 1 && $activity_numeric_index == 0 && stripos( $Activity->getDisplayTitle(), 'check in' ) === false ) {
//						include $itinerary_check_in_path;
//					}
					if ( $Activity->keepPrivate() && ! $is_concierge ) {
						continue;
					}
					include $share_print_activity_path;
				}
//				if ( $d == $Itinerary->getTripDayCount() ) {
//					include $itinerary_check_out_path;
//				}
				?>
			</div>
		<?php } ?>
	</div>
</div>