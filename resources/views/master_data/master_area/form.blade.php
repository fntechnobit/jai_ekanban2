<!-- Modal -->
<div class="modal fade" id="masterAreaModal" tabindex="-1"  aria-labelledby="masterAreaModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" >
        <div class="modal-content">
            <form id="masterAreaForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="masterAreaModalLabel">Add Preassy Area Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="area_id" name="area_id">

                    <div class="mb-3">
                        <label for="area">Area <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="area" name="area" required>
                        <span class="text-danger error-text area_error"></span>
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
