<!-- Import Modal -->
<div class="modal fade" id="importCircuitModal" tabindex="-1"  aria-labelledby="importCircuitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" >
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importCircuitModalLabel">Import Data Circuit (Cutting)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    
                </button>
            </div>
            <form id="importCircuitForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fa-solid fa-circle-info"></i>
                        <strong>Important:</strong> Maximum 1000 rows per upload. If you need to import more data, please split it into smaller batches.
                    </div>

                    <div class="mb-3 row">
                        <label for="import_area_id" class="col-sm-3 col-form-label">Area :</label>
                        <div class="col-sm-9">
                            <select class="form-select select2" id="import_area_id" name="area_id" style="width: 100%;">
                                <option value="">- All Area -</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}">{{ $area->area }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-danger import_area_id_error"></small>
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label for="import_conveyor_id" class="col-sm-3 col-form-label">Conveyor <span class="text-danger">*</span>:</label>
                        <div class="col-sm-9">
                            <select class="form-select select2" id="import_conveyor_id" name="conveyor_id" style="width: 100%;" required>
                                <option value="">- Choose Conveyor -</option>
                                @foreach($conveyors as $conveyor)
                                    <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-danger import_conveyor_id_error"></small>
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label for="import_file" class="col-sm-3 col-form-label">File Import <span class="text-danger">*</span>:</label>
                        <div class="col-sm-9">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="import_file" name="file" accept=".xlsx,.xls" required>
                                <label class="custom-file-label" for="import_file">Browse File</label>
                            </div>
                            <small class="form-text text-muted">Accepted formats: .xlsx, .xls (Max: 10MB)</small>
                            <small class="form-text text-danger import_file_error"></small>
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label for="rows_start" class="col-sm-3 col-form-label">Rows Start :</label>
                        <div class="col-sm-4">
                            <input type="number" class="form-control form-control-sm" id="rows_start" name="rows_start" value="2" min="1" required>
                            <small class="form-text text-danger rows_start_error"></small>
                        </div>
                        <div class="col-sm-5">
                            <small class="form-text text-muted" style="font-style: italic;">*Row number where data starts in Excel file</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-sm" id="btn-download-template-circuit">
                        <i class="fa-solid fa-file-spreadsheet"></i> Download Template
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-submit-import">
                        <i class="fa-solid fa-upload"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
