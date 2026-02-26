@extends('layouts.master')

@section('title', 'eKanban Circuit - Print Preview')

@section('breadcrumb')
    <x-page-header menu-code="ekanban_cutting" />
@endsection

@section('content')
    <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-route"></i> eKanban Circuit - Print Preview from Office</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-info btn-sm" id="btn-refresh">
                            <i class="fa-solid fa-arrows-rotate"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label for="filter_area">Area:</label>
                            <select class="form-select select2" id="filter_area">
                                <option value="">- All Area -</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}">{{ $area->area }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="filter_conveyor">Conveyor:</label>
                            <select class="form-select select2" id="filter_conveyor">
                                <option value="">- Choose Conveyor -</option>
                                @foreach($conveyors as $conveyor)
                                    <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filter_dates">Dates:</label>
                            <input type="text" class="form-control form-control-sm" id="filter_dates" readonly placeholder="Select date range">
                        </div>
                        <div class="col-md-2">
                            <label for="filter_shift">Shift:</label>
                            <select class="form-select select2" id="filter_shift">
                                <option value="">- All Shift -</option>
                                <option value="1">Shift 1</option>
                                <option value="2">Shift 2</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label><br>
                            <button type="button" class="btn btn-secondary btn-sm" id="btn-reset">
                                <i class="fa-solid fa-arrow-rotate-right"></i> Reset
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="circuit-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">Num.</th>
                                    <th>Conveyor</th>
                                    <th>Dates</th>
                                    <th>Shift</th>
                                    <th>Assy</th>
                                    <th>Listing</th>
                                    <th>Circuit</th>
                                    <th>Area</th>
                                    <th>Status</th>
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
                    <h4 class="modal-title">Circuit Label Preview</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        
                    </button>
                </div>
                <div class="modal-body" id="preview-content">
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
            table = $('#circuit-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('schedule.ekanban-circuit.print-preview') }}",
                    data: function(d) {
                        var dates = $('#filter_dates').data('daterangepicker');
                        d.area_id = $('#filter_area').val();
                        d.conveyor_id = $('#filter_conveyor').val();
                        d.start_date = dates.startDate.format('YYYY-MM-DD');
                        d.end_date = dates.endDate.format('YYYY-MM-DD');
                        d.shift = $('#filter_shift').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '5%' },
                    { data: 'conveyor', name: 'conveyor', width: '10%' },
                    { data: 'dates', name: 'dates', width: '10%' },
                    { data: 'shift', name: 'shift', width: '8%' },
                    { data: 'assy', name: 'assy', width: '15%' },
                    { data: 'listing', name: 'listing', width: '8%' },
                    { data: 'circuit_name', name: 'circuit_name', width: '20%' },
                    { data: 'area', name: 'area', width: '10%' },
                    { data: 'status', name: 'status', width: '8%' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '8%' }
                ],
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[2, 'asc']]
            });

            // Auto-reload when filters change
            $('#filter_area, #filter_conveyor, #filter_shift').on('change', function() {
                table.ajax.reload();
            });

            $('#filter_dates').on('apply.daterangepicker', function() {
                table.ajax.reload();
            });

            $('#btn-reset').click(function() {
                $('.select2').val('').trigger('change');
                $('#filter_dates').data('daterangepicker').setStartDate(moment());
                $('#filter_dates').data('daterangepicker').setEndDate(moment());
                table.ajax.reload();
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
                url: "{{ route('schedule.ekanban-circuit.print') }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: ids
                },
                success: function(response) {
                    if (response.success) {
                        currentPreviewData = response.circuits;
                        renderPreview(response.circuits);
                        $('#previewModal').modal('show');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error!', 'Failed to get circuit data', 'error');
                }
            });
        }

        function renderPreview(circuits) {
            var html = '';
            
            circuits.forEach(function(circuit, index) {
                html += `
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5>Circuit Label ${index + 1}</h5>
                        </div>
                        <div class="card-body" style="font-family: monospace;">
                            <div class="row">
                                <div class="col-6">
                                    <strong>Circuit:</strong> ${circuit.circuit_name || 'N/A'}<br>
                                    <strong>Code:</strong> ${circuit.circuit_code || 'N/A'}<br>
                                    <strong>Conveyor:</strong> ${circuit.conveyor}<br>
                                    <strong>Area:</strong> ${circuit.area || 'N/A'}
                                </div>
                                <div class="col-6">
                                    <strong>Assy:</strong> ${circuit.assy}<br>
                                    <strong>Qty:</strong> ${circuit.qty}<br>
                                    <strong>Schedule:</strong> ${moment(circuit.schedule).format('DD-MM-YYYY')}<br>
                                    <strong>Shift:</strong> ${circuit.shift}
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