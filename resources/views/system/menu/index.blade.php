@extends('layout')

@section('title', 'Menu Management')

@section('content')
<div class="content-header" >
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Menu Management</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Menus</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Menu List</h3>
                <div class="card-tools">
                    @if(auth()->user()->hasMenuPermission('menus', 'can_create'))
                        <button type="button" class="btn btn-primary btn-sm" id="btn-add">
                            <i class="fas fa-plus"></i> Add Menu
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <table id="menus-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>URL</th>
                            <th>Icon</th>
                            <th>Parent</th>
                            <th width="8%">Order</th>
                            <th width="10%">Status</th>
                            <th width="12%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

@include('system.menu.form')
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
    var table = $('#menus-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('system.menus.datatable') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'code', name: 'code'},
            {data: 'name', name: 'name'},
            {data: 'url', name: 'url'},
            {data: 'icon', name: 'icon'},
            {data: 'parent_name', name: 'parent.name'},
            {data: 'order', name: 'order'},
            {data: 'status', name: 'is_active', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    // Add Menu Button
    $('#btn-add').click(function() {
        $('#menuForm')[0].reset();
        $('#menu_id').val('');
        $('#menuModalLabel').text('Add Menu');
        $('.error-text').text('');
        $('#menuModal').modal('show');
    });

    // Edit Menu
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        $.ajax({
            url: "{{ route('system.menus.index') }}/" + id + "/edit",
            type: 'GET',
            success: function(response) {
                const menu = response.data || response;

                $('#menu_id').val(menu.id);
                $('#code').val(menu.code);
                $('#name').val(menu.name);
                $('#url').val(menu.url);
                $('#icon').val(menu.icon);
                $('#parent_id').val(menu.parent_id);
                $('#order').val(menu.order);

                if(menu.is_active == 1) {
                    $('#is_active_yes').prop('checked', true);
                } else {
                    $('#is_active_no').prop('checked', true);
                }
                
                $('#menuModalLabel').text('Edit Menu');
                $('.error-text').text('');
                $('#menuModal').modal('show');
            },
            error: function(xhr) {
                Swal.fire('Error!', 'Failed to load menu data', 'error');
            }
        });
    });

    // Save Menu
    $('#menuForm').submit(function(e) {
        e.preventDefault();
        $('.error-text').text('');
        
        var formData = $(this).serialize();
        var menuId = $('#menu_id').val();
        var url = menuId ? "{{ route('system.menus.index') }}/" + menuId : "{{ route('system.menus.store') }}";
        var method = menuId ? 'PUT' : 'POST';
        
        if(menuId) {
            formData += '&_method=PUT';
        }
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function(response) {
                $('#menuModal').modal('hide');
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

    // Delete Menu
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
                    url: "{{ route('system.menus.index') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        table.ajax.reload();
                        Swal.fire('Deleted!', response.message, 'success');
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON.message || 'Failed to delete menu', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
