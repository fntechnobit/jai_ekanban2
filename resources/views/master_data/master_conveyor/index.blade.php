@extends('layout')

@section('title', 'Conveyor Data')

@section('content')
    <x-page-header menu-code="master_conveyor" />

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Conveyor Data</h3>
                    <div class="card-tools">
                        @if(auth()->user()->hasMenuPermission('master_conveyor', 'can_create'))
                            <button type="button" class="btn btn-primary btn-sm" id="btn-add">
                                <i class="fas fa-plus"></i> Add New Data
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="filter_area">Area :</label>
                            <select class="form-control select2" id="filter_area" style="width: 100%;">
                                <option value="">- All Area -</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}">{{ $area->area }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="filter_family">Family :</label>
                            <select class="form-control select2" id="filter_family" style="width: 100%;">
                                <option value="">- All Family -</option>
                                @foreach($families as $family)
                                    <option value="{{ $family->id }}">{{ $family->family }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <table id="master-conveyor-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Area</th>
                                <th>Conveyor</th>
                                <th>Family</th>
                                <th>Shift/Start</th>
                                <th>Capacity</th>
                                <th>Pallet Qty</th>
                                <th width="10%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    @include('master_data.master_conveyor.form')
@endsection

@push('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@push('scripts')
    <!-- DataTables -->
    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

    <script>
        $(function () {
            // Initialize Select2 for filters
            $('#filter_area, #filter_family').select2({
                theme: 'bootstrap4',
                allowClear: true,
                placeholder: function() {
                    return $(this).data('placeholder') || 'Select...';
                }
            });

            // DataTable
            var table = $('#master-conveyor-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('master-data.master-conveyor.datatable') }}",
                    data: function(d) {
                        d.area_id = $('#filter_area').val();
                        d.family_id = $('#filter_family').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'area_name', name: 'area.area' },
                    { data: 'conveyor', name: 'conveyor' },
                    { data: 'family_names', name: 'family_names', orderable: false },
                    { data: 'shift_label', name: 'shift_qty' },
                    { data: 'capacity', name: 'capacity' },
                    { data: 'pallet_qty', name: 'pallet_qty' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                pageLength: 50
            });

            // Filter change events
            $('#filter_area, #filter_family').on('change', function() {
                table.ajax.reload();
            });

            // Initialize Select2 for form
            function initFormSelect2() {
                $('#master_area_id').select2({
                    theme: 'bootstrap4',
                    dropdownParent: $('#masterConveyorModal'),
                    placeholder: 'Select Area'
                });

                $('#family_ids').select2({
                    theme: 'bootstrap4',
                    dropdownParent: $('#masterConveyorModal'),
                    placeholder: 'Select Family',
                    allowClear: true
                });

                $('#shift_qty').select2({
                    theme: 'bootstrap4',
                    dropdownParent: $('#masterConveyorModal'),
                    minimumResultsForSearch: Infinity
                });
            }

            // Add Conveyor Button
            $('#btn-add').click(function () {
                $('#masterConveyorForm')[0].reset();
                $('#conveyor_id').val('');
                $('#masterConveyorModalLabel').text('Add Conveyor');
                $('.error-text').text('');
                
                // Reset Select2
                $('#master_area_id').val('').trigger('change');
                $('#family_ids').val([]).trigger('change');
                $('#shift_qty').val('2').trigger('change');
                
                initFormSelect2();
                $('#masterConveyorModal').modal('show');
            });

            // Edit Conveyor
            $(document).on('click', '.btn-edit', function () {
                var id = $(this).data('id');
                $.ajax({
                    url: "{{ route('master-data.master-conveyor.index') }}/" + id + "/edit",
                    type: 'GET',
                    success: function (response) {
                        const conveyor = response.data || response;

                        $('#conveyor_id').val(conveyor.id);
                        $('#conveyor').val(conveyor.conveyor);
                        $('#capacity').val(conveyor.capacity);
                        $('#pallet_qty').val(conveyor.pallet_qty);
                        
                        initFormSelect2();
                        
                        $('#master_area_id').val(conveyor.master_area_id).trigger('change');
                        $('#shift_qty').val(conveyor.shift_qty).trigger('change');
                        $('#family_ids').val(conveyor.family_ids).trigger('change');

                        $('#masterConveyorModalLabel').text('Edit Conveyor');
                        $('.error-text').text('');
                        $('#masterConveyorModal').modal('show');
                    },
                    error: function (xhr) {
                        Swal.fire('Error!', 'Failed to load conveyor data', 'error');
                    }
                });
            });

            // Save Conveyor
            $('#masterConveyorForm').submit(function (e) {
                e.preventDefault();
                $('.error-text').text('');

                var formData = $(this).serialize();
                var conveyorId = $('#conveyor_id').val();
                var url = conveyorId ? "{{ route('master-data.master-conveyor.index') }}/" + conveyorId : "{{ route('master-data.master-conveyor.store') }}";

                if (conveyorId) {
                    formData += '&_method=PUT';
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    success: function (response) {
                        $('#masterConveyorModal').modal('hide');
                        table.ajax.reload();
                        Swal.fire('Success!', response.message, 'success');
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (key, value) {
                                // Handle array field errors
                                var errorKey = key.replace('.', '_');
                                $('.' + errorKey + '_error').text(value[0]);
                            });
                        } else {
                            Swal.fire('Error!', xhr.responseJSON.message || 'Something went wrong', 'error');
                        }
                    }
                });
            });

            // Delete Conveyor
            $(document).on('click', '.btn-delete', function () {
                var id = $(this).data('id');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('master-data.master-conveyor.index') }}/" + id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function (response) {
                                table.ajax.reload();
                                Swal.fire('Deleted!', response.message, 'success');
                            },
                            error: function (xhr) {
                                Swal.fire('Error!', xhr.responseJSON.message || 'Failed to delete conveyor', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
