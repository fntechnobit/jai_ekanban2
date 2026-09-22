<!-- Remove Data Modal -->
<div class="modal fade" id="removeDataModal" tabindex="-1"  aria-labelledby="removeDataModalLabel" aria-hidden="true">
    <div class="modal-dialog" >
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="removeDataModalLabel">Remove Data Shikake</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form id="removeDataForm" novalidate>
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="remove_area_id">Area <span class="text-danger">*</span></label>
                        <select class="form-select select2" id="remove_area_id" name="area_id" style="width: 100%;" required>
                            <option value="">- Choose Area -</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->area }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="remove_conveyor_id">Conveyor <span class="text-danger">*</span></label>
                        <select class="form-select select2" id="remove_conveyor_id" name="conveyor_id" style="width: 100%;" required disabled>
                            <option value="">- Choose Conveyor -</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="remove_process">Process Type</label>
                        <select class="form-select select2" id="remove_process" name="process" style="width: 100%;">
                            <option value="">- All Process -</option>
                            @foreach($processTypes as $processType)
                                <option value="{{ $processType->value }}">{{ $processType->value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="remove_machine">Machine</label>
                        <select class="form-select select2" id="remove_machine" name="machine" style="width: 100%;" disabled>
                            <option value="">- All Machine -</option>
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-exclamation-triangle"></i>
                        <strong>Warning!</strong> This action will permanently delete all Shikake data on the selected conveyor that match the chosen process type and machine (all of them when left empty).
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                </div>
            </form>
        </div>
    </div>
</div>
