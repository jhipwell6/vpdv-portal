<form autocomplete="off" class="edit-row-form" data-form-guest-id="<?php echo $Guest->getPostID(); ?>" id="guest_list_edit_row_<?php echo $Guest->getPostID(); ?>">
    <input type="hidden" name="guest_id" value="<?php echo $Guest->getPostID(); ?>">
    <div class="popup-confirm-wrapper" style="display: none;">
        <div class ="popup-confirm">
            <!-- Onsite defaults to 'Yes' - there is no answer that is not selected -->
            <select name="onsite_stay">
                <option value="Yes" <?php echo (true === $Guest->isOnsite()) ? 'checked' : ''; ?>>Onsite</option>
                <option value="No" <?php echo (false === $Guest->isOnsite()) ? 'checked' : ''; ?>>Offsite</option>
            </select>
            <button type="button" class="btn btn-secondary js-edit-guest-list-row-submit">Confirm</button>
        </div>
    </div>
    <div class="popup-confirm-wrapper" style="display: none;">
        <div class ="popup-confirm">
            <label for="stay_location">Stay Location</label>
            <input id="stay_location" type="text" name="stay_location" value="<?php echo $Guest->getStayLocation(); ?>">
            <button type="button" class="btn btn-secondary js-edit-guest-list-row-submit">Confirm</button>
        </div>
    </div>
    <div class="popup-confirm-wrapper" style="display: none;">
        <div class ="popup-confirm">
            <select name="villa_id">
                <option value="">Choose One</option>
                <?php if (!empty($Guest->getVillaID())) { ?>
                    <option selected value="<?php echo $Guest->getVillaID(); ?>"><?php echo $Guest->getVillaID(); ?></option>
                <?php } ?>
            </select>
            <button type="button" class="btn btn-secondary js-edit-guest-list-row-submit">Confirm</button>
        </div>
    </div>
    <div class="popup-confirm-wrapper" style="display: none;">
        <div class ="popup-confirm">
            <select name="room_name">
                <option value="">Choose One</option>
                <?php if (!empty($Guest->getRoomName())) { ?>
                    <option selected value="<?php echo $Guest->getRoomName(); ?>"><?php echo $Guest->getRoomName(); ?></option>
                <?php } ?>
            </select>
            <button type="button" class="btn btn-secondary js-edit-guest-list-row-submit">Confirm</button>
        </div>
    </div>
</form>
