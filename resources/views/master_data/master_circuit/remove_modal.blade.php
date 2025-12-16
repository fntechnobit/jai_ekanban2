<!-- Remove Data Modal -->
<div class="modal fade" id="removeDataModal" tabindex="-1" role="dialog" aria-labelledby="removeDataModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="removeDataModalLabel">Remove Data Circuit</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="removeDataForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="remove_conveyor_id">Conveyor <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="remove_conveyor_id" name="conveyor_id" style="width: 100%;" required>
                            <option value="">- Choose Conveyor -</option>
                            @foreach($conveyors as $conveyor)
                                <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Warning!</strong> This action will permanently delete all Circuit data associated with the selected conveyor.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Remove</button>
                </div>
            </form>
        </div>
    </div>
</div>
