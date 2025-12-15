@extends('layout')

@section('title', 'View Shikake Data')

@section('content')
    <x-page-header menu-code="master_shikake" />

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Shikake Detail</h3>
                    <div class="card-tools">
                        <a href="{{ route('master-data.master-shikake.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        @if(auth()->user()->hasMenuPermission('master_shikake', 'can_update'))
                            <a href="{{ route('master-data.master-shikake.edit', $shikake->id) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Conveyor:</th>
                                    <td>{{ $shikake->conveyor ? $shikake->conveyor->conveyor : $shikake->conveyor }}</td>
                                </tr>
                                <tr>
                                    <th>Shikake No:</th>
                                    <td>{{ $shikake->shikake_no ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Family:</th>
                                    <td>{{ $shikake->family ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Quantity:</th>
                                    <td>{{ $shikake->qty ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Issue:</th>
                                    <td>{{ $shikake->issue ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Machine:</th>
                                    <td>{{ $shikake->machine ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Sequence:</th>
                                    <td>{{ $shikake->sequence ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Barcode Kanban:</th>
                                    <td>{{ $shikake->barcode_kanban ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Released Date:</th>
                                    <td>{{ $shikake->released_date ? $shikake->released_date->format('Y-m-d') : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Released Note:</th>
                                    <td>{{ $shikake->released_note ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Store:</th>
                                    <td>{{ $shikake->store ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Barcode Mesin:</th>
                                    <td>{{ $shikake->barcode_mesin ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Address:</th>
                                    <td>{{ $shikake->address ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">CCT A:</th>
                                    <td>{{ $shikake->cct_a ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Address A:</th>
                                    <td>{{ $shikake->address_a ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>CCT B:</th>
                                    <td>{{ $shikake->cct_b ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Address B:</th>
                                    <td>{{ $shikake->address_b ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>CCT C:</th>
                                    <td>{{ $shikake->cct_c ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Address C:</th>
                                    <td>{{ $shikake->address_c ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>CCT 4:</th>
                                    <td>{{ $shikake->cct_4 ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Address 4:</th>
                                    <td>{{ $shikake->address_4 ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>CCT 5:</th>
                                    <td>{{ $shikake->cct_5 ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Address 5:</th>
                                    <td>{{ $shikake->address_5 ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>CCT 6:</th>
                                    <td>{{ $shikake->cct_6 ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Address 6:</th>
                                    <td>{{ $shikake->address_6 ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>CCT 7:</th>
                                    <td>{{ $shikake->cct_7 ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Address 7:</th>
                                    <td>{{ $shikake->address_7 ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h5>Additional Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="20%">Barcode Proses:</th>
                                    <td width="30%">{{ $shikake->barcode_proses ?? '-' }}</td>
                                    <th width="20%">Barcode Navigasi:</th>
                                    <td width="30%">{{ $shikake->barcode_navigasi ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Dies:</th>
                                    <td>{{ $shikake->dies ?? '-' }}</td>
                                    <th>Jumlah Kombinasi:</th>
                                    <td>{{ $shikake->jumlah_kombinasi ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Blade:</th>
                                    <td>{{ $shikake->blade ?? '-' }}</td>
                                    <th>Joint:</th>
                                    <td>{{ $shikake->joint ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h5>T Fields</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>T01</th>
                                    <th>T02</th>
                                    <th>T03</th>
                                    <th>T04</th>
                                    <th>T05</th>
                                    <th>T06</th>
                                    <th>T07</th>
                                    <th>T08</th>
                                    <th>T09</th>
                                </tr>
                                <tr>
                                    <td>{{ $shikake->t01 ?? '-' }}</td>
                                    <td>{{ $shikake->t02 ?? '-' }}</td>
                                    <td>{{ $shikake->t03 ?? '-' }}</td>
                                    <td>{{ $shikake->t04 ?? '-' }}</td>
                                    <td>{{ $shikake->t05 ?? '-' }}</td>
                                    <td>{{ $shikake->t06 ?? '-' }}</td>
                                    <td>{{ $shikake->t07 ?? '-' }}</td>
                                    <td>{{ $shikake->t08 ?? '-' }}</td>
                                    <td>{{ $shikake->t09 ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
