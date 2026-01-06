<!-- Modal -->
<div class="modal fade" id="menuModal" tabindex="-1" aria-labelledby="menuModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="menuForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="menuModalLabel">Add Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="menu_id" name="menu_id">
                    
                    <div class="mb-3">
                        <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="code" name="code" required>
                        <small class="form-text text-muted">Unique identifier (e.g., dashboard, users)</small>
                        <span class="text-danger error-text code_error"></span>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="name" name="name" required>
                        <span class="text-danger error-text name_error"></span>
                    </div>

                    <div class="mb-3">
                        <label for="url" class="form-label">URL <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="url" name="url" required>
                        <small class="form-text text-muted">Example: /dashboard or #</small>
                        <span class="text-danger error-text url_error"></span>
                    </div>

                    <div class="mb-3">
                        <label for="icon" class="form-label">Icon</label>
                        <select class="form-select select2-icon" id="icon" name="icon" style="width: 100%;">
                            <option value="">-- Select Icon --</option>
                        </select>
                        <span class="text-danger error-text icon_error"></span>
                    </div>

                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent Menu</label>
                        <select class="form-select form-select-sm" id="parent_id" name="parent_id">
                            <option value="">None (Top Level)</option>
                            @foreach($parentMenus as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                            @endforeach
                        </select>
                        <span class="text-danger error-text parent_id_error"></span>
                    </div>

                    <div class="mb-3">
                        <label for="order" class="form-label">Order <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-sm" id="order" name="order" value="0" min="0" required>
                        <span class="text-danger error-text order_error"></span>
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
