@php
    // Create composite ID for individual circuit tracking
    $compositeId = ($row->assy_schedule_id ?? $row->id) . '-' . ($row->circuit_id ?? $row->id);
@endphp

<div class="btn-group" role="group">
@if(request()->route()->getName() === 'schedule.ekanban-circuit.print-machine')
    <button type="button" class="btn btn-soft-info btn-sm btn-preview" data-ids="{{ $compositeId }}" title="Preview">
        <i class="fa-solid fa-eye"></i>
    </button>
    <button type="button" class="btn btn-soft-success btn-sm btn-print" data-ids="{{ $compositeId }}" title="Print">
        <i class="fa-solid fa-print"></i>
    </button>
@else
    <button type="button" class="btn btn-soft-info btn-sm btn-preview" data-ids="{{ $compositeId }}">
        <i class="fa-solid fa-eye"></i>
    </button>
@endif
</div>