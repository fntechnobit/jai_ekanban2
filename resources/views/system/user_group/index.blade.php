@extends('layouts.master')

@section('title', 'User Group Management')

@section('breadcrumb')
    <x-page-header menu-code="user_groups" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">User Group List</h5>
                <div class="card-tools float-end">
                    @if(auth()->user()->hasMenuPermission('user_groups', 'can_create'))
                        <button type="button" class="btn btn-primary btn-sm" id="btn-add">
                            <i class="fa-solid fa-plus"></i> Add User Group
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <table id="user-groups-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th width="15%">Users</th>
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

    @include('system.user_group.form')
    @include('system.user_group.permissions')
@endsection

@section('script')
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
                    {data: 'users_link', name: 'users_count', orderable: false, searchable: false},
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
                const group = response.data || response;

                $('#group_id').val(group.id);
                $('#name').val(group.name);
                $('#description').val(group.description);

                if(group.is_active == 1) {
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
                const data = response.data || response;
                const permissions = data.permissions || {};

                $('#permissionsModalLabel').text('Menu Permissions - ' + data.group.name);
                var html = '<table class="table table-bordered table-sm">';
                html += '<thead><tr><th>Menu</th><th width="80">Create</th><th width="80">Read</th><th width="80">Update</th><th width="80">Delete</th></tr></thead>';
                html += '<tbody>';
                
                data.menus.forEach(function(menu) {
                    var perm = permissions[menu.id] || {};
                    html += '<tr>';
                    html += '<td><strong>' + menu.name + '</strong></td>';
                    html += '<td><input type="checkbox" class="perm-checkbox" data-scope="parent" data-menu-id="' + menu.id + '" data-permission="can_create" name="permissions[' + menu.id + '][can_create]" value="1" ' + (perm.can_create ? 'checked' : '') + '></td>';
                    html += '<td><input type="checkbox" class="perm-checkbox" data-scope="parent" data-menu-id="' + menu.id + '" data-permission="can_read" name="permissions[' + menu.id + '][can_read]" value="1" ' + (perm.can_read ? 'checked' : '') + '></td>';
                    html += '<td><input type="checkbox" class="perm-checkbox" data-scope="parent" data-menu-id="' + menu.id + '" data-permission="can_update" name="permissions[' + menu.id + '][can_update]" value="1" ' + (perm.can_update ? 'checked' : '') + '></td>';
                    html += '<td><input type="checkbox" class="perm-checkbox" data-scope="parent" data-menu-id="' + menu.id + '" data-permission="can_delete" name="permissions[' + menu.id + '][can_delete]" value="1" ' + (perm.can_delete ? 'checked' : '') + '></td>';
                    html += '</tr>';
                    
                    if(menu.children && menu.children.length > 0) {
                        menu.children.forEach(function(child) {
                            var childPerm = permissions[child.id] || {};
                            html += '<tr>';
                            html += '<td>&nbsp;&nbsp;&nbsp;&nbsp;' + child.name + '</td>';
                            html += '<td><input type="checkbox" class="perm-checkbox" data-scope="child" data-parent-id="' + menu.id + '" data-menu-id="' + child.id + '" data-permission="can_create" name="permissions[' + child.id + '][can_create]" value="1" ' + (childPerm.can_create ? 'checked' : '') + '></td>';
                            html += '<td><input type="checkbox" class="perm-checkbox" data-scope="child" data-parent-id="' + menu.id + '" data-menu-id="' + child.id + '" data-permission="can_read" name="permissions[' + child.id + '][can_read]" value="1" ' + (childPerm.can_read ? 'checked' : '') + '></td>';
                            html += '<td><input type="checkbox" class="perm-checkbox" data-scope="child" data-parent-id="' + menu.id + '" data-menu-id="' + child.id + '" data-permission="can_update" name="permissions[' + child.id + '][can_update]" value="1" ' + (childPerm.can_update ? 'checked' : '') + '></td>';
                            html += '<td><input type="checkbox" class="perm-checkbox" data-scope="child" data-parent-id="' + menu.id + '" data-menu-id="' + child.id + '" data-permission="can_delete" name="permissions[' + child.id + '][can_delete]" value="1" ' + (childPerm.can_delete ? 'checked' : '') + '></td>';
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

    // Toggle child permissions when parent checkbox is changed and keep parents in sync
    $(document).on('change', '.perm-checkbox', function() {
        var scope = $(this).data('scope');
        var permission = $(this).data('permission');
        var menuId = $(this).data('menuId');
        var isChecked = $(this).is(':checked');

        if (scope === 'parent') {
            $('.perm-checkbox[data-scope="child"][data-parent-id="' + menuId + '"][data-permission="' + permission + '"]').prop('checked', isChecked);
        } else if (scope === 'child') {
            var parentId = $(this).data('parentId');
            var parentSelector = '.perm-checkbox[data-scope="parent"][data-menu-id="' + parentId + '"][data-permission="' + permission + '"]';
            var siblingsSelector = '.perm-checkbox[data-scope="child"][data-parent-id="' + parentId + '"][data-permission="' + permission + '"]';
            var hasCheckedSiblings = $(siblingsSelector + ':checked').length > 0;

            $(parentSelector).prop('checked', hasCheckedSiblings);
        }
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
@endsection
