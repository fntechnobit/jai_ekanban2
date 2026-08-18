@extends('layouts.master')

@section('title', 'Assy Scheduler')

@section('breadcrumb')
    <x-page-header menu-code="assy_scheduler" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fa-solid fa-list"></i> Assy Schedule List</h5>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Filters -->
                        <input type="text" class="form-control form-control-sm" id="filter_dates" readonly
                               placeholder="Select date range" style="width: 220px;">
                        <select class="form-select form-select-sm select2" id="filter_conveyor_id" style="width: 180px;">
                            <option value="">- All Conveyor -</option>
                            @foreach($conveyors as $conveyor)
                                <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-secondary btn-sm" id="btn-reset" title="Reset Filter">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </button>
                        @if(auth()->user()->hasMenuPermission('assy_scheduler', 'can_create'))
                            <button type="button" class="btn btn-primary btn-sm" id="btn-generate">
                                <i class="fa-solid fa-gear"></i> Generate
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">

                <div class="table-responsive">
                    <table id="assy-schedule-table" class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th width="5%">No.</th>
                                <th width="10%">Conveyor</th>
                                <th width="12%">Times</th>
                                <th width="8%">Shift</th>
                                <th width="8%">Cut Off</th>
                                <th width="45%">Assy</th>
                                <th width="8%">Qty.</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('schedule.assy_scheduler.generate_modal')
@endsection

@section('script')
    <script>
        $(function () {
            // Initialize Select2
            $('#filter_conveyor_id').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: '- All Conveyor -'
            });

            // Initialize date range picker
            var startDate = moment();
            var endDate = moment().add(3, 'days');

            $('#filter_dates').daterangepicker({
                startDate: startDate,
                endDate: endDate,
                locale: {
                    format: 'DD-MM-YYYY'
                }
            });

            // DataTable
            var table = $('#assy-schedule-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('schedule.assy-scheduler.assy-schedule-list') }}",
                    data: function(d) {
                        var dates = $('#filter_dates').data('daterangepicker');
                        d.start_date = dates.startDate.format('YYYY-MM-DD');
                        d.end_date = dates.endDate.format('YYYY-MM-DD');
                        d.conveyor_id = $('#filter_conveyor_id').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'conveyor_name', name: 'conveyor.conveyor', orderable: false },
                    { data: 'schedule', name: 'schedule' , orderable: false},
                    { data: 'shift', name: 'shift', className: 'text-center' , orderable: false},
                    { data: 'cutoff', name: 'cutoff', className: 'text-center' , orderable: false},
                    { data: 'assy', name: 'assy' , orderable: false},
                    { data: 'qty', name: 'qty', className: 'text-center' , orderable: false}
                ],
                ordering: false,
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]
            });

            // Auto reload when filter changes
            $('#filter_conveyor_id').on('change', function() {
                table.ajax.reload();
            });

            // Auto reload when date range changes
            $('#filter_dates').on('apply.daterangepicker', function() {
                table.ajax.reload();
            });

            // Reset button
            $('#btn-reset').click(function() {
                $('#filter_conveyor_id').val('').trigger('change');
                $('#filter_dates').data('daterangepicker').setStartDate(moment());
                $('#filter_dates').data('daterangepicker').setEndDate(moment().add(3, 'days'));
                table.ajax.reload();
            });

            // Generate button → open modal
            $('#btn-generate').click(function() {
                resetGenerateModal();
                $('#generateModal').modal('show');
            });

            // btn-modal-close & btn-cancel-generate: only closeable when not progressing
            $('#btn-modal-close, #btn-cancel-generate').on('click', function() {
                $('#generateModal').modal('hide');
            });

            // After generate done → close & reload
            $('#btn-close-after-generate').on('click', function() {
                $('#generateModal').modal('hide');
                table.ajax.reload();
            });

            // Back to form
            $('#btn-back-to-form').on('click', function() {
                $('#generate-progress-panel').hide();
                $('#generate-form-panel').show();
                $('#btn-modal-close').show();
            });

            // Initialize Select2 in modal
            $('#generate_conveyor_id').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#generateModal'),
                allowClear: true,
                placeholder: '- All Conveyor -'
            });

            // Set default date range for generate modal
            var genStartDate = moment();
            var genEndDate = moment().add(10, 'days');

            $('#generate_dates').daterangepicker({
                startDate: genStartDate,
                endDate: genEndDate,
                locale: {
                    format: 'DD-MM-YYYY'
                }
            });

            // Generate form submission
            $('#generateForm').submit(function(e) {
                e.preventDefault();

                var dates     = $('#generate_dates').data('daterangepicker');
                var startDate = dates.startDate.format('YYYY-MM-DD');
                var endDate   = dates.endDate.format('YYYY-MM-DD');
                var conveyorId = $('#generate_conveyor_id').val();

                // Switch to progress panel
                var rangeLabel = dates.startDate.format('DD-MM-YYYY') + ' s/d ' + dates.endDate.format('DD-MM-YYYY');
                $('#progress-date-range').text('Rentang tanggal: ' + rangeLabel);
                $('#generate-form-panel').hide();
                $('#generate-progress-panel').show();
                $('#btn-modal-close').hide();

                // Reset steps ke loading state
                resetProgressSteps();

                $.ajax({
                    url: "{{ route('schedule.assy-scheduler.generate') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        start_date: startDate,
                        end_date: endDate,
                        conveyor_id: conveyorId
                    },
                    success: function(response) {
                        renderProgressResult(response, false);
                    },
                    error: function(xhr) {
                        var res = (xhr.responseJSON) ? xhr.responseJSON : {
                            success: false,
                            step_failed: 'unknown',
                            message: 'Terjadi kesalahan saat menghubungi server.',
                            data: null
                        };
                        renderProgressResult(res, true);
                    }
                });
            });

            // ─── Helpers ──────────────────────────────────────────────────────

            function resetGenerateModal() {
                $('#generate-form-panel').show();
                $('#generate-progress-panel').hide();
                $('#generate-result-banner').hide().html('');
                $('#btn-close-after-generate').hide();
                $('#btn-back-to-form').hide();
                $('#btn-modal-close').show();
                resetProgressSteps();
            }

            function resetProgressSteps() {
                // Step 1 — loading
                $('#step1-row').css('border-left-color', '#6c757d').css('opacity', '1');
                $('#step1-icon').html('<span class="spinner-border spinner-border-sm text-secondary" role="status"></span>');
                $('#step1-detail').text('Mengambil data terbaru dari API SIREP...');

                // Step 2 — waiting
                $('#step2-row').css('border-left-color', '#6c757d').css('opacity', '0.45');
                $('#step2-icon').html('<i class="fa-solid fa-circle text-secondary f-s-14"></i>');
                $('#step2-detail').text('Menunggu proses step 1...');

                $('#generate-result-banner').hide().html('');
                $('#btn-close-after-generate').hide();
                $('#btn-back-to-form').hide();
            }

            function renderProgressResult(response, isHttpError) {
                var stepFailed  = response.step_failed || (response.success ? null : 'unknown');
                var syncDetail  = (response.data && response.data.sync_detail) ? response.data.sync_detail : null;
                var generated   = (response.data) ? (response.data.generated || 0) : 0;
                var msg         = response.message || '';

                // ── Update Step 1 ──
                if (stepFailed === 'sync_listing' || stepFailed === 'unknown') {
                    // Step 1 FAILED
                    setStepFail(1, syncDetail
                        ? buildSyncText(syncDetail)
                        : 'Gagal terhubung ke API SIREP. Proses dihentikan, tidak ada sumber cadangan yang dicoba.');
                    // Step 2 SKIPPED
                    setStepSkipped(2, 'Dilewati karena step 1 gagal.');
                } else {
                    // Step 1 SUCCESS
                    setStepSuccess(1, syncDetail
                        ? buildSyncText(syncDetail)
                        : 'Data listing berhasil di-clone ke listing_stage.');

                    // ── Update Step 2 ──
                    if (stepFailed === 'generate') {
                        setStepFail(2, msg);
                    } else {
                        setStepSuccess(2, generated > 0
                            ? generated + ' schedule berhasil dibuat.'
                            : 'Semua schedule sudah up-to-date, tidak ada data baru.');
                    }
                }

                // ── Result Banner ──
                var bannerType = response.success ? 'success' : 'danger';
                var bannerIcon = response.success ? 'fa-circle-check' : 'fa-circle-xmark';
                $('#generate-result-banner')
                    .removeClass('alert-success alert-danger alert-warning')
                    .addClass('alert-' + bannerType)
                    .html('<i class="fa-solid ' + bannerIcon + ' me-2"></i>' + msg)
                    .fadeIn(300);

                // Show action buttons
                $('#btn-close-after-generate').show();
                if (!response.success) {
                    $('#btn-back-to-form').show();
                }
                $('#btn-modal-close').show();
            }

            function setStepSuccess(num, detail) {
                $('#step' + num + '-row').css('border-left-color', '#198754').css('opacity', '1');
                $('#step' + num + '-icon').html('<i class="fa-solid fa-circle-check text-success f-s-18"></i>');
                $('#step' + num + '-detail').text(detail);
            }

            function setStepFail(num, detail) {
                $('#step' + num + '-row').css('border-left-color', '#dc3545').css('opacity', '1');
                $('#step' + num + '-icon').html('<i class="fa-solid fa-circle-xmark text-danger f-s-18"></i>');
                $('#step' + num + '-detail').text(detail);
            }

            function setStepSkipped(num, detail) {
                $('#step' + num + '-row').css('border-left-color', '#adb5bd').css('opacity', '0.6');
                $('#step' + num + '-icon').html('<i class="fa-solid fa-circle-minus text-secondary f-s-18"></i>');
                $('#step' + num + '-detail').text(detail);
            }

            function buildSyncText(sd) {
                return 'Clone selesai — Total: ' + sd.total_records + ' | Disync: ' + sd.synced + ' | Dilewati: ' + sd.skipped;
            }
        });
    </script>
@endsection
