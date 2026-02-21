<!-- Modal -->
<div class="modal fade" id="masterCarlineModal" tabindex="-1"  aria-labelledby="masterCarlineModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" >
        <div class="modal-content">
            <form id="masterCarlineForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="masterCarlineModalLabel">Add Carline Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="carline_id" name="carline_id">

                    <div class="mb-3">
                        <label for="area_id">Area <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="area_id" name="area_id" required>
                            <option value="">- Select Area -</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->area }}</option>
                            @endforeach
                        </select>
                        <span class="text-danger error-text area_id_error"></span>
                    </div>

                    <div class="mb-3">
                        <label for="code">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="code" name="code" required>
                        <span class="text-danger error-text code_error"></span>
                    </div>

                    <div class="mb-3">
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="name" name="name" required>
                        <span class="text-danger error-text name_error"></span>
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
