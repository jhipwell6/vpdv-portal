<form autocomplete="off" id="form-edit-guest" class="js-edit-guest-form" data-itinerary="<?php echo $Itinerary->getPostID(); ?>">
	<input type="hidden" value="" name="guest_id" />
	<div class="row">
		<div class="col-xxs-12 col-md-6 push-bottom">
			<label>First Name</label>
			<input type="text" value="" name="guest_first_name" />
		</div>
		<div class="col-xxs-12 col-md-6 push-bottom">
			<label>Last Name</label>
			<input type="text" value="" name="guest_last_name" />
		</div>
	</div>
	<div class="row">
		<div class="col-xxs-12 col-md-6 push-bottom">
			<label>Email</label>
			<input type="text" value="" name="guest_email" />
		</div>
		<div class="col-xxs-12 col-md-6 push-bottom">
			<label>Is Child Guest</label>
			<input type="checkbox" value="1" name="guest_is_child" />
		</div>
	</div>
	<div class="row">
		<div class="col-xxs-12 push-bottom">
			<label>Will they be staying overnight at the villa(s)?</label>
			<select value="" name="onsite_stay" class="js-onsite_stay_select">
				<option value="Yes">Yes</option>
				<option value="No">No</option>
			</select>
		</div>
	</div>
	<div class="row js-stay_location hidden">
		<div class="col-xxs-12 push-bottom">
			<label>Where will this guest be staying?</label>
			<select value="" name="stay_location" class="js-stay_location_select">
				<option value="">Select a location</option>
				<option value="Hotel Gaia">Hotel Gaia</option>
				<option value="Hotel Arenas Del Mar">Hotel Arenas Del Mar</option>
				<option value="Hotel Mariposa">Hotel Mariposa</option>
				<option value="Hotel Si Como No">Hotel Si Como No</option>
				<option value="Hotel Parador">Hotel Parador</option>
				<option value="Hotel Los Altos">Hotel Los Altos</option>
				<option value="Hotel Makanda">Hotel Makanda</option>
				<option value="Hotel Jungle Vista">Hotel Jungle Vista</option>
				<option value="Villa Pura Vida">Villa Pura Vida</option>
				<option value="Villa Diamante">Villa Diamante</option>
				<option value="Villa Mi Casa Su Casa">Villa Mi Casa Su Casa</option>
				<option value="Villa Costa Vista">Villa Costa Vista</option>
				<option value="Casa Pura Vista">Casa Pura Vista</option>
				<option value="Makanda Hotel">Makanda Hotel</option>
				<option value="Shana Hotel">Shana Hotel</option>
				<option value="Other">Other</option>
			</select>
		</div>
	</div>
	<div class="row js-stay_location_other hidden">
		<div class="col-xxs-12 push-bottom">
			<label>Other Location</label>
			<input type="text" value="" name="stay_location_other" />
		</div>
	</div>
	<div class="row">
		<div class="col-xxs-12 push-bottom">
			<label>Guest Notes</label>
			<textarea name="guest_notes"></textarea>
		</div>
	</div>
	<div class="row">
		<div class="col-xxs-12 push-bottom">
			<label>Dietary Restrictions</label>
			<div class="row">
				<input type="checkbox" name="guest_dietary_restrictions[]" id="guest_dietary_restrictions_Vegetarian" value="Vegetarian">
				<label for="guest_dietary_restrictions_Vegetarian">Vegetarian</label>
			</div>
			<div class="row">
				<input type="checkbox" name="guest_dietary_restrictions[]" id="guest_dietary_restrictions_Vegan" value="Vegan">
				<label for="guest_dietary_restrictions_Vegan">Vegan</label>
			</div>
			<div class="row">
				<input type="checkbox" name="guest_dietary_restrictions[]" id="guest_dietary_restrictions_Gluten-Free" value="Gluten Free">
				<label for="guest_dietary_restrictions_Gluten-Free">Gluten Free</label>
			</div>
			<div class="row">
				<input type="checkbox" name="guest_dietary_restrictions[]" id="guest_dietary_restrictions_Other" value="Other">
				<label for="guest_dietary_restrictions_Other">Other</label>
			</div>
		</div>
	</div>
	<div class="row js-guest_dietary_restriction_other hidden">
		<div class="col-xxs-12 push-bottom">
			<label>Other Dietary Restrictions</label>
			<input type="text" value="" name="guest_dietary_restriction_other" />
		</div>
	</div>
	<div class="row">
		<div class="col-xxs-12 push-bottom">
			<label>Allergies</label>
			<textarea name="guest_allergies"></textarea>
		</div>
	</div>
	<div class="flex-row">
		<a class="btn btn-thirdary js-edit-guest-cancel" href="#">Cancel</a>
		<button type="submit" class="btn btn-secondary js-edit-guest-submit" style="margin-left:auto">Save</button>
	</div>
</form>