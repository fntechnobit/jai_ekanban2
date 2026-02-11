@extends('layouts.master')

@section('title', 'Defect Cutting')

@section('css')
<style>
    .balance-display {
        font-size: 1.5rem;
        font-weight: bold;
    }
    .balance-bar {
        height: 20px;
        background: linear-gradient(to right, #28a745 0%, #28a745 var(--balance-percent), #e9ecef var(--balance-percent));
        border-radius: 4px;
    }
</style>
@endsection

@section('content')
@section('breadcrumb')
    <x-page-header menu-code="defect_cutting" />
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-scissors text-danger me-2"></i> Defect Cutting
                    </h5>
                    <a href="{{ route('defect.history') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i> History
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fa-solid fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fa-solid fa-exclamation-circle me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('defect.cutting.store') }}" method="POST" id="defect-form">
                        @csrf
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="defect_date" class="form-label">Defect Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('defect_date') is-invalid @enderror" 
                                       id="defect_date" name="defect_date" 
                                       value="{{ old('defect_date', date('Y-m-d')) }}" 
                                       max="{{ date('Y-m-d') }}" required>
                                @error('defect_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="shift" class="form-label">Shift <span class="text-danger">*</span></label>
                                <select class="form-select @error('shift') is-invalid @enderror" 
                                        id="shift" name="shift" required>
                                    <option value="">- Select Shift -</option>
                                    <option value="1" {{ old('shift') == '1' ? 'selected' : '' }}>Shift 1</option>
                                    <option value="2" {{ old('shift') == '2' ? 'selected' : '' }}>Shift 2</option>
                                    <option value="3" {{ old('shift') == '3' ? 'selected' : '' }}>Shift 3</option>
                                </select>
                                @error('shift')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="conveyor_id" class="form-label">Conveyor <span class="text-danger">*</span></label>
                                <select class="form-select select2 @error('conveyor_id') is-invalid @enderror" 
                                        id="conveyor_id" name="conveyor_id" required>
                                    <option value="">- Select Conveyor -</option>
                                    @foreach($conveyors as $conveyor)
                                        <option value="{{ $conveyor->id }}" {{ old('conveyor_id') == $conveyor->id ? 'selected' : '' }}>
                                            {{ $conveyor->conveyor }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('conveyor_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="cct_no" class="form-label">CCT No <span class="text-danger">*</span></label>
                                <select class="form-select select2 @error('cct_no') is-invalid @enderror" 
                                        id="cct_no" name="cct_no" required disabled>
                                    <option value="">- Select CCT No -</option>
                                </select>
                                @error('cct_no')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="cct_code" class="form-label">CCT Code <span class="text-danger">*</span></label>
                                <select class="form-select select2 @error('cct_code') is-invalid @enderror" 
                                        id="cct_code" name="cct_code" required disabled>
                                    <option value="">- Select CCT Code -</option>
                                </select>
                                @error('cct_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <label class="form-label mb-2">Current Balance</label>
                                        <div class="balance-display text-success" id="current-balance">-</div>
                                        <div class="balance-bar mt-2" id="balance-bar" style="--balance-percent: 0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="qty_defect" class="form-label">Qty Defect <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('qty_defect') is-invalid @enderror" 
                                       id="qty_defect" name="qty_defect" 
                                       value="{{ old('qty_defect') }}" 
                                       min="1" required disabled>
                                @error('qty_defect')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Balance After Defect</label>
                                <div class="form-control bg-light" id="balance-after">-</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="reason" class="form-label">Reason / Notes</label>
                                <textarea class="form-control @error('reason') is-invalid @enderror" 
                                          id="reason" name="reason" rows="3" 
                                          placeholder="Enter reason for defect...">{{ old('reason') }}</textarea>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-secondary" id="btn-reset">
                                <i class="fa-solid fa-xmark me-1"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-danger" id="btn-submit" disabled>
                                <i class="fa-solid fa-check me-1"></i> Submit Defect
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fa-solid fa-info-circle me-2"></i> Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <strong>Note:</strong> Defect reduces the kanban balance storage. Make sure to input correct data.
                    </div>
                    
                    <h6 class="text-muted">Steps:</h6>
                    <ol class="ps-3">
                        <li>Select defect date and shift</li>
                        <li>Select conveyor</li>
                        <li>Select CCT No and CCT Code</li>
                        <li>Enter defect quantity</li>
                        <li>Add reason (optional)</li>
                        <li>Click Submit Defect</li>
                    </ol>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Defect quantity cannot exceed current balance.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let circuitData = [];
    let currentBalance = 0;

    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5'
    });

    // On conveyor change, load circuits
    $('#conveyor_id').on('change', function() {
        const conveyorId = $(this).val();
        
        // Reset dependent fields
        $('#cct_no').empty().append('<option value="">- Select CCT No -</option>').prop('disabled', true);
        $('#cct_code').empty().append('<option value="">- Select CCT Code -</option>').prop('disabled', true);
        $('#qty_defect').val('').prop('disabled', true);
        $('#current-balance').text('-');
        $('#balance-after').text('-');
        $('#balance-bar').css('--balance-percent', '0%');
        $('#btn-submit').prop('disabled', true);
        currentBalance = 0;
        
        if (!conveyorId) return;

        // Load circuits for this conveyor
        $.ajax({
            url: '{{ route("defect.cutting.circuits") }}',
            type: 'GET',
            data: { conveyor_id: conveyorId },
            success: function(data) {
                circuitData = data;
                
                // Get unique cct_no values
                const cctNos = [...new Set(data.map(item => item.cct_no))];
                
                $('#cct_no').prop('disabled', false);
                cctNos.forEach(function(cctNo) {
                    $('#cct_no').append(`<option value="${cctNo}">${cctNo}</option>`);
                });
            },
            error: function() {
                alert('Failed to load circuits');
            }
        });
    });

    // On CCT No change, filter CCT Codes
    $('#cct_no').on('change', function() {
        const cctNo = $(this).val();
        
        $('#cct_code').empty().append('<option value="">- Select CCT Code -</option>').prop('disabled', true);
        $('#qty_defect').val('').prop('disabled', true);
        $('#current-balance').text('-');
        $('#balance-after').text('-');
        $('#balance-bar').css('--balance-percent', '0%');
        $('#btn-submit').prop('disabled', true);
        currentBalance = 0;
        
        if (!cctNo) return;

        // Filter circuits by cct_no
        const filteredCircuits = circuitData.filter(item => item.cct_no === cctNo);
        
        $('#cct_code').prop('disabled', false);
        filteredCircuits.forEach(function(circuit) {
            $('#cct_code').append(`<option value="${circuit.cct_code}" data-balance="${circuit.sisa}">${circuit.cct_code} (Balance: ${circuit.sisa})</option>`);
        });
    });

    // On CCT Code change, show balance
    $('#cct_code').on('change', function() {
        const selected = $(this).find(':selected');
        currentBalance = parseInt(selected.data('balance')) || 0;
        
        $('#current-balance').text(currentBalance + ' pcs');
        $('#balance-bar').css('--balance-percent', currentBalance > 0 ? '100%' : '0%');
        
        if (currentBalance > 0) {
            $('#qty_defect').prop('disabled', false).attr('max', currentBalance);
            $('#btn-submit').prop('disabled', false);
        } else {
            $('#qty_defect').val('').prop('disabled', true);
            $('#btn-submit').prop('disabled', true);
        }
        
        updateBalanceAfter();
    });

    // On qty_defect change, update balance after
    $('#qty_defect').on('input', function() {
        updateBalanceAfter();
    });

    function updateBalanceAfter() {
        const qtyDefect = parseInt($('#qty_defect').val()) || 0;
        const balanceAfter = currentBalance - qtyDefect;
        
        if (qtyDefect > currentBalance) {
            $('#balance-after').text('Invalid! Exceeds balance').addClass('text-danger');
            $('#btn-submit').prop('disabled', true);
        } else if (qtyDefect <= 0) {
            $('#balance-after').text('-');
            $('#btn-submit').prop('disabled', true);
        } else {
            $('#balance-after').text(balanceAfter + ' pcs').removeClass('text-danger');
            $('#btn-submit').prop('disabled', false);
        }
    }

    // Reset form
    $('#btn-reset').on('click', function() {
        $('#cct_no').empty().append('<option value="">- Select CCT No -</option>').prop('disabled', true);
        $('#cct_code').empty().append('<option value="">- Select CCT Code -</option>').prop('disabled', true);
        $('#qty_defect').val('').prop('disabled', true);
        $('#current-balance').text('-');
        $('#balance-after').text('-');
        $('#balance-bar').css('--balance-percent', '0%');
        $('#btn-submit').prop('disabled', true);
        currentBalance = 0;
        circuitData = [];
    });

    // Form submission confirmation
    $('#defect-form').on('submit', function(e) {
        const qtyDefect = $('#qty_defect').val();
        const cctCode = $('#cct_code').val();
        
        if (!confirm(`Are you sure you want to record ${qtyDefect} defect for ${cctCode}?`)) {
            e.preventDefault();
        }
    });
});
</script>
@endsection
