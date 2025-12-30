@extends('layout')

@section('title', 'Schedule Verification')

@section('css')
<style>
    .info-header {
        gap: 8px;
    }

    .info-header .badge {
        font-size: 12px;
    }

    .assy-item {
        background: #17a2b8;
        border: 1px solid #138496;
        border-radius: 4px;
        padding: 10px;
        margin: 4px 0;
        cursor: move;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
    }

    .assy-item .assy-code {
        flex: 1;
        font-weight: 500;
    }

    .assy-item .assy-qty {
        margin-left: 8px;
        width: 60px;
        text-align: center;
        background: white;
        border: 1px solid #ddd;
        color: #333;
    }

    .assy-item:hover {
        background: #138496;
        border-color: #0f6674;
    }

    .assy-item.dragging {
        opacity: 0.5;
    }

    .assy-placeholder {
        background: #f5f5f5;
        border: 2px dashed #ccc;
        height: 40px;
        margin: 4px 0;
        border-radius: 4px;
    }

    .shift-card {
        background: #007bff;
        color: white;
        padding: 10px 15px;
        margin-bottom: 15px;
        border-radius: 4px;
    }

    .shift-card h6 {
        margin: 0;
        font-weight: 600;
    }

    .shift-capacity-info {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        margin-top: 5px;
    }

    .shift-capacity-info .badge {
        font-size: 10px;
    }

    .cutoff-section {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 10px;
        margin-bottom: 10px;
    }

    .cutoff-header {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 8px;
        padding-bottom: 5px;
        border-bottom: 1px solid #dee2e6;
    }

    .cutoff-drop-zone {
        min-height: 60px;
        border: 2px dashed transparent;
        border-radius: 4px;
        padding: 5px;
        transition: all 0.3s ease;
    }

    .cutoff-drop-zone.drag-over {
        border-color: #007bff;
        background-color: #e7f3ff;
    }

    .available-cutoff-section {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 10px;
        margin-bottom: 10px;
    }

    .available-cutoff-header {
        font-weight: 600;
        font-size: 13px;
        margin-bottom: 8px;
        padding-bottom: 5px;
        border-bottom: 1px solid #dee2e6;
        color: #0d47a1;
    }

    .available-drop-zone {
        border: 2px dashed transparent;
        border-radius: 4px;
        transition: all 0.3s ease;
        min-height: 50px;
        padding: 5px;
    }

    .available-drop-zone.drag-over {
        border-color: #ff9800;
        background-color: #fff8f0;
    }

    .available-assy-item {
        background: #e3f2fd;
        border: 1px solid #2196f3;
        border-radius: 4px;
        padding: 8px 10px;
        margin: 4px 0;
        cursor: move;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .available-assy-item:hover {
        background: #bbdefb;
        border-color: #1976d2;
    }

    .available-assy-item .assy-code {
        flex: 1;
        font-weight: 500;
        color: #0d47a1;
        font-size: 13px;
    }

    .available-assy-item .assy-qty {
        margin-left: 8px;
        font-size: 12px;
        color: #666;
        font-weight: 600;
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
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="verificationModalLabel">
                        <i class="fas fa-check-circle"></i> Assy Schedule Verification
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Header Info -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="info-header d-flex flex-wrap gap-2">
                                <span class="badge badge-primary p-2" id="modal-conveyor"></span>
                                <span class="badge badge-info p-2" id="modal-date"></span>
                                <span class="badge badge-warning p-2" id="modal-shift"></span>
                                <span class="badge badge-success p-2" id="modal-capacity"></span>
                                <span class="badge badge-secondary p-2" id="modal-assy-count"></span>
                                <span class="badge badge-dark p-2" id="modal-total-listing"></span>
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
                                        <input type="text" id="available-date" class="form-control form-control-sm" 
                                               style="width: 140px;" placeholder="Select date">
                                        <select id="available-shift" class="form-control form-control-sm ml-2" style="width: 100px;">
                                            <option value="1">Shift 1</option>
                                            <option value="2">Shift 2</option>
                                        </select>
                                        <button type="button" id="btn-refresh-available" class="btn btn-sm btn-light ml-2">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <!-- Results summary -->
                                    <div id="available-results-info" class="text-muted small mb-2" style="display: none;">
                                        Showing <span id="results-start">1</span>-<span id="results-end">20</span> of <span id="results-total">0</span> items
                                    </div>
                                    
                                    <!-- Loading indicator -->
                                    <div id="available-loading" class="text-center py-3" style="display: none;">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
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
                    <button type="button" class="btn btn-success" id="btn-verify-schedule" style="display:none;">
                        <i class="fas fa-check"></i> Verify
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Close
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

            // Verify button - opens verification modal in edit mode
            $(document).on('click', '.btn-verify', function() {
                var conveyorId = $(this).data('conveyor-id');
                var date = $(this).data('date');
                var shift = $(this).data('shift');

                // Load verification details in edit mode
                loadVerificationDetails(conveyorId, date, shift, false);
            });

            // Detail button - opens verification modal in read-only mode
            $(document).on('click', '.btn-detail', function() {
                var conveyorId = $(this).data('conveyor-id');
                var date = $(this).data('date');
                var shift = $(this).data('shift');

                // Load verification details in read-only mode
                loadVerificationDetails(conveyorId, date, shift, true);
            });

            // Unverify button - unlocks the schedule with confirmation
            $(document).on('click', '.btn-unverify', function() {
                var conveyorId = $(this).data('conveyor-id');
                var date = $(this).data('date');
                var shift = $(this).data('shift');

                Swal.fire({
                    title: 'Unverify Schedule?',
                    text: 'This will unlock the schedule and allow it to be modified or regenerated. Are you sure?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f39c12',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, unverify it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('schedule.schedule-verification.unverify') }}",
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                conveyor_id: conveyorId,
                                date: date,
                                shift: shift
                            },
                            beforeSend: function() {
                                Swal.fire({
                                    title: 'Processing...',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });
                            },
                            success: function(response) {
                                table.ajax.reload();
                                Swal.fire('Unverified!', response.message, 'success');
                            },
                            error: function(xhr) {
                                var message = 'Failed to unverify schedule';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                Swal.fire('Error!', message, 'error');
                            }
                        });
                    }
                });
            });

            // Load verification details
            function loadVerificationDetails(conveyorId, date, shift, readOnly) {
                readOnly = readOnly || false;
                
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
                        displayVerificationModal(response, readOnly);
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
            function displayVerificationModal(data, readOnly) {
                readOnly = readOnly || false;
                
                // Store read-only state
                $('#verificationModal').data('read-only', readOnly);
                
                // Set header info
                $('#modal-conveyor').text('Conveyor ' + data.conveyor);
                $('#modal-date').text(moment(data.date).format('DD MMMM YYYY'));
                $('#modal-shift').text('Shift ' + data.shift);
                $('#modal-capacity').text(data.capacity + ' Capacity / Shift');
                $('#modal-assy-count').text(data.assy_count + ' Assy');
                $('#modal-total-listing').text(data.total_listing + ' Listing');

                // Build shift with cut-offs
                var shiftsHtml = '';
                var maxCutOff = Math.max(...data.cut_offs.map(co => co.cutoff));
                
                shiftsHtml += '<div class="shift-card">';
                shiftsHtml += '<h6>Shift ' + data.shift + '</h6>';
                
                // Calculate total used for this shift
                var totalUsed = data.cut_offs.reduce((sum, co) => {
                    return sum + co.items.reduce((s, item) => s + parseInt(item.qty), 0);
                }, 0);
                var remain = data.capacity - totalUsed;
                
                shiftsHtml += '<div class="shift-capacity-info">';
                shiftsHtml += '<span class="badge badge-light">Capacity: ' + data.capacity + '</span>';
                shiftsHtml += '<span class="badge badge-light">Used: ' + totalUsed + '</span>';
                shiftsHtml += '<span class="badge badge-light">Remain: ' + remain + '</span>';
                shiftsHtml += '</div>';
                shiftsHtml += '</div>';
                
                // Build cut-off sections
                for (var i = 1; i <= maxCutOff; i++) {
                    var cutOffData = data.cut_offs.find(co => co.cutoff == i);
                    var items = cutOffData ? cutOffData.items : [];
                    var used = items.reduce((sum, item) => sum + parseInt(item.qty), 0);
                    var cutoffRemain = data.capacity - used;
                    
                    shiftsHtml += '<div class="cutoff-section">';
                    shiftsHtml += '<div class="cutoff-header d-flex justify-content-between">';
                    shiftsHtml += '<span>Cut Off ' + i;
                    if (i === maxCutOff) {
                        var ratio = Math.round((used / data.capacity) * 10) / 10;
                        shiftsHtml += ' <small>(' + ratio + 'x)</small>';
                    }
                    shiftsHtml += '</span>';
                    shiftsHtml += '<div>';
                    shiftsHtml += '<span class="badge badge-info badge-sm">Cap: ' + data.capacity + '</span> ';
                    shiftsHtml += '<span class="badge badge-danger badge-sm">Used: ' + used + '</span> ';
                    shiftsHtml += '<span class="badge badge-success badge-sm">Remain: ' + cutoffRemain + '</span>';
                    shiftsHtml += '</div>';
                    shiftsHtml += '</div>';
                    shiftsHtml += '<div class="cutoff-drop-zone" data-cutoff="' + i + '" data-shift="' + data.shift + '">';
                    
                    items.forEach(function(item) {
                        shiftsHtml += '<div class="assy-item" data-id="' + item.id + '" data-cutoff="' + i + '" data-shift="' + data.shift + '"';
                        if (!readOnly) {
                            shiftsHtml += ' draggable="true"';
                        }
                        shiftsHtml += '>';
                        shiftsHtml += '<div class="assy-code">' + item.assy + '</div>';
                        shiftsHtml += '<input type="number" class="form-control form-control-sm assy-qty" value="' + item.qty + '" min="1"';
                        if (readOnly) {
                            shiftsHtml += ' readonly disabled';
                        }
                        shiftsHtml += '>';
                        shiftsHtml += '</div>';
                    });
                    
                    shiftsHtml += '</div></div>';
                }
                
                $('#shifts-container').html(shiftsHtml);

                // Store current data
                $('#verificationModal').data('conveyor-id', data.conveyor_id);
                $('#verificationModal').data('date', data.date);
                $('#verificationModal').data('shift', data.shift);
                $('#verificationModal').data('capacity', data.capacity);

                // Show/hide buttons based on read-only mode
                if (readOnly) {
                    $('#btn-verify-schedule').hide();
                    $('.col-md-6:last').hide(); // Hide available assy section
                    $('.col-md-6:first').removeClass('col-md-6').addClass('col-md-12'); // Make shifts full width
                } else {
                    $('#btn-verify-schedule').show();
                    $('.col-md-6:last').show();
                    $('.col-md-6:first').removeClass('col-md-12').addClass('col-md-6');
                }

                // Initialize single date picker for available assy (tomorrow by default)
                var availDate = moment(data.date).add(1, 'days');
                
                $('#available-date').daterangepicker({
                    singleDatePicker: true,
                    startDate: availDate,
                    locale: {
                        format: 'DD-MM-YYYY'
                    }
                });

                // Set shift selector
                $('#available-shift').val('1');

                // Load available assy data
                loadAvailableAssyData(data.conveyor_id);

                // Initialize drag and drop
                initializeDragDrop();

                // Show modal
                $('#verificationModal').modal('show');
            }

            // Load available assy data
            function loadAvailableAssyData(conveyorId) {
                var dateObj = $('#available-date').data('daterangepicker');
                var date = dateObj.startDate.format('YYYY-MM-DD');
                var shift = $('#available-shift').val();

                $('#available-loading').show();
                $('#available-assy-container').html('');
                $('#available-results-info').hide();

                $.ajax({
                    url: "{{ route('schedule.schedule-verification.available-assy') }}",
                    type: 'GET',
                    data: {
                        conveyor_id: conveyorId,
                        date: date,
                        shift: shift
                    },
                    success: function(response) {
                        $('#available-loading').hide();
                        
                        if (response.success && response.data && response.data.length > 0) {
                            var html = '';
                            var totalItems = 0;
                            
                            // Group by cut-off
                            response.data.forEach(function(cutoffData) {
                                if (cutoffData.items && cutoffData.items.length > 0) {
                                    html += '<div class="available-cutoff-section">';
                                    html += '<div class="available-cutoff-header">Cut Off ' + cutoffData.cutoff + ' (' + cutoffData.items.length + ' items)</div>';
                                    html += '<div class="available-drop-zone" data-cutoff="' + cutoffData.cutoff + '">';
                                    
                                    cutoffData.items.forEach(function(item) {
                                        html += '<div class="available-assy-item" data-id="' + item.id + '" data-assy="' + item.assy + '" data-cutoff="' + cutoffData.cutoff + '" data-date="' + date + '" draggable="true">';
                                        html += '<div class="assy-code">' + item.assy + '</div>';
                                        html += '<div class="assy-qty">' + item.qty + ' pcs</div>';
                                        html += '</div>';
                                        totalItems++;
                                    });
                                    
                                    html += '</div></div>';
                                }
                            });
                            
                            $('#available-assy-container').html(html);
                            $('#available-results-info').show();
                            $('#results-total').text(totalItems);
                            $('#results-end').text(totalItems);
                        } else {
                            $('#available-assy-container').html('<div class="text-center text-muted py-3">No available assy data found</div>');
                        }
                    },
                    error: function(xhr) {
                        $('#available-loading').hide();
                        $('#available-assy-container').html('<div class="text-center text-danger py-3">Failed to load data</div>');
                    }
                });
            }

            // Refresh available assy data when date or shift changes
            $('#btn-refresh-available, #available-shift').on('click change', function() {
                var conveyorId = $('#verificationModal').data('conveyor-id');
                loadAvailableAssyData(conveyorId);
            });

            $('#available-date').on('apply.daterangepicker', function(ev, picker) {
                var conveyorId = $('#verificationModal').data('conveyor-id');
                loadAvailableAssyData(conveyorId);
            });

            // Initialize drag and drop
            function initializeDragDrop() {
                var draggedItem = null;
                var dragSource = null;

                $(document).on('dragstart', '.assy-item, .available-assy-item', function(e) {
                    draggedItem = $(this);
                    dragSource = $(this).hasClass('available-assy-item') ? 'available' : 'cutoff';
                    $(this).addClass('dragging');
                });

                $(document).on('dragend', '.assy-item, .available-assy-item', function(e) {
                    $(this).removeClass('dragging');
                });

                $(document).on('dragover', '.cutoff-drop-zone', function(e) {
                    e.preventDefault();
                    $(this).addClass('drag-over');
                });

                $(document).on('dragleave', '.cutoff-drop-zone', function(e) {
                    $(this).removeClass('drag-over');
                });

                $(document).on('drop', '.cutoff-drop-zone', function(e) {
                    e.preventDefault();
                    $(this).removeClass('drag-over');
                    
                    if (draggedItem) {
                        var newCutOff = $(this).data('cutoff');
                        var newShift = $(this).data('shift');
                        
                        if (dragSource === 'available') {
                            // Create new assy item from available
                            var assyCode = draggedItem.data('assy');
                            var assyDate = draggedItem.data('date');
                            var sourceId = draggedItem.data('id');
                            var sourceQty = draggedItem.find('.assy-qty').text().replace(' pcs', '').trim();
                            
                            var newItem = '<div class="assy-item" data-id="new_' + Date.now() + '" data-cutoff="' + newCutOff + '" data-shift="' + newShift + '" data-assy="' + assyCode + '" data-source-date="' + assyDate + '" data-source-id="' + sourceId + '">';
                            newItem += '<div class="assy-code">' + assyCode + '</div>';
                            newItem += '<input type="number" class="form-control form-control-sm assy-qty" value="' + sourceQty + '" min="1">';
                            newItem += '</div>';
                            $(this).append(newItem);
                            
                            // Remove from available
                            draggedItem.remove();
                        } else {
                            // Move existing item
                            draggedItem.attr('data-cutoff', newCutOff);
                            draggedItem.attr('data-shift', newShift);
                            $(this).append(draggedItem);
                        }
                        
                        updateCutOffStats();
                    }
                });

                // Allow dragging back to available zone (to remove)
                $(document).on('dragover', '.available-drop-zone', function(e) {
                    e.preventDefault();
                    $(this).addClass('drag-over');
                });

                $(document).on('dragleave', '.available-drop-zone', function(e) {
                    $(this).removeClass('drag-over');
                });

                $(document).on('drop', '.available-drop-zone', function(e) {
                    e.preventDefault();
                    $(this).removeClass('drag-over');
                    
                    if (draggedItem && dragSource === 'cutoff') {
                        // Remove item from cut-off
                        draggedItem.remove();
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
                
                // Update each cut-off
                $('.cutoff-drop-zone').each(function() {
                    var cutOffZone = $(this);
                    var cutoff = cutOffZone.data('cutoff');
                    var used = 0;
                    
                    cutOffZone.find('.assy-item').each(function() {
                        var qty = parseInt($(this).find('.assy-qty').val()) || 0;
                        used += qty;
                    });
                    
                    var remain = capacity - used;
                    
                    var cutoffSection = cutOffZone.closest('.cutoff-section');
                    cutoffSection.find('.badge-danger').text('Used: ' + used);
                    cutoffSection.find('.badge-success').text('Remain: ' + remain);
                });

                // Update shift total
                var totalUsed = 0;
                $('.assy-item').each(function() {
                    var qty = parseInt($(this).find('.assy-qty').val()) || 0;
                    totalUsed += qty;
                });
                var shiftRemain = capacity - totalUsed;
                
                $('.shift-card .badge:contains("Used")').text('Used: ' + totalUsed);
                $('.shift-card .badge:contains("Remain")').text('Remain: ' + shiftRemain);
            }

            // Verify schedule button
            $('#btn-verify-schedule').click(function() {
                var conveyorId = $('#verificationModal').data('conveyor-id');
                var date = $('#verificationModal').data('date');
                var shift = $('#verificationModal').data('shift');

                Swal.fire({
                    title: 'Verify This Schedule?',
                    html: '<p>This will lock the schedule for:</p>' +
                          '<ul style="text-align: left; display: inline-block;">' +
                          '<li><strong>Conveyor:</strong> ' + $('#modal-conveyor').text() + '</li>' +
                          '<li><strong>Date:</strong> ' + $('#modal-date').text() + '</li>' +
                          '<li><strong>Shift:</strong> ' + $('#modal-shift').text() + '</li>' +
                          '</ul>' +
                          '<p class="text-warning mt-2"><i class="fas fa-exclamation-triangle"></i> Once verified, the schedule cannot be modified or regenerated.</p>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, verify it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('schedule.schedule-verification.verify') }}",
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                conveyor_id: conveyorId,
                                date: date,
                                shift: shift
                            },
                            beforeSend: function() {
                                $('#btn-verify-schedule').prop('disabled', true)
                                    .html('<i class="fas fa-spinner fa-spin"></i> Verifying...');
                            },
                            success: function(response) {
                                $('#verificationModal').modal('hide');
                                table.ajax.reload();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Verified!',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            },
                            error: function(xhr) {
                                var message = 'Failed to verify schedule';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                Swal.fire('Error!', message, 'error');
                            },
                            complete: function() {
                                $('#btn-verify-schedule').prop('disabled', false)
                                    .html('<i class="fas fa-check"></i> Verify');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
