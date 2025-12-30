@php
    // Create composite ID for individual circuit tracking
    $compositeId = ($row->assy_schedule_id ?? $row->id) . '-' . ($row->circuit_id ?? $row->id);
@endphp

@if(request()->route()->getName() === 'schedule.ekanban-circuit.print-machine')
    <button type="button" class="btn btn-sm btn-info btn-preview" data-ids="{{ $compositeId }}" title="Preview">
        <i class="fas fa-eye"></i>
    </button>
    <button type="button" class="btn btn-sm btn-success btn-print" data-ids="{{ $compositeId }}" title="Print">
        <i class="fas fa-print"></i>
    </button>
@else
    <button type="button" class="btn btn-sm btn-info btn-preview" data-ids="{{ $compositeId }}">
        <i class="fas fa-eye"></i>
    </button>
@endif