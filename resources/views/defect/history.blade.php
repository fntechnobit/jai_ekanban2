@extends('layouts.master')

@section('title', 'Defect History')

@section('css')
<style>
    .filter-badge {
        cursor: pointer;
    }
    .filter-badge:hover {
        opacity: 0.8;
    }
    .type-selector .btn {
        min-width: 120px;
    }
    .type-selector .btn.active {
        font-weight: bold;
    }
</style>
@endsection

@section('content')
@section('breadcrumb')
    <x-page-header menu-code="defect_history" />
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fa-solid fa-clock-rotate-left me-2"></i> Defect History
            </h5>
            <div class="d-flex gap-2">
                <a href="{{ route('defect.cutting.index') }}" class="btn btn-outline-danger btn-sm">
                    <i class="fa-solid fa-scissors me-1"></i> Defect Cutting
                </a>
                <a href="{{ route('defect.shikake.index') }}" class="btn btn-outline-danger btn-sm">
                    <i class="fa-solid fa-link-slash me-1"></i> Defect Shikake
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Type Selector -->
            <div class="mb-3 type-selector">
                <div class="btn-group" role="group" aria-label="Defect Type">
                    <a href="{{ route('defect.history', array_merge(request()->except('type', 'shikake_type', 'page'), ['type' => 'circuit'])) }}" 
                       class="btn btn-outline-primary {{ ($filters['type'] ?? 'circuit') == 'circuit' ? 'active' : '' }}">
                        <i class="fa-solid fa-scissors me-1"></i> Circuit
                    </a>
                    <a href="{{ route('defect.history', array_merge(request()->except('type', 'page'), ['type' => 'shikake'])) }}" 
                       class="btn btn-outline-warning {{ ($filters['type'] ?? '') == 'shikake' ? 'active' : '' }}">
                        <i class="fa-solid fa-link-slash me-1"></i> Shikake
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <form action="{{ route('defect.history') }}" method="GET" class="mb-4">
                <input type="hidden" name="type" value="{{ $filters['type'] ?? 'circuit' }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control form-control-sm" id="date_from" name="date_from"
                               value="{{ $filters['date_from'] ?? date('Y-m-01') }}">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control form-control-sm" id="date_to" name="date_to"
                               value="{{ $filters['date_to'] ?? date('Y-m-d') }}">
                    </div>
                    <div class="col-md-2">
                        <label for="conveyor_id" class="form-label">Conveyor</label>
                        <select class="form-select form-select-sm" id="conveyor_id" name="conveyor_id">
                            <option value="">- All -</option>
                            @foreach($conveyors as $conveyor)
                                <option value="{{ $conveyor->id }}" {{ ($filters['conveyor_id'] ?? '') == $conveyor->id ? 'selected' : '' }}>
                                    {{ $conveyor->conveyor }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if(($filters['type'] ?? 'circuit') == 'shikake')
                    <div class="col-md-2">
                        <label for="shikake_type" class="form-label">Shikake Type</label>
                        <select class="form-select form-select-sm" id="shikake_type" name="shikake_type">
                            <option value="">- All -</option>
                            @foreach($shikakeTypes as $key => $label)
                                <option value="{{ $key }}" {{ ($filters['shikake_type'] ?? '') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2">
                        <label for="shift" class="form-label">Shift</label>
                        <select class="form-select form-select-sm" id="shift" name="shift">
                            <option value="">- All -</option>
                            <option value="1" {{ ($filters['shift'] ?? '') == '1' ? 'selected' : '' }}>Shift 1</option>
                            <option value="2" {{ ($filters['shift'] ?? '') == '2' ? 'selected' : '' }}>Shift 2</option>
                            <option value="3" {{ ($filters['shift'] ?? '') == '3' ? 'selected' : '' }}>Shift 3</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-search me-1"></i> Filter
                        </button>
                        <a href="{{ route('defect.history', ['type' => $filters['type'] ?? 'circuit']) }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrows-rotate me-1"></i> Reset
                        </a>
                    </div>
                </div>
            </form>

            <!-- Summary Cards -->
            <div class="row mb-4" id="summary-cards">
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1">Total Records</h6>
                            <h3 class="mb-0">{{ $history->total() }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h6 class="mb-1">Total Qty Defect</h6>
                            <h3 class="mb-0">{{ number_format($history->sum('qty_defect')) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card {{ ($filters['type'] ?? 'circuit') == 'circuit' ? 'bg-primary' : 'bg-warning' }} {{ ($filters['type'] ?? 'circuit') == 'circuit' ? 'text-white' : 'text-dark' }}">
                        <div class="card-body text-center">
                            <h6 class="mb-1">{{ ($filters['type'] ?? 'circuit') == 'circuit' ? 'Circuit' : 'Shikake' }} Defects</h6>
                            <h3 class="mb-0">{{ $history->total() }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">No</th>
                            <th width="10%">Date</th>
                            <th width="5%">Shift</th>
                            <th width="12%">Conveyor</th>
                            @if(($filters['type'] ?? 'circuit') == 'shikake')
                            <th width="10%">Type</th>
                            @endif
                            <th width="18%">Code</th>
                            <th width="8%">Qty</th>
                            <th width="8%">Before</th>
                            <th width="8%">After</th>
                            <th width="16%">Reason</th>
                            <th width="10%">Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $index => $defect)
                            <tr>
                                <td>{{ $history->firstItem() + $index }}</td>
                                <td>{{ $defect->defect_date?->format('Y-m-d') }}</td>
                                <td class="text-center">
                                    @if($defect->shift == 0)
                                        <span class="badge bg-secondary">Admin</span>
                                    @else
                                        {{ $defect->shift }}
                                    @endif
                                </td>
                                <td>{{ $defect->conveyor?->conveyor ?? '-' }}</td>
                                @if(($filters['type'] ?? 'circuit') == 'shikake')
                                <td>
                                    @if($defect->shikake_type)
                                        <span class="badge bg-warning text-dark">{{ $defect->shikake_type }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                @endif
                                <td>
                                    @if(($filters['type'] ?? 'circuit') == 'circuit')
                                        @if($defect->masterCircuit)
                                            {{ $defect->masterCircuit->cct_no }} - {{ $defect->masterCircuit->cct_code }}
                                        @else
                                            <span class="text-muted">Circuit #{{ $defect->master_circuit_id }}</span>
                                        @endif
                                    @else
                                        {{ $defect->masterShikake?->machine ?? "SHK-{$defect->master_shikake_id}" }}
                                    @endif
                                </td>
                                <td class="text-end text-danger fw-bold">{{ number_format($defect->qty_defect) }}</td>
                                <td class="text-end">{{ number_format($defect->balance_before) }}</td>
                                <td class="text-end">{{ number_format($defect->balance_after) }}</td>
                                <td>
                                    @if($defect->reason)
                                        <span data-bs-toggle="tooltip" title="{{ $defect->reason }}">
                                            {{ Str::limit($defect->reason, 30) }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $defect->creator?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ ($filters['type'] ?? 'circuit') == 'shikake' ? '12' : '11' }}" class="text-center text-muted py-4">
                                    <i class="fa-solid fa-inbox fa-2x mb-2 d-block"></i>
                                    No defect records found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Showing {{ $history->firstItem() ?? 0 }} to {{ $history->lastItem() ?? 0 }} of {{ $history->total() }} entries
                </div>
                {{ $history->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection
