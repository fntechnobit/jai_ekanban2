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
                        <label for="area_id" class="form-label">Area</label>
                        <select class="form-select form-select-sm" id="area_id" name="area_id">
                            <option value="">- Select Area -</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->area }}</option>
                            @endforeach
                        </select>
                        <span class="text-danger error-text area_id_error"></span>
                    </div>

                    <div class="mb-3">
                        <label for="carline_id" class="form-label">Carline</label>
                        <select class="form-select form-select-sm" id="carline_id" name="carline_id">
                            <option value="">- Select Carline -</option>
                        </select>
                        <span class="text-danger error-text carline_id_error"></span>
                    </div>

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
