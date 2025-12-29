@extends('layout')

@section('title', 'Schedule Verification')

@section('css')
<style>
    .badge-lg {
        font-size: 14px;
        padding: 8px 12px;
    }
    
    .cut-off-card {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        margin-bottom: 20px;
        background: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .cut-off-card .card-header {
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: 10px 15px;
    }
    
    .cut-off-title {
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 8px;
    }
    
    .cut-off-stats .badge {
        font-size: 12px;
        padding: 4px 8px;
        margin-right: 5px;
    }
    
    .assy-item {
        background: #17a2b8;
        color: white;
        padding: 12px 15px;
        margin: 8px 0;
        border-radius: 5px;
        cursor: move;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 2px solid #138496;
    }
    
    .assy-item.dragging {
        opacity: 0.5;
        border: 2px dashed #138496;
    }
    
    .assy-code {
        font-weight: bold;
        flex: 1;
        font-size: 14px;
    }
    
    .assy-qty {
        width: 70px;
        text-align: center;
        font-weight: bold;
        border: 1px solid #ddd;
        background: white;
    }
    
    .cut-off-zone {
        border: 2px dashed #ddd;
        padding: 15px;
        border-radius: 5px;
        min-height: 120px;
        background: #f8f9fa;
    }
    
    .cut-off-zone.drag-over {
        background-color: #e3f2fd;
        border-color: #007bff;
        border-style: solid;
    }
    
    .shift-section {
        margin-bottom: 20px;
    }
    
    .shift-title {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #dee2e6;
    }
</style>
@endsection

@section('content')
    <x-page-header menu-code="schedule_verification" />

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Schedule List</h3>
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
                        <table id="schedule-verification-table" class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th width="5%">Num.</th>
                                    <th width="10%">Conveyor</th>
                                    <th width="12%">Dates</th>
                                    <th width="8%">Shift</th>
                                    <th width="8%">Capacity</th>
                                    <th width="8%">Listing</th>
                                    <th width="35%">Assy</th>
                                    <th width="8%">Status</th>
                                    <th width="6%">#</th>
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

    <!-- Verification Modal -->
    <div class="modal fade" id="verificationModal" tabindex="-1" role="dialog" aria-labelledby="verificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" id="verificationModalLabel">Assy Schedule Verification</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Header Info -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <span class="badge badge-info badge-lg mr-2" id="modal-conveyor"></span>
                            <span class="badge badge-info badge-lg mr-2" id="modal-date"></span>
                            <span class="badge badge-warning badge-lg mr-2" id="modal-shift"></span>
                            <span class="badge badge-success badge-lg mr-2" id="modal-capacity"></span>
                            <span class="badge badge-primary badge-lg mr-2" id="modal-assy-count"></span>
                            <span class="badge badge-primary badge-lg" id="modal-total-listing"></span>
                        </div>
                    </div>

                    <div id="cut-off-container">
                        <!-- Cut off sections will be dynamically loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="btn-save-verification">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </div>
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
            var startDate = moment().subtract(10, 'days');
            var endDate = moment().add(31, 'days');

            $('#filter_dates').daterangepicker({
                startDate: startDate,
                endDate: endDate,
                locale: {
                    format: 'DD-MM-YYYY'
                }
            });

            // DataTable
            var table = $('#schedule-verification-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('schedule.schedule-verification.datatable') }}",
                    data: function(d) {
                        var dates = $('#filter_dates').data('daterangepicker');
                        d.start_date = dates.startDate.format('YYYY-MM-DD');
                        d.end_date = dates.endDate.format('YYYY-MM-DD');
                        d.conveyor_id = $('#filter_conveyor_id').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'conveyor_name', name: 'conveyor.conveyor' },
                    { data: 'dates', name: 'schedule_date' },
                    { data: 'shift_name', name: 'shift', className: 'text-center' },
                    { data: 'capacity', name: 'capacity', className: 'text-center' },
                    { data: 'listing', name: 'total_listing', className: 'text-center' },
                    { data: 'assy', name: 'assy_list' },
                    { data: 'status', name: 'is_lock', className: 'text-center' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                ],
                ordering: false,
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[2, 'asc'], [1, 'asc'], [3, 'asc']]
            });

            // Filter button
            $('#btn-filter').click(function() {
                table.ajax.reload();
            });

            // Reset button
            $('#btn-reset').click(function() {
                $('#filter_conveyor_id').val('').trigger('change');
                $('#filter_dates').data('daterangepicker').setStartDate(moment().subtract(10, 'days'));
                $('#filter_dates').data('daterangepicker').setEndDate(moment().add(31, 'days'));
                table.ajax.reload();
            });

            // Verify button - opens verification modal
            $(document).on('click', '.btn-verify', function() {
                var conveyorId = $(this).data('conveyor-id');
                var date = $(this).data('date');
                var shift = $(this).data('shift');

                // Load verification details
                loadVerificationDetails(conveyorId, date, shift);
            });

            // Load verification details
            function loadVerificationDetails(conveyorId, date, shift) {
                $.ajax({
                    url: "{{ route('schedule.schedule-verification.details') }}",
                    type: 'GET',
                    data: {
                        conveyor_id: conveyorId,
                        date: date,
                        shift: shift
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Loading...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        displayVerificationModal(response);
                    },
                    error: function(xhr) {
                        var message = 'Failed to load verification details';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire('Error!', message, 'error');
                    }
                });
            }

            // Display verification modal
            function displayVerificationModal(data) {
                // Set header info
                $('#modal-conveyor').text('Conveyor ' + data.conveyor);
                $('#modal-date').text(moment(data.date).format('DD MMMM YYYY'));
                $('#modal-shift').text('Shift ' + data.shift);
                $('#modal-capacity').text(data.capacity + ' Capacity');
                $('#modal-assy-count').text(data.assy_count + ' Assy');
                $('#modal-total-listing').text(data.total_listing + ' Listing');

                // Build cut off sections
                var cutOffHtml = '';
                var maxCutOff = Math.max(...data.cut_offs.map(co => co.cutoff));
                
                cutOffHtml += '<div class="shift-section">';
                cutOffHtml += '<div class="shift-title">SHIFT ' + data.shift + '</div>';
                cutOffHtml += '<div class="row">';

                for (var i = 1; i <= maxCutOff; i++) {
                    var cutOffData = data.cut_offs.find(co => co.cutoff == i);
                    var items = cutOffData ? cutOffData.items : [];
                    var used = items.reduce((sum, item) => sum + parseInt(item.qty), 0);
                    var remain = data.capacity - used;

                    var colClass = i <= 4 ? 'col-md-6 mb-3' : 'col-md-12 mb-3';
                    
                    cutOffHtml += '<div class="' + colClass + '">';
                    cutOffHtml += '<div class="cut-off-card">';
                    cutOffHtml += '<div class="card-header">';
                    cutOffHtml += '<div class="cut-off-title">Cut Off ' + i;
                    if (i === maxCutOff) {
                        var ratio = Math.round((used / data.capacity) * 10) / 10;
                        cutOffHtml += ' <small>(' + ratio + 'x)</small>';
                    }
                    cutOffHtml += '</div>';
                    cutOffHtml += '<div class="cut-off-stats">';
                    cutOffHtml += '<span class="badge badge-info">Capacity: ' + data.capacity + '</span> ';
                    cutOffHtml += '<span class="badge badge-danger">Used: ' + used + '</span> ';
                    cutOffHtml += '<span class="badge badge-success">Remain: ' + remain + '</span>';
                    cutOffHtml += '</div>';
                    cutOffHtml += '</div>';
                    cutOffHtml += '<div class="card-body"><div class="cut-off-zone" data-cutoff="' + i + '">';
                    
                    items.forEach(function(item) {
                        cutOffHtml += '<div class="assy-item" data-id="' + item.id + '" data-cutoff="' + i + '" draggable="true">';
                        cutOffHtml += '<div class="assy-code">' + item.assy + '</div>';
                        cutOffHtml += '<input type="number" class="form-control form-control-sm assy-qty" value="' + item.qty + '" min="1">';
                        cutOffHtml += '</div>';
                    });
                    
                    cutOffHtml += '</div></div></div></div>';
                }

                cutOffHtml += '</div></div>';
                $('#cut-off-container').html(cutOffHtml);

                // Store current data
                $('#verificationModal').data('conveyor-id', data.conveyor_id);
                $('#verificationModal').data('date', data.date);
                $('#verificationModal').data('shift', data.shift);
                $('#verificationModal').data('capacity', data.capacity);

                // Initialize drag and drop
                initializeDragDrop();

                // Show modal
                $('#verificationModal').modal('show');
            }

            // Initialize drag and drop
            function initializeDragDrop() {
                var draggedItem = null;

                $(document).on('dragstart', '.assy-item', function(e) {
                    draggedItem = $(this);
                    $(this).addClass('dragging');
                });

                $(document).on('dragend', '.assy-item', function(e) {
                    $(this).removeClass('dragging');
                });

                $(document).on('dragover', '.cut-off-zone', function(e) {
                    e.preventDefault();
                    $(this).addClass('drag-over');
                });

                $(document).on('dragleave', '.cut-off-zone', function(e) {
                    $(this).removeClass('drag-over');
                });

                $(document).on('drop', '.cut-off-zone', function(e) {
                    e.preventDefault();
                    $(this).removeClass('drag-over');
                    
                    if (draggedItem) {
                        var newCutOff = $(this).data('cutoff');
                        draggedItem.attr('data-cutoff', newCutOff);
                        $(this).append(draggedItem);
                        updateCutOffStats();
                    }
                });

                // Update stats when quantity changes
                $(document).on('change', '.assy-qty', function() {
                    updateCutOffStats();
                });
            }

            // Update cut off statistics
            function updateCutOffStats() {
                var capacity = $('#verificationModal').data('capacity');
                
                $('.cut-off-zone').each(function() {
                    var cutOffZone = $(this);
                    var cutoff = cutOffZone.data('cutoff');
                    var used = 0;
                    
                    cutOffZone.find('.assy-item').each(function() {
                        var qty = parseInt($(this).find('.assy-qty').val()) || 0;
                        used += qty;
                    });
                    
                    var remain = capacity - used;
                    
                    var cardHeader = cutOffZone.closest('.cut-off-card').find('.card-header');
                    cardHeader.find('.badge-danger').text('Used: ' + used);
                    cardHeader.find('.badge-success').text('Remain: ' + remain);
                });
            }

            // Save verification
            $('#btn-save-verification').click(function() {
                var conveyorId = $('#verificationModal').data('conveyor-id');
                var date = $('#verificationModal').data('date');
                var shift = $('#verificationModal').data('shift');
                
                var schedules = [];
                
                $('.assy-item').each(function() {
                    schedules.push({
                        id: $(this).data('id'),
                        cutoff: $(this).data('cutoff'),
                        qty: parseInt($(this).find('.assy-qty').val())
                    });
                });

                Swal.fire({
                    title: 'Save Changes?',
                    text: 'Do you want to save all changes?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, save it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('schedule.schedule-verification.save') }}",
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                conveyor_id: conveyorId,
                                date: date,
                                shift: shift,
                                schedules: schedules
                            },
                            beforeSend: function() {
                                $('#btn-save-verification').prop('disabled', true)
                                    .html('<i class="fas fa-spinner fa-spin"></i> Saving...');
                            },
                            success: function(response) {
                                $('#verificationModal').modal('hide');
                                table.ajax.reload();
                                Swal.fire('Saved!', response.message, 'success');
                            },
                            error: function(xhr) {
                                var message = 'Failed to save changes';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                Swal.fire('Error!', message, 'error');
                            },
                            complete: function() {
                                $('#btn-save-verification').prop('disabled', false)
                                    .html('<i class="fas fa-save"></i> Save');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
