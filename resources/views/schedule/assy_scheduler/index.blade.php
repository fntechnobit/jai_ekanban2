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
