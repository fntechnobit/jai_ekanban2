<!-- Generate Schedule Modal -->
<div class="modal fade" id="generateModal" tabindex="-1" aria-labelledby="generateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generateModalLabel">
                    <i class="fa-solid fa-gear"></i> Generate Assy Schedule
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="generateForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="generate_dates" class="form-label">Date Range <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="generate_dates" required readonly>
                        <small class="form-text text-muted">
                            Select the date range for schedule generation (default: 7 days)
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="generate_conveyor_id" class="form-label">Conveyor (Optional)</label>
                        <select class="form-select form-select-sm" id="generate_conveyor_id" name="generate_conveyor_id">
                            <option value="">- All Conveyor -</option>
                            @foreach($conveyors as $conveyor)
                                <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Leave empty to generate for all conveyors
                        </small>
                    </div>

                    <div class="alert alert-info">
                        <i class="fa-solid fa-circle-info"></i> 
                        <strong>Note:</strong> This will generate schedules based on listing data and conveyor capacity. 
                        Existing schedules for the selected date range may be overwritten.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-submit-generate">
                        <i class="fa-solid fa-gear"></i> Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
