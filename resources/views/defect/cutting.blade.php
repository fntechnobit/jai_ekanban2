@extends('layouts.master')

@section('title', 'Defect Cutting')

@section('breadcrumb')
    <x-page-header menu-code="defect_cutting" />
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <h5 class="card-title mb-0">
                <i class="fa-solid fa-scissors text-danger me-2"></i> Defect Cutting - Circuit List
            </h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <select class="form-select form-select-sm select2" id="filter_area_id" data-placeholder="- All Area -" style="width: 160px;">
                    <option value="">- All Area -</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}">{{ $area->area }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm select2" id="filter_conveyor_id" data-placeholder="- All Conveyor -" style="width: 180px;">
                    <option value="">- All Conveyor -</option>
                    @foreach($conveyors as $conveyor)
                        <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm select2" id="filter_type" data-placeholder="- All Type -" style="width: 160px;">
                    <option value="">- All Type -</option>
                    <option value="CUTTING">CUTTING</option>
                    <option value="CUTTING_TWIST">CUTTING TWIST</option>
                </select>
                <a href="{{ route('defect.history') }}" class="btn btn-outline-secondary btn-sm" title="History">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </a>
                <button type="button" class="btn btn-outline-danger btn-sm" id="btn-reset-filter" title="Reset Filter">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
                <button type="button" class="btn btn-danger btn-sm" id="btn-import-sto">
                    <i class="fa-solid fa-file-import me-1"></i> Import STO
                </button>
            </div>
        </div>
        <div class="card-body">
            <table id="cutting-table" class="table table-bordered table-striped table-sm">
                <thead>
                    <tr>
                        <th width="4%">No</th>
                        <th width="6%">Type</th>
                        <th>Carline</th>
                        <th>Conveyor</th>
                        <th>CCT No</th>
                        <th>CCT Code</th>
                        <th>Shikake</th>
                        <th>Family</th>
                        <th width="5%">QTY</th>
                        <th>Machine</th>
                        <th width="5%">Seq</th>
                        <th width="8%">Balance</th>
                        <th width="10%">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Defect Modal -->
<div class="modal fade" id="defectModal" tabindex="-1" aria-labelledby="defectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="defectModalLabel">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> Record Defect
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="defect-form">
                <div class="modal-body">
                    <input type="hidden" id="master_circuit_id" name="master_circuit_id">
                    <input type="hidden" id="conveyor_id" name="conveyor_id">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Circuit Info</label>
                        <div class="card bg-light">
                            <div class="card-body py-2">
                                <div class="row">
                                    <div class="col-4"><small class="text-muted">Conveyor</small><div id="info-conveyor" class="fw-bold">-</div></div>
                                    <div class="col-4"><small class="text-muted">CCT No</small><div id="info-cct-no" class="fw-bold">-</div></div>
                                    <div class="col-4"><small class="text-muted">CCT Code</small><div id="info-cct-code" class="fw-bold">-</div></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 text-center">
                        <label class="form-label">Current Balance</label>
                        <div class="fs-3 fw-bold text-success" id="current-balance">0</div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="defect_date" class="form-label">Defect Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="defect_date" name="defect_date"
                                   value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="shift" class="form-label">Shift <span class="text-danger">*</span></label>
                            <select class="form-select" id="shift" name="shift" required>
                                <option value="">- Select Shift -</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="qty_defect" class="form-label">Qty Defect <span class="text-danger">*</span></label>
                            <input type="number" class="form-control text-end" id="qty_defect" name="qty_defect" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Balance After</label>
                            <div class="form-control bg-light text-end" id="balance-after">-</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason / Notes</label>
                        <textarea class="form-control" id="reason" name="reason" rows="2" placeholder="Enter reason..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="btn-submit">
                        <i class="fa-solid fa-check me-1"></i> Submit Defect
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import STO Modal -->
<div class="modal fade" id="importStoModal" tabindex="-1" aria-labelledby="importStoModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="importStoModalLabel">
                    <i class="fa-solid fa-file-import me-2"></i> Import STO Circuit Scan History
                </h5>
                <button type="button" class="btn-close btn-close-white" id="btn-sto-close-x" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <!-- Step indicator -->
                <div class="sto-steps d-flex mb-4">
                    <div class="sto-step active" id="sto-step-indicator-1">
                        <span class="sto-step-circle">1</span>
                        <span class="sto-step-label">Upload</span>
                    </div>
                    <div class="sto-step-line"></div>
                    <div class="sto-step" id="sto-step-indicator-2">
                        <span class="sto-step-circle">2</span>
                        <span class="sto-step-label">Preview</span>
                    </div>
                    <div class="sto-step-line"></div>
                    <div class="sto-step" id="sto-step-indicator-3">
                        <span class="sto-step-circle">3</span>
                        <span class="sto-step-label">Import</span>
                    </div>
                </div>

                <!-- STEP 1: Upload -->
                <div id="sto-step-1">
                    <div class="alert alert-danger py-2 small mb-3">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        File must be the <strong>Stock Opname Barcode Scan History</strong> export from
                        <strong>jai_sto_wip</strong>. Only <strong>CCT Code</strong> and <strong>STO</strong> columns
                        are used; each CCT Code is matched against the selected conveyor's circuits &mdash;
                        unmatched codes are skipped. Matched quantities will be <strong>deducted</strong> from
                        each circuit's current balance as a defect.
                    </div>

                    <div class="mb-3">
                        <label for="import_sto_conveyor_id" class="form-label">Conveyor <span class="text-danger">*</span></label>
                        <select class="form-select select2" id="import_sto_conveyor_id" style="width: 100%;" required>
                            <option value="">- Choose Conveyor -</option>
                            @foreach($conveyors as $conveyor)
                                <option value="{{ $conveyor->id }}" data-shift-qty="{{ $conveyor->shift_qty }}">{{ $conveyor->conveyor }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-danger" id="import_sto_conveyor_id_error"></small>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Excel File <span class="text-danger">*</span></label>
                        <div class="sto-dropzone" id="sto-dropzone">
                            <input type="file" id="import_sto_file" accept=".xlsx,.xls" hidden>
                            <div class="sto-dropzone-empty text-center">
                                <div class="sto-dropzone-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                <div class="sto-dropzone-text">
                                    <strong>Drag &amp; drop</strong> your Excel file here, or
                                    <span class="text-danger text-decoration-underline">browse</span>
                                </div>
                                <div class="sto-dropzone-hint text-muted small">Accepted: .xlsx, .xls (Max 10MB)</div>
                            </div>
                            <div class="sto-dropzone-file d-none">
                                <i class="fa-solid fa-file-excel text-success fa-2x me-2"></i>
                                <div class="text-start flex-grow-1">
                                    <div class="sto-file-name fw-bold"></div>
                                    <div class="sto-file-size text-muted small"></div>
                                </div>
                                <button type="button" class="btn btn-sm btn-link text-danger" id="sto-file-remove" title="Remove file">
                                    <i class="fa-solid fa-circle-xmark fa-lg"></i>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-danger" id="import_sto_file_error"></small>
                    </div>
                </div>

                <!-- STEP 2: Preview -->
                <div id="sto-step-2" class="d-none">
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="card text-center border-success h-100">
                                <div class="card-body py-2">
                                    <div class="fs-4 fw-bold text-success" id="sto-preview-matched">0</div>
                                    <div class="small text-muted">Matched Circuits</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card text-center border-danger h-100">
                                <div class="card-body py-2">
                                    <div class="fs-4 fw-bold text-danger" id="sto-preview-notfound">0</div>
                                    <div class="small text-muted">Not Found</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card text-center border-primary h-100">
                                <div class="card-body py-2">
                                    <div class="fs-4 fw-bold text-primary" id="sto-preview-qty">0</div>
                                    <div class="small text-muted">Total STO Qty</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <ul class="nav nav-tabs nav-tabs-sm">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sto-tab-matched" type="button">Matched</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sto-tab-notfound" type="button">
                                Not Found <span class="badge bg-danger ms-1" id="sto-notfound-badge">0</span>
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content border border-top-0 rounded-bottom" style="max-height: 220px; overflow-y: auto;">
                        <div class="tab-pane fade show active p-0" id="sto-tab-matched">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="table-light sticky-top">
                                    <tr><th>CCT Code</th><th>CCT No</th><th class="text-end">Qty STO</th></tr>
                                </thead>
                                <tbody id="sto-matched-body"></tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade p-2" id="sto-tab-notfound">
                            <div class="small text-muted mb-2">These CCT Codes were not found for the selected conveyor and will be skipped:</div>
                            <div id="sto-notfound-list" class="d-flex flex-wrap gap-1"></div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <label for="import_sto_date" class="form-label">Defect Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="import_sto_date"
                                   value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                            <small class="form-text text-danger" id="import_sto_defect_date_error"></small>
                        </div>
                        <div class="col-md-6">
                            <label for="import_sto_shift" class="form-label">Shift <span class="text-danger">*</span></label>
                            <select class="form-select" id="import_sto_shift" required>
                                <option value="">- Select Shift -</option>
                            </select>
                            <small class="form-text text-danger" id="import_sto_shift_error"></small>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Progress -->
                <div id="sto-step-3" class="d-none text-center">
                    <div class="sto-progress-icon mb-2" id="sto-progress-icon">
                        <i class="fa-solid fa-arrows-rotate fa-spin text-danger"></i>
                    </div>
                    <h6 id="sto-progress-title">Importing data...</h6>
                    <div class="progress mb-2" style="height: 24px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger"
                             id="sto-progress-bar" role="progressbar" style="width: 0%">0%</div>
                    </div>
                    <div class="small text-muted mb-3" id="sto-progress-status">Preparing...</div>
                    <div class="text-start border rounded p-2 bg-light sto-progress-log" id="sto-progress-log"></div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btn-sto-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-secondary d-none" id="btn-sto-back">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                </button>
                <button type="button" class="btn btn-danger" id="btn-sto-preview" disabled>
                    <i class="fa-solid fa-eye me-1"></i> Preview Data
                </button>
                <button type="button" class="btn btn-danger d-none" id="btn-sto-confirm" disabled>
                    <i class="fa-solid fa-check me-1"></i> Confirm Import
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    .sto-steps { user-select: none; }
    .sto-step { display: flex; flex-direction: column; align-items: center; gap: 4px; flex: 0 0 auto; opacity: 0.45; transition: opacity .2s ease; }
    .sto-step.active, .sto-step.done { opacity: 1; }
    .sto-step-circle {
        width: 28px; height: 28px; border-radius: 50%; background: #e9ecef; color: #6c757d;
        display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: .8rem;
        transition: background-color .2s ease, color .2s ease;
    }
    .sto-step.active .sto-step-circle { background: #dc3545; color: #fff; }
    .sto-step.done .sto-step-circle { background: #dc3545; color: #fff; }
    .sto-step.done .sto-step-circle::before { content: '\2713'; }
    .sto-step-label { font-size: .72rem; color: #6c757d; white-space: nowrap; }
    .sto-step.active .sto-step-label { color: #dc3545; font-weight: 600; }
    .sto-step-line { flex: 1 1 auto; height: 2px; background: #e9ecef; margin: 14px 6px 0; }

    .sto-dropzone {
        border: 2px dashed #ced4da; border-radius: .5rem; padding: 28px 16px;
        display: flex; align-items: center; justify-content: center; flex-direction: column;
        cursor: pointer; transition: border-color .15s ease, background-color .15s ease; background: #fafafa;
    }
    .sto-dropzone:hover { border-color: #dc3545; background: #fdf4f4; }
    .sto-dropzone.dragover { border-color: #dc3545; background: #fbe9ea; }
    .sto-dropzone-icon { font-size: 2rem; color: #adb5bd; margin-bottom: 6px; }
    .sto-dropzone.dragover .sto-dropzone-icon { color: #dc3545; }
    .sto-dropzone-file { display: flex; align-items: center; width: 100%; }

    .sto-progress-icon { font-size: 2.2rem; }
    .sto-progress-log { max-height: 160px; overflow-y: auto; font-size: .8rem; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    .sto-progress-log .log-line { padding: 1px 0; }
    .sto-progress-log .log-ok { color: #198754; }
    .sto-progress-log .log-warn { color: #b02a37; }
</style>
@endsection

@section('script')
<script>
$(function () {
    // Initialize Select2 for filters
    $('#filter_area_id, #filter_conveyor_id, #filter_type').select2({
        theme: 'bootstrap-5',
        allowClear: true,
        placeholder: function () {
            return $(this).data('placeholder') || 'Select...';
        }
    });

    // Reset filter button
    $('#btn-reset-filter').on('click', function () {
        $('#filter_area_id').val('').trigger('change');
        $('#filter_conveyor_id').val('').trigger('change');
        $('#filter_type').val('').trigger('change');
    });

    // Initialize DataTable
    var table = $('#cutting-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('defect.cutting.datatable') }}",
            data: function (d) {
                d.area_id = $('#filter_area_id').val();
                d.conveyor_id = $('#filter_conveyor_id').val();
                d.type = $('#filter_type').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'type_badge', name: 'type', orderable: true, searchable: false, className: 'text-center' },
            { data: 'carline', name: 'carline' },
            { data: 'conveyor_name', name: 'master_conveyor.conveyor' },
            { data: 'cct_no', name: 'cct_no' },
            { data: 'cct_code', name: 'cct_code' },
            { data: 'shikake_code', name: 'shikake_code' },
            { data: 'family', name: 'family' },
            { data: 'qty', name: 'qty', className: 'text-center' },
            { data: 'machine', name: 'machine' },
            { data: 'sequence', name: 'sequence', className: 'text-center' },
            { data: 'balance_display', name: 'balance', className: 'text-center', orderable: true, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        pageLength: 50,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[3, 'asc'], [4, 'asc']]
    });

    // Filter change handlers
    $('#filter_area_id, #filter_conveyor_id, #filter_type').on('change', function () {
        table.ajax.reload();
    });

    // Open defect modal
    let currentBalance = 0;

    $(document).on('click', '.btn-defect', function () {
        var btn = $(this);
        currentBalance = parseInt(btn.data('balance')) || 0;
        var shiftQty = parseInt(btn.data('shift-qty')) || 1;

        // Build shift options dynamically
        var shiftSelect = $('#shift');
        shiftSelect.empty().append('<option value="">- Select Shift -</option>');
        for (var i = 1; i <= shiftQty; i++) {
            shiftSelect.append('<option value="' + i + '">Shift ' + i + '</option>');
        }

        $('#master_circuit_id').val(btn.data('id'));
        $('#conveyor_id').val(btn.data('conveyor-id'));
        $('#info-conveyor').text(btn.data('conveyor'));
        $('#info-cct-no').text(btn.data('cct-no'));
        $('#info-cct-code').text(btn.data('cct-code'));
        $('#current-balance').text(currentBalance + ' pcs');
        $('#qty_defect').attr('max', currentBalance).val('');
        $('#balance-after').text('-');
        $('#shift').val('');
        $('#reason').val('');
        $('#defect_date').val('{{ date("Y-m-d") }}');

        $('#defectModal').modal('show');
    });

    // Update balance after
    $('#qty_defect').on('input', function () {
        var qty = parseInt($(this).val()) || 0;
        var after = currentBalance - qty;
        if (qty > currentBalance) {
            $('#balance-after').text('Exceeds balance!').addClass('text-danger fw-bold');
            $('#btn-submit').prop('disabled', true);
        } else if (qty <= 0) {
            $('#balance-after').text('-').removeClass('text-danger fw-bold');
            $('#btn-submit').prop('disabled', true);
        } else {
            $('#balance-after').text(after + ' pcs').removeClass('text-danger fw-bold');
            $('#btn-submit').prop('disabled', false);
        }
    });

    // Submit defect form
    $('#defect-form').on('submit', function (e) {
        e.preventDefault();

        var qty = $('#qty_defect').val();
        var cctCode = $('#info-cct-code').text();

        Swal.fire({
            title: 'Confirm Defect?',
            html: `Record <strong>${qty}</strong> defect for <strong>${cctCode}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Submit',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (result.isConfirmed) {
                $('#btn-submit').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

                $.ajax({
                    url: "{{ route('defect.cutting.store') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        master_circuit_id: $('#master_circuit_id').val(),
                        conveyor_id: $('#conveyor_id').val(),
                        defect_date: $('#defect_date').val(),
                        shift: $('#shift').val(),
                        qty_defect: $('#qty_defect').val(),
                        reason: $('#reason').val()
                    },
                    success: function (res) {
                        $('#defectModal').modal('hide');
                        table.ajax.reload(null, false);
                        Swal.fire('Success', res.message, 'success');
                    },
                    error: function (xhr) {
                        var msg = xhr.responseJSON?.message || 'Failed to record defect';
                        Swal.fire('Error', msg, 'error');
                    },
                    complete: function () {
                        $('#btn-submit').prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Submit Defect');
                    }
                });
            }
        });
    });

    // ===== Import STO (drag & drop + preview + chunked commit) =====
    (function () {
        var CHUNK_SIZE = 20;
        var selectedFile = null;
        var previewData = null; // { matched: [...], not_found_codes: [...] }
        var importing = false;

        function escapeHtml(str) {
            return $('<div>').text(str == null ? '' : String(str)).html();
        }

        function formatBytes(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        }

        function clearErrors() {
            $('#importStoModal .text-danger').text('');
        }

        function goToStep(step) {
            $('#sto-step-1, #sto-step-2, #sto-step-3').addClass('d-none');
            $('#sto-step-' + step).removeClass('d-none');

            [1, 2, 3].forEach(function (i) {
                var el = $('#sto-step-indicator-' + i);
                el.removeClass('active done');
                if (i < step) el.addClass('done');
                if (i === step) el.addClass('active');
            });

            $('#btn-sto-preview, #btn-sto-back, #btn-sto-confirm').addClass('d-none');
            $('#btn-sto-cancel').prop('disabled', false);
            $('#btn-sto-close-x').prop('disabled', false);

            if (step === 1) {
                $('#btn-sto-preview').removeClass('d-none');
            } else if (step === 2) {
                $('#btn-sto-back, #btn-sto-confirm').removeClass('d-none');
            }
            // step 3 footer is controlled by the import runner itself
        }

        function resetDropzone() {
            selectedFile = null;
            $('#import_sto_file').val('');
            $('.sto-dropzone-empty').removeClass('d-none');
            $('.sto-dropzone-file').addClass('d-none');
            $('#sto-dropzone').removeClass('dragover');
        }

        function setFile(file) {
            if (!file) return;

            var validExt = /\.(xlsx|xls)$/i.test(file.name);
            if (!validExt) {
                $('#import_sto_file_error').text('Only .xlsx or .xls files are accepted.');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                $('#import_sto_file_error').text('File exceeds the 10MB limit.');
                return;
            }

            $('#import_sto_file_error').text('');
            selectedFile = file;
            $('.sto-dropzone-empty').addClass('d-none');
            $('.sto-dropzone-file').removeClass('d-none');
            $('.sto-file-name').text(file.name);
            $('.sto-file-size').text(formatBytes(file.size));
            updatePreviewButtonState();
        }

        function updatePreviewButtonState() {
            var conveyorId = $('#import_sto_conveyor_id').val();
            $('#btn-sto-preview').prop('disabled', !(conveyorId && selectedFile));
        }

        // Open modal: reset everything to step 1
        $('#btn-import-sto').on('click', function () {
            clearErrors();
            resetDropzone();
            previewData = null;
            importing = false;
            $('#import_sto_conveyor_id').val('').trigger('change');
            $('#import_sto_date').val('{{ date("Y-m-d") }}');
            $('#import_sto_shift').empty().append('<option value="">- Select Shift -</option>');
            goToStep(1);
            $('#importStoModal').modal('show');
        });

        // Prevent accidental close mid-import
        $('#importStoModal').on('hide.bs.modal', function (e) {
            if (importing) {
                e.preventDefault();
            }
        });

        $('#import_sto_conveyor_id').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#importStoModal'),
            placeholder: '- Choose Conveyor -'
        }).on('change', updatePreviewButtonState);

        // Dropzone interactions
        var dropzone = $('#sto-dropzone');
        dropzone.on('click', function (e) {
            // Ignore the remove button, and ignore the bubbled click that the
            // trigger('click') below produces on the input itself — without
            // this guard the two handlers call each other infinitely.
            if (e.target.id === 'import_sto_file' || $(e.target).closest('#sto-file-remove').length) {
                return;
            }
            $('#import_sto_file').trigger('click');
        });
        $('#import_sto_file').on('change', function () {
            if (this.files && this.files[0]) setFile(this.files[0]);
        });
        dropzone.on('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.addClass('dragover');
        });
        dropzone.on('dragleave drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.removeClass('dragover');
        });
        dropzone.on('drop', function (e) {
            var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
            if (files && files[0]) setFile(files[0]);
        });
        $('#sto-file-remove').on('click', function (e) {
            e.stopPropagation();
            resetDropzone();
            updatePreviewButtonState();
        });

        // Build shift options from the selected conveyor's shift_qty
        function buildShiftOptions() {
            var shiftQty = parseInt($('#import_sto_conveyor_id').find(':selected').data('shift-qty')) || 1;
            var shiftSelect = $('#import_sto_shift');
            shiftSelect.empty().append('<option value="">- Select Shift -</option>');
            for (var i = 1; i <= shiftQty; i++) {
                shiftSelect.append('<option value="' + i + '">Shift ' + i + '</option>');
            }
        }

        // ---- Step 1 -> Step 2: Preview ----
        $('#btn-sto-preview').on('click', function () {
            clearErrors();

            var formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('conveyor_id', $('#import_sto_conveyor_id').val());
            formData.append('file', selectedFile);

            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Reading file...');

            $.ajax({
                url: "{{ route('defect.cutting.import-sto.preview') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    previewData = res.data;

                    $('#sto-preview-matched').text(previewData.matched_count.toLocaleString());
                    $('#sto-preview-notfound').text(previewData.not_found_count.toLocaleString());
                    $('#sto-preview-qty').text(previewData.total_qty.toLocaleString());
                    $('#sto-notfound-badge').text(previewData.not_found_count);

                    var matchedBody = $('#sto-matched-body').empty();
                    if (previewData.matched.length > 0) {
                        previewData.matched.forEach(function (row) {
                            matchedBody.append(
                                '<tr><td>' + escapeHtml(row.cct_code) + '</td>' +
                                '<td>' + escapeHtml(row.cct_no) + '</td>' +
                                '<td class="text-end">' + row.qty.toLocaleString() + '</td></tr>'
                            );
                        });
                    } else {
                        matchedBody.append('<tr><td colspan="3" class="text-center text-muted py-3">No matching circuits found in this file.</td></tr>');
                    }

                    var notFoundList = $('#sto-notfound-list').empty();
                    if (previewData.not_found_codes.length > 0) {
                        previewData.not_found_codes.forEach(function (code) {
                            notFoundList.append('<span class="badge bg-danger-subtle text-danger border border-danger">' + escapeHtml(code) + '</span>');
                        });
                    } else {
                        notFoundList.append('<span class="text-muted small">None &mdash; every CCT Code matched.</span>');
                    }

                    buildShiftOptions();
                    $('#btn-sto-confirm').prop('disabled', previewData.matched_count === 0);
                    goToStep(2);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function (key, value) {
                            $('#import_sto_' + key + '_error').text(Array.isArray(value) ? value[0] : value);
                        });
                    } else {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to read the file';
                        Swal.fire('Error', msg, 'error');
                    }
                },
                complete: function () {
                    btn.prop('disabled', false).html('<i class="fa-solid fa-eye me-1"></i> Preview Data');
                    updatePreviewButtonState();
                }
            });
        });

        // ---- Step 2 -> Step 1: Back ----
        $('#btn-sto-back').on('click', function () {
            goToStep(1);
        });

        // ---- Step 2 -> Step 3: Confirm & run chunked import ----
        $('#btn-sto-confirm').on('click', function () {
            clearErrors();

            var date = $('#import_sto_date').val();
            var shift = $('#import_sto_shift').val();

            if (!date) { $('#import_sto_defect_date_error').text('Date is required.'); return; }
            if (!shift) { $('#import_sto_shift_error').text('Shift is required.'); return; }

            var conveyorId = $('#import_sto_conveyor_id').val();
            var items = previewData.matched;
            var total = items.length;
            var chunks = [];
            for (var i = 0; i < total; i += CHUNK_SIZE) {
                chunks.push(items.slice(i, i + CHUNK_SIZE));
            }

            var log = $('#sto-progress-log').empty();
            var bar = $('#sto-progress-bar');
            var status = $('#sto-progress-status');
            var icon = $('#sto-progress-icon');
            var title = $('#sto-progress-title');

            function appendLog(html, cls) {
                log.append('<div class="log-line' + (cls ? ' ' + cls : '') + '">' + html + '</div>');
                log.scrollTop(log[0].scrollHeight);
            }

            function setProgress(processed) {
                var pct = total > 0 ? Math.round((processed / total) * 100) : 100;
                bar.css('width', pct + '%').text(pct + '%');
            }

            importing = true;
            goToStep(3);
            $('#btn-sto-cancel, #btn-sto-close-x').prop('disabled', true);
            icon.html('<i class="fa-solid fa-arrows-rotate fa-spin text-danger"></i>');
            title.text('Importing data...');
            status.text('Preparing ' + total + ' circuit(s) in ' + chunks.length + ' batch(es)...');
            setProgress(0);

            var processed = 0, successCount = 0, failedCount = 0, allErrors = [];

            function runChunk(index) {
                if (index >= chunks.length) {
                    // Finished
                    importing = false;
                    $('#btn-sto-cancel, #btn-sto-close-x').prop('disabled', false);
                    $('#btn-sto-cancel').text('Close');

                    if (failedCount === 0 && previewData.not_found_count === 0) {
                        icon.html('<i class="fa-solid fa-circle-check text-success"></i>');
                        title.text('Import completed successfully');
                    } else {
                        icon.html('<i class="fa-solid fa-triangle-exclamation text-warning"></i>');
                        title.text('Import completed with warnings');
                    }
                    status.text(successCount + ' recorded, ' + failedCount + ' failed, ' + previewData.not_found_count + ' not found.');

                    previewData.not_found_codes.forEach(function (code) {
                        appendLog('<i class="fa-solid fa-circle-minus me-1"></i> CCT Code "' + escapeHtml(code) + '" not found &mdash; skipped.', 'log-warn');
                    });

                    var summary = successCount + ' of ' + total + ' matched circuit(s) recorded as defect';
                    summary += (failedCount + previewData.not_found_count) > 0
                        ? ', ' + (failedCount + previewData.not_found_count) + ' failed/skipped.'
                        : '.';

                    setTimeout(function () {
                        $('#importStoModal').modal('hide');
                        table.ajax.reload(null, false);

                        if (failedCount === 0 && previewData.not_found_count === 0) {
                            Swal.fire('Success', summary, 'success');
                        } else {
                            var html = summary;
                            if (allErrors.length > 0) {
                                html += '<br><br><strong>Details:</strong><br>' + allErrors.slice(0, 10).map(escapeHtml).join('<br>');
                                if (allErrors.length > 10) html += '<br>... and ' + (allErrors.length - 10) + ' more';
                            }
                            Swal.fire({ title: 'Import Completed with Warnings', html: html, icon: 'warning' });
                        }
                    }, 500);
                    return;
                }

                var chunk = chunks[index];
                status.text('Processing batch ' + (index + 1) + ' of ' + chunks.length + ' (' + processed + '/' + total + ' circuits)...');

                $.ajax({
                    url: "{{ route('defect.cutting.import-sto.commit') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        conveyor_id: conveyorId,
                        defect_date: date,
                        shift: shift,
                        items: chunk
                    },
                    success: function (res) {
                        processed += chunk.length;
                        successCount += res.success_count;
                        failedCount += res.failed_count;
                        if (res.errors && res.errors.length) {
                            allErrors = allErrors.concat(res.errors);
                            res.errors.forEach(function (msg) {
                                appendLog('<i class="fa-solid fa-circle-exclamation me-1"></i> ' + escapeHtml(msg), 'log-warn');
                            });
                        }
                        appendLog('<i class="fa-solid fa-circle-check me-1"></i> Batch ' + (index + 1) + '/' + chunks.length + ': ' + res.success_count + ' recorded' + (res.failed_count ? ', ' + res.failed_count + ' failed' : '') + '.', 'log-ok');
                        setProgress(processed);
                        runChunk(index + 1);
                    },
                    error: function (xhr) {
                        processed += chunk.length;
                        failedCount += chunk.length;
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Batch ' + (index + 1) + ' failed unexpectedly.';
                        allErrors.push(msg);
                        appendLog('<i class="fa-solid fa-circle-xmark me-1"></i> Batch ' + (index + 1) + '/' + chunks.length + ' failed: ' + escapeHtml(msg), 'log-warn');
                        setProgress(processed);
                        runChunk(index + 1);
                    }
                });
            }

            runChunk(0);
        });
    })();
});
</script>
@endsection
