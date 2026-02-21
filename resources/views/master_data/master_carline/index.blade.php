@extends('layouts.master')

@section('title', 'Carline Data')

@section('breadcrumb')
    <x-page-header menu-code="master_carline" />
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Carline Data List</h5>
            <div class="card-tools">
                @if(auth()->user()->hasMenuPermission('master_carline', 'can_create'))
                    <button type="button" class="btn btn-primary btn-sm" id="btn-add">
                        <i class="fa-solid fa-plus me-1"></i> Add Carline Data
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="master-carline-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Area</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th width="15%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('master_data.master_carline.form')
@endsection

@section('script')
<script>
    $(function () {
        // DataTable
        var table = $('#master-carline-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: "{{ route('master-data.master-carline.datatable') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'area_name', name: 'area.area' },
                { data: 'code', name: 'code' },
                { data: 'name', name: 'name' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        // Add Carline Data Button
        $('#btn-add').click(function () {
            $('#masterCarlineForm')[0].reset();
            $('#carline_id').val('');
            $('#area_id').val('').trigger('change');
            $('#masterCarlineModalLabel').text('Add Carline Data');
            $('.error-text').text('');
            $('#masterCarlineModal').modal('show');
        });

        // Edit Carline Data
        $(document).on('click', '.btn-edit', function () {
            var id = $(this).data('id');
            $.ajax({
                url: "{{ route('master-data.master-carline.index') }}/" + id + "/edit",
                type: 'GET',
                success: function (response) {
                    const carline = response.data || response;

                    $('#carline_id').val(carline.id);
                    $('#area_id').val(carline.area_id).trigger('change');
                    $('#code').val(carline.code);
                    $('#name').val(carline.name);

                    $('#masterCarlineModalLabel').text('Edit Carline Data');
                    $('.error-text').text('');
                    $('#masterCarlineModal').modal('show');
                },
                error: function (xhr) {
                    Swal.fire('Error!', 'Failed to load master carline data', 'error');
                }
            });
        });

        // Save Carline Data
        $('#masterCarlineForm').submit(function (e) {
            e.preventDefault();
            $('.error-text').text('');

            var formData = $(this).serialize();
            var carlineId = $('#carline_id').val();
            var url = carlineId ? "{{ route('master-data.master-carline.index') }}/" + carlineId : "{{ route('master-data.master-carline.store') }}";
            var method = carlineId ? 'PUT' : 'POST';

            if (carlineId) {
                formData += '&_method=PUT';
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                success: function (response) {
                    $('#masterCarlineModal').modal('hide');
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

        // Delete Carline Data
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
                        url: "{{ route('master-data.master-carline.index') }}/" + id,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            table.ajax.reload();
                            Swal.fire('Deleted!', response.message, 'success');
                        },
                        error: function (xhr) {
                            Swal.fire('Error!', xhr.responseJSON.message || 'Failed to delete master carline', 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
