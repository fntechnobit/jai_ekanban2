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
            <!-- Filters -->
            <form action="{{ route('defect.history') }}" method="GET" class="mb-4">
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
                    <div class="col-md-2">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select form-select-sm" id="type" name="type">
                            <option value="">- All -</option>
                            <option value="circuit" {{ ($filters['type'] ?? '') == 'circuit' ? 'selected' : '' }}>Cutting/Circuit</option>
                            <option value="shikake" {{ ($filters['type'] ?? '') == 'shikake' ? 'selected' : '' }}>Shikake</option>
                        </select>
                    </div>
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
                        <a href="{{ route('defect.history') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrows-rotate me-1"></i> Reset
                        </a>
                    </div>
                </div>
            </form>

            <!-- Summary Cards -->
            <div class="row mb-4" id="summary-cards">
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1">Total Defects</h6>
                            <h3 class="mb-0">{{ $history->total() }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h6 class="mb-1">Total Qty Defect</h6>
                            <h3 class="mb-0">{{ number_format($history->sum('qty_defect')) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h6 class="mb-1">Circuit Defects</h6>
                            <h3 class="mb-0">{{ $history->where('type', 'circuit')->count() }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body text-center">
                            <h6 class="mb-1">Shikake Defects</h6>
                            <h3 class="mb-0">{{ $history->where('type', 'shikake')->count() }}</h3>
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
                            <th width="10%">Conveyor</th>
                            <th width="8%">Type</th>
                            <th width="15%">Code</th>
                            <th width="8%">Qty</th>
                            <th width="8%">Before</th>
                            <th width="8%">After</th>
                            <th width="15%">Reason</th>
                            <th width="8%">Created By</th>
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
                                <td>
                                    @if($defect->type == 'circuit')
                                        <span class="badge bg-primary">Circuit</span>
                                    @else
                                        <span class="badge bg-warning text-dark">
                                            Shikake
                                            @if($defect->shikake_type)
                                                <br><small>({{ $defect->shikake_type }})</small>
                                            @endif
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($defect->type == 'circuit')
                                        {{ $defect->cct_no }} - {{ $defect->cct_code }}
                                    @else
                                        {{ $defect->shikake?->machine ?? "SHK-{$defect->master_shikake_id}" }}
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
                                <td colspan="11" class="text-center text-muted py-4">
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
