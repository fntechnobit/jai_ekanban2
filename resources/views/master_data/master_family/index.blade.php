@extends('layouts.master')

@section('title', 'Family Data')

@section('breadcrumb')
    <x-page-header menu-code="master_family" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Family Data List</h5>
                <div class="card-tools float-end">
                    @if(auth()->user()->hasMenuPermission('master_family', 'can_create'))
                        <button type="button" class="btn btn-primary btn-sm" id="btn-add">
                            <i class="fa-solid fa-plus"></i> Add Family Data
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <table id="master-family-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Area</th>
                            <th>Carline Code</th>
                            <th>Carline Name</th>
                            <th>Family</th>
                            <th width="15%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('master_data.master_family.form')
@endsection

@section('script')
    <script>
        $(function () {
            // DataTable
            var table = $('#master-family-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('master-data.master-family.datatable') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'area_name', name: 'carline.area.area' },
                    { data: 'carline_code', name: 'carline.code' },
                    { data: 'carline_name', name: 'carline.name' },
                    { data: 'family', name: 'family' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ]
            });

            // Add Family Data Button
            $('#btn-add').click(function () {
                $('#masterFamilyForm')[0].reset();
                $('#family_id').val('');
                $('#area_id').val('').trigger('change');
                $('#carline_id').val('').trigger('change');
                $('#masterFamilyModalLabel').text('Add Family Data');
                $('.error-text').text('');
                $('#masterFamilyModal').modal('show');
            });

            // Edit Family Data
            $(document).on('click', '.btn-edit', function () {
                var id = $(this).data('id');
                $.ajax({
                    url: "{{ route('master-data.master-family.index') }}/" + id + "/edit",
                    type: 'GET',
                    success: function (response) {
                        const family = response.data || response;

                        $('#family_id').val(family.id);
                        $('#family').val(family.family);
                        
                        // Set area and trigger carline loading
                        if (family.carline) {
                            $('#area_id').val(family.carline.area_id).trigger('change');
                            // Set carline after brief delay to allow options to load
                            setTimeout(function() {
                                $('#carline_id').val(family.carline_id).trigger('change');
                            }, 100);
                        }

                        $('#masterFamilyModalLabel').text('Edit Family Data');
                        $('.error-text').text('');
                        $('#masterFamilyModal').modal('show');
                    },
                    error: function (xhr) {
                        Swal.fire('Error!', 'Failed to load family data', 'error');
                    }
                });
            });

            // Save Family Data
            $('#masterFamilyForm').submit(function (e) {
                e.preventDefault();
                $('.error-text').text('');

                var formData = $(this).serialize();
                var familyId = $('#family_id').val();
                var url = familyId ? "{{ route('master-data.master-family.index') }}/" + familyId : "{{ route('master-data.master-family.store') }}";
                var method = familyId ? 'PUT' : 'POST';

                if (familyId) {
                    formData += '&_method=PUT';
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    success: function (response) {
                        $('#masterFamilyModal').modal('hide');
                        table.ajax.reload();
                        Swal.fire('Success!', response.message, 'success');
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (key, value) {
                                $('.' + key + '_error').text(value[0]);
                            });
                        } else {
                            Swal.fire('Error!', xhr.responseJSON.message || 'Something went wrong', 'error');
                        }
                    }
                });
            });

            // Delete Family Data
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
                            url: "{{ route('master-data.master-family.index') }}/" + id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function (response) {
                                table.ajax.reload();
                                Swal.fire('Deleted!', response.message, 'success');
                            },
                            error: function (xhr) {
                                Swal.fire('Error!', xhr.responseJSON.message || 'Failed to delete family data', 'error');
                            }
                        });
                    }
                });
            });

            // Area change handler - load carlines
            $('#area_id').on('change', function() {
                var areaId = $(this).val();
                var $carlineSelect = $('#carline_id');
                
                $carlineSelect.html('<option value="">- Select Carline -</option>');
                
                if (areaId) {
                    var carlines = @json($carlines);
                    var filteredCarlines = carlines.filter(function(carline) {
                        return carline.area_id == areaId;
                    });
                    
                    filteredCarlines.forEach(function(carline) {
                        $carlineSelect.append(new Option(carline.code + ' - ' + carline.name, carline.id));
                    });
                }
            });
        });
    </script>
@endsection
