@extends('layouts.master')

@section('title', 'Assy Scheduler')

@section('breadcrumb')
    <x-page-header menu-code="assy_scheduler" />
@endsection

@section('content')
    <div class="container-fluid">

        {{-- Dynamic banner: auto-sync / manual generate result --}}
        <div id="assy-generate-banner" style="display:none;"></div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
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
                        <select class="form-select form-select-sm" id="filter_status" style="width: 130px;">
                            <option value="">- All Status -</option>
                            <option value="pending">Pending</option>
                            <option value="verified">Verified</option>
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

                {{-- Ringkasan rentang yang sedang dilihat, supaya gambaran besarnya
                     terbaca tanpa menelusuri seluruh baris. --}}
                <div class="row g-2 mb-3" id="assy-summary">
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="sched-stat">
                            <span class="sched-stat-lab">Total Qty</span>
                            <span class="sched-stat-val" id="sum-qty">-</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="sched-stat">
                            <span class="sched-stat-lab">Baris Jadwal</span>
                            <span class="sched-stat-val" id="sum-baris">-</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="sched-stat">
                            <span class="sched-stat-lab">Assy / Conveyor</span>
                            <span class="sched-stat-val" id="sum-assy">-</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="sched-stat">
                            <span class="sched-stat-lab">Terverifikasi</span>
                            <span class="sched-stat-val" id="sum-verif">-</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="sched-stat sched-stat-ot">
                            <span class="sched-stat-lab">Qty di CO5 (lembur)</span>
                            <span class="sched-stat-val" id="sum-co5">-</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="sched-stat" id="sum-box-warn">
                            <span class="sched-stat-lab">Belum Sinkron</span>
                            <span class="sched-stat-val" id="sum-warn">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="assy-schedule-table" class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th width="3%">Num.</th>
                                <th width="12%">Conveyor</th>
                                <th width="9%">Dates</th>
                                <th width="6%" class="text-center">Shift</th>
                                <th width="6%" class="text-center">Cut Off</th>
                                <th width="5%" class="text-center">Cap</th>
                                <th width="5%" class="text-center">OT</th>
                                <th width="11%" class="text-center">API Time</th>
                                <th width="25%">Assy</th>
                                <th width="6%" class="text-center">Qty</th>
                                <th width="7%" class="text-center">Status</th>
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

            // Ringkasan rentang yang sedang dilihat. Dipanggil ulang tiap tabel
            // digambar supaya angkanya selalu mengikuti filter yang aktif.
            function muatRingkasan() {
                var dates = $('#filter_dates').data('daterangepicker');

                $.get("{{ route('schedule.assy-scheduler.summary') }}", {
                    start_date: dates.startDate.format('YYYY-MM-DD'),
                    end_date: dates.endDate.format('YYYY-MM-DD'),
                    conveyor_id: $('#filter_conveyor_id').val()
                }).done(function (res) {
                    var d = (res && res.data) || {};
                    var n = function (v) { return Number(v || 0).toLocaleString('id-ID'); };

                    $('#sum-qty').text(n(d.total_qty));
                    $('#sum-baris').text(n(d.baris));
                    $('#sum-assy').text(n(d.jumlah_assy) + ' / ' + n(d.jumlah_conveyor));
                    $('#sum-verif').text(n(d.terverifikasi) + ' dari ' + n(d.baris));
                    $('#sum-co5').text(n(d.qty_co5));

                    var warn = Number(d.tanpa_kapasitas || 0);
                    $('#sum-warn').text(warn ? n(warn) + ' baris' : 'tidak ada');
                    $('#sum-box-warn').toggleClass('sched-stat-bad', warn > 0);
                }).fail(function () {
                    $('#assy-summary .sched-stat-val').text('-');
                });
            }

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
                        d.status = $('#filter_status').val();
                    }
                },
                drawCallback: function () { muatRingkasan(); },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'conveyor_name', name: 'mc.conveyor', orderable: false },
                    { data: 'dates', name: 'assy_schedule.schedule', orderable: false },
                    { data: 'shift_name', name: 'assy_schedule.shift', className: 'text-center', orderable: false },
                    { data: 'cutoff_label', name: 'assy_schedule.cutoff', className: 'text-center', orderable: false },
                    { data: 'capacity', name: 'mc.capacity', className: 'text-center', orderable: false, searchable: false },
                    { data: 'over_time', name: 'is_overtime', className: 'text-center', orderable: false, searchable: false },
                    { data: 'api_time', name: 'listing_synced_at', className: 'text-center', orderable: false, searchable: false },
                    { data: 'assy', name: 'assy_schedule.assy', orderable: false },
                    { data: 'qty', name: 'assy_schedule.qty', className: 'text-center', orderable: false },
                    { data: 'status', name: 'assy_schedule.is_lock', className: 'text-center', orderable: false, searchable: false }
                ],
                ordering: false,
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]
            });

            // Auto reload when filter changes
            $('#filter_conveyor_id, #filter_status').on('change', function() {
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

            // Generate modal (manual) — shared with Schedule Verification page
            initAssyGenerateModal({
                generateUrl: "{{ route('schedule.assy-scheduler.generate') }}",
                syncStatusUrl: "{{ route('dashboard.sync-status') }}",
                csrfToken: '{{ csrf_token() }}',
                defaultDays: 10,
                onSuccess: function () { table.ajax.reload(); }
            });

            // Sync status badges + silent auto sync/generate on page load
            initAssyAutoSync({
                syncStatusUrl: "{{ route('dashboard.sync-status') }}",
                generateUrl: "{{ route('schedule.assy-scheduler.generate') }}",
                csrfToken: '{{ csrf_token() }}',
                autoDays: 3,
                onSuccess: function () { table.ajax.reload(); }
            });
        });
    </script>
@endsection

@push('styles')
<style>
/* Kotak ringkasan di atas tabel. Warna diambil dari tema aplikasi supaya ikut
   berubah bila temanya diganti. */
.sched-stat{
    background:var(--bs-tertiary-bg); border:1px solid var(--bs-border-color);
    border-radius:.5rem; padding:.6rem .8rem; height:100%;
    display:flex; flex-direction:column; gap:.15rem;
}
.sched-stat-lab{
    font-size:.68rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase;
    color:var(--bs-secondary-color);
}
.sched-stat-val{
    font-size:1.05rem; font-weight:700; color:var(--bs-body-color);
    font-variant-numeric:tabular-nums; line-height:1.2;
}
.sched-stat-ot{ border-left:3px solid rgba(255,193,7,.85) }
.sched-stat-bad{ border-left:3px solid var(--bs-danger); background:rgba(220,53,69,.06) }
.sched-stat-bad .sched-stat-val{ color:var(--bs-danger) }

/* Tabel jadwal: angka rata kanan dan sejajar, teks tidak melompat. */
#assy-schedule-table td, #assy-schedule-table th{ vertical-align:middle }
#assy-schedule-table td:nth-child(6),
#assy-schedule-table td:nth-child(10){ font-variant-numeric:tabular-nums }
#assy-schedule-table small{ line-height:1.15 }
</style>
@endpush
