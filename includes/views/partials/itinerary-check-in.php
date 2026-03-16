<?php if ( ( isset( $is_summary ) && $is_summary ) || ( isset( $is_share ) && $is_share ) ) : ?>
<div class="js-accordion-wrapper">
    <h6 class="accordion-btn active">
		<span class="activity--title-name">3:00 PM - Check in. Welcome to Villa Punto de Vista!</span>
    </h6>
</div>
<?php ; else : ?>
<div class="js-accordion-wrapper activity-accordion">
    <h6 class="accordion-btn">
		<span class="activity--title">
			<span class="activity--title-time">
				<span class="activity--title-timePrefix">3:00 PM</span>
				<span class="activity--title-timeSeparator"> - </span>
			</span>
			<span class="activity--title-name">Check in. Welcome to Villa Punto de Vista!</span>
        </span>
    </h6>
</div>
<?php endif; ?>
