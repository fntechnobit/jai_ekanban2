@extends('layouts.master')

@section('title', 'Defect Cutting')

@section('breadcrumb')
    <x-page-header menu-code="defect_cutting" />
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <h5 class="card-title mb-0">
                <i class="fa-solid fa-scissors text-danger me-2"></i> Defect Cutting - Circuit List
            </h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <select class="form-select form-select-sm select2" id="filter_area_id" data-placeholder="- All Area -" style="width: 160px;">
                    <option value="">- All Area -</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}">{{ $area->area }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm select2" id="filter_conveyor_id" data-placeholder="- All Conveyor -" style="width: 180px;">
                    <option value="">- All Conveyor -</option>
                    @foreach($conveyors as $conveyor)
                        <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm select2" id="filter_type" data-placeholder="- All Type -" style="width: 160px;">
                    <option value="">- All Type -</option>
                    <option value="CUTTING">CUTTING</option>
                    <option value="CUTTING_TWIST">CUTTING TWIST</option>
                </select>
                <a href="{{ route('defect.history') }}" class="btn btn-outline-secondary btn-sm" title="History">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </a>
                <button type="button" class="btn btn-outline-danger btn-sm" id="btn-reset-filter" title="Reset Filter">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <table id="cutting-table" class="table table-bordered table-striped table-sm">
                <thead>
                    <tr>
                        <th width="4%">No</th>
                        <th width="6%">Type</th>
                        <th>Carline</th>
                        <th>Conveyor</th>
                        <th>CCT No</th>
                        <th>CCT Code</th>
                        <th>Shikake</th>
                        <th>Family</th>
                        <th width="5%">QTY</th>
                        <th>Machine</th>
                        <th width="5%">Seq</th>
                        <th width="8%">Balance</th>
                        <th width="10%">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Defect Modal -->
<div class="modal fade" id="defectModal" tabindex="-1" aria-labelledby="defectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="defectModalLabel">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> Record Defect
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="defect-form">
                <div class="modal-body">
                    <input type="hidden" id="master_circuit_id" name="master_circuit_id">
                    <input type="hidden" id="conveyor_id" name="conveyor_id">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Circuit Info</label>
                        <div class="card bg-light">
                            <div class="card-body py-2">
                                <div class="row">
                                    <div class="col-4"><small class="text-muted">Conveyor</small><div id="info-conveyor" class="fw-bold">-</div></div>
                                    <div class="col-4"><small class="text-muted">CCT No</small><div id="info-cct-no" class="fw-bold">-</div></div>
                                    <div class="col-4"><small class="text-muted">CCT Code</small><div id="info-cct-code" class="fw-bold">-</div></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 text-center">
                        <label class="form-label">Current Balance</label>
                        <div class="fs-3 fw-bold text-success" id="current-balance">0</div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="defect_date" class="form-label">Defect Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="defect_date" name="defect_date"
                                   value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="shift" class="form-label">Shift <span class="text-danger">*</span></label>
                            <select class="form-select" id="shift" name="shift" required>
                                <option value="">- Select Shift -</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="qty_defect" class="form-label">Qty Defect <span class="text-danger">*</span></label>
                            <input type="number" class="form-control text-end" id="qty_defect" name="qty_defect" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Balance After</label>
                            <div class="form-control bg-light text-end" id="balance-after">-</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason / Notes</label>
                        <textarea class="form-control" id="reason" name="reason" rows="2" placeholder="Enter reason..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="btn-submit">
                        <i class="fa-solid fa-check me-1"></i> Submit Defect
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(function () {
    // Initialize Select2 for filters
    $('#filter_area_id, #filter_conveyor_id, #filter_type').select2({
        theme: 'bootstrap-5',
        allowClear: true,
        placeholder: function () {
            return $(this).data('placeholder') || 'Select...';
        }
    });

    // Reset filter button
    $('#btn-reset-filter').on('click', function () {
        $('#filter_area_id').val('').trigger('change');
        $('#filter_conveyor_id').val('').trigger('change');
        $('#filter_type').val('').trigger('change');
    });

    // Initialize DataTable
    var table = $('#cutting-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('defect.cutting.datatable') }}",
            data: function (d) {
                d.area_id = $('#filter_area_id').val();
                d.conveyor_id = $('#filter_conveyor_id').val();
                d.type = $('#filter_type').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'type_badge', name: 'type', orderable: true, searchable: false, className: 'text-center' },
            { data: 'carline', name: 'carline' },
            { data: 'conveyor_name', name: 'conveyor_name' },
            { data: 'cct_no', name: 'cct_no' },
            { data: 'cct_code', name: 'cct_code' },
            { data: 'shikake_code', name: 'shikake_code' },
            { data: 'family', name: 'family' },
            { data: 'qty', name: 'qty', className: 'text-center' },
            { data: 'machine', name: 'machine' },
            { data: 'sequence', name: 'sequence', className: 'text-center' },
            { data: 'balance_display', name: 'balance', className: 'text-center', orderable: true },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        pageLength: 50,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[3, 'asc'], [4, 'asc']]
    });

    // Filter change handlers
    $('#filter_area_id, #filter_conveyor_id, #filter_type').on('change', function () {
        table.ajax.reload();
    });

    // Open defect modal
    let currentBalance = 0;

    $(document).on('click', '.btn-defect', function () {
        var btn = $(this);
        currentBalance = parseInt(btn.data('balance')) || 0;
        var shiftQty = parseInt(btn.data('shift-qty')) || 1;

        // Build shift options dynamically
        var shiftSelect = $('#shift');
        shiftSelect.empty().append('<option value="">- Select Shift -</option>');
        for (var i = 1; i <= shiftQty; i++) {
            shiftSelect.append('<option value="' + i + '">Shift ' + i + '</option>');
        }

        $('#master_circuit_id').val(btn.data('id'));
        $('#conveyor_id').val(btn.data('conveyor-id'));
        $('#info-conveyor').text(btn.data('conveyor'));
        $('#info-cct-no').text(btn.data('cct-no'));
        $('#info-cct-code').text(btn.data('cct-code'));
        $('#current-balance').text(currentBalance + ' pcs');
        $('#qty_defect').attr('max', currentBalance).val('');
        $('#balance-after').text('-');
        $('#shift').val('');
        $('#reason').val('');
        $('#defect_date').val('{{ date("Y-m-d") }}');

        $('#defectModal').modal('show');
    });

    // Update balance after
    $('#qty_defect').on('input', function () {
        var qty = parseInt($(this).val()) || 0;
        var after = currentBalance - qty;
        if (qty > currentBalance) {
            $('#balance-after').text('Exceeds balance!').addClass('text-danger fw-bold');
            $('#btn-submit').prop('disabled', true);
        } else if (qty <= 0) {
            $('#balance-after').text('-').removeClass('text-danger fw-bold');
            $('#btn-submit').prop('disabled', true);
        } else {
            $('#balance-after').text(after + ' pcs').removeClass('text-danger fw-bold');
            $('#btn-submit').prop('disabled', false);
        }
    });

    // Submit defect form
    $('#defect-form').on('submit', function (e) {
        e.preventDefault();

        var qty = $('#qty_defect').val();
        var cctCode = $('#info-cct-code').text();

        Swal.fire({
            title: 'Confirm Defect?',
            html: `Record <strong>${qty}</strong> defect for <strong>${cctCode}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Submit',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (result.isConfirmed) {
                $('#btn-submit').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

                $.ajax({
                    url: "{{ route('defect.cutting.store') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        master_circuit_id: $('#master_circuit_id').val(),
                        conveyor_id: $('#conveyor_id').val(),
                        defect_date: $('#defect_date').val(),
                        shift: $('#shift').val(),
                        qty_defect: $('#qty_defect').val(),
                        reason: $('#reason').val()
                    },
                    success: function (res) {
                        $('#defectModal').modal('hide');
                        table.ajax.reload(null, false);
                        Swal.fire('Success', res.message, 'success');
                    },
                    error: function (xhr) {
                        var msg = xhr.responseJSON?.message || 'Failed to record defect';
                        Swal.fire('Error', msg, 'error');
                    },
                    complete: function () {
                        $('#btn-submit').prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Submit Defect');
                    }
                });
            }
        });
    });
});
</script>
@endsection
