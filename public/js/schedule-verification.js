/**
 * Schedule Verification JavaScript
 * Last Updated: 2026-02-12 - Panel 8:4 with H+10 date selector
 * Version: 2.0.0
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

// Global table variable
var scheduleVerificationTable;

$(function () {
    // Initialize Select2
    $('#filter_conveyor_id').select2({
        allowClear: true,
        placeholder: '- All Conveyor -'
    });

    // Initialize date range picker
    var startDate = moment();
    var endDate = moment().add(10, 'days');

    $('#filter_dates').daterangepicker({
        startDate: startDate,
        endDate: endDate,
        locale: {
            format: 'DD-MM-YYYY'
        }
    });

    // DataTable
    scheduleVerificationTable = $('#schedule-verification-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: routeUrls.datatable,
            data: function(d) {
                var dates = $('#filter_dates').data('daterangepicker');
                d.start_date = dates.startDate.format('YYYY-MM-DD');
                d.end_date = dates.endDate.format('YYYY-MM-DD');
                d.conveyor_id = $('#filter_conveyor_id').val();
                d.status = $('#filter_status').val();
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

    // Auto reload when filter changes
    $('#filter_conveyor_id, #filter_status').on('change', function() {
        scheduleVerificationTable.ajax.reload();
    });

    // Auto reload when date range changes
    $('#filter_dates').on('apply.daterangepicker', function() {
        scheduleVerificationTable.ajax.reload();
    });

    // Reset button
    $('#btn-reset').click(function() {
        $('#filter_conveyor_id').val('').trigger('change');
        $('#filter_status').val('');
        $('#filter_dates').data('daterangepicker').setStartDate(moment());
        $('#filter_dates').data('daterangepicker').setEndDate(moment().add(10, 'days'));
        scheduleVerificationTable.ajax.reload();
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
                        scheduleVerificationTable.ajax.reload();
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
        
        // Set target shift label
        $('#target-shift-label').text(data.shift + ' (' + moment(data.date).format('DD MMM YYYY') + ')');

        // Build shift with cut-offs
        var shiftsHtml = '';
        var maxCutOff = 5; // Always show up to Cut Off 5
        
        // Calculate total used for this shift
        var totalUsed = data.cut_offs.reduce((sum, co) => {
            return sum + co.items.reduce((s, item) => s + parseInt(item.qty), 0);
        }, 0);
        var remain = data.capacity - totalUsed;
        
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
            
            shiftsHtml += '<div class="cutoff-section mb-2">';
            shiftsHtml += '<div class="cutoff-header d-flex justify-content-between align-items-center p-2">';
            shiftsHtml += '<span class="cutoff-title">Cut Off ' + i;
            if (i === 5) {
                shiftsHtml += ' <small>(0.875x)</small>';
            }
            shiftsHtml += '</span>';
            shiftsHtml += '<div class="cutoff-badges">';
            shiftsHtml += '<span class="badge bg-primary badge-sm">Cap: ' + cutoffCapacity + '</span> ';
            shiftsHtml += '<span class="badge bg-danger badge-sm">Used: ' + used + '</span> ';
            shiftsHtml += '<span class="badge bg-success badge-sm">Rem: ' + cutoffRemain.toFixed(2) + '</span>';
            shiftsHtml += '</div>';
            shiftsHtml += '</div>';
            shiftsHtml += '<div class="cutoff-drop-zone target-list" data-cutoff="' + i + '" data-shift="' + data.shift + '">';
            
            items.forEach(function(item) {
                shiftsHtml += '<div class="cutoff-item item-target" ';
                shiftsHtml += 'data-id="' + item.id + '" ';
                shiftsHtml += 'data-cutoff="' + item.cutoff + '" ';
                shiftsHtml += 'data-shift="' + data.shift + '" ';
                shiftsHtml += 'data-listing-id="' + (item.listing_id || 0) + '" ';
                shiftsHtml += 'data-assy="' + (item.assy || '') + '" ';
                shiftsHtml += 'data-assycode="' + (item.assycode || '') + '" ';
                shiftsHtml += 'data-seq="' + (item.seq || 0) + '" ';
                shiftsHtml += 'data-plt="' + (item.plt || 0) + '" ';
                shiftsHtml += 'data-mode="' + (item.mode || 0) + '" ';
                shiftsHtml += 'data-snp="' + (item.snp || 0) + '" ';
                shiftsHtml += 'data-snpa="' + (item.snpa || 0) + '" ';
                shiftsHtml += 'data-type="current" ';
                shiftsHtml += 'data-is-transfer="0"';
                if (!readOnly) {
                    shiftsHtml += ' draggable="true"';
                }
                shiftsHtml += '>';
                shiftsHtml += '<div class="d-flex justify-content-between align-items-center">';
                shiftsHtml += '<div class="item-head flex-grow-1">';
                shiftsHtml += '<span class="item-code">' + (item.assycode || '') + '</span> ';
                shiftsHtml += '<span class="item-name">' + item.assy + '</span>';
                shiftsHtml += '</div>';
                shiftsHtml += '<input type="number" class="form-control form-control-sm w-60 text-end vs-item-qty ms-2" value="' + item.qty + '" min="1"';
                if (readOnly) {
                    shiftsHtml += ' readonly disabled';
                }
                shiftsHtml += '>';
                shiftsHtml += '</div>';
                shiftsHtml += '</div>';
            });
            
            shiftsHtml += '</div></div>';
        }
        
        $('#shifts-container').html(shiftsHtml);

        // Store current data
        $('#verificationModal').data('conveyor-id', data.conveyor_id);
        $('#verificationModal').data('conveyor', data.conveyor);
        $('#verificationModal').data('date', data.date);
        $('#verificationModal').data('shift', data.shift);
        $('#verificationModal').data('capacity', data.capacity);
        $('#verificationModal').data('normal-cutoff-capacity', data.normal_cutoff_capacity);
        $('#verificationModal').data('cutoff5-capacity', data.cutoff5_capacity);

        // Show/hide buttons based on read-only mode
        if (readOnly) {
            $('#btn-verify-schedule').hide();
            $('.col-md-4').hide(); // Hide source panel
            $('.col-md-8').removeClass('col-md-8').addClass('col-md-12'); // Make target full width
        } else {
            $('#btn-verify-schedule').show();
            $('.col-md-4').show();
            if ($('.col-md-12').length && !$('.col-md-8').length) {
                $('.col-md-12').removeClass('col-md-12').addClass('col-md-8');
            }
            
            // Initialize source date options (H to H+10)
            initializeSourceDateOptions(data.date, data.conveyor_id, data.shift);
        }

        // Initialize drag and drop
        if (!readOnly) {
            initializeDragDrop();
        }

        // Show modal
        $('#verificationModal').modal('show');
    }

    // Initialize source date options from H to H+10
    function initializeSourceDateOptions(currentDate, conveyorId, currentShift) {
        var $dateSelect = $('#source-date');
        $dateSelect.html('<option value="">-- Pilih Tanggal --</option>');
        
        var startDate = moment(currentDate);
        
        // Jika shift target adalah Shift 2 (shift terakhir), mulai dari H+1
        // Karena tidak mungkin lagi mengambil data dari tanggal yang sama
        var startIndex = (parseInt(currentShift) === 2) ? 1 : 0;
        
        // Generate dates from H (or H+1) to H+10
        for (var i = startIndex; i <= 10; i++) {
            var date = moment(startDate).add(i, 'days');
            var dateStr = date.format('YYYY-MM-DD');
            var label = date.format('DD MMM YYYY');
            var isCurrent = (i === 0) ? ' (Current)' : '';
            
            $dateSelect.append('<option value="' + dateStr + '" data-current="' + (i === 0 ? '1' : '0') + '">' + 
                              label + isCurrent + '</option>');
        }
        
        // Store conveyor and shift info
        $dateSelect.data('conveyor-id', conveyorId);
        $dateSelect.data('current-date', currentDate);
        $dateSelect.data('current-shift', currentShift);
        
        // Event handler for date change
        $dateSelect.off('change').on('change', function() {
            var selectedDate = $(this).val();
            if (selectedDate) {
                // Update shift dropdown
                updateShiftOptions(selectedDate, currentDate, currentShift);
                // Load source items
                loadSourceItems();
            } else {
                $('#source-shift').html('<option value="all">Semua Shift</option>');
                $('#source-items-list').html('');
                $('#source-info').html('<span class="text-muted">Pilih tanggal untuk memuat data sumber</span>');
            }
        });
        
        // Event handler for shift change
        $('#source-shift').off('change').on('change', function() {
            loadSourceItems();
        });
    }

    // Update shift options based on selected date
    function updateShiftOptions(selectedDate, currentDate, currentShift) {
        var $shiftSelect = $('#source-shift');
        $shiftSelect.html('');
        
        var isSameDate = (selectedDate === currentDate);
        
        // Jika tanggal berbeda dengan target, tampilkan "Semua Shift"
        if (!isSameDate) {
            $shiftSelect.append('<option value="all">Semua Shift</option>');
        }
        
        // Add shift options (1 and 2)
        for (var i = 1; i <= 2; i++) {
            // Skip current shift if same date
            if (isSameDate && i === parseInt(currentShift)) {
                continue;
            }
            $shiftSelect.append('<option value="' + i + '">Shift ' + i + '</option>');
        }
        
        // Auto-select first available option
        if ($shiftSelect.find('option').length > 0) {
            $shiftSelect.val($shiftSelect.find('option:first').val());
        }
    }

    // Load source items based on selected date and shift
    function loadSourceItems() {
        var date = $('#source-date').val();
        var shift = $('#source-shift').val();
        var conveyorId = $('#source-date').data('conveyor-id');
        var currentDate = $('#source-date').data('current-date');
        var currentShift = $('#source-date').data('current-shift');
        
        var $list = $('#source-items-list');
        var $info = $('#source-info');
        
        if (!date) {
            $list.html('');
            $info.html('<span class="text-muted">Pilih tanggal untuk memuat data sumber</span>');
            return;
        }
        
        if (!shift) {
            shift = 'all';
        }
        
        $list.html('<div class="text-center p-3 text-primary"><i class="fa-solid fa-spinner fa-spin"></i> <strong>Memuat...</strong></div>');
        $info.html('<span class="text-primary fw-bold">Memuat data...</span>');
        
        $.ajax({
            url: routeUrls.availableAssy,
            type: 'GET',
            data: {
                conveyor_id: conveyorId,
                date: date,
                shift: shift
            },
            success: function(response) {
                $list.html('');
                
                console.log('=== AJAX Response ===');
                console.log('URL:', routeUrls.availableAssy);
                console.log('Params:', { conveyor_id: conveyorId, date: date, shift: shift });
                console.log('Response:', response);
                
                if (response.success && response.data && response.data.length > 0) {
                    var totalItems = 0;
                    var html = '';
                    var allItems = [];
                    
                    console.log('Response data:', response.data);
                    
                    // Collect all items from all cutoffs first
                    response.data.forEach(function(cutoffData) {
                        if (cutoffData.items && cutoffData.items.length > 0) {
                            cutoffData.items.forEach(function(item) {
                                console.log('Processing item:', item.id, item.assy, 'Shift', item.shift, 'CO' + cutoffData.cutoff, 
                                           'verified_at:', item.verified_at, 'is_lock:', item.is_lock);
                                
                                // CRITICAL: Skip verified or locked items
                                // Check multiple conditions for verified_at
                                var isVerified = item.verified_at && 
                                                item.verified_at !== null && 
                                                item.verified_at !== '' && 
                                                item.verified_at !== '0000-00-00 00:00:00';
                                var isLocked = item.is_lock == 1 || item.is_lock === '1' || item.is_lock === true;
                                
                                if (isVerified || isLocked) {
                                    console.error('❌ SKIPPED: VERIFIED/LOCKED DATA', {
                                        id: item.id,
                                        assy: item.assy,
                                        shift: item.shift,
                                        cutoff: cutoffData.cutoff,
                                        verified_at: item.verified_at,
                                        is_lock: item.is_lock,
                                        reason: isVerified ? 'VERIFIED' : 'LOCKED'
                                    });
                                    return;
                                }
                                
                                // Skip if same date AND same shift
                                var itemShift = parseInt(item.shift);
                                if (date === currentDate && itemShift === parseInt(currentShift)) {
                                    console.warn('⚠️ SKIPPED: Same date AND shift', {
                                        id: item.id,
                                        assy: item.assy,
                                        shift: itemShift,
                                        reason: 'Same as target (' + currentDate + ' Shift ' + currentShift + ')'
                                    });
                                    return;
                                }
                                
                                console.log('✅ ACCEPTED:', item.id, item.assy, 'Shift', itemShift, 'CO' + cutoffData.cutoff);
                                
                                allItems.push({
                                    item: item,
                                    cutoff: cutoffData.cutoff,
                                    shift: itemShift
                                });
                            });
                        }
                    });
                    
                    // Sort items: shift -> cutoff -> listing_id (urutan dari listing sumber)
                    allItems.sort(function(a, b) {
                        if (a.shift !== b.shift) return a.shift - b.shift;
                        if (a.cutoff !== b.cutoff) return a.cutoff - b.cutoff;
                        return (parseInt(a.item.listing_id) || 0) - (parseInt(b.item.listing_id) || 0);
                    });
                    
                    // Build HTML from sorted items
                    allItems.forEach(function(obj) {
                        var item = obj.item;
                        var cutoffData = { cutoff: obj.cutoff };
                                
                        html += '<div class="cutoff-item item-source" ';
                        html += 'data-id="' + item.id + '" ';
                        html += 'data-source-id="' + item.id + '" ';
                        html += 'data-source-date="' + date + '" ';
                        html += 'data-source-shift="' + obj.shift + '" ';
                        html += 'data-source-cutoff="' + obj.cutoff + '" ';
                        html += 'data-assy="' + item.assy + '" ';
                        html += 'data-assycode="' + (item.assycode || '') + '" ';
                        html += 'data-listing-id="' + (item.listing_id || 0) + '" ';
                        html += 'data-seq="' + (item.seq || 0) + '" ';
                        html += 'data-plt="' + (item.plt || 0) + '" ';
                        html += 'data-mode="' + (item.mode || 0) + '" ';
                        html += 'data-snp="' + (item.snp || 0) + '" ';
                        html += 'data-snpa="' + (item.snpa || 0) + '" ';
                        html += 'data-is-transfer="1" ';
                        html += 'data-max-qty="' + item.qty + '" ';
                        html += 'draggable="true">';
                        html += '<div class="d-flex justify-content-between align-items-center">';
                        html += '<div class="item-head flex-grow-1">';
                        html += '<span class="item-code">' + (item.assycode || '') + '</span> ';
                        html += '<span class="item-name">' + item.assy + '</span>';
                        html += '<div class="source-info-badges mt-1">';
                        html += '<span class="badge bg-info badge-sm">' + moment(date).format('DD MMM') + '</span> ';
                        html += '<span class="badge bg-warning badge-sm">Shift ' + obj.shift + '</span> ';
                        html += '<span class="badge bg-primary badge-sm">CO' + obj.cutoff + '</span>';
                        html += '</div>';
                        html += '</div>';
                        html += '<input type="number" class="form-control form-control-sm w-60 text-end vs-item-qty ms-2" ';
                        html += 'value="' + item.qty + '" min="1" max="' + item.qty + '">';
                        html += '</div>';
                        html += '</div>';
                        
                        totalItems++;
                    });
                    
                    if (totalItems > 0) {
                        $list.html(html);
                        $info.html('<strong>Ditemukan ' + totalItems + ' item.</strong> Drag ke target cutoff.');
                    } else {
                        $info.html('<span class="text-warning">Tidak ada item yang tersedia dari shift lain.</span>');
                    }
                } else {
                    $info.html('<span class="text-info">Tidak ada item yang tersedia (sudah verified atau kosong).</span>');
                }
            },
            error: function(xhr) {
                $list.html('');
                $info.html('<span class="text-danger">Gagal memuat data.</span>');
                Swal.fire('Error!', 'Gagal memuat data sumber', 'error');
            }
        });
    }

    // Initialize drag and drop
    function initializeDragDrop() {
        var draggedItem = null;
        var dragSource = null;

        // Drag start event untuk cutoff-item
        $(document).on('dragstart', '.cutoff-item', function(e) {
            draggedItem = $(this);
            dragSource = $(this).hasClass('item-source') ? 'source' : 'target';
            $(this).addClass('dragging');
            e.originalEvent.dataTransfer.effectAllowed = 'move';
        });

        // Drag end event
        $(document).on('dragend', '.cutoff-item', function(e) {
            $(this).removeClass('dragging');
            $('.cutoff-drop-zone').removeClass('drag-over');
            draggedItem = null;
            dragSource = null;
        });

        // Drag over cutoff drop zone
        $(document).on('dragover', '.cutoff-drop-zone', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('drag-over');
            e.originalEvent.dataTransfer.dropEffect = 'move';
        });

        // Drag leave cutoff drop zone
        $(document).on('dragleave', '.cutoff-drop-zone', function(e) {
            $(this).removeClass('drag-over');
        });

        // Drop on cutoff drop zone
        $(document).on('drop', '.cutoff-drop-zone', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
            
            if (draggedItem && !draggedItem.hasClass('dropping')) {
                draggedItem.addClass('dropping'); // Prevent duplicate drops
                
                var newCutOff = $(this).data('cutoff');
                var newShift = $(this).data('shift');
                
                if (dragSource === 'source') {
                    // Move from source to target
                    // Update item attributes
                    draggedItem.attr('data-cutoff', newCutOff);
                    draggedItem.attr('data-shift', newShift);
                    
                    // Remove source classes and add transfer class
                    draggedItem.removeClass('item-source').addClass('item-target is-transfer');
                    
                    // Move to target zone
                    $(this).append(draggedItem);
                    
                } else {
                    // Move within target zones
                    draggedItem.attr('data-cutoff', newCutOff);
                    draggedItem.attr('data-shift', newShift);
                    $(this).append(draggedItem);
                }
                
                setTimeout(function() {
                    if (draggedItem) {
                        draggedItem.removeClass('dropping');
                    }
                }, 100);
                
                recomputeAll();
                
                // Clear draggedItem
                draggedItem = null;
                dragSource = null;
            }
        });

        // Allow dragging back to source list (to remove from target)
        $(document).on('dragover', '#source-items-list', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });

        $(document).on('dragleave', '#source-items-list', function(e) {
            $(this).removeClass('drag-over');
        });

        $(document).on('drop', '#source-items-list', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            
            if (draggedItem && dragSource === 'target') {
                var isTransfer = draggedItem.attr('data-is-transfer') === '1';
                
                if (isTransfer) {
                    // Can return to source - restore item-source class
                    draggedItem.removeClass('item-target is-transfer').addClass('item-source');
                    $(this).append(draggedItem);
                } else {
                    // Original target item - just remove it
                    draggedItem.remove();
                }
                
                recomputeAll();
            }
        });

        // Update stats when quantity changes
        $(document).on('input change', '.vs-item-qty', function() {
            var cutoffZone = $(this).closest('.cutoff-drop-zone');
            if (cutoffZone.length) {
                recompute(cutoffZone[0]);
            }
        });
    }

    // Recompute capacity for a specific cutoff
    function recompute(el) {
        var $el = $(el);
        var cutoff = $el.data('cutoff');
        var capacity;
        
        if (cutoff === 5) {
            capacity = parseFloat($('#verificationModal').data('cutoff5-capacity')) || 0;
        } else {
            capacity = parseFloat($('#verificationModal').data('normal-cutoff-capacity')) || 0;
        }
        
        var sum = 0;
        $el.find('.vs-item-qty').each(function() {
            sum += (parseInt($(this).val() || 0, 10) || 0);
        });
        
        // Update badges in header
        var $section = $el.closest('.cutoff-section');
        $section.find('.cutoff-badges .bg-danger').text('Used: ' + sum);
        $section.find('.cutoff-badges .bg-success').text('Rem: ' + (capacity - sum).toFixed(2));
        
        // Add over-capacity warning
        if (sum > capacity) {
            $el.addClass('over-capacity');
        } else {
            $el.removeClass('over-capacity');
        }
    }

    // Recompute all cutoffs
    function recomputeAll() {
        $('.cutoff-drop-zone').each(function() {
            recompute(this);
        });
    }

    // Update cut-off stats (legacy function name for compatibility)
    function updateCutOffStats() {
        recomputeAll();
    }

    // Verify schedule button
    $('#btn-verify-schedule').click(function() {
        var conveyorId = $('#verificationModal').data('conveyor-id');
        var date = $('#verificationModal').data('date');
        var shift = $('#verificationModal').data('shift');

        // Collect all cutoffs data from the modal
        var cutoffs = [];
        var transferred = []; // Track transferred items separately
        
        $('#shifts-container .cutoff-drop-zone').each(function() {
            var cutoffNumber = $(this).data('cutoff');
            var items = [];
            
            $(this).find('.cutoff-item').each(function() {
                var $item = $(this);
                var itemData = $item.data();
                var qty = parseInt($item.find('.vs-item-qty').val()) || 0;
                var isTransfer = $item.attr('data-is-transfer') === '1';
                
                if (qty > 0) {
                    var itemObj = {
                        id: itemData.id || 0,
                        listing_id: itemData.listingId || 0,
                        assy: itemData.assy || '',
                        assycode: itemData.assycode || '',
                        qty: qty,
                        seq: itemData.seq || 0,
                        plt: itemData.plt || 0,
                        mode: itemData.mode || 0,
                        snp: itemData.snp || 0,
                        snpa: itemData.snpa || 0,
                        type: itemData.type || 'current'
                    };
                    
                    // If this is a transferred item, add to transferred array
                    if (isTransfer && itemData.sourceId) {
                        transferred.push({
                            source_id: itemData.sourceId,
                            source_date: itemData.sourceDate,
                            source_shift: itemData.sourceShift,
                            target_cutoff: cutoffNumber,
                            qty: qty
                        });
                        itemObj.source_id = itemData.sourceId;
                        itemObj.source_date = itemData.sourceDate;
                    }
                    
                    items.push(itemObj);
                }
            });
            
            cutoffs.push({
                cutoff: cutoffNumber,
                items: items
            });
        });

        Swal.fire({
            title: 'Verify This Schedule?',
            html: '<p>This will save any changes and lock the schedule for:</p>' +
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
                    contentType: 'application/json',
                    headers: {
                        'X-CSRF-TOKEN': routeUrls.csrfToken
                    },
                    data: JSON.stringify({
                        conveyor_id: conveyorId,
                        date: date,
                        shift: shift,
                        cutoffs: cutoffs,
                        transferred: transferred
                    }),
                    beforeSend: function() {
                        $('#btn-verify-schedule').prop('disabled', true)
                            .html('<i class="fas fa-spinner fa-spin"></i> Verifying...');
                    },
                    success: function(response) {
                        $('#verificationModal').modal('hide');
                        scheduleVerificationTable.ajax.reload();
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
