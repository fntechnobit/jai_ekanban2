<!-- Generate Schedule Modal -->
<div class="modal fade" id="generateModal" tabindex="-1" role="dialog" aria-labelledby="generateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generateModalLabel">
                    <i class="fas fa-cogs"></i> Generate Assy Schedule
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="generateForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="generate_dates">Date Range <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="generate_dates" required readonly>
                        <small class="form-text text-muted">
                            Select the date range for schedule generation (default: 7 days)
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="generate_conveyor_id">Conveyor (Optional)</label>
                        <select class="form-control" id="generate_conveyor_id" name="generate_conveyor_id">
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
                        <i class="fas fa-info-circle"></i> 
                        <strong>Note:</strong> This will generate schedules based on listing data and conveyor capacity. 
                        Existing schedules for the selected date range may be overwritten.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-generate">
                        <i class="fas fa-cogs"></i> Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
