@extends('layout')

@section('title', 'Assy Scheduler')

@section('content')
    <x-page-header menu-code="assy_scheduler" />

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Schedule List</h3>
                    <div class="card-tools">
                        @if(auth()->user()->hasMenuPermission('assy_scheduler', 'can_create'))
                            <button type="button" class="btn btn-primary btn-sm" id="btn-generate">
                                <i class="fas fa-cogs"></i> Generate Schedule
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
                        <table id="assy-scheduler-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">Num.</th>
                                    <th>Conveyor</th>
                                    <th>Dates</th>
                                    <th>Shift</th>
                                    <th>Capacity</th>
                                    <th>Listing</th>
                                    <th>Assy</th>
                                    <th>Status</th>
                                    <th width="8%">#</th>
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
            var startDate = moment().subtract(6, 'days');
            var endDate = moment();

            $('#filter_dates').daterangepicker({
                startDate: startDate,
                endDate: endDate,
                locale: {
                    format: 'DD-MM-YYYY'
                }
            });

            // DataTable
            var table = $('#assy-scheduler-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('schedule.assy-scheduler.datatable') }}",
                    data: function(d) {
                        var dates = $('#filter_dates').data('daterangepicker');
                        d.start_date = dates.startDate.format('YYYY-MM-DD');
                        d.end_date = dates.endDate.format('YYYY-MM-DD');
                        d.conveyor_id = $('#filter_conveyor_id').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '5%' },
                    { data: 'conveyor_name', name: 'conveyor_name', width: '8%' },
                    { data: 'date', name: 'schedule', width: '8%' },
                    { data: 'shift_name', name: 'shift', width: '8%' },
                    { data: 'capacity', name: 'capacity', orderable: false, width: '8%' },
                    { data: 'listing_count', name: 'qty', width: '8%' },
                    { data: 'assy_list', name: 'assy', width: '35%' },
                    { data: 'status', name: 'is_lock', width: '8%' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, width: '12%' }
                ],
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[2, 'asc']]
            });

            // Filter button
            $('#btn-filter').click(function() {
                table.ajax.reload();
            });

            // Reset button
            $('#btn-reset').click(function() {
                $('#filter_conveyor_id').val('').trigger('change');
                $('#filter_dates').data('daterangepicker').setStartDate(moment().subtract(6, 'days'));
                $('#filter_dates').data('daterangepicker').setEndDate(moment());
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

            // Set default date range for generate modal (7 days)
            var genStartDate = moment();
            var genEndDate = moment().add(6, 'days');

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

            // Verify button handler
            $(document).on('click', '.btn-verify', function() {
                var ids = $(this).data('ids');

                Swal.fire({
                    title: 'Verify Schedule?',
                    text: 'Are you sure you want to verify this schedule?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, verify it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('schedule.assy-scheduler.verify', ['id' => 0]) }}".replace('/0/', '/' + ids.toString().split(',')[0] + '/'),
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                ids: ids.toString()
                            },
                            success: function(response) {
                                table.ajax.reload();
                                Swal.fire('Verified!', response.message, 'success');
                            },
                            error: function(xhr) {
                                Swal.fire('Error!', xhr.responseJSON.message || 'Failed to verify schedule', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
