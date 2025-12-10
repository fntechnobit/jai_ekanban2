<!-- Modal -->
<div class="modal fade" id="masterMachineModal" tabindex="-1" role="dialog" aria-labelledby="masterMachineModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="masterMachineForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="masterMachineModalLabel">Add Machine</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="machine_id" name="machine_id">

                    <div class="form-group">
                        <label for="machine">Machine <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="machine" name="machine" required>
                        <span class="text-danger error-text machine_error"></span>
                    </div>

                    <div class="form-group">
                        <label for="conveyor_ids">Conveyor</label>
                        <select class="form-control" id="conveyor_ids" name="conveyor_ids[]" multiple style="width: 100%;">
                            @foreach($conveyors as $conveyor)
                                <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }} ({{ $conveyor->area->area ?? '-' }})</option>
                            @endforeach
                        </select>
                        <span class="text-danger error-text conveyor_ids_error"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btn-save">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
