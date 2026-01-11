<div class="btn-group" role="group">
@if(request()->route()->getName() === 'schedule.ekanban-shikake.print-machine')
    {{-- Preview Button --}}
    <button type="button" class="btn btn-soft-info btn-sm btn-preview" data-group-id="{{ $groupId }}" title="Preview">
        <i class="fa-solid fa-eye"></i>
    </button>
    {{-- Print Button --}}
    <button type="button" class="btn btn-soft-success btn-sm btn-print" data-group-id="{{ $groupId }}" title="Print">
        <i class="fa-solid fa-print"></i>
    </button>
@else
    <button type="button" class="btn btn-soft-info btn-sm btn-preview" data-group-id="{{ $groupId }}" title="Preview">
        <i class="fa-solid fa-eye"></i>
    </button>
@endif
</div>