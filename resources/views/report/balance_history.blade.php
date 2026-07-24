@extends('layouts.master')

@section('title', 'Balance History Report')

@section('breadcrumb')
    <x-page-header menu-code="report_balance" title="Balance History" />
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <h5 class="card-title mb-0">
                <i class="fa-solid fa-scale-balanced me-2"></i> Balance History
            </h5>

            <form method="GET" action="{{ route('report.balance.index') }}"
                  class="d-flex align-items-center gap-2 flex-wrap" id="filter-form">
                <input type="date" name="date" class="form-control form-control-sm"
                       value="{{ $date }}" style="width: 150px;">

                <select name="conveyor_id" class="form-select form-select-sm" style="width: 170px;">
                    <option value="">- All Conveyor -</option>
                    @foreach($conveyors as $conveyor)
                        <option value="{{ $conveyor->id }}" @selected((string) $conveyorId === (string) $conveyor->id)>
                            {{ $conveyor->conveyor }}
                        </option>
                    @endforeach
                </select>

                <select name="type" class="form-select form-select-sm" style="width: 140px;">
                    <option value="all"     @selected($type === 'all')>All Type</option>
                    <option value="circuit" @selected($type === 'circuit')>Circuit</option>
                    <option value="shikake" @selected($type === 'shikake')>Shikake</option>
                </select>

                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Tampilkan
                </button>
                <a href="{{ route('report.balance.export', array_filter(['date' => $date, 'conveyor_id' => $conveyorId, 'type' => $type])) }}"
                   class="btn btn-success btn-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                </a>
            </form>
        </div>

        <div class="card-body">
            <div class="alert alert-light border small mb-3">
                <i class="fa-solid fa-circle-info me-1 text-primary"></i>
                Rumus per baris:
                <strong>Sisa Hari Ini = Sisa H-1 + Kanban (Produced) − Kebutuhan Listing + Add Cutting − Defect</strong>.
                Kolom <strong>Sisa H-1</strong> = saldo akhir tanggal <strong>{{ $prevDate }}</strong>.
                Kolom <strong>Cek</strong> harus <strong>0</strong>; nilai ≠ 0 menandakan anomali (mis. admin reset / urutan lintas-tanggal) yang perlu ditelusuri.
                Hanya item yang <em>berubah</em> pada tanggal ini yang ditampilkan.
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th width="3%">No</th>
                            <th width="12%">Conveyor</th>
                            <th width="8%">Tipe</th>
                            <th>Kode</th>
                            <th width="8%" class="text-end">Sisa H-1</th>
                            <th width="10%" class="text-end">Kebutuhan Listing</th>
                            <th width="9%" class="text-end">Kanban (Produced)</th>
                            <th width="8%" class="text-end">Add Cutting</th>
                            <th width="8%" class="text-end">Defect</th>
                            <th width="9%" class="text-end">Sisa Hari Ini</th>
                            <th width="6%" class="text-end">Cek</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $i => $r)
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td>{{ $r['conveyor_name'] }}</td>
                                <td>
                                    @if($r['item_type'] === 'circuit')
                                        <span class="badge bg-primary">{{ $r['type_label'] }}</span>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ $r['type_label'] }}</span>
                                    @endif
                                </td>
                                <td>{{ $r['code'] }}</td>
                                <td class="text-end">{{ number_format($r['sisa_h1']) }}</td>
                                <td class="text-end">{{ number_format($r['kebutuhan']) }}</td>
                                <td class="text-end">{{ number_format($r['produced']) }}</td>
                                <td class="text-end text-success">{{ $r['add'] ? '+' . number_format($r['add']) : '0' }}</td>
                                <td class="text-end text-danger">{{ $r['defect'] ? '-' . number_format($r['defect']) : '0' }}</td>
                                <td class="text-end fw-bold">{{ number_format($r['sisa_today']) }}</td>
                                <td class="text-end">
                                    @if($r['check'] === 0)
                                        <span class="text-success">0</span>
                                    @else
                                        <span class="badge bg-danger">{{ number_format($r['check']) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    Tidak ada perubahan balance pada tanggal <strong>{{ $date }}</strong>
                                    @if($conveyorId) untuk conveyor terpilih @endif.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($rows) > 0)
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="4" class="text-end">TOTAL</td>
                                <td class="text-end">{{ number_format($totals['sisa_h1']) }}</td>
                                <td class="text-end">{{ number_format($totals['kebutuhan']) }}</td>
                                <td class="text-end">{{ number_format($totals['produced']) }}</td>
                                <td class="text-end text-success">{{ number_format($totals['add']) }}</td>
                                <td class="text-end text-danger">{{ number_format($totals['defect']) }}</td>
                                <td class="text-end">{{ number_format($totals['sisa_today']) }}</td>
                                <td class="text-end">
                                    @if($totals['check'] === 0)
                                        <span class="text-success">0</span>
                                    @else
                                        <span class="badge bg-danger">{{ number_format($totals['check']) }}</span>
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
