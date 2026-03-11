@extends('layouts.master')

@section('title', 'Master Circuit')

@section('breadcrumb')
    <x-page-header menu-code="master_circuit" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <h5 class="card-title mb-0">Master Circuit (Cutting) Data List</h5>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <select class="form-select form-select-sm select2" id="filter_area_id" data-placeholder="- All Area -" style="width: 160px;">
                        <option value="">- All Area -</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}">{{ $area->area }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm select2" id="filter_conveyor_id" data-placeholder="- All Conveyor -" style="width: 180px;">
                        <option value="">- All Conveyor -</option>
                        @foreach($conveyors as $conveyor)
                            <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm select2" id="filter_type" data-placeholder="- All Type -" style="width: 160px;">
                        <option value="">- All Type -</option>
                        <option value="CUTTING">CUTTING</option>
                        <option value="CUTTING_TWIST">CUTTING TWIST</option>
                    </select>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="btn-reset-filter" title="Reset Filter">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                    @if(auth()->user()->hasMenuPermission('master_circuit', 'can_create'))
                        <button type="button" class="btn btn-success btn-sm" id="btn-import">
                            <i class="fa-solid fa-upload"></i> Import
                        </button>
                    @endif
                    @if(auth()->user()->hasMenuPermission('master_circuit', 'can_delete'))
                        <button type="button" class="btn btn-danger btn-sm" id="btn-remove-data" title="Remove Data">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <table id="master-circuit-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="10%">Type</th>
                            <th>Carline</th>
                            <th>Conveyor</th>
                            <th>CCT No</th>
                            <th>CCT Code</th>
                            <th>Shikake</th>
                            <th>Family</th>
                            <th>QTY</th>
                            <th>Machine</th>
                            <th>Sequence</th>
                            <th width="15%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('master_data.master_circuit.import_modal')
    @include('master_data.master_circuit.detail_modal')
    @include('master_data.master_circuit.edit_modal')
    @include('master_data.master_circuit.remove_modal')
@endsection

@section('script')
    <script>
        $(function () {
            // Initialize Select2 for filters
            $('#filter_area_id, #filter_conveyor_id, #filter_type').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: function() {
                    return $(this).data('placeholder') || 'Select...';
                }
            });

            // Reset filter button
            $('#btn-reset-filter').on('click', function () {
                $('#filter_area_id').val('').trigger('change');
                $('#filter_conveyor_id').val('').trigger('change');
                $('#filter_type').val('').trigger('change');
            });

            // Initialize DataTable
            var table = $('#master-circuit-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('master-data.master-circuit.datatable') }}",
                    data: function(d) {
                        d.area_id = $('#filter_area_id').val();
                        d.conveyor_id = $('#filter_conveyor_id').val();
                        d.type = $('#filter_type').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'type_badge', name: 'type', orderable: true, searchable: false },
                    { data: 'carline', name: 'carline' },
                    { data: 'conveyor_name', name: 'conveyor_name', searchable: false },
                    { data: 'cct_no', name: 'cct_no' },
                    { data: 'cct_code', name: 'cct_code' },
                    { data: 'shikake_code', name: 'shikake_code' },
                    { data: 'family', name: 'family' },
                    { data: 'qty', name: 'qty' },
                    { data: 'machine', name: 'machine' },
                    { data: 'sequence', name: 'sequence' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[1, 'asc']]
            });

            // Filter handlers
            $('#filter_area_id, #filter_conveyor_id, #filter_type').on('change', function() {
                table.ajax.reload();
            });

            // Import button handler
            $('#btn-import').click(function() {
                $('#importCircuitModal').modal('show');
            });

            // Initialize Select2 for import modal
            $('#import_area_id, #import_conveyor_id').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#importCircuitModal'),
                allowClear: true
            });

            // Filter conveyors by area in import modal
            $('#import_area_id').on('change', function() {
                var areaId = $(this).val();
                var conveyorSelect = $('#import_conveyor_id');
                
                conveyorSelect.val('').trigger('change');
                
                if (areaId) {
                    conveyorSelect.find('option').each(function() {
                        var option = $(this);
                        if (option.val() !== '') {
                            option.show();
                        }
                    });
                } else {
                    conveyorSelect.find('option').show();
                }
            });

            // Update file input label
            $('#import_file').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName || 'Browse File');
            });

            // Download Template
            $('#btn-download-template-circuit').click(function() {
                window.location.href = "{{ route('master-data.master-circuit.download-template') }}";
            });

            // Submit Import Form
            $('#importCircuitForm').submit(function(e) {
                e.preventDefault();
                $('.error-text, .text-danger').text('');

                var formData = new FormData(this);
                var submitBtn = $('#btn-submit-import');
                
                submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner ti-spin"></i> Importing...');

                $.ajax({
                    url: "{{ route('master-data.master-circuit.import') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#importCircuitModal').modal('hide');
                        $('#importCircuitForm')[0].reset();
                        $('.custom-file-label').html('Browse File');
                        table.ajax.reload();
                        
                        var result = response.data.result;
                        var message = response.message;
                        
                        if (result.errors && result.errors.length > 0) {
                            message += '<br><br><strong>Errors:</strong><br>' + result.errors.slice(0, 5).join('<br>');
                            if (result.errors.length > 5) {
                                message += '<br>... and ' + (result.errors.length - 5) + ' more errors';
                            }
                            Swal.fire({
                                title: 'Import Completed with Warnings',
                                html: message,
                                icon: 'warning'
                            });
                        } else {
                            Swal.fire('Success!', message, 'success');
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.data || xhr.responseJSON.errors;
                            if (typeof errors === 'object') {
                                $.each(errors, function(key, value) {
                                    var errorKey = key.replace('.', '_');
                                    $('.import_' + errorKey + '_error').text(Array.isArray(value) ? value[0] : value);
                                });
                            } else {
                                Swal.fire('Error!', xhr.responseJSON.message || 'Validation failed', 'error');
                            }
                        } else {
                            Swal.fire('Error!', xhr.responseJSON.message || 'Something went wrong', 'error');
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html('<i class="fa-solid fa-upload"></i> Import');
                    }
                });
            });

            // Delete button handler
            $(document).on('click', '.btn-delete', function() {
                var id = $(this).data('id');
                var type = $(this).data('type');
                var cctNo = $(this).data('cct-no');
                var conveyor = $(this).data('conveyor');
                var carline = $(this).data('carline');
                
                Swal.fire({
                    title: 'Delete Circuit Data?',
                    html: `<div style="text-align: left;">
                        <p><strong>Type:</strong> ${type}</p>
                        <p><strong>CCT No:</strong> ${cctNo}</p>
                        <p><strong>Conveyor:</strong> ${conveyor}</p>
                        <p><strong>Carline:</strong> ${carline}</p>
                        <p class="text-danger mt-3">This action cannot be undone!</p>
                    </div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('master-data.master-circuit.destroy', ':id') }}".replace(':id', id),
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                table.ajax.reload();
                                Swal.fire('Deleted!', response.message, 'success');
                            },
                            error: function(xhr) {
                                Swal.fire('Error!', xhr.responseJSON.message || 'Something went wrong', 'error');
                            }
                        });
                    }
                });
            });

            // === VIEW (Read-Only) handler ===
            $('#master-circuit-table').on('click', '.btn-view', function() {
                const id = $(this).data('id');
                $('#detailCircuitModal').modal('show');

                $.ajax({
                    url: "{{ route('master-data.master-circuit.show', ':id') }}".replace(':id', id),
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (!response.success) return;
                        const d = response.data;
                        const v = (val) => val || '-';
                        const typeBadge = (d.type === 'CUTTING_TWIST')
                            ? '<span class="badge bg-warning text-dark">TWS</span>'
                            : '<span class="badge bg-info text-white">CCT</span>';

                        // Info Utama
                        $('#v_type').html(typeBadge);
                        $('#v_conveyor').text(d.conveyor ? d.conveyor.conveyor : '-');
                        $('#v_carline').text(v(d.carline));
                        $('#v_cct_no').text(v(d.cct_no));
                        $('#v_cct_code').text(v(d.cct_code));
                        $('#v_shikake_code').text(v(d.shikake_code));
                        $('#v_family').text(v(d.family));
                        $('#v_qty').text(v(d.qty));
                        $('#v_machine').text(v(d.machine));
                        $('#v_machine_twist').text(v(d.machine_twist));
                        $('#v_memory_twist').text(v(d.memory_twist));
                        $('#v_sequence').text(v(d.sequence));
                        $('#v_sequence_2').text(v(d.sequence_2));
                        $('#v_released_note').text(v(d.released_note));
                        $('#v_cust_no').text(v(d.cust_no));
                        $('#v_kind').text(v(d.kind));
                        $('#v_size').text(v(d.size));
                        $('#v_col').text(v(d.col));
                        $('#v_cl').text(v(d.cl));
                        $('#v_to_store').text(v(d.to_store));
                        $('#v_address').text(v(d.address));
                        $('#v_barcode_mesin').text(v(d.barcode_mesin));
                        $('#v_barcode_navigasi').text(v(d.barcode_navigasi));
                        $('#v_barcode_process').text(v(d.barcode_process));
                        $('#v_barcode_shikake').text(v(d.barcode_shikake));

                        // Terminal 1
                        $('#v_terminal_1').text(v(d.terminal_1));
                        $('#v_note_1').text(v(d.note_1));
                        $('#v_gold_1').text(v(d.gold_1));
                        $('#v_strip_1').text(v(d.strip_1));
                        $('#v_acc_1').text(v(d.acc_1));
                        $('#v_acc_1a').text(v(d.acc_1a));
                        $('#v_tube_1').text(v(d.tube_1));
                        $('#v_mark_1').text(v(d.mark_1));

                        // Terminal 2
                        $('#v_terminal_2').text(v(d.terminal_2));
                        $('#v_note_2').text(v(d.note_2));
                        $('#v_gold_2').text(v(d.gold_2));
                        $('#v_strip_2').text(v(d.strip_2));
                        $('#v_acc_2').text(v(d.acc_2));
                        $('#v_acc_2a').text(v(d.acc_2a));
                        $('#v_tube_2').text(v(d.tube_2));
                        $('#v_mark_2').text(v(d.mark_2));

                        // T fields
                        $('#v_t01').text(v(d.t01));
                        $('#v_t02').text(v(d.t02));
                        $('#v_t03').text(v(d.t03));

                        // Assy
                        if (d.assemblies && d.assemblies.length > 0) {
                            $('#v_assy_list').html(d.assemblies.map(a => '<span class="badge bg-light text-dark me-1 mb-1">' + a.assy + '</span>').join(''));
                        } else {
                            $('#v_assy_list').html('<span class="text-muted">-</span>');
                        }

                        // Drawing (only for CUTTING_TWIST)
                        if (d.type === 'CUTTING_TWIST' && d.image_path) {
                            $('#v_drawing_img').attr('src', '{{ asset("") }}' + d.image_path);
                            $('#v_drawing_container').show();
                        } else {
                            $('#v_drawing_container').hide();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to load data', 'error');
                        $('#detailCircuitModal').modal('hide');
                    }
                });
            });

            // === EDIT handler ===
            function loadEditModal(id) {
                $.ajax({
                    url: "{{ route('master-data.master-circuit.show', ':id') }}".replace(':id', id),
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (!response.success) return;
                        const d = response.data;

                        $('#edit_circuit_id').val(d.id);
                        $('#edit_conveyor').val(d.conveyor ? d.conveyor.conveyor : '');
                        $('#edit_carline').val(d.carline);
                        $('#edit_cct_no').val(d.cct_no);
                        $('#edit_cct_code').val(d.cct_code);
                        $('#edit_shikake_code').val(d.shikake_code);
                        $('#edit_family').val(d.family);
                        $('#edit_qty').val(d.qty);
                        $('#edit_machine').val(d.machine);
                        $('#edit_machine_twist').val(d.machine_twist);
                        $('#edit_memory_twist').val(d.memory_twist);
                        $('#edit_sequence').val(d.sequence);
                        $('#edit_sequence_2').val(d.sequence_2);
                        $('#edit_released_note').val(d.released_note);
                        $('#edit_cust_no').val(d.cust_no);
                        $('#edit_kind').val(d.kind);
                        $('#edit_size').val(d.size);
                        $('#edit_col').val(d.col);
                        $('#edit_cl').val(d.cl);
                        $('#edit_to_store').val(d.to_store);
                        $('#edit_address').val(d.address);

                        // Terminal 1
                        $('#edit_terminal_1').val(d.terminal_1);
                        $('#edit_note_1').val(d.note_1);
                        $('#edit_gold_1').val(d.gold_1);
                        $('#edit_strip_1').val(d.strip_1);
                        $('#edit_acc_1').val(d.acc_1);
                        $('#edit_acc_1a').val(d.acc_1a);
                        $('#edit_tube_1').val(d.tube_1);
                        $('#edit_mark_1').val(d.mark_1);

                        // Terminal 2
                        $('#edit_terminal_2').val(d.terminal_2);
                        $('#edit_note_2').val(d.note_2);
                        $('#edit_gold_2').val(d.gold_2);
                        $('#edit_strip_2').val(d.strip_2);
                        $('#edit_acc_2').val(d.acc_2);
                        $('#edit_acc_2a').val(d.acc_2a);
                        $('#edit_tube_2').val(d.tube_2);
                        $('#edit_mark_2').val(d.mark_2);

                        // Drawing section (only for CUTTING_TWIST)
                        $('#edit_drawing_file').val('');
                        if (d.type === 'CUTTING_TWIST') {
                            $('#edit_drawing_section').show();
                            $('#edit_barcode_twist').val(d.barcode_twist);
                            $('#edit_qrcode_drawing').val(d.qrcode_drawing);
                            if (d.image_path) {
                                $('#edit_drawing_preview').attr('src', '{{ asset("") }}' + d.image_path);
                                $('#edit_drawing_preview_container').show();
                            } else {
                                $('#edit_drawing_preview_container').hide();
                            }
                        } else {
                            $('#edit_drawing_section').hide();
                        }

                        $('#editCircuitModal').modal('show');
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to load data', 'error');
                    }
                });
            }

            $('#master-circuit-table').on('click', '.btn-edit', function() {
                loadEditModal($(this).data('id'));
            });

            // Preview new drawing in edit modal
            $('#edit_drawing_file').on('change', function(e) {
                var file = e.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#edit_drawing_preview').attr('src', e.target.result);
                        $('#edit_drawing_preview_container').show();
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Submit edit form
            $('#editCircuitForm').on('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const id = $('#edit_circuit_id').val();
                formData.append('_method', 'PUT');

                var submitBtn = $('#btn-submit-edit');
                submitBtn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i> Saving...');

                $.ajax({
                    url: "{{ route('master-data.master-circuit.update', ':id') }}".replace(':id', id),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#editCircuitModal').modal('hide');
                            table.ajax.reload();
                            Swal.fire('Success!', response.message, 'success');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to update data', 'error');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Save');
                    }
                });
            });

            // Remove Data button click
            $('#btn-remove-data').click(function() {
                $('#removeDataModal').modal('show');
                // Re-initialize Select2 after modal is shown
                setTimeout(function() {
                    $('#remove_conveyor_id').select2({
                        theme: 'bootstrap-5',
                        dropdownParent: $('#removeDataModal')
                    });
                }, 200);
            });

            // Remove Data form submission
            $('#removeDataForm').submit(function(e) {
                e.preventDefault();
                
                var conveyorId = $('#remove_conveyor_id').val();
                var conveyorName = $('#remove_conveyor_id option:selected').text();
                
                if (!conveyorId) {
                    Swal.fire('Warning!', 'Please select a conveyor', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Are you sure?',
                    html: `<p>Semua data Circuit pada conveyor <strong>${conveyorName}</strong> akan dihapus permanen.</p><p class="text-danger mb-0">Tindakan ini tidak dapat dibatalkan!</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete all!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('master-data.master-circuit.remove-by-conveyor') }}",
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                conveyor_id: conveyorId
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('#removeDataModal').modal('hide');
                                    table.ajax.reload();
                                    Swal.fire('Deleted!', response.message, 'success');
                                    $('#remove_conveyor_id').val('').trigger('change');
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to delete data', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection