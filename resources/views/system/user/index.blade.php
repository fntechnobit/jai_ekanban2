@extends('layouts.master')

@section('title', 'User Management')

@section('breadcrumb')
    <x-page-header menu-code="users" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">User List</h5>
                <div class="card-tools float-end">
                    @if(auth()->user()->hasMenuPermission('users', 'can_create'))
                        <button type="button" class="btn btn-primary btn-sm" id="btn-add">
                            <i class="fa-solid fa-plus"></i> Add User
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="card card-primary card-filter mb-4">
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="filter-group" class="form-label filter-label">User Group</label>
                                <select id="filter-group" class="form-select filter-select" style="width: 100%;">
                                    <option value="">All Groups</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <table id="users-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>User Group</th>
                            <th>Area</th>
                            <th width="10%">Status</th>
                            <th width="15%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('system.user.form')
@endsection

@section('script')
    <script>
        $(function () {
            // Initialize Select2
            $('.select2').not('#filter-group').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#userModal')
            });

            $('#filter-group').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('body'),
                placeholder: 'All Groups',
                allowClear: true,
                minimumResultsForSearch: 8
            });

            var initialGroup = "{{ request()->query('group_id') }}";
            if (initialGroup) {
                $('#filter-group').val(initialGroup).trigger('change');
            }

            // DataTable
            var table = $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('system.users.datatable') }}",
                    data: function (params) {
                        params.group_id = $('#filter-group').val();
                    }
                },
                order: [[1, 'asc']],
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
                    { data: 'username', name: 'username' },
                    { data: 'email', name: 'email' },
                    { data: 'group_label', name: 'group.name', orderable: false, searchable: false },
                    { data: 'area_label', name: 'area.area', orderable: false, searchable: false },
                    { data: 'status', name: 'is_active', orderable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ]
            });

            // Auto reload when filter changes
            $('#filter-group').on('change', function () {
                toggleCardState();
                table.ajax.reload();
            }).trigger('change');

            function toggleCardState() {
                var hasFilter = !!$('#filter-group').val();
                $('.card-filter').toggleClass('is-filtered', hasFilter);
            }

            // Add User Button
            $('#btn-add').click(function () {
                $('#userForm')[0].reset();
                $('#user_id').val('');
                $('#userModalLabel').text('Add User');
                $('.error-text').text('');
                $('.password-required').show();
                $('#password').prop('required', true);
                $('#password_confirmation').prop('required', true);
                $('.select2').val(null).trigger('change');
                $('#userModal').modal('show');
            });

            // Edit User
            $(document).on('click', '.btn-edit', function () {
                var id = $(this).data('id');
                $.ajax({
                    url: "{{ route('system.users.index') }}/" + id + "/edit",
                    type: 'GET',
                    success: function (response) {
                        const user = response.data || response;

                        $('#user_id').val(user.id);
                        $('#name').val(user.name);
                        $('#username').val(user.username);
                        $('#email').val(user.email);
                        $('#group_id').val(user.group_id).trigger('change');
                        $('#area_id').val(user.area_id ? user.area_id : '').trigger('change');

                        if (user.is_active == 1) {
                            $('#is_active_yes').prop('checked', true);
                        } else {
                            $('#is_active_no').prop('checked', true);
                        }

                        $('#userModalLabel').text('Edit User');
                        $('.error-text').text('');
                        $('.password-required').hide();
                        $('#password').prop('required', false);
                        $('#password_confirmation').prop('required', false);
                        $('#userModal').modal('show');
                    },
                    error: function (xhr) {
                        Swal.fire('Error!', 'Failed to load user data', 'error');
                    }
                });
            });

            // Save User
            $('#userForm').submit(function (e) {
                e.preventDefault();
                $('.error-text').text('');

                var formData = $(this).serialize();
                var userId = $('#user_id').val();
                var url = userId ? "{{ route('system.users.index') }}/" + userId : "{{ route('system.users.store') }}";
                var method = userId ? 'PUT' : 'POST';

                if (userId) {
                    formData += '&_method=PUT';
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    success: function (response) {
                        $('#userModal').modal('hide');
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

            // Delete User
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
                            url: "{{ route('system.users.index') }}/" + id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function (response) {
                                table.ajax.reload();
                                Swal.fire('Deleted!', response.message, 'success');
                            },
                            error: function (xhr) {
                                Swal.fire('Error!', xhr.responseJSON.message || 'Failed to delete user', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection