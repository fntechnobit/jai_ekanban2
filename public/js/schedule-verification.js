/**
 * Schedule Verification JavaScript
 */

// Global variables to store route URLs (will be set from blade template)
var routeUrls = {
    datatable: '',
    details: '',
    availableAssy: '',
    verify: '',
    unverify: '',
    csrfToken: ''
};

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
            url: routeUrls.datatable,
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
                    url: routeUrls.unverify,
                    type: 'POST',
                    data: {
                        _token: routeUrls.csrfToken,
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
            url: routeUrls.details,
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
        var maxCutOff = 5; // Always show up to Cut Off 5
        
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
        
        // Build cut-off sections (always show 1-5)
        for (var i = 1; i <= maxCutOff; i++) {
            var cutOffData = data.cut_offs.find(co => co.cutoff == i);
            var items = cutOffData ? cutOffData.items : [];
            var used = items.reduce((sum, item) => sum + parseInt(item.qty), 0);
            
            // Calculate capacity for this cut off
            var cutoffCapacity, cutoffRemain;
            if (i === 5) {
                // Cut Off 5 uses special formula: 0.875 x (capacity/4)
                cutoffCapacity = data.cutoff5_capacity;
                cutoffRemain = cutoffCapacity - used;
            } else {
                // Normal cut offs 1-4: capacity/4
                cutoffCapacity = data.normal_cutoff_capacity;
                cutoffRemain = cutoffCapacity - used;
            }
            
            var isOverCapacity = used > cutoffCapacity;
            
            shiftsHtml += '<div class="cutoff-section">';
            shiftsHtml += '<div class="cutoff-header d-flex justify-content-between">';
            shiftsHtml += '<span>Cut Off ' + i;
            if (i === 5) {
                var ratio = Math.round((used / data.normal_cutoff_capacity) * 100) / 100;
                shiftsHtml += ' <small>(' + ratio + 'x)</small>';
            }
            shiftsHtml += '</span>';
            shiftsHtml += '<div>';
            shiftsHtml += '<span class="badge badge-info badge-sm">Cap: ' + cutoffCapacity + '</span> ';
            shiftsHtml += '<span class="badge badge-' + (isOverCapacity ? 'danger' : 'warning') + ' badge-sm">Used: ' + used + '</span> ';
            shiftsHtml += '<span class="badge badge-' + (isOverCapacity ? 'danger' : 'success') + ' badge-sm">Remain: ' + cutoffRemain.toFixed(2) + '</span>';
            if (isOverCapacity && i === 5) {
                shiftsHtml += ' <span class="badge badge-danger badge-sm"><i class="fas fa-exclamation-triangle"></i> Over Capacity!</span>';
            }
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
        $('#verificationModal').data('normal-cutoff-capacity', data.normal_cutoff_capacity);
        $('#verificationModal').data('cutoff5-capacity', data.cutoff5_capacity);

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
            url: routeUrls.availableAssy,
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
            e.originalEvent.dataTransfer.effectAllowed = 'move';
        });

        $(document).on('dragend', '.assy-item, .available-assy-item', function(e) {
            $(this).removeClass('dragging');
            draggedItem = null;
            dragSource = null;
        });

        $(document).on('dragover', '.cutoff-drop-zone', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('drag-over');
            e.originalEvent.dataTransfer.dropEffect = 'move';
        });

        $(document).on('dragleave', '.cutoff-drop-zone', function(e) {
            $(this).removeClass('drag-over');
        });

        $(document).on('drop', '.cutoff-drop-zone', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
            
            if (draggedItem && !draggedItem.hasClass('dropping')) {
                draggedItem.addClass('dropping'); // Prevent duplicate drops
                
                var newCutOff = $(this).data('cutoff');
                var newShift = $(this).data('shift');
                
                if (dragSource === 'available') {
                    // Create new assy item from available
                    var assyCode = draggedItem.data('assy');
                    var assyDate = draggedItem.data('date');
                    var sourceId = draggedItem.data('id');
                    var sourceQty = draggedItem.find('.assy-qty').text().replace(' pcs', '').trim();
                    
                    var newItem = $('<div class="assy-item" data-id="new_' + Date.now() + '" data-cutoff="' + newCutOff + '" data-shift="' + newShift + '" data-assy="' + assyCode + '" data-source-date="' + assyDate + '" data-source-id="' + sourceId + '" draggable="true">' +
                        '<div class="assy-code">' + assyCode + '</div>' +
                        '<input type="number" class="form-control form-control-sm assy-qty" value="' + sourceQty + '" min="1">' +
                        '</div>');
                    
                    $(this).append(newItem);
                    
                    // Remove from available
                    draggedItem.remove();
                } else {
                    // Move existing item
                    draggedItem.attr('data-cutoff', newCutOff);
                    draggedItem.attr('data-shift', newShift);
                    draggedItem.attr('draggable', 'true');
                    $(this).append(draggedItem);
                }
                
                setTimeout(function() {
                    if (draggedItem) {
                        draggedItem.removeClass('dropping');
                    }
                }, 100);
                
                updateCutOffStats();
                
                // Clear draggedItem to prevent further drops
                draggedItem = null;
                dragSource = null;
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
        var normalCutoffCapacity = $('#verificationModal').data('normal-cutoff-capacity');
        var cutoff5Capacity = $('#verificationModal').data('cutoff5-capacity');
        
        // Update each cut-off
        $('.cutoff-drop-zone').each(function() {
            var cutOffZone = $(this);
            var cutoff = cutOffZone.data('cutoff');
            var used = 0;
            
            cutOffZone.find('.assy-item').each(function() {
                var qty = parseInt($(this).find('.assy-qty').val()) || 0;
                used += qty;
            });
            
            // Determine capacity for this cutoff
            var cutoffCapacity = (cutoff === 5) ? cutoff5Capacity : normalCutoffCapacity;
            var remain = cutoffCapacity - used;
            var isOverCapacity = used > cutoffCapacity;
            
            var cutoffSection = cutOffZone.closest('.cutoff-section');
            var cutoffHeader = cutoffSection.find('.cutoff-header > div');
            
            // Get all badges
            var badges = cutoffHeader.children('.badge');
            
            // Update capacity badge (first badge with "Cap:")
            badges.filter(':contains("Cap:")').text('Cap: ' + cutoffCapacity);
            
            // Update used badge (badge with "Used:")
            var usedBadge = badges.filter(':contains("Used:")');
            usedBadge.removeClass('badge-warning badge-danger').addClass(isOverCapacity ? 'badge-danger' : 'badge-warning');
            usedBadge.text('Used: ' + used);
            
            // Update remain badge (badge with "Remain:")
            var remainBadge = badges.filter(':contains("Remain:")');
            remainBadge.removeClass('badge-success badge-danger').addClass(isOverCapacity ? 'badge-danger' : 'badge-success');
            remainBadge.text('Remain: ' + remain.toFixed(2));
            
            // Update ratio for Cut Off 5
            if (cutoff === 5) {
                var ratio = Math.round((used / normalCutoffCapacity) * 100) / 100;
                var ratioText = cutoffSection.find('.cutoff-header > span small');
                if (ratioText.length) {
                    ratioText.text('(' + ratio + 'x)');
                } else {
                    cutoffSection.find('.cutoff-header > span').append(' <small>(' + ratio + 'x)</small>');
                }
                
                // Show/hide warning badge
                var warningBadge = badges.filter(':contains("Over Capacity")');
                if (isOverCapacity && !warningBadge.length) {
                    cutoffHeader.append(' <span class="badge badge-danger badge-sm"><i class="fas fa-exclamation-triangle"></i> Over Capacity!</span>');
                } else if (!isOverCapacity && warningBadge.length) {
                    warningBadge.remove();
                }
            }
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
                    url: routeUrls.verify,
                    type: 'POST',
                    data: {
                        _token: routeUrls.csrfToken,
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
