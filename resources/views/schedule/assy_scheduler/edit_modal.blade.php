<!-- Edit Qty Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel"><i class="ti ti-edit"></i> Edit Assy Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle"></i>
                        <strong>Warning:</strong> This change cannot be undone, even after re-sync. The edited record will be preserved during future synchronizations.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Assy</label>
                        <input type="text" class="form-control" id="edit_assy" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_qty" class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit_qty" name="qty" min="0" required>
                        <div class="form-text text-muted">
                            <i class="ti ti-info-circle"></i> If set to 0 or less, this schedule will not appear in the verification page.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-edit">
                        <i class="ti ti-check"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
