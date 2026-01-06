<!-- Modal -->
<div class="modal fade" id="userGroupModal" tabindex="-1" aria-labelledby="userGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="userGroupForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="userGroupModalLabel">Add User Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="group_id" name="group_id">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="name" name="name" required>
                        <span class="text-danger error-text name_error"></span>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control form-control-sm" id="description" name="description" rows="3"></textarea>
                        <span class="text-danger error-text description_error"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="is_active_yes" name="is_active" value="1" checked>
                            <label for="is_active_yes" class="form-check-label">Active</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="is_active_no" name="is_active" value="0">
                            <label for="is_active_no" class="form-check-label">Inactive</label>
                        </div>
                        <span class="text-danger error-text is_active_error"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
