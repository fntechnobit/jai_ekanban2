<!-- Modal -->
<div class="modal fade" id="masterFamilyModal" tabindex="-1" role="dialog" aria-labelledby="masterFamilyModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="masterFamilyForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="masterFamilyModalLabel">Add Family Data</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="family_id" name="family_id">

                    <div class="form-group">
                        <label for="family">Family <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="family" name="family" required>
                        <span class="text-danger error-text family_error"></span>
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
