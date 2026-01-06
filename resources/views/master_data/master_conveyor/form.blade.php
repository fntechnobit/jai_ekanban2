<!-- Modal -->
<div class="modal fade" id="masterConveyorModal" tabindex="-1"  aria-labelledby="masterConveyorModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" >
        <div class="modal-content">
            <form id="masterConveyorForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="masterConveyorModalLabel">Add Conveyor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="conveyor_id" name="conveyor_id">

                    <div class="mb-3">
                        <label for="master_area_id">Area <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="master_area_id" name="master_area_id" style="width: 100%;" required>
                            <option value="">Select Area</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->area }}</option>
                            @endforeach
                        </select>
                        <span class="text-danger error-text master_area_id_error"></span>
                    </div>

                    <div class="mb-3">
                        <label for="conveyor">Conveyor <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="conveyor" name="conveyor" required>
                        <span class="text-danger error-text conveyor_error"></span>
                    </div>

                    <div class="mb-3">
                        <label for="family_ids">Family</label>
                        <select class="form-select form-select-sm" id="family_ids" name="family_ids[]" multiple style="width: 100%;">
                            @foreach($families as $family)
                                <option value="{{ $family->id }}">{{ $family->family }}</option>
                            @endforeach
                        </select>
                        <span class="text-danger error-text family_ids_error"></span>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="capacity">Capacity/Shift <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" id="capacity" name="capacity" min="1" required>
                                <span class="text-danger error-text capacity_error"></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="shift_qty">Shift <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="shift_qty" name="shift_qty" style="width: 100%;" required>
                                    <option value="1">1 Shift</option>
                                    <option value="2">2 Shift</option>
                                </select>
                                <span class="text-danger error-text shift_qty_error"></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="pallet_qty">Pallet Qty. <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" id="pallet_qty" name="pallet_qty" min="1" required>
                                <span class="text-danger error-text pallet_qty_error"></span>
                            </div>
                        </div>
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
