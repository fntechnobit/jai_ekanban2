<!-- Modal -->
<div class="modal fade" id="masterMachineModal" tabindex="-1" aria-labelledby="masterMachineModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="masterMachineForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="masterMachineModalLabel">Add Machine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="machine_id" name="machine_id">

                    <div class="mb-3">
                        <label for="machine" class="form-label">Machine <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="machine" name="machine" required>
                        <span class="text-danger error-text machine_error"></span>
                    </div>

                    <div class="mb-3">
                        <label for="master_area_id" class="form-label">Area <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="master_area_id" name="master_area_id" required style="width: 100%;">
                            <option value="">Select Area</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->area }}</option>
                            @endforeach
                        </select>
                        <span class="text-danger error-text master_area_id_error"></span>
                    </div>

                    <div class="mb-3">
                        <label for="conveyor_ids" class="form-label">Conveyor</label>
                        {{-- Opsi diisi lewat JS, dibatasi ke conveyor milik area yang dipilih --}}
                        <select class="form-select form-select-sm" id="conveyor_ids" name="conveyor_ids[]" multiple style="width: 100%;"></select>
                        <small class="text-muted">Pilih area terlebih dahulu, conveyor yang tampil hanya milik area tersebut.</small>
                        <span class="text-danger error-text conveyor_ids_error"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-save">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
