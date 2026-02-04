@extends('layouts.master')

@section('title', 'Master Circuit')

@section('breadcrumb')
    <x-page-header menu-code="master_circuit" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Master Circuit (Cutting) Data List</h5>
                <div class="card-tools float-end">
                    @if(auth()->user()->hasMenuPermission('master_circuit', 'can_create'))
                        <button type="button" class="btn btn-primary btn-sm" id="btn-import">
                            <i class="fa-solid fa-upload"></i> Import/Upload Circuit
                        </button>
                    @endif
                    @if(auth()->user()->hasMenuPermission('master_circuit', 'can_delete'))
                        <button type="button" class="btn btn-danger btn-sm" id="btn-remove-data">
                            <i class="fa-solid fa-trash"></i> Remove Data
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="filter_area_id" class="form-label">Area * :</label>
                        <select class="form-select select2" id="filter_area_id" style="width: 100%;">
                            <option value="">- All Area -</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->area }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter_conveyor_id" class="form-label">Conveyor * :</label>
                        <select class="form-select select2" id="filter_conveyor_id" style="width: 100%;">
                            <option value="">- All Conveyor -</option>
                            @foreach($conveyors as $conveyor)
                                <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filter_type" class="form-label">Type :</label>
                        <select class="form-select select2" id="filter_type" style="width: 100%;">
                            <option value="">- All Type -</option>
                            <option value="CUTTING">CUTTING</option>
                            <option value="CUTTING_TWIST">CUTTING TWIST</option>
                        </select>
                    </div>
                </div>

                <table id="master-circuit-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Type</th>
                            <th>Carline</th>
                            <th>Conveyor</th>
                            <th>CCT No</th>
                            <th>Family</th>
                            <th>QTY</th>
                            <th>Machine</th>
                            <th>Sequence</th>
                            <th width="12%">Action</th>
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
                    { data: 'type', name: 'type' },
                    { data: 'carline', name: 'carline' },
                    { data: 'conveyor_name', name: 'conveyor_name' },
                    { data: 'cct_no', name: 'cct_no' },
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
                var name = $(this).data('name');
                var barcode = $(this).data('barcode');
                
                Swal.fire({
                    title: 'Delete Circuit Data?',
                    html: `<div style="text-align: left;">
                        <p><strong>Machine Sequence:</strong> ${name}</p>
                        <p><strong>Barcode Kanban:</strong> ${barcode}</p>
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

            // Toggle modal CUTTING_TWIST fields based on type
            function toggleModalCuttingTwistFields() {
                var type = $('#modal_circuit_type').val();
                if (type === 'CUTTING_TWIST') {
                    $('.modal-cutting-twist-fields').show();
                    $('.modal-cutting-only-field').hide();
                } else {
                    $('.modal-cutting-twist-fields').hide();
                    $('.modal-cutting-only-field').show();
                }
            }

            // On modal type change
            $('#modal_circuit_type').on('change', toggleModalCuttingTwistFields);

            // Handle View button click
            $('#master-circuit-table').on('click', '.btn-view', function() {
                const id = $(this).data('id');

                // Show modal
                $('#detailCircuitModal').modal('show');

                // Fetch data via AJAX
                $.ajax({
                    url: "{{ route('master-data.master-circuit.show', ':id') }}".replace(':id', id),
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            console.log('Circuit data:', data);

                            // Populate form fields
                            $('#circuit_id').val(data.id);
                            $('#modal_circuit_type').val(data.type || 'CUTTING');
                            $('#conveyor').val(data.conveyor ? data.conveyor.conveyor : '');
                            $('#carline').val(data.carline);
                            $('#cct_no').val(data.cct_no);
                            $('#family').val(data.family);
                            $('#qty').val(data.qty);
                            $('#machine').val(data.machine);
                            $('#sequence').val(data.sequence);
                            $('#released_note').val(data.released_note);
                            $('#cust_no').val(data.cust_no);
                            $('#barcode_mesin').val(data.barcode_mesin);
                            $('#address').val(data.address);
                            $('#cct_code').val(data.cct_code);
                            $('#kind').val(data.kind);
                            $('#size').val(data.size);
                            $('#col').val(data.col);
                            $('#cl').val(data.cl);

                            // CUTTING_TWIST specific fields
                            $('#machine_twist').val(data.machine_twist);
                            $('#sequence_2').val(data.sequence_2);
                            $('#barcode_navigasi').val(data.barcode_navigasi);
                            $('#barcode_process').val(data.barcode_process);
                            $('#barcode_shikake').val(data.barcode_shikake);
                            $('#to_store').val(data.to_store);

                            // Toggle fields based on type
                            toggleModalCuttingTwistFields();

                            // Terminal 1 fields
                            $('#terminal_1').val(data.terminal_1);
                            $('#note_1').val(data.note_1);
                            $('#gold_1').val(data.gold_1);
                            $('#strip_1').val(data.strip_1);
                            $('#acc_1').val(data.acc_1);
                            $('#acc_1a').val(data.acc_1a);
                            $('#tube_1').val(data.tube_1);
                            $('#mark_1').val(data.mark_1);
                            $('#remark_1').val(data.remark_1);

                            // Terminal 2 fields
                            $('#terminal_2').val(data.terminal_2);
                            $('#note_2').val(data.note_2);
                            $('#gold_2').val(data.gold_2);
                            $('#strip_2').val(data.strip_2);
                            $('#acc_2').val(data.acc_2);
                            $('#acc_2a').val(data.acc_2a);
                            $('#tube_2').val(data.tube_2);
                            $('#mark_2').val(data.mark_2);
                            $('#remark_2').val(data.remark_2);

                            // T fields
                            $('#ta').val(data.ta);
                            $('#tb').val(data.tb);
                            for(let i = 1; i <= 6; i++) {
                                const fieldName = 't' + String(i).padStart(2, '0');
                                $('#' + fieldName).val(data[fieldName]);
                            }

                            // Image preview
                            if (data.image_path) {
                                $('#imagePreview').attr('src', '{{ asset("") }}' + data.image_path);
                                $('#imagePreviewContainer').show();
                            } else {
                                $('#imagePreviewContainer').hide();
                            }

                            // Assy list
                            if (data.assemblies && data.assemblies.length > 0) {
                                const assyText = data.assemblies.map(a => a.assy).join(', ');
                                $('#assyList').html('<p class="mb-0 small">' + assyText + '</p>');
                            } else {
                                $('#assyList').html('<p class="text-muted mb-0 small">No assembly data</p>');
                            }
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to load data', 'error');
                        $('#detailCircuitModal').modal('hide');
                    }
                });
            });

            // Handle image preview on file select
            $('#imageInput').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#imagePreview').attr('src', e.target.result);
                        $('#imagePreviewContainer').show();
                    }
                    reader.readAsDataURL(file);
                }
            });

            // Handle form submission
            $('#circuitDetailForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const id = $('#circuit_id').val();
                formData.append('_method', 'PUT');
                
                // Disable form inputs during upload
                $('#circuitDetailForm input, #circuitDetailForm textarea, #circuitDetailForm select').prop('disabled', true);
                $('#detailCircuitModal .btn-primary').prop('disabled', true).html('<i class="fa-solid fa-spinner ti-spin"></i> Saving...');
                
                $.ajax({
                    url: "{{ route('master-data.master-circuit.update', ':id') }}".replace(':id', id),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#detailCircuitModal').modal('hide');
                            table.ajax.reload();
                            Swal.fire('Success!', response.message, 'success');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to update data', 'error');
                    },
                    complete: function() {
                        // Re-enable form inputs
                        $('#circuitDetailForm input, #circuitDetailForm textarea, #circuitDetailForm select').prop('disabled', false);
                        $('#detailCircuitModal .btn-primary').prop('disabled', false).html('<i class="fa-solid fa-floppy-disk"></i> Save');
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
                    html: `You are about to delete all Circuit data for:<br><strong>${conveyorName}</strong><br><br>This action cannot be undone!`,
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