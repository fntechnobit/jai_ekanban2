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
                        <div class="col-md-4">
                            <label for="filter_process">Process :</label>
                            <select class="form-select select2" id="filter_process" style="width: 100%;">
                                <option value="">- All Process -</option>
                                @foreach($processTypes as $processType)
                                    <option value="{{ $processType->value }}">{{ $processType->value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <table id="master-shikake-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Carline</th>
                                <th>Conveyor</th>
                                <th>Identifier</th>
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
            $('#filter_area, #filter_conveyor, #filter_process').select2({
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
                        d.process = $('#filter_process').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'carline', name: 'carline' },
                    { data: 'conveyor_name', name: 'conveyor' },
                    { data: 'identifier', name: 'identifier', orderable: false, searchable: false },
                    { data: 'family', name: 'family' },
                    { data: 'process', name: 'process', orderable: false },
                    { data: 'qty', name: 'qty' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]
            });

            // Filter change events
            $('#filter_area, #filter_conveyor, #filter_process').on('change', function() {
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

            // Enable/disable download template button based on process selection
            $('#import_process').on('change', function() {
                var process = $(this).val();
                var downloadBtn = $('#btn-download-template-shikake');
                if (process) {
                    downloadBtn.prop('disabled', false);
                } else {
                    downloadBtn.prop('disabled', true);
                }
            });

            // Download Template based on selected process
            $('#btn-download-template-shikake').click(function() {
                var process = $('#import_process').val();
                if (!process) {
                    Swal.fire('Warning', 'Please select a process type first', 'warning');
                    return;
                }
                window.location.href = "{{ route('master-data.master-shikake.download-template') }}?process=" + encodeURIComponent(process);
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
                        
                        // Reset form including Select2 dropdowns
                        $('#importShikakeForm')[0].reset();
                        $('#import_area_id').val('').trigger('change');
                        $('#import_conveyor_id').val('').trigger('change');
                        $('#import_process').val('').trigger('change');
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
                
                // Show loading indicator
                showLoadingSpinner();
                
                // Show modal
                $('#detailShikakeModal').modal('show');
                
                // Clear any previous validation errors
                clearValidationErrors();
                
                // Fetch data via AJAX
                $.ajax({
                    url: "{{ route('master-data.master-shikake.index') }}/" + id,
                    type: 'GET',
                    dataType: 'json',
                    beforeSend: function() {
                        $('#loading-indicator').show();
                    },
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            console.log('Shikake data:', data);
                            
                            // Populate main form fields
                            populateMainFields(data);
                            
                            // Populate process-specific fields
                            populateProcessFields(data.process, data.process_data || {});
                            
                            // Show appropriate process section
                            toggleProcessSections();
                            
                            // Handle image preview
                            handleImagePreview(data.image_path);
                            
                            // Handle assembly list
                            handleAssemblyList(data.assemblies);
                        }
                    },
                    error: function(xhr) {
                        console.error('Error loading shikake details:', xhr);
                        Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to load data', 'error');
                        $('#detailShikakeModal').modal('hide');
                    },
                    complete: function() {
                        hideLoadingSpinner();
                        $('#loading-indicator').hide();
                    }
                });
            });

            // Helper function to populate main fields
            function populateMainFields(data) {
                $('#shikake_id').val(data.id);
                $('#conveyor').val(data.conveyor ? data.conveyor.conveyor : '');
                $('#carline').val(data.carline);
                $('#process').val(data.process);
                $('#machine').val(data.machine);
                $('#family').val(data.family);
                $('#sequence').val(data.sequence);
                $('#released_note').val(data.released_note);
                $('#qty').val(data.qty);
            }

            // Helper function to populate process-specific fields
            function populateProcessFields(process, processData) {
                if (!process || !processData) return;
                
                const prefix = process.toLowerCase().replace(' ', '_');
                
                // Clear all process fields first
                $('.process-section input').val('');
                
                // Populate based on process type
                switch(process) {
                    case 'TWIST':
                        populateTwistFields(processData);
                        break;
                    case 'BONDER':
                        populateBonderFields(processData);
                        break;
                    case 'JOINT':
                        populateJointFields(processData);
                        break;
                    case 'SHIELD':
                        populateShieldFields(processData);
                        break;
                    case 'DBL CRIMP':
                        populateDblCrimpFields(processData);
                        break;
                }
            }

            function populateTwistFields(data) {
                $('#twist_cct_no').val(data.cct_no);
                $('#twist_cct_code').val(data.cct_code);
                $('#twist_machine_twist').val(data.machine_twist);
                $('#twist_sequence_2').val(data.sequence_2);
                $('#twist_barcode_navigasi').val(data.barcode_navigasi);
                $('#twist_barcode_process').val(data.barcode_process);
                $('#twist_barcode_shikake').val(data.barcode_shikake);
                $('#twist_to_store').val(data.to_store);
                $('#twist_cust_no').val(data.cust_no);
                $('#twist_kind').val(data.kind);
                $('#twist_size').val(data.size);
                $('#twist_color').val(data.color);
                $('#twist_cl').val(data.cl);
                $('#twist_terminal_a').val(data.terminal_a);
                $('#twist_acc_1_a').val(data.acc_1_a);
                $('#twist_tube_a').val(data.tube_a);
                $('#twist_note_a').val(data.note_a);
                $('#twist_strip_a').val(data.strip_a);
                $('#twist_mark_a').val(data.mark_a);
                $('#twist_terminal_b').val(data.terminal_b);
                $('#twist_acc_1_ab').val(data.acc_1_ab);
                $('#twist_tube_b').val(data.tube_b);
                $('#twist_note_b').val(data.note_b);
                $('#twist_strip_b').val(data.strip_b);
                $('#twist_mark_b').val(data.mark_b);
            }

            function populateBonderFields(data) {
                $('#bonder_bonder_no').val(data.bonder_no);
                $('#bonder_address').val(data.address);
                $('#bonder_dies').val(data.dies);
                $('#bonder_to_machine').val(data.to_machine);
                $('#bonder_barcode_navigasi').val(data.barcode_navigasi);
                $('#bonder_barcode_process').val(data.barcode_process);
                
                // Populate Side A CCT & Bonder pairs
                $('#bonder_cct_no_a_1').val(data.cct_no_a_1);
                $('#bonder_bonder_no_a_1').val(data.bonder_no_a_1);
                $('#bonder_cct_no_a_2').val(data.cct_no_a_2);
                $('#bonder_bonder_no_a_2').val(data.bonder_no_a_2);
                $('#bonder_cct_no_a_3').val(data.cct_no_a_3);
                $('#bonder_bonder_no_a_3').val(data.bonder_no_a_3);
                $('#bonder_cct_no_a_4').val(data.cct_no_a_4);
                $('#bonder_bonder_no_a_4').val(data.bonder_no_a_4);
                $('#bonder_cct_no_a_5').val(data.cct_no_a_5);
                $('#bonder_bonder_no_a_5').val(data.bonder_no_a_5);
                $('#bonder_cct_no_a_6').val(data.cct_no_a_6);
                $('#bonder_bonder_no_a_6').val(data.bonder_no_a_6);
                $('#bonder_cct_no_a_7').val(data.cct_no_a_7);
                $('#bonder_bonder_no_a_7').val(data.bonder_no_a_7);
                
                // Populate Side B CCT & Bonder pairs
                $('#bonder_cct_no_b_1').val(data.cct_no_b_1);
                $('#bonder_bonder_no_b_1').val(data.bonder_no_b_1);
                $('#bonder_cct_no_b_2').val(data.cct_no_b_2);
                $('#bonder_bonder_no_b_2').val(data.bonder_no_b_2);
                $('#bonder_cct_no_b_3').val(data.cct_no_b_3);
                $('#bonder_bonder_no_b_3').val(data.bonder_no_b_3);
                $('#bonder_cct_no_b_4').val(data.cct_no_b_4);
                $('#bonder_bonder_no_b_4').val(data.bonder_no_b_4);
                $('#bonder_cct_no_b_5').val(data.cct_no_b_5);
                $('#bonder_bonder_no_b_5').val(data.bonder_no_b_5);
                $('#bonder_cct_no_b_6').val(data.cct_no_b_6);
                $('#bonder_bonder_no_b_6').val(data.bonder_no_b_6);
                $('#bonder_cct_no_b_7').val(data.cct_no_b_7);
                $('#bonder_bonder_no_b_7').val(data.bonder_no_b_7);
            }

            function populateJointFields(data) {
                $('#joint_bonder_no').val(data.bonder_no);
                $('#joint_address').val(data.address);
                $('#joint_address_store').val(data.address_store);
                $('#joint_to_machine').val(data.to_machine);
                $('#joint_barcode_process').val(data.barcode_process);
                
                // Populate CCT & Bonder pairs (1-5)
                $('#joint_cct_no_1').val(data.cct_no_1);
                $('#joint_bonder_no_1').val(data.bonder_no_1);
                $('#joint_cct_no_2').val(data.cct_no_2);
                $('#joint_bonder_no_2').val(data.bonder_no_2);
                $('#joint_cct_no_3').val(data.cct_no_3);
                $('#joint_bonder_no_3').val(data.bonder_no_3);
                $('#joint_cct_no_4').val(data.cct_no_4);
                $('#joint_bonder_no_4').val(data.bonder_no_4);
                $('#joint_cct_no_5').val(data.cct_no_5);
                $('#joint_bonder_no_5').val(data.bonder_no_5);
            }

            function populateShieldFields(data) {
                $('#shield_shield_no').val(data.shield_no);
                $('#shield_address').val(data.address);
                $('#shield_blade').val(data.blade);
                
                // Populate TO fields
                $('#shield_to_1').val(data.to_1);
                $('#shield_to_2').val(data.to_2);
                $('#shield_to_3').val(data.to_3);
                $('#shield_to_4').val(data.to_4);
                $('#shield_to_5').val(data.to_5);
                $('#shield_to_6').val(data.to_6);
                $('#shield_to_7').val(data.to_7);
                $('#shield_to_8').val(data.to_8);
                $('#shield_to_9').val(data.to_9);
                
                // Populate CCT & Address pairs (only 2 pairs)
                $('#shield_cct_no_1').val(data.cct_no_1);
                $('#shield_address_no_1_1').val(data.address_no_1_1);
                $('#shield_cct_no_2').val(data.cct_no_2);
                $('#shield_address_no_1_2').val(data.address_no_1_2);
            }

            function populateDblCrimpFields(data) {
                $('#dbl_crimp_drawing_no').val(data.drawing_no);
                $('#dbl_crimp_address').val(data.address);
                $('#dbl_crimp_barcode_mesin').val(data.barcode_mesin);
                $('#dbl_crimp_to_machine').val(data.to_machine);
                
                // Populate CCT No & Address pairs
                $('#dbl_crimp_cct_no_1').val(data.cct_no_1);
                $('#dbl_crimp_address_1').val(data.address_1);
                $('#dbl_crimp_cct_no_2').val(data.cct_no_2);
                $('#dbl_crimp_address_2').val(data.address_2);
                $('#dbl_crimp_cct_no_3').val(data.cct_no_3);
                $('#dbl_crimp_address_3').val(data.address_3);
                $('#dbl_crimp_cct_no_4').val(data.cct_no_4);
                $('#dbl_crimp_address_4').val(data.address_4);
                $('#dbl_crimp_cct_no_5').val(data.cct_no_5);
                $('#dbl_crimp_address_5').val(data.address_5);
            }

            function handleImagePreview(imagePath) {
                if (imagePath) {
                    $('#imagePreview').attr('src', '{{ asset("") }}' + imagePath);
                    $('#imagePreviewContainer').show();
                } else {
                    $('#imagePreviewContainer').hide();
                }
            }

            function handleAssemblyList(assemblies) {
                if (assemblies && assemblies.length > 0) {
                    const assyText = assemblies.map(a => a.assy).join(', ');
                    $('#assyList').html('<p class="mb-0 small">' + assyText + '</p>');
                } else {
                    $('#assyList').html('<p class="text-muted mb-0 small">No assembly data</p>');
                }
            }

            function showLoadingSpinner() {
                $('#loading-indicator').show();
                $('#submit-spinner').show();
                $('#submit-icon').hide();
                $('#submit-text').text('Loading...');
                $('#submit-btn').prop('disabled', true);
            }

            function hideLoadingSpinner() {
                $('#loading-indicator').hide();
                $('#submit-spinner').hide();
                $('#submit-icon').show();
                $('#submit-text').text('Update');
                $('#submit-btn').prop('disabled', false);
            }

            function clearValidationErrors() {
                $('.form-control, .form-select').removeClass('is-invalid');
                $('.invalid-feedback').text('').hide();
            }

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
                
                // Clear previous validation errors
                clearValidationErrors();
                
                // Show loading state
                showSubmissionLoading();
                
                $.ajax({
                    url: "{{ route('master-data.master-shikake.index') }}/" + id,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        // Disable form during submission
                        $('#shikakeDetailForm input, #shikakeDetailForm textarea, #shikakeDetailForm select').prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#detailShikakeModal').modal('hide');
                            table.ajax.reload();
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Form submission error:', xhr);
                        
                        if (xhr.status === 422) {
                            // Handle validation errors
                            const errors = xhr.responseJSON.errors;
                            displayValidationErrors(errors);
                            
                            Swal.fire({
                                icon: 'warning',
                                title: 'Validation Error',
                                text: 'Please check the form for errors and try again.',
                                timer: 3000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: xhr.responseJSON?.message || 'Failed to update shikake data',
                                showConfirmButton: true
                            });
                        }
                    },
                    complete: function() {
                        // Re-enable form inputs
                        $('#shikakeDetailForm input, #shikakeDetailForm textarea, #shikakeDetailForm select').prop('disabled', false);
                        hideSubmissionLoading();
                    }
                });
            });

            function showSubmissionLoading() {
                $('#submit-spinner').show();
                $('#submit-icon').hide();
                $('#submit-text').text('Updating...');
                $('#submit-btn').prop('disabled', true);
            }

            function hideSubmissionLoading() {
                $('#submit-spinner').hide();
                $('#submit-icon').show();
                $('#submit-text').text('Update');
                $('#submit-btn').prop('disabled', false);
            }

            function displayValidationErrors(errors) {
                for (const [field, messages] of Object.entries(errors)) {
                    const fieldElement = $(`[name="${field}"]`);
                    const errorElement = $(`#${field.replace(/\./g, '_').replace(/\[/g, '_').replace(/\]/g, '')}-error`);
                    
                    if (fieldElement.length) {
                        fieldElement.addClass('is-invalid');
                    }
                    
                    if (errorElement.length) {
                        errorElement.text(messages[0]).show();
                    }
                    
                    // Handle process_data fields specifically
                    if (field.includes('process_data.')) {
                        const processField = field.replace('process_data.', '');
                        const process = $('#process').val().toLowerCase().replace(' ', '_');
                        const processFieldElement = $(`#${process}_${processField}`);
                        const processErrorElement = $(`#${process}_${processField}-error`);
                        
                        if (processFieldElement.length) {
                            processFieldElement.addClass('is-invalid');
                        }
                        
                        if (processErrorElement.length) {
                            processErrorElement.text(messages[0]).show();
                        }
                    }
                }
                
                // Scroll to first error
                const firstError = $('.is-invalid').first();
                if (firstError.length) {
                    firstError[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }

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