{{--
    Import STO modal shared by Add Cutting and Add Shikake.

    $conveyors  conveyor options
    $config     previewUrl, commitUrl, tableSelector (datatable reloaded after import),
                title, help (HTML), noun (in sentences, e.g. 'circuit(s)'), nounLabel
                (stat card, e.g. 'Circuits'), codeLabel,
                idKey + codeKey (fields sent per commit item, besides qty),
                columns ([[key, label], ...] shown in the preview table)
--}}
<div class="modal fade" id="importStoModal" tabindex="-1" aria-labelledby="importStoModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="importStoModalLabel">
                    <i class="fa-solid fa-file-import me-2"></i> {{ $config['title'] }}
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
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        {!! $config['help'] !!}
                    </div>

                    <div class="mb-3">
                        <label for="import_sto_conveyor_id" class="form-label">Conveyor <span class="text-danger">*</span></label>
                        <select class="form-select select2" id="import_sto_conveyor_id" style="width: 100%;" required>
                            <option value="">- Choose Conveyor -</option>
                            @foreach($conveyors as $conveyor)
                                <option value="{{ $conveyor->id }}" data-shift-qty="{{ \App\Services\Schedule\ShiftCapacityCalculator::MAX_SHIFT }}">{{ $conveyor->conveyor }}</option>
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
                                    <span class="text-success text-decoration-underline">browse</span>
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
                                    <div class="small text-muted">Matched {{ $config['nounLabel'] }}</div>
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

                    <div class="alert alert-warning py-2 small mb-3 d-none" id="sto-new-info">
                        <i class="fa-solid fa-circle-plus me-1"></i>
                        <strong id="sto-new-count">0</strong> {{ $config['noun'] }} have no balance yet (never generated / verified).
                        Their balance will be created starting from 0.
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
                                    <tr>
                                        @foreach($config['columns'] as [$key, $label])
                                            <th>{{ $label }}</th>
                                        @endforeach
                                        <th class="text-end">Balance</th>
                                        <th class="text-end">Qty STO</th>
                                    </tr>
                                </thead>
                                <tbody id="sto-matched-body"></tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade p-2" id="sto-tab-notfound">
                            <div class="small text-muted mb-2">These {{ $config['codeLabel'] }} codes were not found for the selected conveyor and will be skipped:</div>
                            <div id="sto-notfound-list" class="d-flex flex-wrap gap-1"></div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <label for="import_sto_date" class="form-label">Addition Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="import_sto_date"
                                   value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                            <small class="form-text text-danger" id="import_sto_addition_date_error"></small>
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
                        <i class="fa-solid fa-arrows-rotate fa-spin text-success"></i>
                    </div>
                    <h6 id="sto-progress-title">Importing data...</h6>
                    <div class="progress mb-2" style="height: 24px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
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
                <button type="button" class="btn btn-success" id="btn-sto-preview" disabled>
                    <i class="fa-solid fa-eye me-1"></i> Preview Data
                </button>
                <button type="button" class="btn btn-success d-none" id="btn-sto-confirm" disabled>
                    <i class="fa-solid fa-check me-1"></i> Confirm Import
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .sto-steps { user-select: none; }
    .sto-step { display: flex; flex-direction: column; align-items: center; gap: 4px; flex: 0 0 auto; opacity: 0.45; transition: opacity .2s ease; }
    .sto-step.active, .sto-step.done { opacity: 1; }
    .sto-step-circle {
        width: 28px; height: 28px; border-radius: 50%; background: #e9ecef; color: #6c757d;
        display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: .8rem;
        transition: background-color .2s ease, color .2s ease;
    }
    .sto-step.active .sto-step-circle { background: #198754; color: #fff; }
    .sto-step.done .sto-step-circle { background: #198754; color: #fff; }
    .sto-step.done .sto-step-circle::before { content: '\2713'; }
    .sto-step-label { font-size: .72rem; color: #6c757d; white-space: nowrap; }
    .sto-step.active .sto-step-label { color: #198754; font-weight: 600; }
    .sto-step-line { flex: 1 1 auto; height: 2px; background: #e9ecef; margin: 14px 6px 0; }

    .sto-dropzone {
        border: 2px dashed #ced4da; border-radius: .5rem; padding: 28px 16px;
        display: flex; align-items: center; justify-content: center; flex-direction: column;
        cursor: pointer; transition: border-color .15s ease, background-color .15s ease; background: #fafafa;
    }
    .sto-dropzone:hover { border-color: #198754; background: #f4fbf7; }
    .sto-dropzone.dragover { border-color: #198754; background: #eaf7f0; }
    .sto-dropzone-icon { font-size: 2rem; color: #adb5bd; margin-bottom: 6px; }
    .sto-dropzone.dragover .sto-dropzone-icon { color: #198754; }
    .sto-dropzone-file { display: flex; align-items: center; width: 100%; }

    .sto-progress-icon { font-size: 2.2rem; }
    .sto-progress-log { max-height: 160px; overflow-y: auto; font-size: .8rem; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    .sto-progress-log .log-line { padding: 1px 0; }
    .sto-progress-log .log-ok { color: #198754; }
    .sto-progress-log .log-warn { color: #b02a37; }
</style>
@endpush

@push('scripts')
<script>
// ===== Import STO (drag & drop + preview + chunked commit) =====
$(function () {
    var cfg = @json($config);
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

    // Build shift options from the fixed shift limit (shift is no longer per conveyor)
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
            url: cfg.previewUrl,
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
                $('#sto-new-count').text(previewData.new_count.toLocaleString());
                $('#sto-new-info').toggleClass('d-none', previewData.new_count === 0);

                var matchedBody = $('#sto-matched-body').empty();
                if (previewData.matched.length > 0) {
                    previewData.matched.forEach(function (row) {
                        var cells = cfg.columns.map(function (col) {
                            return '<td>' + escapeHtml(row[col[0]]) + '</td>';
                        }).join('');
                        var balance = row.is_new
                            ? '<span class="badge bg-warning text-dark">New</span>'
                            : row.balance.toLocaleString();
                        matchedBody.append(
                            '<tr>' + cells +
                            '<td class="text-end">' + balance + '</td>' +
                            '<td class="text-end">' + row.qty.toLocaleString() + '</td></tr>'
                        );
                    });
                } else {
                    matchedBody.append('<tr><td colspan="' + (cfg.columns.length + 2) + '" class="text-center text-muted py-3">No matching ' + escapeHtml(cfg.noun) + ' found in this file.</td></tr>');
                }

                var notFoundList = $('#sto-notfound-list').empty();
                if (previewData.not_found_codes.length > 0) {
                    previewData.not_found_codes.forEach(function (code) {
                        notFoundList.append('<span class="badge bg-danger-subtle text-danger border border-danger">' + escapeHtml(code) + '</span>');
                    });
                } else {
                    notFoundList.append('<span class="text-muted small">None &mdash; every ' + escapeHtml(cfg.codeLabel) + ' matched.</span>');
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

        if (!date) { $('#import_sto_addition_date_error').text('Date is required.'); return; }
        if (!shift) { $('#import_sto_shift_error').text('Shift is required.'); return; }

        var conveyorId = $('#import_sto_conveyor_id').val();
        var items = previewData.matched.map(function (row) {
            var item = { qty: row.qty };
            item[cfg.idKey] = row[cfg.idKey];
            item[cfg.codeKey] = row[cfg.codeKey];
            return item;
        });
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
        icon.html('<i class="fa-solid fa-arrows-rotate fa-spin text-success"></i>');
        title.text('Importing data...');
        status.text('Preparing ' + total + ' ' + cfg.noun + ' in ' + chunks.length + ' batch(es)...');
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
                status.text(successCount + ' added, ' + failedCount + ' failed, ' + previewData.not_found_count + ' not found.');

                previewData.not_found_codes.forEach(function (code) {
                    appendLog('<i class="fa-solid fa-circle-minus me-1"></i> ' + escapeHtml(cfg.codeLabel) + ' "' + escapeHtml(code) + '" not found &mdash; skipped.', 'log-warn');
                });

                var summary = successCount + ' of ' + total + ' matched ' + cfg.noun + ' added';
                summary += (failedCount + previewData.not_found_count) > 0
                    ? ', ' + (failedCount + previewData.not_found_count) + ' failed/skipped.'
                    : '.';

                setTimeout(function () {
                    $('#importStoModal').modal('hide');
                    $(cfg.tableSelector).DataTable().ajax.reload(null, false);

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
            status.text('Processing batch ' + (index + 1) + ' of ' + chunks.length + ' (' + processed + '/' + total + ' ' + cfg.noun + ')...');

            $.ajax({
                url: cfg.commitUrl,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    conveyor_id: conveyorId,
                    addition_date: date,
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
                    appendLog('<i class="fa-solid fa-circle-check me-1"></i> Batch ' + (index + 1) + '/' + chunks.length + ': ' + res.success_count + ' added' + (res.failed_count ? ', ' + res.failed_count + ' failed' : '') + '.', 'log-ok');
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
});
</script>
@endpush
