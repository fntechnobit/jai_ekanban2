@extends('layout')

@section('title', 'Assy Scheduler')

@section('content')
    <x-page-header menu-code="assy_scheduler" />

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Assy Schedule List</h3>
                    <div class="card-tools">
                        @if(auth()->user()->hasMenuPermission('assy_scheduler', 'can_create'))
                            <button type="button" class="btn btn-primary btn-sm" id="btn-generate">
                                <i class="fas fa-cogs"></i> Generate
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-5">
                            <label for="filter_dates">Dates:</label>
                            <input type="text" class="form-control" id="filter_dates" readonly
                                   placeholder="Select date range">
                        </div>
                        <div class="col-md-4">
                            <label for="filter_conveyor_id">Conveyor:</label>
                            <select class="form-control select2" id="filter_conveyor_id" style="width: 100%;">
                                <option value="">- All Conveyor -</option>
                                @foreach($conveyors as $conveyor)
                                    <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label><br>
                            <button type="button" class="btn btn-info" id="btn-filter">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <button type="button" class="btn btn-secondary" id="btn-reset">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </div>

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
    </section>

    @include('schedule.assy_scheduler.generate_modal')
@endsection

@section('script')
    <script>
        $(function () {
            // Initialize Select2
            $('#filter_conveyor_id').select2({
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

            // Filter button
            $('#btn-filter').click(function() {
                table.ajax.reload();
            });

            // Reset button
            $('#btn-reset').click(function() {
                $('#filter_conveyor_id').val('').trigger('change');
                $('#filter_dates').data('daterangepicker').setStartDate(moment());
                $('#filter_dates').data('daterangepicker').setEndDate(moment().add(3, 'days'));
                table.ajax.reload();
            });

            // Generate button
            $('#btn-generate').click(function() {
                $('#generateModal').modal('show');
            });

            // Initialize Select2 in modal
            $('#generate_conveyor_id').select2({
                theme: 'bootstrap4',
                dropdownParent: $('#generateModal'),
                allowClear: true,
                placeholder: '- All Conveyor -'
            });

            // Set default date range for generate modal
            var genStartDate = moment();
            var genEndDate = moment().add(3, 'days');

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

                var dates = $('#generate_dates').data('daterangepicker');
                var submitBtn = $('#btn-submit-generate');
                
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');

                $.ajax({
                    url: "{{ route('schedule.assy-scheduler.generate') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        start_date: dates.startDate.format('YYYY-MM-DD'),
                        end_date: dates.endDate.format('YYYY-MM-DD'),
                        conveyor_id: $('#generate_conveyor_id').val()
                    },
                    success: function(response) {
                        $('#generateModal').modal('hide');
                        table.ajax.reload();
                        Swal.fire('Success!', response.message, 'success');
                    },
                    error: function(xhr) {
                        var message = 'Failed to generate schedules';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire('Error!', message, 'error');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html('<i class="fas fa-cogs"></i> Generate');
                    }
                });
            });
        });
    </script>
@endsection
