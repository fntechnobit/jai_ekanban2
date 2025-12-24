@if(request()->route()->getName() === 'schedule.ekanban-shikake.print-machine')
    <button type="button" class="btn btn-sm btn-success btn-print" data-ids="{{ $row->id }}">
        <i class="fas fa-print"></i>
    </button>
@else
    <button type="button" class="btn btn-sm btn-info btn-preview" data-ids="{{ $row->id }}">
        <i class="fas fa-eye"></i>
    </button>
@endif