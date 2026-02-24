<div class="btn-group" role="group" style="white-space: nowrap;">
@if(request()->route()->getName() === 'schedule.ekanban-circuit.print-machine')
    <button type="button" class="btn btn-soft-info btn-preview" 
            data-group-id="{{ $groupId }}" 
            title="Preview all {{ $row->issue_count }} issue(s)"
            style="padding: 0.4rem 0.65rem; font-size: 0.9rem;">
            <i class="fa-solid fa-eye"></i>
    </button>
    <button type="button" class="btn btn-soft-success btn-print" data-group-id="{{ $groupId }}" 
            title="Print all {{ $row->issue_count }} issue(s)"
            style="padding: 0.4rem 0.65rem; font-size: 0.9rem;">
        <i class="fa-solid fa-print"></i>
    </button>
@else
    @php
        // Fallback for other routes - create composite ID
        $compositeId = ($row->assy_schedule_id ?? $row->id) . '-' . ($row->circuit_id ?? $row->id);
    @endphp
    <button type="button" class="btn btn-soft-info btn-sm btn-preview" data-ids="{{ $compositeId }}">
        <i class="fa-solid fa-eye"></i>
    </button>
@endif
</div>