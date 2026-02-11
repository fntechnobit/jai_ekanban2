@extends('layouts.master')

@section('title', 'Defect Shikake')

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
    .shikake-type-btn {
        padding: 0.5rem 1rem;
        border-radius: 0.25rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .shikake-type-btn:hover {
        background-color: #e9ecef;
    }
    .shikake-type-btn.active {
        background-color: #0d6efd;
        color: white;
    }
    .shikake-type-btn input {
        display: none;
    }
</style>
@endsection

@section('content')
@section('breadcrumb')
    <x-page-header menu-code="defect_shikake" />
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-link-slash text-danger me-2"></i> Defect Shikake
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

                    <form action="{{ route('defect.shikake.store') }}" method="POST" id="defect-form">
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
                            <div class="col-12">
                                <label class="form-label">Shikake Type <span class="text-danger">*</span></label>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($shikakeTypes as $value => $label)
                                        <label class="shikake-type-btn border {{ old('shikake_type') == $value ? 'active' : '' }}">
                                            <input type="radio" name="shikake_type" value="{{ $value }}" 
                                                   {{ old('shikake_type') == $value ? 'checked' : '' }} required>
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('shikake_type')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="master_shikake_id" class="form-label">Shikake <span class="text-danger">*</span></label>
                                <select class="form-select select2 @error('master_shikake_id') is-invalid @enderror" 
                                        id="master_shikake_id" name="master_shikake_id" required disabled>
                                    <option value="">- Select Shikake -</option>
                                </select>
                                @error('master_shikake_id')
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
                        <strong>Note:</strong> Defect reduces the kanban balance storage for the selected shikake type.
                    </div>
                    
                    <h6 class="text-muted">Shikake Types:</h6>
                    <ul class="ps-3 mb-3">
                        @foreach($shikakeTypes as $value => $label)
                            <li><strong>{{ $label }}</strong></li>
                        @endforeach
                    </ul>
                    
                    <h6 class="text-muted">Steps:</h6>
                    <ol class="ps-3">
                        <li>Select defect date and shift</li>
                        <li>Select conveyor</li>
                        <li>Select shikake type</li>
                        <li>Select specific shikake</li>
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
    let shikakeData = [];
    let currentBalance = 0;

    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5'
    });

    // Shikake type button click
    $('.shikake-type-btn').on('click', function() {
        $('.shikake-type-btn').removeClass('active');
        $(this).addClass('active');
        loadShikakes();
    });

    // On conveyor change
    $('#conveyor_id').on('change', function() {
        resetShikakeSelection();
        if ($(this).val() && $('input[name="shikake_type"]:checked').val()) {
            loadShikakes();
        }
    });

    function loadShikakes() {
        const conveyorId = $('#conveyor_id').val();
        const shikakeType = $('input[name="shikake_type"]:checked').val();
        
        if (!conveyorId || !shikakeType) {
            resetShikakeSelection();
            return;
        }

        // Reset and disable
        resetShikakeSelection();

        // Load shikakes
        $.ajax({
            url: '{{ route("defect.shikake.list") }}',
            type: 'GET',
            data: { 
                conveyor_id: conveyorId,
                shikake_type: shikakeType 
            },
            success: function(data) {
                shikakeData = data;
                
                $('#master_shikake_id').prop('disabled', false);
                data.forEach(function(shikake) {
                    $('#master_shikake_id').append(
                        `<option value="${shikake.master_shikake_id}" data-balance="${shikake.sisa}">${shikake.display}</option>`
                    );
                });
            },
            error: function() {
                alert('Failed to load shikakes');
            }
        });
    }

    function resetShikakeSelection() {
        $('#master_shikake_id').empty().append('<option value="">- Select Shikake -</option>').prop('disabled', true);
        $('#qty_defect').val('').prop('disabled', true);
        $('#current-balance').text('-');
        $('#balance-after').text('-');
        $('#balance-bar').css('--balance-percent', '0%');
        $('#btn-submit').prop('disabled', true);
        currentBalance = 0;
        shikakeData = [];
    }

    // On shikake change, show balance
    $('#master_shikake_id').on('change', function() {
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
        $('.shikake-type-btn').removeClass('active');
        resetShikakeSelection();
    });

    // Form submission confirmation
    $('#defect-form').on('submit', function(e) {
        const qtyDefect = $('#qty_defect').val();
        const shikakeText = $('#master_shikake_id option:selected').text();
        
        if (!confirm(`Are you sure you want to record ${qtyDefect} defect for ${shikakeText}?`)) {
            e.preventDefault();
        }
    });
});
</script>
@endsection
