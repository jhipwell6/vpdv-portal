<?php
	$Villa = $Itinerary->getVilla();
	$previous_room_sub_villa = null; // Used to group rooms by Villa
?>
<div class="accordion sec-itinerary-accordion push-top">
	<?php foreach( $Villa->getRooms() as $index => $Room ): ?>
		<?php $room = $Room->getFormKey(); // Example: Red_Room-102 ?>
		<?php if ($previous_room_sub_villa !== $Room->getSubVilla()->getPostID()): $previous_room_sub_villa = $Room->getSubVilla()->getPostID(); ?>
		<!-- Group by Villa -->
		<h3 class="current-room-villa"><?php echo $Room->getSubVilla()->getTitle(); ?></h3>
		<?php endif; ?>
		<h6 class="current-room-villa-name accordion-btn active">
			<?php echo $Room->getRoomName(); ?> - <?php echo $Room->getFloorLocationText(); ?>
			<?php if ( $Room->getIsAccessible() ) : ?><span data-toggle="tooltip" title="Accessible" style="margin-left:auto"><span class="fas fa-universal-access"></span></span><?php endif; ?>
			<div class="current-room-villa-color" style="background-color: <?php echo $Room->getRoomColor() ? $Room->getRoomColor() : '#FFFFFF'; ?> !important"></div>
		</h6>
		<div class="accordion-cont accordion-active column-wrapper-simple">
			<p><b>Room Configuration:</b> <?php echo $Room->getBedConfiguration() ? ucfirst($Room->getBedConfiguration()) : ''; ?></p>
			<?php if ($Room->isPackAndPlay()) { ?>
				<p><b>Pack and Play: </b>Yes</p>
			<?php } ?>
			<?php for ($i = 1; $i <= $Room::getDefaultAllowedGuests(); ++$i ): ?>
				<?php if( $Room->isGuestChild($i) ): ?>
				<p><b>Child <?php echo $i; ?> Name:</b> <?php echo $Room->getGuestChildName($i) ? $Room->getGuestChildName($i) : 'N/A'; ?></p>
				<?php else: ?>
					<p><b>Adult <?php echo $i; ?> Name:</b> <?php echo $Room->getGuest($i) ? $Room->getGuest($i)->getFullName() : 'N/A'; ?></p>
				<?php endif; ?>
			<?php endfor; ?>
			<p><b>Additional Guest?</b> <?php echo $Room->isAdditionalGuest() ? 'Yes' : 'No'; ?></p>
			<?php if ($Room->isAdditionalGuest()): ?>
				<?php for ($i = $Room->getDefaultAllowedGuests() + 1; $i <= $Room->getTotalAllowedGuests(); ++$i): ?>
					<?php if( $Room->isGuestChild($i) ): ?>
					<p><b>Child <?php echo $i; ?> Name:</b> <?php echo $Room->getGuestChildName($i) ? $Room->getGuestChildName($i) : 'N/A'; ?></p>
					<?php else: ?>
						<p><b>Adult <?php echo $i; ?> Name:</b> <?php echo $Room->getGuest($i) ? $Room->getGuest($i)->getFullName() : 'N/A'; ?></p>
					<?php endif; ?>
				<?php endfor; ?>
			<?php endif; ?>
			<?php if ($Room->getSpecialRequests()): ?>
				<p><b>Special Requests</b> <?php echo $Room->getSpecialRequests(); ?></p>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>