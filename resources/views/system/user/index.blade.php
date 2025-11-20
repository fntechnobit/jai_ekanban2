@extends('layout')

@section('title', 'User Management')

@section('content')
<div class="content-header" >
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">User Management</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Users</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">User List</h3>
                <div class="card-tools">
                    @if(auth()->user()->hasMenuPermission('users', 'can_create'))
                        <button type="button" class="btn btn-primary btn-sm" id="btn-add">
                            <i class="fas fa-plus"></i> Add User
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <table id="users-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Group</th>
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
</section>

@include('system.user.form')
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
$(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        dropdownParent: $('#userModal')
    });

    // DataTable
    var table = $('#users-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('system.users.datatable') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'name', name: 'name'},
            {data: 'email', name: 'email'},
            {data: 'group_name', name: 'group.name'},
            {data: 'status', name: 'is_active', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    // Add User Button
    $('#btn-add').click(function() {
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
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        $.ajax({
            url: "{{ route('system.users.index') }}/" + id + "/edit",
            type: 'GET',
            success: function(response) {
                const user = response.data || response;

                $('#user_id').val(user.id);
                $('#name').val(user.name);
                $('#email').val(user.email);
                $('#group_id').val(user.group_id).trigger('change');

                if(user.is_active == 1) {
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
            error: function(xhr) {
                Swal.fire('Error!', 'Failed to load user data', 'error');
            }
        });
    });

    // Save User
    $('#userForm').submit(function(e) {
        e.preventDefault();
        $('.error-text').text('');
        
        var formData = $(this).serialize();
        var userId = $('#user_id').val();
        var url = userId ? "{{ route('system.users.index') }}/" + userId : "{{ route('system.users.store') }}";
        var method = userId ? 'PUT' : 'POST';
        
        if(userId) {
            formData += '&_method=PUT';
        }
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function(response) {
                $('#userModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Success!', response.message, 'success');
            },
            error: function(xhr) {
                if(xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        $('.' + key + '_error').text(value[0]);
                    });
                } else {
                    Swal.fire('Error!', xhr.responseJSON.message || 'Something went wrong', 'error');
                }
            }
        });
    });

    // Delete User
    $(document).on('click', '.btn-delete', function() {
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
                    success: function(response) {
                        table.ajax.reload();
                        Swal.fire('Deleted!', response.message, 'success');
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON.message || 'Failed to delete user', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
