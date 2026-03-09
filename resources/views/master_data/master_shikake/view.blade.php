@extends('layouts.master')

@section('title', 'View Shikake Data')

@section('breadcrumb')
    <x-page-header menu-code="master_shikake" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Shikake Detail</h3>
                <div class="card-tools">
                    <a href="{{ route('master-data.master-shikake.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back to List
                    </a>
                    @if(auth()->user()->hasMenuPermission('master_shikake', 'can_update'))
                        <a href="{{ route('master-data.master-shikake.edit', $shikake->id) }}" class="btn btn-warning btn-sm">
                            <i class="fa-solid fa-pen-to-square"></i> Edit
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <!-- Main Information -->
                <div class="row g-0 mb-3">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0 small">
                            <tr><td class="text-muted fw-semibold" width="35%">Process</td><td>
                                @php
                                    $badgeClass = match($shikake->process) {
                                        'TWIST' => 'bg-primary',
                                        'BONDER' => 'bg-success',
                                        'JOINT' => 'bg-info',
                                        'SHIELD' => 'bg-warning',
                                        'DBL CRIMP' => 'bg-secondary',
                                        default => 'bg-dark'
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $shikake->process ?? '-' }}</span>
                            </td></tr>
                            <tr><td class="text-muted fw-semibold">Conveyor</td><td>{{ $shikake->conveyor ? $shikake->conveyor->conveyor : ($shikake->conveyor ?? '-') }}</td></tr>
                            <tr><td class="text-muted fw-semibold">Carline</td><td>{{ $shikake->carline ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-semibold">Machine</td><td>{{ $shikake->machine ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-semibold">Family</td><td>{{ $shikake->family ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-semibold">QTY</td><td>{{ $shikake->qty ?? '-' }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0 small">
                            <tr><td class="text-muted fw-semibold" width="35%">Shikake No</td><td>{{ $shikake->shikake_no ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-semibold">Sequence</td><td>{{ $shikake->sequence ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-semibold">Store</td><td>{{ $shikake->store ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-semibold">Address</td><td>{{ $shikake->address ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-semibold">Barcode Mesin</td><td>{{ $shikake->barcode_mesin ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-semibold">Released Note</td><td>{{ $shikake->released_note ?? '-' }}</td></tr>
                        </table>
                    </div>
                </div>

                <!-- Additional Info -->
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="border rounded p-2">
                            <h6 class="fw-semibold small mb-1 text-primary">Barcode & Additional</h6>
                            <table class="table table-sm table-borderless mb-0 small">
                                <tr><td class="text-muted" width="35%">Barcode Proses</td><td>{{ $shikake->barcode_proses ?? '-' }}</td></tr>
                                <tr><td class="text-muted">Barcode Navigasi</td><td>{{ $shikake->barcode_navigasi ?? '-' }}</td></tr>
                                <tr><td class="text-muted">Dies</td><td>{{ $shikake->dies ?? '-' }}</td></tr>
                                <tr><td class="text-muted">Jumlah Kombinasi</td><td>{{ $shikake->jumlah_kombinasi ?? '-' }}</td></tr>
                                <tr><td class="text-muted">Blade</td><td>{{ $shikake->blade ?? '-' }}</td></tr>
                                <tr><td class="text-muted">Joint</td><td>{{ $shikake->joint ?? '-' }}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-2">
                            <h6 class="fw-semibold small mb-1 text-primary">CCT & Address Pairs</h6>
                            <table class="table table-sm table-bordered mb-0 small text-center">
                                <thead class="table-light">
                                    <tr><th>CCT</th><th>Address</th></tr>
                                </thead>
                                <tbody>
                                    @php
                                        $pairs = [
                                            ['cct_a', 'address_a'],
                                            ['cct_b', 'address_b'],
                                            ['cct_c', 'address_c'],
                                            ['cct_4', 'address_4'],
                                            ['cct_5', 'address_5'],
                                            ['cct_6', 'address_6'],
                                            ['cct_7', 'address_7'],
                                        ];
                                        $hasPairs = false;
                                    @endphp
                                    @foreach($pairs as $pair)
                                        @if(!empty($shikake->{$pair[0]}) || !empty($shikake->{$pair[1]}))
                                            @php $hasPairs = true; @endphp
                                            <tr>
                                                <td>{{ $shikake->{$pair[0]} ?? '-' }}</td>
                                                <td>{{ $shikake->{$pair[1]} ?? '-' }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                    @if(!$hasPairs)
                                        <tr><td colspan="2" class="text-muted">-</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- T Fields -->
                <div class="border rounded p-2 mb-3">
                    <h6 class="fw-semibold small mb-1 text-primary">T Fields</h6>
                    <table class="table table-sm table-bordered mb-0 small text-center">
                        <thead class="table-light">
                            <tr>
                                @for($i = 1; $i <= 9; $i++)
                                    <th>T{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                @for($i = 1; $i <= 9; $i++)
                                    <td>{{ $shikake->{'t' . str_pad($i, 2, '0', STR_PAD_LEFT)} ?? '-' }}</td>
                                @endfor
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Assy List -->
                @if(isset($shikake) && $shikake->assemblies && $shikake->assemblies->count() > 0)
                <div class="border rounded p-2 mb-3">
                    <h6 class="fw-semibold small mb-1 text-primary">Assy List</h6>
                    <div class="small" style="max-height: 100px; overflow-y: auto;">
                        {{ $shikake->assemblies->pluck('assy')->implode(', ') }}
                    </div>
                </div>
                @endif

                <!-- Drawing Image -->
                @if(isset($shikake) && $shikake->image_path)
                <div class="text-center">
                    <div class="border rounded p-2">
                        <h6 class="fw-semibold small mb-1 text-primary">Drawing</h6>
                        <img src="{{ asset($shikake->image_path) }}" alt="Drawing" class="img-fluid rounded" style="max-height: 300px; cursor:pointer;" onclick="window.open(this.src,'_blank')">
                        <div class="mt-1"><small class="text-muted">Klik gambar untuk memperbesar</small></div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection
