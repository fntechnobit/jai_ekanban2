@extends('layouts.master')

@section('title', 'Shikake Data')

@section('breadcrumb')
    <x-page-header menu-code="master_shikake" />
@endsection

@section('content')
    <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Shikake Data List</h3>
                    <div class="card-tools">
                        @if(auth()->user()->hasMenuPermission('master_shikake', 'can_create'))
                            <button type="button" class="btn btn-primary btn-sm" id="btn-import">
                                <i class="fa-solid fa-upload"></i> Import/Upload Shikake
                            </button>
                        @endif
                        @if(auth()->user()->hasMenuPermission('master_shikake', 'can_delete'))
                            <button type="button" class="btn btn-danger btn-sm" id="btn-remove-data">
                                <i class="fa-solid fa-trash"></i> Remove Data
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="filter_area">Area * :</label>
                            <select class="form-select select2" id="filter_area" style="width: 100%;">
                                <option value="">- All Area -</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}">{{ $area->area }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="filter_conveyor">Conveyor * :</label>
                            <select class="form-select select2" id="filter_conveyor" style="width: 100%;">
                                <option value="">- All Conveyor -</option>
                                @foreach($conveyors as $conveyor)
                                    <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <table id="master-shikake-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Conveyor</th>
                                <th>Shikake Number</th>
                                <th>Barcode</th>
                                <th>Family</th>
                                <th>Process</th>
                                <th>Qty.</th>
                                <th width="12%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('master_data.master_shikake.import_modal')
    @include('master_data.master_shikake.detail_modal')
@endsection

@section('script')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(function () {
            // Initialize Select2 for filters
            $('#filter_area, #filter_conveyor').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: function() {
                    return $(this).data('placeholder') || 'Select...';
                }
            });

            // DataTable
            var table = $('#master-shikake-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('master-data.master-shikake.datatable') }}",
                    data: function(d) {
                        d.area_id = $('#filter_area').val();
                        d.conveyor_id = $('#filter_conveyor').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'conveyor_name', name: 'conveyor' },
                    { data: 'shikake_no', name: 'shikake_no' },
                    { data: 'barcode_kanban', name: 'barcode_kanban' },
                    { data: 'family', name: 'family' },
                    { data: 'process', name: 'barcode_proses' },
                    { data: 'qty', name: 'qty' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]
            });

            // Filter change events
            $('#filter_area, #filter_conveyor').on('change', function() {
                table.ajax.reload();
            });

            // Import Button
            $('#btn-import').click(function () {
                $('#importShikakeModal').modal('show');
            });

            // Initialize Select2 for import modal
            $('#import_area_id, #import_conveyor_id').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#importShikakeModal'),
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
            $('#btn-download-template-shikake').click(function() {
                window.location.href = "{{ route('master-data.master-shikake.download-template') }}";
            });

            // Submit Import Form
            $('#importShikakeForm').submit(function(e) {
                e.preventDefault();
                $('.error-text, .text-danger').text('');

                var formData = new FormData(this);
                var submitBtn = $('#btn-submit-import-shikake');
                
                submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner ti-spin"></i> Importing...');

                $.ajax({
                    url: "{{ route('master-data.master-shikake.import') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#importShikakeModal').modal('hide');
                        $('#importShikakeForm')[0].reset();
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

            // Delete Shikake
            $(document).on('click', '.btn-delete', function () {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var barcode = $(this).data('barcode');

                Swal.fire({
                    title: 'Delete Shikake Data?',
                    html: `<div style="text-align: left;">
                        <p><strong>Shikake Number:</strong> ${name}</p>
                        <p><strong>Barcode:</strong> ${barcode}</p>
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
                            url: "{{ route('master-data.master-shikake.index') }}/" + id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function (response) {
                                table.ajax.reload();
                                Swal.fire('Deleted!', response.message, 'success');
                            },
                            error: function (xhr) {
                                Swal.fire('Error!', xhr.responseJSON.message || 'Failed to delete shikake', 'error');
                            }
                        });
                    }
                });
            });

            // Handle View button click
            $('#master-shikake-table').on('click', '.btn-view', function() {
                const id = $(this).data('id');
                
                // Show modal
                $('#detailShikakeModal').modal('show');
                
                // Fetch data via AJAX
                $.ajax({
                    url: "{{ route('master-data.master-shikake.index') }}/" + id,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            console.log('Shikake data:', data);
                            console.log('Released date:', data.released_date);
                            
                            // Populate form fields
                            $('#shikake_id').val(data.id);
                            $('#conveyor').val(data.conveyor ? data.conveyor.conveyor : '');
                            $('#shikake_no').val(data.shikake_no);
                            $('#barcode_kanban').val(data.barcode_kanban);
                            $('#family').val(data.family);
                            $('#barcode_proses').val(data.barcode_proses);
                            $('#qty').val(data.qty);
                            $('#issue').val(data.issue);
                            $('#machine').val(data.machine);
                            $('#sequence').val(data.sequence);
                            $('#released_date').val(data.released_date || '');
                            $('#released_note').val(data.released_note);
                            $('#store').val(data.store);
                            $('#barcode_mesin').val(data.barcode_mesin);
                            $('#address').val(data.address);
                            
                            // CCT and Address fields
                            $('#cct_a').val(data.cct_a);
                            $('#address_a').val(data.address_a);
                            $('#cct_b').val(data.cct_b);
                            $('#address_b').val(data.address_b);
                            $('#cct_c').val(data.cct_c);
                            $('#address_c').val(data.address_c);
                            $('#cct_4').val(data.cct_4);
                            $('#address_4').val(data.address_4);
                            $('#cct_5').val(data.cct_5);
                            $('#address_5').val(data.address_5);
                            $('#cct_6').val(data.cct_6);
                            $('#address_6').val(data.address_6);
                            $('#cct_7').val(data.cct_7);
                            $('#address_7').val(data.address_7);
                            
                            $('#barcode_navigasi').val(data.barcode_navigasi);
                            $('#dies').val(data.dies);
                            $('#jumlah_kombinasi').val(data.jumlah_kombinasi);
                            $('#blade').val(data.blade);
                            
                            // T fields
                            for(let i = 1; i <= 9; i++) {
                                const fieldName = 't' + String(i).padStart(2, '0');
                                $('#' + fieldName).val(data[fieldName]);
                            }
                            
                            $('#joint').val(data.joint);
                            
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
                        $('#detailShikakeModal').modal('hide');
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
            $('#shikakeDetailForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const id = $('#shikake_id').val();
                formData.append('_method', 'PUT');
                
                // Disable form inputs during upload
                $('#detailShikakeForm input, #detailShikakeForm textarea, #detailShikakeForm select').prop('disabled', true);
                $('#detailShikakeModal .btn-primary').prop('disabled', true).html('<i class="fa-solid fa-spinner ti-spin"></i> Saving...');
                
                $.ajax({
                    url: "{{ route('master-data.master-shikake.index') }}/" + id,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#detailShikakeModal').modal('hide');
                            table.ajax.reload();
                            Swal.fire('Success!', response.message, 'success');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to update data', 'error');
                    },
                    complete: function() {
                        // Re-enable form inputs
                        $('#detailShikakeForm input, #detailShikakeForm textarea, #detailShikakeForm select').prop('disabled', false);
                        $('#detailShikakeModal .btn-primary').prop('disabled', false).html('<i class="fa-solid fa-floppy-disk"></i> Save');
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
                    html: `You are about to delete all Shikake data for:<br><strong>${conveyorName}</strong><br><br>This action cannot be undone!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete all!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('master-data.master-shikake.remove-by-conveyor') }}",
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

@include('master_data.master_shikake.remove_modal')