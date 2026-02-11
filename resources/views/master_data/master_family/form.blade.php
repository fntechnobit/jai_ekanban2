<!-- Modal -->
<div class="modal fade" id="masterFamilyModal" tabindex="-1" aria-labelledby="masterFamilyModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="masterFamilyForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="masterFamilyModalLabel">Add Family Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="family_id" name="family_id">

                    <div class="mb-3">
                        <label for="family" class="form-label">Family <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="family" name="family" required>
                        <span class="text-danger error-text family_error"></span>
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
