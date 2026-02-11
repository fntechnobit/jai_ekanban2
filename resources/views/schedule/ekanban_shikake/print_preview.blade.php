@extends('layouts.master')

@section('title', 'eKanban Shikake - Print Preview')

@section('breadcrumb')
    <x-page-header menu-code="ekanban_shikake_print_preview" />
@endsection

@section('content')
    <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-wrench"></i> eKanban Shikake - Print Preview from Office</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-info btn-sm" id="btn-refresh">
                            <i class="fa-solid fa-arrows-rotate"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form class="form-horizontal">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3 row">
                                    <label for="filter_area" class="col-sm-3 col-form-label">Area:</label>
                                    <div class="col-sm-9">
                                        <select class="form-select select2" id="filter_area">
                                            <option value="">- All Area -</option>
                                            @foreach($areas as $area)
                                                <option value="{{ $area->id }}">{{ $area->area }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 row">
                                    <label for="filter_conveyor" class="col-sm-3 col-form-label">Conveyor:</label>
                                    <div class="col-sm-9">
                                        <select class="form-select select2" id="filter_conveyor">
                                            <option value="">- Choose Conveyor -</option>
                                            @foreach($conveyors as $conveyor)
                                                <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3 row">
                                    <label for="filter_machine" class="col-sm-3 col-form-label">Machine:</label>
                                    <div class="col-sm-9">
                                        <select class="form-select select2" id="filter_machine">
                                            <option value="">- Choose Machine -</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 row">
                                    <label for="filter_dates" class="col-sm-3 col-form-label">Dates:</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control form-control-sm" id="filter_dates" readonly placeholder="Select date range">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3 row">
                                    <label for="filter_shift" class="col-sm-3 col-form-label">Shift:</label>
                                    <div class="col-sm-9">
                                        <select class="form-select select2" id="filter_shift">
                                            <option value="">- All Shift -</option>
                                            <option value="1">Shift 1</option>
                                            <option value="2">Shift 2</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label">&nbsp;</label>
                                    <div class="col-sm-9">
                                        <button type="button" class="btn btn-secondary btn-sm" id="btn-reset">
                                            <i class="fa-solid fa-arrow-rotate-right"></i> Reset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table id="shikake-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">Num.</th>
                                    <th>Conveyor</th>
                                    <th>Dates</th>
                                    <th>Shift</th>
                                    <th>Assy</th>
                                    <th>Listing</th>
                                    <th>Pallet</th>
                                    <th>W/D</th>
                                    <th width="8%">#</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" >
        <div class="modal-dialog modal-lg" >
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Shikake Label Preview</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        
                    </button>
                </div>
                <div class="modal-body" id="preview-content" style="max-height: 70vh; overflow: auto;">
                    <!-- Preview content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="printPreview()">
                        <i class="fa-solid fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        var table;
        var currentPreviewData = null;
        
        $(function () {
            // Initialize Select2
            $('.select2').select2({
                allowClear: true
            });

            // Initialize date range picker
            var startDate = moment();
            var endDate = moment();

            $('#filter_dates').daterangepicker({
                startDate: startDate,
                endDate: endDate,
                locale: {
                    format: 'DD-MM-YYYY'
                }
            });

            // DataTable
            table = $('#shikake-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('schedule.ekanban-shikake.print-preview') }}",
                    data: function(d) {
                        var dates = $('#filter_dates').data('daterangepicker');
                        d.area_id = $('#filter_area').val();
                        d.conveyor_id = $('#filter_conveyor').val();
                        d.machine = $('#filter_machine').val();
                        d.start_date = dates.startDate.format('YYYY-MM-DD');
                        d.end_date = dates.endDate.format('YYYY-MM-DD');
                        d.shift = $('#filter_shift').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '5%' },
                    { data: 'conveyor', name: 'conveyor', width: '12%' },
                    { data: 'dates', name: 'dates', width: '12%' },
                    { data: 'shift', name: 'shift', width: '10%' },
                    { data: 'assy', name: 'assy', width: '20%' },
                    { data: 'listing', name: 'listing', width: '10%' },
                    { data: 'pallet', name: 'pallet', width: '10%' },
                    { data: 'wd', name: 'wd', width: '13%' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '8%' }
                ],
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[2, 'asc']]
            });

            // Auto-reload when filters change
            $('#filter_machine, #filter_shift').on('change', function() {
                table.ajax.reload();
            });

            $('#filter_dates').on('apply.daterangepicker', function() {
                table.ajax.reload();
            });

            $('#btn-reset').click(function() {
                $('.select2').val('').trigger('change');
                $('#filter_machine').empty().append('<option value="">- Choose Machine -</option>');
                $('#filter_dates').data('daterangepicker').setStartDate(moment());
                $('#filter_dates').data('daterangepicker').setEndDate(moment());
                table.ajax.reload();
            });

            // Dynamic machine loading based on conveyor selection
            $('#filter_conveyor').change(function() {
                var conveyorId = $(this).val();
                var machineSelect = $('#filter_machine');
                
                machineSelect.empty().append('<option value="">- Choose Machine -</option>');
                
                if (conveyorId) {
                    $.ajax({
                        url: "{{ route('schedule.ekanban-shikake.machines-by-conveyor') }}",
                        type: 'GET',
                        data: { conveyor_id: conveyorId },
                        success: function(machines) {
                            $.each(machines, function(index, machine) {
                                machineSelect.append('<option value="' + machine.machine + '">' + machine.name + '</option>');
                            });
                        },
                        error: function() {
                            console.error('Failed to load machines');
                        }
                    });
                }
            });

            $('#btn-refresh').click(function() {
                table.ajax.reload();
            });

            // Preview button handler
            $(document).on('click', '.btn-preview', function() {
                var ids = $(this).data('ids');
                showPreview(ids);
            });
        });

        function showPreview(ids) {
            $.ajax({
                url: "{{ route('schedule.ekanban-shikake.print') }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: ids
                },
                success: function(response) {
                    if (response.success) {
                        currentPreviewData = response.shikakes;
                        renderPreview(response.shikakes);
                        $('#previewModal').modal('show');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error!', 'Failed to get shikake data', 'error');
                }
            });
        }

        function renderPreview(shikakes) {
            var html = '';
            
            shikakes.forEach(function(shikake, index) {
                html += `
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5>Shikake Label ${index + 1}</h5>
                        </div>
                        <div class="card-body" style="font-family: monospace;">
                            <div class="row">
                                <div class="col-6">
                                    <strong>Shikake:</strong> ${shikake.shikake_name || 'N/A'}<br>
                                    <strong>Code:</strong> ${shikake.shikake_code || 'N/A'}<br>
                                    <strong>Conveyor:</strong> ${shikake.conveyor}<br>
                                    <strong>Area:</strong> ${shikake.area || 'N/A'}
                                </div>
                                <div class="col-6">
                                    <strong>Assy:</strong> ${shikake.assy}<br>
                                    <strong>Qty:</strong> ${shikake.qty}<br>
                                    <strong>Pallet Count:</strong> ${shikake.pallet_count}<br>
                                    <strong>Schedule:</strong> ${moment(shikake.schedule).format('DD-MM-YYYY')}<br>
                                    <strong>Shift:</strong> ${shikake.shift}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            $('#preview-content').html(html);
        }

        function printPreview() {
            if (currentPreviewData) {
                window.print();
            }
        }
    </script>

    <style>
        @media print {
            .modal-header, .modal-footer, .btn {
                display: none !important;
            }
            .modal-dialog {
                max-width: 100% !important;
                margin: 0 !important;
            }
            .modal-content {
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>
@endsection