@php
    // Use the pre-constructed groupId for actions
    // groupId format: assyScheduleId-masterShikakeId
    $actionId = $groupId ?? $row->shikake_ids ?? '';

    // Progressive print: a cut off only unlocks after every earlier cut off is
    // fully printed. When the flag is not supplied nothing is locked.
    $canPrint = $canPrint ?? true;
    $maxPrintableCutoff = $maxPrintableCutoff ?? null;
@endphp

<div class="btn-group" role="group" style="white-space: nowrap;">
    <button type="button" class="btn btn-soft-info btn-preview"
            data-group-id="{{ $actionId }}" title="Preview"
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
    <button type="button" class="btn btn-soft-success btn-print"
            data-group-id="{{ $actionId }}" title="Print"
            style="padding: 0.55rem 1rem; font-size: 1rem;">
        <i class="fa-solid fa-print"></i> Print
    </button>
    @endif
</div>
