@extends('layout')

@section('title', 'Family Data')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Family Data</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item">Master Data</li>
                        <li class="breadcrumb-item active">Family Data</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Family Data List</h3>
                    <div class="card-tools">
                        @if(auth()->user()->hasMenuPermission('master_family', 'can_create'))
                            <button type="button" class="btn btn-primary btn-sm" id="btn-add">
                                <i class="fas fa-plus"></i> Add Family Data
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <table id="master-family-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
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
    </section>

    @include('master_data.master_family.form')
@endsection

@push('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endpush

@push('scripts')
    <!-- DataTables -->
    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

    <script>
        $(function () {
            // DataTable
            var table = $('#master-family-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('master-data.master-family.datatable') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'family', name: 'family' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ]
            });

            // Add Family Data Button
            $('#btn-add').click(function () {
                $('#masterFamilyForm')[0].reset();
                $('#family_id').val('');
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
        });
    </script>
@endpush
