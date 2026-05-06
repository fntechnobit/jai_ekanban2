@php
    // Use the pre-constructed groupId for actions
    // groupId format: assyScheduleId-process-identifier (URL encoded)
    $actionId = $groupId ?? $row->shikake_ids ?? '';
@endphp

<div class="btn-group" role="group" style="white-space: nowrap;">
    <button type="button" class="btn btn-soft-info btn-preview"
            data-group-id="{{ $actionId }}" title="Preview"
            style="padding: 0.55rem 1rem; font-size: 1rem;">
        <i class="fa-solid fa-eye"></i>
    </button>
    <button type="button" class="btn btn-soft-success btn-print"
            data-group-id="{{ $actionId }}" title="Print"
            style="padding: 0.55rem 1rem; font-size: 1rem;">
        <i class="fa-solid fa-print"></i> Print
    </button>
</div>