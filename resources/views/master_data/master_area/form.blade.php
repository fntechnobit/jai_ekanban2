<!-- Modal -->
<div class="modal fade" id="masterAreaModal" tabindex="-1" role="dialog" aria-labelledby="masterAreaModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="masterAreaForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="masterAreaModalLabel">Add Preassy Area Data</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="area_id" name="area_id">

                    <div class="form-group">
                        <label for="area">Area <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="area" name="area" required>
                        <span class="text-danger error-text area_error"></span>
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
