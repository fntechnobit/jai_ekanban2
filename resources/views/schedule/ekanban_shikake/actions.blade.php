@php
    // Use the pre-constructed groupId for actions
    // groupId format: assyScheduleId-process-identifier (URL encoded)
    $actionId = $groupId ?? $row->shikake_ids ?? '';
    $prevPending = !empty($row->prev_co_pending) ? 1 : 0;
@endphp

<div class="btn-group" role="group" style="white-space: nowrap;">
    <button type="button" class="btn btn-soft-info btn-preview"
            data-group-id="{{ $actionId }}" title="Preview"
            style="padding: 0.55rem 1rem; font-size: 1rem;">
        <i class="fa-solid fa-eye"></i>
    </button>
    <button type="button"
            class="btn {{ $prevPending ? 'btn-soft-secondary' : 'btn-soft-success' }} btn-print"
            data-group-id="{{ $actionId }}"
            data-prev-pending="{{ $prevPending }}"
            title="{{ $prevPending ? 'CO sebelumnya belum selesai diprint' : 'Print' }}"
            style="padding: 0.55rem 1rem; font-size: 1rem;">
        <i class="fa-solid {{ $prevPending ? 'fa-lock' : 'fa-print' }}"></i> Print
    </button>
</div>