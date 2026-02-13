@php
    // Use the pre-constructed groupId for actions
    // groupId format: assyScheduleId-process-identifier (URL encoded)
    $actionId = $groupId ?? $row->shikake_ids ?? '';
@endphp

<div class="btn-group" role="group" style="white-space: nowrap;">
    <button type="button" class="btn btn-soft-info btn-sm btn-preview" data-group-id="{{ $actionId }}" title="Preview">
        <i class="fa-solid fa-eye"></i>
    </button>
    <button type="button" class="btn btn-soft-success btn-sm btn-print" data-group-id="{{ $actionId }}" title="Print">
        <i class="fa-solid fa-print"></i>
    </button>
</div>