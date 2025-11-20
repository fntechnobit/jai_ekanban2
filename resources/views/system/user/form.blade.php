<!-- Modal -->
<div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="userForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Add User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="user_id" name="user_id">
                    
                    <div class="form-group">
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <span class="text-danger error-text name_error"></span>
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <span class="text-danger error-text email_error"></span>
                    </div>

                    <div class="form-group">
                        <label for="group_id">User Group <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="group_id" name="group_id" required style="width: 100%;">
                            <option value="">Select Group</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                        <span class="text-danger error-text group_id_error"></span>
                    </div>

                    <div class="form-group" id="password-group">
                        <label for="password">Password <span class="text-danger password-required">*</span></label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="form-text text-muted">Leave blank to keep current password (when editing)</small>
                        <span class="text-danger error-text password_error"></span>
                    </div>

                    <div class="form-group" id="password-confirmation-group">
                        <label for="password_confirmation">Confirm Password <span class="text-danger password-required">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                        <span class="text-danger error-text password_confirmation_error"></span>
                    </div>

                    <div class="form-group">
                        <label>Status <span class="text-danger">*</span></label>
                        <div class="custom-control custom-radio">
                            <input class="custom-control-input" type="radio" id="is_active_yes" name="is_active" value="1" checked>
                            <label for="is_active_yes" class="custom-control-label">Active</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input class="custom-control-input" type="radio" id="is_active_no" name="is_active" value="0">
                            <label for="is_active_no" class="custom-control-label">Inactive</label>
                        </div>
                        <span class="text-danger error-text is_active_error"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
