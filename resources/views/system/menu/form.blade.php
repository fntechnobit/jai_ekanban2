<!-- Modal -->
<div class="modal fade" id="menuModal" tabindex="-1" role="dialog" aria-labelledby="menuModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="menuForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="menuModalLabel">Add Menu</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="menu_id" name="menu_id">
                    
                    <div class="form-group">
                        <label for="code">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="code" name="code" required>
                        <small class="form-text text-muted">Unique identifier (e.g., dashboard, users)</small>
                        <span class="text-danger error-text code_error"></span>
                    </div>

                    <div class="form-group">
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <span class="text-danger error-text name_error"></span>
                    </div>

                    <div class="form-group">
                        <label for="url">URL <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="url" name="url" required>
                        <small class="form-text text-muted">Example: /dashboard or #</small>
                        <span class="text-danger error-text url_error"></span>
                    </div>

                    <div class="form-group">
                        <label for="icon">Icon</label>
                        <input type="text" class="form-control" id="icon" name="icon">
                        <small class="form-text text-muted">FontAwesome class (e.g., fas fa-home)</small>
                        <span class="text-danger error-text icon_error"></span>
                    </div>

                    <div class="form-group">
                        <label for="parent_id">Parent Menu</label>
                        <select class="form-control" id="parent_id" name="parent_id">
                            <option value="">None (Top Level)</option>
                            @foreach($parentMenus as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                            @endforeach
                        </select>
                        <span class="text-danger error-text parent_id_error"></span>
                    </div>

                    <div class="form-group">
                        <label for="order">Order <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="order" name="order" value="0" min="0" required>
                        <span class="text-danger error-text order_error"></span>
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
