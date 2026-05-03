@extends('layouts.master')

@section('title', 'Add History')

@section('breadcrumb')
    <x-page-header menu-code="addition_history" />
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <h5 class="card-title mb-0">
                <i class="fa-solid fa-clock-rotate-left me-2"></i> Addition History
            </h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary active" id="btn-type-circuit">Circuit</button>
                    <button type="button" class="btn btn-outline-warning" id="btn-type-shikake">Shikake</button>
                </div>
                <input type="date" class="form-control form-control-sm" id="filter_date_from" value="{{ date('Y-m-01') }}" style="width: 140px;">
                <span class="text-muted">-</span>
                <input type="date" class="form-control form-control-sm" id="filter_date_to" value="{{ date('Y-m-d') }}" style="width: 140px;">
                <select class="form-select form-select-sm select2" id="filter_conveyor_id" data-placeholder="- All Conveyor -" style="width: 160px;">
                    <option value="">- All Conveyor -</option>
                    @foreach($conveyors as $conveyor)
                        <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm select2" id="filter_shift" data-placeholder="- All Shift -" style="width: 120px;">
                    <option value="">- All Shift -</option>
                    <option value="1">Shift 1</option>
                    <option value="2">Shift 2</option>
                    <option value="3">Shift 3</option>
                </select>
                <select class="form-select form-select-sm select2" id="filter_shikake_type" data-placeholder="- All Type -" style="width: 140px; display: none;">
                    <option value="">- All Type -</option>
                    @foreach($shikakeTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-danger btn-sm" id="btn-reset" title="Reset Filter">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- History Table -->
            <table id="history-table" class="table table-bordered table-striped table-sm">
                <thead class="table-dark">
                    <tr>
                        <th width="4%">No</th>
                        <th width="10%">Date</th>
                        <th width="6%">Shift</th>
                        <th width="12%">Conveyor</th>
                        <th>Code</th>
                        <th width="8%" id="th-type" style="display: none;">Type</th>
                        <th width="7%">Qty</th>
                        <th width="7%">Before</th>
                        <th width="7%">After</th>
                        <th width="14%">Reason</th>
                        <th width="10%">Created By</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(function () {
    var currentType = 'circuit';

    // Initialize Select2 for filters
    $('#filter_conveyor_id, #filter_shift, #filter_shikake_type').select2({
        theme: 'bootstrap-5',
        allowClear: true,
        placeholder: function () {
            return $(this).data('placeholder') || 'Select...';
        }
    });

    // Hide shikake type filter initially (circuit mode)
    $('#filter_shikake_type').closest('.select2-container').hide();

    // Build DataTable columns based on type
    function getColumns(type) {
        var cols = [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'date_display', name: 'date_display', className: 'text-center' },
            { data: 'shift_display', name: 'shift', className: 'text-center' },
            { data: 'conveyor_display', name: 'conveyor_display' },
            { data: 'code_display', name: 'code_display' }
        ];
        if (type === 'shikake') {
            cols.push({ data: 'type_display', name: 'type_display', className: 'text-center' });
        }
        cols.push(
            { data: 'qty_display', name: 'qty_addition', className: 'text-end' },
            { data: 'balance_before', name: 'balance_before', className: 'text-end' },
            { data: 'balance_after', name: 'balance_after', className: 'text-end' },
            { data: 'reason_display', name: 'reason' },
            { data: 'creator_name', name: 'creator_name' }
        );
        return cols;
    }

    // Initialize DataTable
    var table = $('#history-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('addition.history.datatable') }}",
            data: function (d) {
                d.type         = currentType;
                d.date_from    = $('#filter_date_from').val();
                d.date_to      = $('#filter_date_to').val();
                d.conveyor_id  = $('#filter_conveyor_id').val();
                d.shift        = $('#filter_shift').val();
                d.shikake_type = $('#filter_shikake_type').val();
            }
        },
        columns: getColumns('circuit'),
        pageLength: 50,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[1, 'desc']]
    });

    // Type selector
    function switchType(type) {
        currentType = type;

        // Update button active state
        $('#btn-type-circuit, #btn-type-shikake').removeClass('active');
        if (type === 'circuit') {
            $('#btn-type-circuit').addClass('active');
            $('#filter_shikake_type').closest('.select2-container').hide();
            $('#filter_shikake_type').hide();
            $('#th-type').hide();
        } else {
            $('#btn-type-shikake').addClass('active');
            $('#filter_shikake_type').closest('.select2-container').show();
            $('#filter_shikake_type').show();
            $('#th-type').show();
        }

        // Destroy and rebuild table with correct columns
        table.destroy();
        table = $('#history-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('addition.history.datatable') }}",
                data: function (d) {
                    d.type         = currentType;
                    d.date_from    = $('#filter_date_from').val();
                    d.date_to      = $('#filter_date_to').val();
                    d.conveyor_id  = $('#filter_conveyor_id').val();
                    d.shift        = $('#filter_shift').val();
                    d.shikake_type = $('#filter_shikake_type').val();
                }
            },
            columns: getColumns(type),
            pageLength: 50,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            order: [[1, 'desc']]
        });
    }

    $('#btn-type-circuit').on('click', function () { switchType('circuit'); });
    $('#btn-type-shikake').on('click', function () { switchType('shikake'); });

    // Filter change handlers
    $('#filter_date_from, #filter_date_to').on('change', function () {
        table.ajax.reload();
    });
    $('#filter_conveyor_id, #filter_shift, #filter_shikake_type').on('change', function () {
        table.ajax.reload();
    });

    // Reset filter
    $('#btn-reset').on('click', function () {
        $('#filter_date_from').val('{{ date("Y-m-01") }}');
        $('#filter_date_to').val('{{ date("Y-m-d") }}');
        $('#filter_conveyor_id').val('').trigger('change');
        $('#filter_shift').val('').trigger('change');
        $('#filter_shikake_type').val('').trigger('change');
        table.ajax.reload();
    });
});
</script>
@endsection
