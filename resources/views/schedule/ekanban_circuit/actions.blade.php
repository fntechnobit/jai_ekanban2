@php
    // Progressive print: a cut off only unlocks after every earlier cut off is
    // fully printed. When the flag is not supplied (other routes) nothing is locked.
    $canPrint = $canPrint ?? true;
    $maxPrintableCutoff = $maxPrintableCutoff ?? null;
@endphp
<div class="btn-group" role="group" style="white-space: nowrap;">
@if(request()->route()->getName() === 'schedule.ekanban-circuit.print-machine')
    <button type="button" class="btn btn-soft-info btn-preview"
            data-group-id="{{ $groupId }}"
            title="Preview all {{ $row->issue_count }} issue(s)"
            style="padding: 0.55rem 1rem; font-size: 1rem;">
            <i class="fa-solid fa-eye"></i>
    </button>
    @if(!$canPrint)
    <button type="button" class="btn btn-soft-secondary" disabled
            title="Cut Off {{ $row->cutoff }} terkunci - selesaikan print Cut Off 1 dan Cut Off 2 terlebih dahulu"
            style="padding: 0.55rem 1rem; font-size: 1rem;">
        <i class="fa-solid fa-lock"></i>
    </button>
    @elseif(!$row->is_printed || auth()->user()->isAdmin())
    <button type="button" class="btn btn-soft-success btn-print" data-group-id="{{ $groupId }}"
            title="Print all {{ $row->issue_count }} issue(s)"
            style="padding: 0.55rem 1rem; font-size: 1rem;">
        <i class="fa-solid fa-print"></i> Print
    </button>
    @endif
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
