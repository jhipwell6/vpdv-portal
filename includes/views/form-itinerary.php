<form id="form-itinerary">
    <div class="">
        <div class="form-group">
            <label for="user-id">Name</label>
            <div class="input-errors">
                <span data-input-errors="user_id"></span>
            </div>
            <select id="user-id" name="user_id" class="form-control">
            <?php foreach ($users as $index => $user_info_object) { ?>
                <option value="<?php echo $user_info_object->ID; ?>"><?php echo $user_info_object->display_name . ' - ' . $user_info_object->user_email; ?></option>
            <?php } ?>
            </select>
        </div>
        <div class="form-group">
            <label for="group-name">Group Name</label>
            <div class="input-errors">
                <span data-input-errors="group_name"></span>
            </div>
            <input id="group-name" type="text" name="group_name" class="form-control">
        </div>
        <div class="form-group">
            <label for="trip-dates">Trip Dates</label>
            <div class="input-errors">
                <span data-input-errors="trip_dates"></span>
            </div>
            <input id="trip-dates" type="text" name="trip_dates" class="form-control" autocomplete="off">
        </div>
        <div class="form-group">
            <label for="villa-id">Villa</label>
            <div class="input-errors">
                <span data-input-errors="villa_id"></span>
            </div>
            <select id="villa-id" name="villa_id" class="form-control">
            <?php foreach ($villas as $villa_title => $villa_id) { ?>
                <option value="<?php echo $villa_id; ?>"><?php echo $villa_title; ?></option>
            <?php } ?>
            </select>
        </div>
        <div class="form-group submit-button-container">
            <input type="submit" id="submit-form-itinerary" class="btn btn-primary" value="Submit">
        </div>
        <div style="display: none;">
            <input type="hidden" id="trip-start-date" name="trip_start_date">
            <input type="hidden" id="trip-end-date" name="trip_end_date">
        </div>
    </div>
</form>