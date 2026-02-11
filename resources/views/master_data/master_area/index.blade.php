@extends('layouts.master')

@section('title', 'Preassy Area Data')

@section('breadcrumb')
    <x-page-header menu-code="master_area" />
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Preassy Area Data List</h5>
            <div class="card-tools">
                @if(auth()->user()->hasMenuPermission('master_area', 'can_create'))
                    <button type="button" class="btn btn-primary btn-sm" id="btn-add">
                        <i class="fa-solid fa-plus me-1"></i> Add Preassy Area Data
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="master-area-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Area</th>
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

@include('master_data.master_area.form')
@endsection

@section('script')
<script>
    $(function () {
        // DataTable
        var table = $('#master-area-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: "{{ route('master-data.master-area.datatable') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'area', name: 'area' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        // Add Preassy Area Data Button
        $('#btn-add').click(function () {
            $('#masterAreaForm')[0].reset();
            $('#area_id').val('');
            $('#masterAreaModalLabel').text('Add Preassy Area Data');
            $('.error-text').text('');
            $('#masterAreaModal').modal('show');
        });

        // Edit Preassy Area Data
        $(document).on('click', '.btn-edit', function () {
            var id = $(this).data('id');
            $.ajax({
                url: "{{ route('master-data.master-area.index') }}/" + id + "/edit",
                type: 'GET',
                success: function (response) {
                    const area = response.data || response;

                    $('#area_id').val(area.id);
                    $('#area').val(area.area);

                    $('#masterAreaModalLabel').text('Edit Preassy Area Data');
                    $('.error-text').text('');
                    $('#masterAreaModal').modal('show');
                },
                error: function (xhr) {
                    Swal.fire('Error!', 'Failed to load master area data', 'error');
                }
            });
        });

        // Save Preassy Area Data
        $('#masterAreaForm').submit(function (e) {
            e.preventDefault();
            $('.error-text').text('');

            var formData = $(this).serialize();
            var areaId = $('#area_id').val();
            var url = areaId ? "{{ route('master-data.master-area.index') }}/" + areaId : "{{ route('master-data.master-area.store') }}";
            var method = areaId ? 'PUT' : 'POST';

            if (areaId) {
                formData += '&_method=PUT';
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                success: function (response) {
                    $('#masterAreaModal').modal('hide');
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

        // Delete Preassy Area Data
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
                        url: "{{ route('master-data.master-area.index') }}/" + id,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            table.ajax.reload();
                            Swal.fire('Deleted!', response.message, 'success');
                        },
                        error: function (xhr) {
                            Swal.fire('Error!', xhr.responseJSON.message || 'Failed to delete master area', 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
