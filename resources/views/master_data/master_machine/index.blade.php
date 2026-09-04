@extends('layouts.master')

@section('title', 'Machine Data')

@section('breadcrumb')
    <x-page-header menu-code="master_machine" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Machine Data</h5>
                <div class="card-tools float-end">
                    @if(auth()->user()->hasMenuPermission('master_machine', 'can_create'))
                        <button type="button" class="btn btn-primary btn-sm" id="btn-add">
                            <i class="fa-solid fa-plus"></i> Add New Data
                        </button>       
                    @endif
                </div>
            </div>
            <div class="card-body"
                 data-can-update="{{ auth()->user()->hasMenuPermission('master_machine', 'can_update') ? '1' : '0' }}"
                 data-can-delete="{{ auth()->user()->hasMenuPermission('master_machine', 'can_delete') ? '1' : '0' }}">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="filter_area" class="form-label">Area :</label>
                        <select class="form-select select2" id="filter_area" style="width: 100%;">
                            <option value="">- All Area -</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->area }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filter_conveyor" class="form-label">Conveyor :</label>
                        {{-- Opsi diisi lewat JS supaya mengikuti area yang dipilih --}}
                        <select class="form-select select2" id="filter_conveyor" style="width: 100%;"></select>
                    </div>
                </div>

                <table id="master-machine-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Machine</th>
                            <th>Area</th>
                            <th>Conveyor</th>
                            <th width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('master_data.master_machine.form')
@endsection

@section('script')
    @php
        $conveyorOptions = $conveyors->map(fn ($conveyor) => [
            'id' => $conveyor->id,
            'conveyor' => $conveyor->conveyor,
            'area_id' => $conveyor->master_area_id,
            'area' => $conveyor->area->area ?? '-',
        ])->values();
    @endphp
    <script>
        $(function () {
            // Sumber data conveyor untuk cascading area -> conveyor
            var CONVEYORS = @json($conveyorOptions);

            /**
             * Isi ulang opsi conveyor sesuai area yang dipilih.
             * opts.placeholder : teks opsi kosong (khusus dropdown filter)
             * opts.showAll     : true = area kosong berarti semua conveyor, false = tidak ada opsi
             * opts.selected    : nilai yang dipilih ulang setelah opsi dibangun
             */
            function fillConveyorOptions($select, areaId, opts) {
                opts = opts || {};
                $select.empty();

                if (opts.placeholder) {
                    $select.append(new Option(opts.placeholder, '', false, false));
                }

                if (areaId || opts.showAll) {
                    CONVEYORS.forEach(function (conveyor) {
                        if (areaId && String(conveyor.area_id) !== String(areaId)) {
                            return;
                        }
                        var label = areaId ? conveyor.conveyor : conveyor.conveyor + ' (' + conveyor.area + ')';
                        $select.append(new Option(label, conveyor.id, false, false));
                    });
                }

                // change.select2 hanya menyegarkan tampilan select2, tidak memicu handler filter
                $select.val(opts.selected || ($select.prop('multiple') ? [] : '')).trigger('change.select2');
            }

            // Initialize Select2 for filters
            $('#filter_area').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: '- All Area -'
            });

            $('#filter_conveyor').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: '- All Conveyor -'
            });

            fillConveyorOptions($('#filter_conveyor'), '', { placeholder: '- All Conveyor -', showAll: true });

            // DataTable
            var table = $('#master-machine-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('master-data.master-machine.datatable') }}",
                    data: function(d) {
                        d.area_id = $('#filter_area').val();
                        d.conveyor_id = $('#filter_conveyor').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'machine', name: 'machine' },
                    { data: 'area_name', name: 'area_name', orderable: false, searchable: false },
                    { data: 'conveyor_names', name: 'conveyor_names', orderable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                pageLength: 50
            });

            // Filter change events
            $('#filter_area').on('change', function() {
                // Conveyor mengikuti area, pilihan conveyor sebelumnya direset
                fillConveyorOptions($('#filter_conveyor'), $(this).val(), { placeholder: '- All Conveyor -', showAll: true });
                table.ajax.reload();
            });

            $('#filter_conveyor').on('change', function() {
                table.ajax.reload();
            });

            // Initialize Select2 for form
            function initFormSelect2() {
                $('#master_area_id').select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $('#masterMachineModal'),
                    placeholder: 'Select Area',
                    allowClear: true
                });

                $('#conveyor_ids').select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $('#masterMachineModal'),
                    placeholder: 'Select Conveyor',
                    allowClear: true
                });
            }

            // Conveyor pada form dibatasi ke area yang dipilih
            $('#master_area_id').on('change', function() {
                fillConveyorOptions($('#conveyor_ids'), $(this).val());
            });

            // Add Machine Button
            $('#btn-add').click(function () {
                $('#masterMachineForm')[0].reset();
                $('#machine_id').val('');
                $('#masterMachineModalLabel').text('Add Machine');
                $('.error-text').text('');
                
                initFormSelect2();

                // Reset Select2
                $('#master_area_id').val('').trigger('change.select2');
                fillConveyorOptions($('#conveyor_ids'), '');

                $('#masterMachineModal').modal('show');
            });

            // Edit Machine
            $(document).on('click', '.btn-edit', function () {
                var id = $(this).data('id');
                $.ajax({
                    url: "{{ route('master-data.master-machine.index') }}/" + id + "/edit",
                    type: 'GET',
                    success: function (response) {
                        const machine = response.data || response;

                        $('#machine_id').val(machine.id);
                        $('#machine').val(machine.machine);

                        initFormSelect2();

                        $('#master_area_id').val(machine.master_area_id || '').trigger('change.select2');
                        fillConveyorOptions($('#conveyor_ids'), machine.master_area_id || '', {
                            selected: machine.conveyor_ids
                        });

                        $('#masterMachineModalLabel').text('Edit Machine');
                        $('.error-text').text('');
                        $('#masterMachineModal').modal('show');
                    },
                    error: function (xhr) {
                        Swal.fire('Error!', 'Failed to load machine data', 'error');
                    }
                });
            });

            // Save Machine
            $('#masterMachineForm').submit(function (e) {
                e.preventDefault();
                $('.error-text').text('');

                var formData = $(this).serialize();
                var machineId = $('#machine_id').val();
                var url = machineId ? "{{ route('master-data.master-machine.index') }}/" + machineId : "{{ route('master-data.master-machine.store') }}";

                if (machineId) {
                    formData += '&_method=PUT';
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    success: function (response) {
                        $('#masterMachineModal').modal('hide');
                        table.ajax.reload();
                        Swal.fire('Success!', response.message, 'success');
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (key, value) {
                                // Handle array field errors (conveyor_ids.0 -> conveyor_ids)
                                var errorKey = key.split('.')[0];
                                $('.' + errorKey + '_error').text(value[0]);
                            });
                        } else {
                            Swal.fire('Error!', xhr.responseJSON.message || 'Something went wrong', 'error');
                        }
                    }
                });
            });

            // Delete Machine
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
                            url: "{{ route('master-data.master-machine.index') }}/" + id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function (response) {
                                table.ajax.reload();
                                Swal.fire('Deleted!', response.message, 'success');
                            },
                            error: function (xhr) {
                                Swal.fire('Error!', xhr.responseJSON.message || 'Failed to delete machine', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
