<div class="btn-group" role="group" style="white-space: nowrap;">
@if(request()->route()->getName() === 'schedule.ekanban-circuit.print-machine')
    @php $prevPending = !empty($row->prev_co_pending) ? 1 : 0; @endphp
    <button type="button" class="btn btn-soft-info btn-preview" 
            data-group-id="{{ $groupId }}" 
            title="Preview all {{ $row->issue_count }} issue(s)"
            style="padding: 0.55rem 1rem; font-size: 1rem;">
            <i class="fa-solid fa-eye"></i>
    </button>
    <button type="button"
            class="btn {{ $prevPending ? 'btn-soft-secondary' : 'btn-soft-success' }} btn-print"
            data-group-id="{{ $groupId }}"
            data-prev-pending="{{ $prevPending }}"
            title="{{ $prevPending ? 'CO sebelumnya belum selesai diprint' : 'Print all ' . $row->issue_count . ' issue(s)' }}"
            style="padding: 0.55rem 1rem; font-size: 1rem;">
        <i class="fa-solid {{ $prevPending ? 'fa-lock' : 'fa-print' }}"></i> Print
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