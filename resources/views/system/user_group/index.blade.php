@extends('layout')

@section('title', 'User Group Management')

@section('content')
<div class="content-header" >
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">User Group Management</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">User Groups</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">User Group List</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" id="btn-add">
                        <i class="fas fa-plus"></i> Add User Group
                    </button>
                </div>
            </div>
            <div class="card-body">
                <table id="user-groups-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th width="10%">Users</th>
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

@include('system.user_group.form')
@include('system.user_group.permissions')
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
$(function() {
    // DataTable
    var table = $('#user-groups-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('system.user-groups.datatable') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'name', name: 'name'},
            {data: 'description', name: 'description'},
            {data: 'users_count', name: 'users_count'},
            {data: 'status', name: 'is_active', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    // Add User Group Button
    $('#btn-add').click(function() {
        $('#userGroupForm')[0].reset();
        $('#group_id').val('');
        $('#userGroupModalLabel').text('Add User Group');
        $('.error-text').text('');
        $('#userGroupModal').modal('show');
    });

    // Edit User Group
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        $.ajax({
            url: "{{ route('system.user-groups.index') }}/" + id + "/edit",
            type: 'GET',
            success: function(response) {
                $('#group_id').val(response.id);
                $('#name').val(response.name);
                $('#description').val(response.description);
                
                if(response.is_active == 1) {
                    $('#is_active_yes').prop('checked', true);
                } else {
                    $('#is_active_no').prop('checked', true);
                }
                
                $('#userGroupModalLabel').text('Edit User Group');
                $('.error-text').text('');
                $('#userGroupModal').modal('show');
            },
            error: function(xhr) {
                Swal.fire('Error!', 'Failed to load user group data', 'error');
            }
        });
    });

    // Save User Group
    $('#userGroupForm').submit(function(e) {
        e.preventDefault();
        $('.error-text').text('');
        
        var formData = $(this).serialize();
        var groupId = $('#group_id').val();
        var url = groupId ? "{{ route('system.user-groups.index') }}/" + groupId : "{{ route('system.user-groups.store') }}";
        var method = groupId ? 'PUT' : 'POST';
        
        if(groupId) {
            formData += '&_method=PUT';
        }
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function(response) {
                $('#userGroupModal').modal('hide');
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

    // Permissions
    $(document).on('click', '.btn-permission', function() {
        var id = $(this).data('id');
        $('#perm_group_id').val(id);
        
        $.ajax({
            url: "{{ route('system.user-groups.index') }}/" + id + "/permissions",
            type: 'GET',
            success: function(response) {
                $('#permissionsModalLabel').text('Menu Permissions - ' + response.group.name);
                var html = '<table class="table table-bordered table-sm">';
                html += '<thead><tr><th>Menu</th><th width="80">Create</th><th width="80">Read</th><th width="80">Update</th><th width="80">Delete</th></tr></thead>';
                html += '<tbody>';
                
                response.menus.forEach(function(menu) {
                    var perm = response.permissions[menu.id] || {};
                    html += '<tr>';
                    html += '<td><strong>' + menu.name + '</strong></td>';
                    html += '<td><input type="checkbox" name="permissions[' + menu.id + '][can_create]" value="1" ' + (perm.can_create ? 'checked' : '') + '></td>';
                    html += '<td><input type="checkbox" name="permissions[' + menu.id + '][can_read]" value="1" ' + (perm.can_read ? 'checked' : '') + '></td>';
                    html += '<td><input type="checkbox" name="permissions[' + menu.id + '][can_update]" value="1" ' + (perm.can_update ? 'checked' : '') + '></td>';
                    html += '<td><input type="checkbox" name="permissions[' + menu.id + '][can_delete]" value="1" ' + (perm.can_delete ? 'checked' : '') + '></td>';
                    html += '</tr>';
                    
                    if(menu.children && menu.children.length > 0) {
                        menu.children.forEach(function(child) {
                            var childPerm = response.permissions[child.id] || {};
                            html += '<tr>';
                            html += '<td>&nbsp;&nbsp;&nbsp;&nbsp;' + child.name + '</td>';
                            html += '<td><input type="checkbox" name="permissions[' + child.id + '][can_create]" value="1" ' + (childPerm.can_create ? 'checked' : '') + '></td>';
                            html += '<td><input type="checkbox" name="permissions[' + child.id + '][can_read]" value="1" ' + (childPerm.can_read ? 'checked' : '') + '></td>';
                            html += '<td><input type="checkbox" name="permissions[' + child.id + '][can_update]" value="1" ' + (childPerm.can_update ? 'checked' : '') + '></td>';
                            html += '<td><input type="checkbox" name="permissions[' + child.id + '][can_delete]" value="1" ' + (childPerm.can_delete ? 'checked' : '') + '></td>';
                            html += '</tr>';
                        });
                    }
                });
                
                html += '</tbody></table>';
                $('#permissions-content').html(html);
                $('#permissionsModal').modal('show');
            },
            error: function(xhr) {
                Swal.fire('Error!', 'Failed to load permissions', 'error');
            }
        });
    });

    // Save Permissions
    $('#permissionsForm').submit(function(e) {
        e.preventDefault();
        var groupId = $('#perm_group_id').val();
        var formData = $(this).serialize();
        
        $.ajax({
            url: "{{ route('system.user-groups.index') }}/" + groupId + "/permissions",
            type: 'POST',
            data: formData + '&_method=PUT',
            success: function(response) {
                $('#permissionsModal').modal('hide');
                Swal.fire('Success!', response.message, 'success');
            },
            error: function(xhr) {
                Swal.fire('Error!', xhr.responseJSON.message || 'Failed to update permissions', 'error');
            }
        });
    });

    // Delete User Group
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
                    url: "{{ route('system.user-groups.index') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        table.ajax.reload();
                        Swal.fire('Deleted!', response.message, 'success');
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON.message || 'Failed to delete user group', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
