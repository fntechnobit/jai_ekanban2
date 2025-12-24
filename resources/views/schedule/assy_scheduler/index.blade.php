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
                                    <!-- <th>Status</th> -->
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
        // Track moved items from available area to prevent them from showing again on refresh
        var movedItemsFromAvailable = new Set();
        var currentAvailablePage = 1;
        
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
                    //{ data: 'status', name: 'is_lock', width: '8%' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, width: '15%' }
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

            // Manage button handler
            $(document).on('click', '.btn-manage', function() {
                var conveyorId = $(this).data('conveyor-id');
                var conveyorName = $(this).data('conveyor-name');
                var date = $(this).data('date');
                var capacity = $(this).data('capacity');
                var maxShifts = $(this).data('max-shifts');
                
                // Set modal header info with data storage
                $('#manage-conveyor-info').text('Conveyor ' + conveyorName).data('conveyor-id', conveyorId);
                $('#manage-date-info').text(moment(date).format('D MMMM YYYY')).data('date', date);
                $('#manage-capacity-info').text(capacity + ' Capacity / Shift');
                $('#manage-shifts-info').text(maxShifts + ' Shift' + (maxShifts > 1 ? 's' : ''));
                
                // Load manage data
                loadManageData(conveyorId, date);
                
                $('#manageModal').modal('show');
            });
            
            // Event handlers for available data pagination
            $(document).on('click', '#btn-refresh-available', function() {
                refreshAvailableData(1);
            });
            
            $(document).on('click', '#btn-prev-page', function() {
                if (currentAvailablePage > 1) {
                    refreshAvailableData(currentAvailablePage - 1);
                }
            });
            
            $(document).on('click', '#btn-next-page', function() {
                var totalPages = parseInt($('#pagination-info').text().split('of ')[1]);
                if (currentAvailablePage < totalPages) {
                    refreshAvailableData(currentAvailablePage + 1);
                }
            });
        });

        function loadManageData(conveyorId, date) {
            // Load existing schedule data only
            $.ajax({
                url: "{{ route('schedule.assy-scheduler.manage-data') }}",
                type: 'GET',
                data: {
                    conveyor_id: conveyorId,
                    date: date
                },
                success: function(response) {
                    if (response.success) {
                        // Update header with real counts
                        $('#manage-assy-count').text(response.total_assy_count + ' Assy');
                        $('#manage-listing-count').text(response.total_listing_count + ' Listing');
                        
                        renderShiftData(response.shifts);
                        initializeDragDrop();
                        
                        // Initialize date range picker and load available data
                        initializeDateRangeFilter(date, conveyorId);
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error!', 'Failed to load manage data', 'error');
                }
            });
        }

        function renderShiftData(shifts) {
            var shiftsContainer = $('#shifts-container');
            shiftsContainer.empty();
            
            // Get max shifts from current conveyor
            var maxShifts = parseInt($('#manage-shifts-info').text().split(' ')[0]);
            
            // Only render shifts up to maxShifts
            for (var i = 1; i <= maxShifts; i++) {
                var shiftData = shifts[i];
                if (shiftData) {
                    var usedCapacity = shiftData.used_capacity;
                    var totalCapacity = shiftData.total_capacity;
                    var remainCapacity = totalCapacity - usedCapacity;
                    
                    // Calculate column width based on number of shifts
                    var shiftHtml = `
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6>Shift ${i}</h6>
                                    <div class="shift-capacity-info">
                                        <span class="badge badge-primary">Capacity: ${totalCapacity}</span>
                                        <span class="badge badge-danger">Used: ${usedCapacity}</span>
                                        <span class="badge badge-success">Remain: ${remainCapacity}</span>
                                    </div>
                                </div>
                                <div class="card-body shift-drop-zone" data-shift="${i}" style="min-height: 150px;">
                    `;
                    
                    $.each(shiftData.items, function(index, item) {
                        shiftHtml += `
                            <div class="assy-item" data-type="shift" data-id="${item.id}" data-listing-id="${item.listing_id}" data-qty="${item.qty}">
                                <span class="assy-code">${item.assy}</span>
                                <input type="number" class="form-control form-control-sm assy-qty-input" id="qty-input-${item.id}" value="${item.qty}" min="1" style="width: 60px; display: inline-block; margin-left: 5px;">
                            </div>
                        `;
                    });
                    
                    shiftHtml += `
                                </div>
                            </div>
                        </div>
                    `;
                    
                    shiftsContainer.append(shiftHtml);
                }
            }
        }

        function renderAvailableData(availableItems) {
            var availableContainer = $('#available-assy-container');
            availableContainer.empty();
            
            // Filter out items that have been moved to shifts (UI-only tracking)
            var filteredItems = availableItems.filter(function(item) {
                return !movedItemsFromAvailable.has(item.id.toString());
            });
            
            if (filteredItems.length === 0) {
                availableContainer.html('<div class="text-center text-muted py-3">No available items found</div>');
                return;
            }
            
            $.each(filteredItems, function(index, item) {
                var itemHtml = `
                    <div class="assy-item" data-type="available" data-id="${item.id}" data-listing-id="${item.listing_id}" data-qty="${item.qty}">
                        <span class="assy-code">${item.assy} - ${item.schedule_date}</span>
                        <input type="number" class="form-control form-control-sm assy-qty-input" value="${item.qty}" min="1" style="width: 60px; display: inline-block; margin-left: 5px;">
                    </div>
                `;
                availableContainer.append(itemHtml);
            });
        }

        function initializeDateRangeFilter(selectedDate, conveyorId) {
            // Get main filter dates as fallback (selected date to +7 days)
            var mainStartDate = $('#start_date').val() || moment(selectedDate).format('DD-MM-YYYY');
            var mainEndDate = $('#end_date').val() || moment(selectedDate).add(7, 'days').format('DD-MM-YYYY');
            
            // Initialize DateRangePicker
            $('#available-date-range').daterangepicker({
                startDate: mainStartDate,
                endDate: mainEndDate,
                locale: {
                    format: 'DD-MM-YYYY'
                },
                maxSpan: {
                    days: 7
                },
                ranges: {
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Next 7 Days': [moment(), moment().add(6, 'days')],
                    'This Week': [moment().startOf('week'), moment().endOf('week')]
                }
            }, function(start, end) {
                // Auto refresh when date range changes
                refreshAvailableData(1);
            });
            
            // Store conveyor ID for refresh function
            $('#available-date-range').data('conveyor-id', conveyorId);
            $('#available-date-range').data('selected-date', selectedDate);
            
            // Initial load of available data
            refreshAvailableData(1);
        }

        var currentAvailablePage = 1;
        
        function refreshAvailableData(page = 1) {
            var dateRange = $('#available-date-range').data('daterangepicker');
            var conveyorId = $('#available-date-range').data('conveyor-id');
            var selectedDate = $('#available-date-range').data('selected-date');
            
            if (!dateRange || !conveyorId) {
                return;
            }
            
            // Show loading
            $('#available-loading').show();
            $('#available-assy-container').empty();
            $('#available-pagination').hide();
            $('#available-results-info').hide();
            $('#btn-refresh-available').prop('disabled', true);
            
            $.ajax({
                url: "{{ route('schedule.assy-scheduler.available-assy') }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    conveyor_id: conveyorId,
                    selected_date: selectedDate,
                    start_date: dateRange.startDate.format('YYYY-MM-DD'),
                    end_date: dateRange.endDate.format('YYYY-MM-DD'),
                    page: page
                },
                success: function(response) {
                    $('#available-loading').hide();
                    $('#btn-refresh-available').prop('disabled', false);
                    
                    if (response.success) {
                        currentAvailablePage = response.pagination.current_page;
                        
                        renderAvailableData(response.available);
                        updatePaginationControls(response.pagination);
                        updateResultsInfo(response.pagination);
                        
                        // Re-initialize drag and drop for new items
                        initializeAvailableDragDrop();
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    $('#available-loading').hide();
                    $('#btn-refresh-available').prop('disabled', false);
                    
                    var errorMsg = xhr.responseJSON?.message || 'Failed to load available data';
                    Swal.fire('Error!', errorMsg, 'error');
                }
            });
        }
        
        function updatePaginationControls(pagination) {
            if (pagination.total_pages > 1) {
                $('#available-pagination').show();
                $('#pagination-info').text(`Page ${pagination.current_page} of ${pagination.total_pages}`);
                
                $('#btn-prev-page').prop('disabled', !pagination.has_prev);
                $('#btn-next-page').prop('disabled', !pagination.has_next);
            } else {
                $('#available-pagination').hide();
            }
        }
        
        function updateResultsInfo(pagination) {
            if (pagination.total > 0) {
                var start = ((pagination.current_page - 1) * pagination.per_page) + 1;
                var end = Math.min(start + pagination.per_page - 1, pagination.total);
                
                $('#results-start').text(start);
                $('#results-end').text(end);
                $('#results-total').text(pagination.total);
                $('#available-results-info').show();
            } else {
                $('#available-results-info').hide();
            }
        }
        
        function initializeAvailableDragDrop() {
            // Initialize sortable for available items
            $('#available-assy-container').sortable({
                connectWith: '.shift-drop-zone',
                helper: 'clone',
                placeholder: 'assy-placeholder',
                stop: function(event, ui) {
                    // Check if item was moved to a shift zone (not returned to available area)
                    var targetParent = ui.item.parent();
                    if (targetParent.hasClass('shift-drop-zone')) {
                        // Item was moved from available to a shift, track it
                        var itemId = ui.item.data('id');
                        if (itemId) {
                            movedItemsFromAvailable.add(itemId.toString());
                        }
                    }
                    
                    // Update header counts when item is moved from available area
                    setTimeout(function() {
                        updateHeaderCounts();
                    }, 100);
                }
            });
            
            // Add event handler for quantity input changes in available items
            $(document).off('input', '#available-assy-container .assy-qty-input').on('input', '#available-assy-container .assy-qty-input', function() {
                var newQty = parseInt($(this).val()) || 1;
                $(this).closest('.assy-item').data('qty', newQty);
            });
        }

        function initializeDragDrop() {
            // Initialize sortable for shift zones only (available items handled separately)
            $('.shift-drop-zone').sortable({
                connectWith: '#available-assy-container, .shift-drop-zone',
                placeholder: 'assy-placeholder',
                receive: function(event, ui) {
                    // Handle item moved to this shift
                    var shiftNumber = $(this).data('shift');
                    var itemId = ui.item.data('id');
                    var itemQty = ui.item.data('qty');
                    
                    // Update shift capacity display
                    updateShiftCapacity(shiftNumber);
                    
                    // Update header counts
                    updateHeaderCounts();
                },
                remove: function(event, ui) {
                    // Handle item removed from this shift
                    var shiftNumber = $(this).data('shift');
                    var itemId = ui.item.data('id');
                    
                    // Check if item is being moved back to available area
                    setTimeout(function() {
                        if (ui.item.parent().is('#available-assy-container')) {
                            // Item was moved back to available area, remove from tracking
                            if (itemId) {
                                movedItemsFromAvailable.delete(itemId.toString());
                            }
                        }
                    }, 50);
                    
                    updateShiftCapacity(shiftNumber);
                    
                    // Update header counts
                    updateHeaderCounts();
                }
            });
            
            // Add event handlers for quantity input changes in shift zones
            $(document).off('input', '.shift-drop-zone .assy-qty-input').on('input', '.shift-drop-zone .assy-qty-input', function() {
                var newQty = parseInt($(this).val()) || 1;
                var shiftNumber = $(this).closest('.shift-drop-zone').data('shift');
                
                // Update data attribute
                $(this).closest('.assy-item').data('qty', newQty);
                
                // Update shift capacity and header counts
                updateShiftCapacity(shiftNumber);
                updateHeaderCounts();
            });
        }

        function updateShiftCapacity(shiftNumber) {
            var shiftZone = $(`.shift-drop-zone[data-shift="${shiftNumber}"]`);
            var totalQty = 0;
            
            shiftZone.find('.assy-item').each(function() {
                var qtyInput = $(this).find('.assy-qty-input');
                var qty = qtyInput.length ? parseInt(qtyInput.val()) || 0 : parseInt($(this).data('qty')) || 0;
                totalQty += qty;
            });
            
            // Update capacity badges
            var capacityInfo = shiftZone.closest('.card').find('.shift-capacity-info');
            var totalCapacity = parseInt(capacityInfo.find('.badge-primary').text().split(': ')[1]);
            var remainCapacity = totalCapacity - totalQty;
            
            capacityInfo.find('.badge-danger').text(`Used: ${totalQty}`);
            capacityInfo.find('.badge-success').text(`Remain: ${remainCapacity}`);
        }
        
        function updateHeaderCounts() {
            var totalListingCount = 0;
            var uniqueAssyCodes = new Set();
            
            // Count items in all shifts
            $('.shift-drop-zone').each(function() {
                $(this).find('.assy-item').each(function() {
                    var qtyInput = $(this).find('.assy-qty-input');
                    var qty = qtyInput.length ? parseInt(qtyInput.val()) || 0 : parseInt($(this).data('qty')) || 0;
                    var assyCode = $(this).find('.assy-code').text().split(' - ')[0]; // Get assy code before dash
                    
                    totalListingCount += qty;
                    uniqueAssyCodes.add(assyCode);
                });
            });
            
            // Update header display
            $('#manage-assy-count').text(uniqueAssyCodes.size + ' Assy');
            $('#manage-listing-count').text(totalListingCount + ' Listing');
        }
        
        // Save manage changes
        $('#btn-save-manage').click(function() {
            var shifts = {};
            
            $('.shift-drop-zone').each(function() {
                var shiftNumber = $(this).data('shift');
                var items = [];
                
                $(this).find('.assy-item').each(function() {
                    var qtyInput = $(this).find('.assy-qty-input');
                    var qty = qtyInput.length ? parseInt(qtyInput.val()) || 1 : parseInt($(this).data('qty')) || 1;
                    
                    items.push({
                        id: $(this).data('id'),
                        type: $(this).data('type'),
                        listing_id: $(this).data('listing-id'),
                        qty: qty
                    });
                });
                
                shifts[shiftNumber] = {
                    items: items
                };
            });
            
            var conveyorId = $('.btn-manage.active').data('conveyor-id') || $('#manage-conveyor-info').data('conveyor-id');
            var date = $('.btn-manage.active').data('date') || $('#manage-date-info').data('date');
            
            $.ajax({
                url: "{{ route('schedule.assy-scheduler.save-manage') }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    conveyor_id: conveyorId,
                    date: date,
                    shifts: shifts
                },
                success: function(response) {
                    // Clear moved items tracking after successful save
                    movedItemsFromAvailable.clear();
                    
                    $('#manageModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire('Success!', response.message, 'success');
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON.message || 'Failed to save changes', 'error');
                }
            });
        });

        // Clear moved items tracking when manage modal is closed without saving
        $('#manageModal').on('hidden.bs.modal', function() {
            movedItemsFromAvailable.clear();
        });
    </script>
@endsection

@include('schedule.assy_scheduler.manage_modal')
