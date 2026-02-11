<!-- Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="userForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="user_id" name="user_id">

                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="name" name="name" required>
                        <span class="text-danger error-text name_error"></span>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="username" name="username" required>
                        <span class="text-danger error-text username_error"></span>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control form-control-sm" id="email" name="email" required>
                        <span class="text-danger error-text email_error"></span>
                    </div>

                    <div class="mb-3">
                        <label for="group_id" class="form-label">User Group <span class="text-danger">*</span></label>
                        <select class="form-select select2" id="group_id" name="group_id" required
                            style="width: 100%;">
                            <option value="">Select Group</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                        <span class="text-danger error-text group_id_error"></span>
                    </div>

                    <div class="mb-3" id="password-group">
                        <label for="password" class="form-label">Password <span class="text-danger password-required">*</span></label>
                        <input type="password" class="form-control form-control-sm" id="password" name="password">
                        <small class="form-text text-muted">Leave blank to keep current password (when editing)</small>
                        <span class="text-danger error-text password_error"></span>
                    </div>

                    <div class="mb-3" id="password-confirmation-group">
                        <label for="password_confirmation" class="form-label">Confirm Password <span
                                class="text-danger password-required">*</span></label>
                        <input type="password" class="form-control form-control-sm" id="password_confirmation"
                            name="password_confirmation">
                        <span class="text-danger error-text password_confirmation_error"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="is_active_yes" name="is_active"
                                value="1" checked>
                            <label for="is_active_yes" class="form-check-label">Active</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="is_active_no" name="is_active"
                                value="0">
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