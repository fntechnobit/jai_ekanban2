@extends('layouts.master')

@section('title', 'Assy Scheduler')

@section('breadcrumb')
    <x-page-header menu-code="assy_scheduler" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fa-solid fa-list"></i> Assy Schedule List</h5>
                <div class="card-tools float-end">
                    @if(auth()->user()->hasMenuPermission('assy_scheduler', 'can_create'))
                        <button type="button" class="btn btn-primary btn-sm" id="btn-generate">
                            <i class="fa-solid fa-gear"></i> Generate
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-5">
                        <label for="filter_dates" class="form-label">Dates:</label>
                        <input type="text" class="form-control form-control-sm" id="filter_dates" readonly
                               placeholder="Select date range">
                    </div>
                    <div class="col-md-4">
                        <label for="filter_conveyor_id" class="form-label">Conveyor:</label>
                        <select class="form-select select2" id="filter_conveyor_id" style="width: 100%;">
                            <option value="">- All Conveyor -</option>
                            @foreach($conveyors as $conveyor)
                                <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-secondary btn-sm" id="btn-reset">
                            <i class="fa-solid fa-arrows-rotate"></i> Reset
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
                                <th width="35%">Assy</th>
                                <th width="12%">Qty.</th>
                                <th width="10%">Action</th>
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
    @include('schedule.assy_scheduler.edit_modal')
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
                    { data: 'qty', name: 'qty', className: 'text-center' , orderable: false},
                    { data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false }
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

            // Generate button
            $('#btn-generate').click(function() {
                $('#generateModal').modal('show');
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
                
                submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner ti-spin"></i> Generating...');

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
                        submitBtn.prop('disabled', false).html('<i class="fa-solid fa-gear"></i> Generate');
                    }
                });
            });

            // Edit button click
            $(document).on('click', '.btn-edit', function() {
                var id = $(this).data('id');
                var assy = $(this).data('assy');
                var qty = $(this).data('qty');
                
                $('#edit_id').val(id);
                $('#edit_assy').val(assy);
                $('#edit_qty').val(qty);
                $('#editModal').modal('show');
            });

            // Edit form submission
            $('#editForm').submit(function(e) {
                e.preventDefault();
                
                var id = $('#edit_id').val();
                var qty = $('#edit_qty').val();
                var submitBtn = $('#btn-submit-edit');
                
                Swal.fire({
                    title: 'Are you sure?',
                    html: '<div class="text-warning"><i class="ti ti-alert-triangle fa-2x mb-2"></i></div>' +
                          '<p>This change <strong>cannot be undone</strong>, even after re-sync.</p>' +
                          '<p class="text-muted small">The edited record will be preserved during future synchronizations.</p>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, save changes',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Saving...');
                        
                        $.ajax({
                            url: "{{ url('schedule/assy-scheduler') }}/" + id,
                            type: 'PUT',
                            data: {
                                _token: '{{ csrf_token() }}',
                                qty: qty
                            },
                            success: function(response) {
                                $('#editModal').modal('hide');
                                table.ajax.reload();
                                Swal.fire('Success!', response.message, 'success');
                            },
                            error: function(xhr) {
                                var message = 'Failed to update schedule';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                Swal.fire('Error!', message, 'error');
                            },
                            complete: function() {
                                submitBtn.prop('disabled', false).html('<i class="ti ti-check"></i> Save Changes');
                            }
                        });
                    }
                });
            });

            // Delete button click
            $(document).on('click', '.btn-delete', function() {
                var id = $(this).data('id');
                var assy = $(this).data('assy');
                
                Swal.fire({
                    title: 'Delete Schedule?',
                    html: '<div class="text-danger"><i class="ti ti-trash fa-2x mb-2"></i></div>' +
                          '<p>Are you sure you want to delete schedule for <strong>' + assy + '</strong>?</p>' +
                          '<p class="text-warning"><strong>Warning:</strong> This change cannot be undone, even after re-sync.</p>' +
                          '<p class="text-muted small">The deleted record will not appear in verification and will be preserved during future synchronizations.</p>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ url('schedule/assy-scheduler') }}/" + id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                table.ajax.reload();
                                Swal.fire('Deleted!', response.message, 'success');
                            },
                            error: function(xhr) {
                                var message = 'Failed to delete schedule';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                Swal.fire('Error!', message, 'error');
                            }
                        });
                    }
                });
            });

            // Restore button click (for soft-deleted records)
            $(document).on('click', '.btn-restore', function() {
                var id = $(this).data('id');
                var assy = $(this).data('assy');
                
                Swal.fire({
                    title: 'Restore Schedule?',
                    html: '<p>Are you sure you want to restore schedule for <strong>' + assy + '</strong>?</p>' +
                          '<p class="text-muted small">You will need to set the quantity after restoring.</p>',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, restore it',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Restore by updating with current qty (which triggers restore in backend)
                        $.ajax({
                            url: "{{ url('schedule/assy-scheduler') }}/" + id,
                            type: 'GET',
                            success: function(response) {
                                if (response.success) {
                                    // Open edit modal with fetched data to set new qty
                                    $('#edit_id').val(response.data.id);
                                    $('#edit_assy').val(response.data.assy);
                                    $('#edit_qty').val(response.data.qty);
                                    $('#editModal').modal('show');
                                }
                            },
                            error: function(xhr) {
                                var message = 'Failed to fetch schedule data';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                Swal.fire('Error!', message, 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
