@extends('layouts.master')

@section('title', 'History Print Cutting')

@section('breadcrumb')
    <x-page-header menu-code="ekanban_cutting_history" />
@endsection

@section('content')
    <style>
        .card-body .select2-container--bootstrap-5 .select2-selection--single,
        .card-body .select2-container--bootstrap-5 .select2-selection {
            height: 31px !important;
            min-height: 31px !important;
            padding: 0.25rem 0.5rem !important;
        }
        .card-body .select2-container--bootstrap-5 .select2-selection__rendered {
            line-height: 1.5 !important;
            padding-left: 0 !important;
            padding-top: 0 !important;
        }
        .card-body .select2-container--bootstrap-5 .select2-selection__arrow {
            height: 29px !important;
        }
        #filter_dates {
            text-align: right;
        }
    </style>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    <i class="fa-solid fa-clock-rotate-left"></i> History Print Cutting
                </h3>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-info btn-sm" id="btn-refresh">
                        <i class="fa-solid fa-arrows-rotate"></i> Refresh
                    </button>
                    <button type="button" class="btn btn-success btn-sm" id="btn-export">
                        <i class="fa-solid fa-file-excel"></i> Export Excel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters - one row: type/process, area, machine, print date range, shift-cutoff, reset -->
                <form class="mb-3">
                    <div class="row g-2">
                        <div class="col-md-2">
                            <select class="form-select form-select-sm select2" id="filter_type">
                                <option value="all">- All Type -</option>
                                <option value="CUTTING">CUTTING</option>
                                <option value="CUTTING_TWIST">CUTTING TWIST</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm select2" id="filter_area">
                                <option value="">- All Area -</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}">{{ $area->area }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm select2" id="filter_machine">
                                <option value="all">- All Machine -</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm" id="filter_dates" readonly placeholder="Rentang tanggal print">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm select2" id="filter_shift_co">
                                <option value="">- All Shift/CO -</option>
                                @foreach([1, 2] as $shiftNo)
                                    @foreach([1, 2, 3, 4, 5] as $cutoffNo)
                                        <option value="{{ $shiftNo }}-{{ $cutoffNo }}">S{{ $shiftNo }}/CO{{ $cutoffNo }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-secondary w-100" id="btn-reset" title="Reset Filters"
                                    style="padding: 0.25rem 0.5rem; font-size: 0.875rem; height: 31px;">
                                <i class="fa-solid fa-arrow-rotate-right"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table id="history-table" class="table table-bordered table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th width="4%">No</th>
                                <th>CCT</th>
                                <th>Shikake</th>
                                <th>CV</th>
                                <th>Store</th>
                                <th>Family</th>
                                <th>Qty</th>
                                <th>Issue</th>
                                <th>Seq</th>
                                <th>Kanban</th>
                                <th>CO</th>
                                <th>Machine</th>
                                <th>Tgl Schedule</th>
                                <th>Tgl Print</th>
                                <th>Diprint Oleh</th>
                                <th>Jml Print</th>
                                <th width="6%">#</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal Styles -->
    <style>
        #previewContent #print_stack_ajax {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        #previewContent .ticket {
            transform: scale(0.55);
            transform-origin: top center;
            margin-bottom: -259px; /* compensate 576px * 0.45 wasted space from scale */
        }
        #previewContent .ticket:last-child {
            margin-bottom: 0;
        }
        /* Force borders visible inside preview - override Bootstrap resets */
        #previewContent .ticket-circuit-print,
        #previewContent .ticket-twist-print {
            border: 2px solid #000 !important;
            overflow: visible !important;
        }
        #previewContent .ticket-circuit-print table,
        #previewContent .ticket-twist-print table {
            border-collapse: collapse !important;
            border: 1px solid #000 !important;
        }
        #previewContent .ticket-circuit-print th,
        #previewContent .ticket-circuit-print td,
        #previewContent .ticket-twist-print th,
        #previewContent .ticket-twist-print td {
            border: 1px solid #000 !important;
        }
        #previewContent .ticket-circuit-print thead th,
        #previewContent .ticket-twist-print thead th {
            border: 2px solid #000 !important;
        }
        #previewContent .ticket-circuit-print .section-label.black-bg,
        #previewContent .ticket-twist-print .twist-section-label.black-bg {
            background-color: #000 !important;
            color: #fff !important;
        }
    </style>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewModalLabel">Print Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="previewContent" style="max-height: 70vh; overflow-y: auto;">
                    <div class="text-center">
                        <i class="fa-solid fa-spinner ti-spin" style="font-size: 3rem;"></i>
                        <p>Loading preview...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        var table;

        // Suppress default DataTables error alert (we handle it ourselves)
        $.fn.dataTable.ext.errMode = 'none';

        // localStorage key for persisting filters, so a refresh or a trip to
        // another page comes back to the same view (same as the print menu)
        var FILTER_STORAGE_KEY = 'ekanban_circuit_history_filters';

        function saveFilters() {
            var range = $('#filter_dates').data('daterangepicker');
            var filters = {
                type: $('#filter_type').val(),
                area: $('#filter_area').val(),
                machine: $('#filter_machine').val(),
                shift_co: $('#filter_shift_co').val(),
                date_start: range.startDate.format('YYYY-MM-DD'),
                date_end: range.endDate.format('YYYY-MM-DD')
            };
            try {
                localStorage.setItem(FILTER_STORAGE_KEY, JSON.stringify(filters));
            } catch (e) {
                console.warn('Failed to save filters:', e);
            }
        }

        function loadFilters() {
            try {
                var raw = localStorage.getItem(FILTER_STORAGE_KEY);
                return raw ? JSON.parse(raw) : null;
            } catch (e) {
                console.warn('Failed to load filters:', e);
                return null;
            }
        }

        function clearFilters() {
            try {
                localStorage.removeItem(FILTER_STORAGE_KEY);
            } catch (e) {
                console.warn('Failed to clear filters:', e);
            }
        }

        $(function () {
            $('.select2').select2({
                allowClear: true,
                theme: 'bootstrap-5'
            });

            // Restore the filters saved on the previous visit (default: today)
            var savedFilters = loadFilters();

            $('#filter_dates').daterangepicker({
                autoApply: true,
                startDate: (savedFilters && savedFilters.date_start)
                    ? moment(savedFilters.date_start, 'YYYY-MM-DD')
                    : moment(),
                endDate: (savedFilters && savedFilters.date_end)
                    ? moment(savedFilters.date_end, 'YYYY-MM-DD')
                    : moment(),
                locale: { format: 'DD-MM-YYYY' }
            });

            // Restore the dropdowns except machine, which is filled after its
            // options are fetched for the restored area + type
            if (savedFilters) {
                if (savedFilters.type) $('#filter_type').val(savedFilters.type);
                if (savedFilters.area) $('#filter_area').val(savedFilters.area);
                if (savedFilters.shift_co) $('#filter_shift_co').val(savedFilters.shift_co);
                $('#filter_type, #filter_area, #filter_shift_co').trigger('change.select2');
            }

            function currentFilters() {
                var range = $('#filter_dates').data('daterangepicker');
                // The Shift/CO filter carries both values as "shift-cutoff"
                var shiftCutoff = ($('#filter_shift_co').val() || '').split('-');

                return {
                    date_start: range.startDate.format('YYYY-MM-DD'),
                    date_end: range.endDate.format('YYYY-MM-DD'),
                    type: $('#filter_type').val(),
                    area_id: $('#filter_area').val(),
                    machine: $('#filter_machine').val(),
                    shift: shiftCutoff[0] || '',
                    cutoff: shiftCutoff[1] || ''
                };
            }

            table = $('#history-table').DataTable({
                processing: true,
                serverSide: true,
                deferLoading: 0, // first load is fired once machines are restored
                scrollX: true,
                scrollCollapse: true,
                ordering: false,
                pageLength: 50,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                ajax: {
                    url: "{{ route('schedule.ekanban-circuit.history') }}",
                    data: function (d) {
                        $.extend(d, currentFilters());
                    },
                    error: function (xhr) {
                        if (xhr.status === 401 || xhr.status === 419 ||
                            (xhr.status === 200 && typeof xhr.responseJSON === 'undefined' &&
                             xhr.responseText && xhr.responseText.indexOf('<html') !== -1)) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Sesi Berakhir',
                                text: 'Sesi login Anda telah berakhir. Halaman akan dimuat ulang.',
                                timer: 3000,
                                showConfirmButton: false
                            }).then(function () {
                                window.location.reload();
                            });
                            return;
                        }
                        var msg = (xhr.responseJSON && xhr.responseJSON.error)
                            ? xhr.responseJSON.error
                            : 'Gagal memuat data (HTTP ' + xhr.status + '). Coba refresh.';
                        $('#history-table tbody').html(
                            '<tr><td colspan="17" class="text-center text-danger">' +
                            '<i class="fa-solid fa-triangle-exclamation"></i> ' + msg + '</td></tr>'
                        );
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', width: '4%', className: 'text-center' },
                    {
                        data: 'type',
                        name: 'type',
                        width: '12%',
                        render: function (data, type, row) {
                            var badge = data === 'CUTTING_TWIST'
                                ? '<span class="badge bg-info">TWS</span>'
                                : '<span class="badge bg-primary">CCT</span>';
                            return badge + ' <span class="text-muted">' + (row.cct_code || '') + '</span>';
                        }
                    },
                    { data: 'shikake_code', name: 'shikake_code' },
                    { data: 'conveyor', name: 'conveyor' },
                    { data: 'to_store', name: 'to_store' },
                    { data: 'family', name: 'family' },
                    { data: 'qty', name: 'qty', className: 'text-end' },
                    {
                        data: 'issue_count',
                        name: 'issue_count',
                        className: 'text-end',
                        render: function (data) {
                            return '<span class="badge bg-secondary fw-bold">' + (data || 0) + '</span>';
                        }
                    },
                    { data: 'sequence', name: 'sequence', className: 'text-center' },
                    {
                        data: 'barcodes',
                        name: 'barcodes',
                        render: function (data) {
                            if (data && data !== '-') {
                                return data.split(', ').map(function (b) {
                                    return '<code>' + b + '</code>';
                                }).join('<br>');
                            }
                            return '-';
                        }
                    },
                    {
                        data: 'shift',
                        name: 'shift',
                        className: 'text-center',
                        render: function (data, type, row) {
                            var colorClass = (row.shift == 1) ? 'bg-primary' : 'bg-danger';
                            return '<span class="badge ' + colorClass + '">' + (row.shift || '-') + '/' + (row.cutoff || '-') + '</span>';
                        }
                    },
                    { data: 'machine', name: 'machine' },
                    { data: 'date', name: 'date', className: 'text-center' },
                    {
                        data: 'printed_at',
                        name: 'printed_at',
                        className: 'text-center',
                        render: function (data) {
                            return '<span class="text-nowrap">' + (data || '-') + '</span>';
                        }
                    },
                    { data: 'printed_by', name: 'printed_by' },
                    {
                        data: 'print_count',
                        name: 'print_count',
                        className: 'text-center',
                        render: function (data) {
                            return '<span class="badge bg-success">' + (data || 0) + 'x</span>';
                        }
                    },
                    {
                        data: 'group_id',
                        name: 'group_id',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function (data) {
                            return '<button type="button" class="btn btn-soft-info btn-sm btn-preview" ' +
                                'data-group-id="' + data + '" title="Preview hasil print">' +
                                '<i class="fa-solid fa-eye"></i></button>';
                        }
                    }
                ]
            });

            // Area + type drive the machine list - refresh its options first and
            // only then reload, so the table never queries a stale machine
            $('#filter_area, #filter_type').on('change', function () {
                loadMachines($('#filter_area').val(), $('#filter_type').val(), { autoReload: true });
            });

            $('#filter_machine, #filter_shift_co').on('change', function () {
                saveFilters();
                table.ajax.reload();
            });

            $('#filter_dates').on('apply.daterangepicker', function () {
                saveFilters();
                table.ajax.reload();
            });

            $('#btn-refresh').click(function () {
                table.ajax.reload(null, false);
            });

            $('#btn-reset').click(function () {
                clearFilters();
                $('#filter_type').val('all').trigger('change.select2');
                $('#filter_area').val('').trigger('change.select2');
                $('#filter_shift_co').val('').trigger('change.select2');
                $('#filter_dates').data('daterangepicker').setStartDate(moment());
                $('#filter_dates').data('daterangepicker').setEndDate(moment());
                loadMachines('', 'all', { autoReload: true });
            });

            $('#btn-export').click(function () {
                var params = $.param(currentFilters());
                window.location.href = "{{ route('schedule.ekanban-circuit.history.export') }}?" + params;
            });

            // Machine options follow area (and type) - "all" stays available.
            // opts.selected keeps a previously chosen machine when it is still
            // offered; opts.autoReload refreshes the table once the list is set.
            function loadMachines(areaId, machineType, opts) {
                opts = opts || {};
                var machineSelect = $('#filter_machine');
                machineSelect.empty().append('<option value="all">- All Machine -</option>');

                var finish = function () {
                    machineSelect.trigger('change.select2');
                    saveFilters();
                    if (opts.autoReload) {
                        table.ajax.reload();
                    }
                };

                if (!areaId) {
                    machineSelect.val('all');
                    finish();
                    return;
                }

                $.ajax({
                    url: "{{ route('schedule.ekanban-circuit.machines-by-conveyor') }}",
                    type: 'GET',
                    data: { area_id: areaId, type: machineType },
                    success: function (machines) {
                        $.each(machines, function (index, machine) {
                            machineSelect.append('<option value="' + machine.machine + '">' + machine.name + '</option>');
                        });

                        if (opts.selected && machineSelect.find('option[value="' + opts.selected + '"]').length) {
                            machineSelect.val(opts.selected);
                        } else {
                            machineSelect.val('all');
                        }
                        finish();
                    },
                    error: function () {
                        console.error('Failed to load machines');
                        finish();
                    }
                });
            }

            // Restore the machine options for the saved area + type, then run
            // the first query (the table was created with deferLoading)
            loadMachines($('#filter_area').val(), $('#filter_type').val(), {
                selected: savedFilters && savedFilters.machine,
                autoReload: true
            });

            // Preview the printed kanban exactly as it was sent to the printer
            $(document).on('click', '.btn-preview', function () {
                var groupId = $(this).data('group-id');

                $('#previewModal').modal('show');
                $('#previewContent').html('<div class="text-center"><i class="fa-solid fa-spinner ti-spin" style="font-size: 3rem;"></i><p>Loading preview...</p></div>');

                $.ajax({
                    url: "{{ route('schedule.ekanban-circuit.print-preview') }}",
                    type: 'GET',
                    data: { ids: groupId },
                    success: function (response) {
                        $('#previewContent').html(response);
                    },
                    error: function (xhr, status, error) {
                        $('#previewContent').html('<div class="alert alert-danger">Failed to load preview: ' + error + '</div>');
                    }
                });
            });
        });
    </script>
@endsection
