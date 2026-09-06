@extends('layouts.master')

@section('title', 'Schedule Verification')

@section('breadcrumb')
    <x-page-header menu-code="schedule_verification" />
@endsection

@section('css')
<link rel="stylesheet" href="{{ url('css/schedule-verification.css') }}?v={{ time() }}">
@endsection

@section('content')
<div class="container-fluid">

    {{-- Dynamic banner: auto-sync / manual generate result --}}
    <div id="assy-generate-banner" style="display:none;"></div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="card-title mb-0"><i class="fa-solid fa-list me-2"></i> Schedule List</h5>
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
                    <select class="form-select form-select-sm select2" id="filter_status" style="width: 160px;">
                        <option value="with_data" selected>All w/ Data</option>
                        <option value="pending">Pending</option>
                        <option value="verified">Verified</option>
                        <option value="no_data">All w/ No Data</option>
                    </select>
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-reset" title="Reset Filter">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                    @if(auth()->user()->hasMenuPermission('schedule_verification', 'can_create'))
                        <button type="button" class="btn btn-primary btn-sm" id="btn-generate">
                            <i class="fa-solid fa-gear"></i> Generate
                        </button>
                    @endif
                    <button type="button" class="btn btn-danger btn-sm" id="btn-reset-balance" title="Reset All Kanban Balance">
                        <i class="fa-solid fa-triangle-exclamation"></i> Reset Balance
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">

            <div class="table-responsive">
                <table id="schedule-verification-table" class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th width="3%">Num.</th>
                            <th width="10%">Conveyor</th>
                            <th width="8%">Dates</th>
                            <th width="5%">Shift</th>
                            <th width="5%">Cap</th>
                            <th width="5%">OT</th>
                            <th width="9%">API Time</th>
                            <th width="7%">Listing</th>
                            <th width="36%">Assy</th>
                            <th width="6%">Status</th>
                            <th width="6%" class="text-end">#</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Verification Modal -->
<div class="modal fade" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="verificationModalLabel">
                    <i class="fa-solid fa-circle-check me-2"></i> Assy Schedule Verification
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Header Info -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="info-header d-flex flex-wrap gap-2">
                            <span class="badge bg-primary p-2" id="modal-conveyor"></span>
                            <span class="badge bg-info p-2" id="modal-date"></span>
                            <span class="badge bg-warning p-2" id="modal-shift"></span>
                            <span class="badge bg-success p-2" id="modal-capacity"></span>
                            <span class="badge bg-secondary p-2" id="modal-assy-count"></span>
                            <span class="badge bg-dark p-2" id="modal-total-listing"></span>
                            <span class="badge p-2" id="modal-overtime"></span>
                            </div>

                            <div class="sirep-panel" id="modal-sirep-panel" data-state="loading">
                                <div class="sirep-panel-head">
                                    <span class="sirep-head-icon" id="sirep-head-icon"><i class="fa-solid fa-satellite-dish"></i></span>
                                    <div class="sirep-head-text">
                                        <div class="sirep-head-title" id="sirep-head-title">Memuat status SIREP&hellip;</div>
                                        <div class="sirep-head-desc-row">
                                            <div class="sirep-head-desc" id="sirep-head-desc">-</div>
                                            <div class="sirep-bar-caption" id="sirep-bar-caption">-</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="sirep-panel-bar" id="sirep-bar-wrap">
                                    <div class="sirep-bar-track">
                                        <div class="sirep-bar-fill" id="sirep-bar-fill"></div>
                                        <div class="sirep-bar-over" id="sirep-bar-over"></div>
                                    </div>
                                </div>

                                <div class="sirep-panel-stats">
                                    <div class="sirep-stat">
                                        <i class="fa-solid fa-gauge-high sirep-stat-icon"></i>
                                        <div>
                                            <div class="sirep-label">Kapasitas / Shift</div>
                                            <div class="sirep-value" id="sirep-capacity">-</div>
                                            <div class="sirep-note" id="sirep-capacity-synced">-</div>
                                        </div>
                                    </div>
                                    <div class="sirep-stat">
                                        <i class="fa-solid fa-business-time sirep-stat-icon"></i>
                                        <div>
                                            <div class="sirep-label">Status Overtime</div>
                                            <div class="sirep-value" id="sirep-overtime">-</div>
                                            <div class="sirep-note" id="sirep-overtime-note">-</div>
                                        </div>
                                    </div>
                                    <div class="sirep-stat">
                                        <i class="fa-solid fa-cloud-arrow-down sirep-stat-icon"></i>
                                        <div>
                                            <div class="sirep-label">Update Data Listing</div>
                                            <div class="sirep-value" id="sirep-listing-synced">-</div>
                                            <div class="sirep-note" id="sirep-listing-source">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Shifts Container with Cut-offs -->
                        <div class="col-md-6">
                            <div id="shifts-container">
                                <!-- Shifts and cut-offs will be loaded dynamically -->
                            </div>
                        </div>
                        
                        <!-- Available Assy Data -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">Generated Assy Data</h6>
                                    <div class="date-filter-controls d-flex mt-2">
                                        <select id="available-date" class="form-select form-select-sm" style="width: 160px;">
                                            <option value="">-- Pilih Tanggal --</option>
                                        </select>
                                        <select id="available-shift" class="form-select form-select-sm ms-2" style="width: 140px;">
                                            <option value="all">Semua Shift</option>
                                        </select>
                                        <button type="button" id="btn-refresh-available" class="btn btn-sm btn-light ms-2">
                                            <i class="fa-solid fa-arrows-rotate"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <!-- Info & Loading -->
                                    <div id="available-info" class="text-muted small mb-2">
                                        <span>Pilih tanggal untuk memuat data sumber</span>
                                    </div>
                                    
                                    <div id="available-assy-container">
                                        <!-- Available assy items grouped by cut-off will be loaded dynamically -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-sm" id="btn-verify-schedule" style="display:none;">
                        <i class="fa-solid fa-check"></i> Verify
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
<!-- Reset Balance Confirmation Modal -->
<div class="modal fade" id="resetBalanceModal" tabindex="-1" aria-labelledby="resetBalanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="resetBalanceModalLabel">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> PERINGATAN: Reset Kanban Balance
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger border-danger mb-3">
                    <h6 class="alert-heading fw-bold"><i class="fa-solid fa-skull-crossbones me-1"></i> TINDAKAN INI TIDAK DAPAT DIBATALKAN!</h6>
                    <hr>
                    <p class="mb-1">Anda akan <strong>mereset SEMUA sisa balance kanban</strong> (circuit &amp; shikake) menjadi <strong>0 (nol)</strong>.</p>
                    <p class="mb-1">Ini berarti:</p>
                    <ul class="mb-1">
                        <li>Semua <strong>sisa carry-over</strong> akan hilang</li>
                        <li>Semua <strong>nomor urut</strong> akan kembali ke 0</li>
                        <li>Perhitungan issue pada verifikasi berikutnya akan <strong>dimulai dari awal</strong></li>
                    </ul>
                    <p class="mb-0 fw-bold text-danger">Gunakan fitur ini HANYA untuk keperluan trial/testing!</p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Pilih Conveyor:</label>
                    <select class="form-select form-select-sm" id="reset-conveyor-select">
                        <option value="">Semua Conveyor</option>
                        @foreach($conveyors as $conveyor)
                            <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Mode Reset:</label>
                    <div class="form-check">
                        <input class="form-check-input reset-mode-radio" type="radio" name="reset_mode" id="reset-mode-full" value="full" checked>
                        <label class="form-check-label" for="reset-mode-full">
                            <strong>Reset Penuh</strong> &mdash; nol-kan saldo, <strong>hapus semua kanban</strong>, dan <strong>unverify</strong> semua jadwal.
                            <span class="d-block form-text">Jadwal yang sudah terverifikasi harus diverifikasi ulang.</span>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input reset-mode-radio" type="radio" name="reset_mode" id="reset-mode-balance" value="balance_only">
                        <label class="form-check-label" for="reset-mode-balance">
                            <strong>Saldo Saja</strong> &mdash; hanya nol-kan saldo (sisa &amp; nomor urut). Kanban &amp; status verifikasi <strong>tidak diubah</strong>.
                            <span class="d-block form-text text-warning">Hati-hati: jika masih ada kanban ter-generate, generate berikutnya dapat menghasilkan nomor urut/barcode duplikat.</span>
                        </label>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Ketik <code>RESET SEMUA BALANCE</code> untuk konfirmasi:</label>
                    <input type="text" class="form-control" id="reset-confirmation-input" placeholder="Ketik konfirmasi di sini..." autocomplete="off">
                    <div class="form-text text-danger">Perhatikan huruf besar/kecil dan spasi.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark"></i> Batal
                </button>
                <button type="button" class="btn btn-danger btn-sm" id="btn-confirm-reset-balance" disabled>
                    <i class="fa-solid fa-trash"></i> Reset Semua Balance
                </button>
            </div>
        </div>
    </div>
</div>

@include('schedule.assy_scheduler.generate_modal')
@endsection

@section('script')
    <script src="{{ url('js/schedule-verification.js') }}?v={{ time() }}"></script>
    <script>
        // Set route URLs for the external JavaScript file
        routeUrls = {
            datatable: "{{ route('schedule.schedule-verification.datatable') }}",
            details: "{{ route('schedule.schedule-verification.details') }}",
            availableAssy: "{{ route('schedule.schedule-verification.available-assy') }}",
            availableDates: "{{ route('schedule.schedule-verification.available-dates') }}",
            verify: "{{ route('schedule.schedule-verification.verify') }}",
            unverify: "{{ route('schedule.schedule-verification.unverify') }}",
            previewUnverify: "{{ route('schedule.schedule-verification.preview-unverify') }}",
            csrfToken: '{{ csrf_token() }}'
        };

        // Reset Balance functionality
        $(function() {
            $('#btn-reset-balance').click(function() {
                $('#reset-confirmation-input').val('');
                $('#reset-conveyor-select').val('');
                $('#reset-mode-full').prop('checked', true);
                $('#btn-confirm-reset-balance').prop('disabled', true);
                $('#resetBalanceModal').modal('show');
            });

            $('#reset-confirmation-input').on('input', function() {
                var val = $(this).val().trim();
                $('#btn-confirm-reset-balance').prop('disabled', val !== 'RESET SEMUA BALANCE');
            });

            $('#btn-confirm-reset-balance').click(function() {
                var confirmation = $('#reset-confirmation-input').val().trim();
                if (confirmation !== 'RESET SEMUA BALANCE') return;

                var $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Memproses...');

                $.ajax({
                    url: "{{ route('schedule.schedule-verification.reset-balance') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        confirmation: confirmation,
                        conveyor_id: $('#reset-conveyor-select').val(),
                        reset_mode: $('input[name="reset_mode"]:checked').val()
                    },
                    success: function(response) {
                        $('#resetBalanceModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Reset Berhasil',
                            text: response.message,
                            timer: 3000,
                            showConfirmButton: true
                        });
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON?.message || 'Terjadi kesalahan saat reset.';
                        Swal.fire('Error', msg, 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="fa-solid fa-trash"></i> Reset Semua Balance');
                    }
                });
            });
        });

        // Generate modal (manual) — shared with Assy Scheduler page
        $(function() {
            initAssyGenerateModal({
                generateUrl: "{{ route('schedule.assy-scheduler.generate') }}",
                syncStatusUrl: "{{ route('dashboard.sync-status') }}",
                csrfToken: '{{ csrf_token() }}',
                defaultDays: 10,
                onSuccess: function () { scheduleVerificationTable.ajax.reload(); }
            });

            // Sync status badges + silent auto sync/generate on page load
            initAssyAutoSync({
                syncStatusUrl: "{{ route('dashboard.sync-status') }}",
                generateUrl: "{{ route('schedule.assy-scheduler.generate') }}",
                csrfToken: '{{ csrf_token() }}',
                autoDays: 3,
                onSuccess: function () { scheduleVerificationTable.ajax.reload(); }
            });
        });
    </script>
@endsection
