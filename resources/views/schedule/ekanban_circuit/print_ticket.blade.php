{{-- CIRCUIT Print Template - PRINT VERSION (landscape, 80mm height) --}}
<style>
/* CIRCUIT Print Template - Landscape layout for 80mm thermal printer */
/* Native 576px height = 80mm paper width at 203dpi (1:1 no scaling) */
/* Ticket width: 130mm = 1070px at 203dpi */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.circuit-print-wrapper {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 0;
    margin: 0;
    justify-content: flex-start;
    align-items: stretch;
    background: white;
    page-break-after: always;
    height: 576px;
}

.circuit-print-wrapper .circuit-image-section {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    height: 100%;
}

.circuit-print-wrapper .circuit-image-section img {
    height: 100%;
    width: auto;
    object-fit: contain;
}

.ticket-circuit-print {
    width: 800px;
    min-width: 800px;
    height: 576px;
    flex-shrink: 0;
    background: white;
    margin: 0;
    padding: 0;
    border: none;
    overflow: hidden;
    font-family: Arial, sans-serif;
    display: block;
    line-height: 0;
}

.ticket-circuit-print table {
    width: 100%;
    height: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    border-spacing: 0;
    margin: 0;
    padding: 0;
}

.ticket-circuit-print th,
.ticket-circuit-print td {
    border: 1px solid #000;
    padding: 0;
    text-align: center;
    vertical-align: middle;
    line-height: 1.1;
    font-size: 16px;
    box-sizing: border-box;
}

.ticket-circuit-print thead th {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 24px;
    padding: 0;
    border: 2px solid #000;
}

.ticket-circuit-print .label-cell {
    background-color: #f0f0f0;
    font-weight: 500;
    font-size: 12px;
}

.ticket-circuit-print .value-cell {
    font-weight: bold;
    font-size: 16px;
}

.ticket-circuit-print .section-a {
    background-color: #fff;
    color: #000;
    font-weight: bold;
    font-size: 18px;
    width: 20px;
}

.ticket-circuit-print .section-b {
    background-color: #000;
    color: #fff;
    font-weight: bold;
    font-size: 18px;
    width: 20px;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.ticket-circuit-print .qrcode-cell {
    padding: 0;
    vertical-align: middle;
}

.ticket-circuit-print .qrcode-cell img {
    max-width: 120px;
    max-height: 120px;
    display: block;
    margin: 0 auto;
}

.ticket-circuit-print .qr-img {
    width: 120px;
    height: 120px;
}

.ticket-circuit-print .qr-label {
    font-size: 11px;
    font-weight: bold;
    margin-bottom: 2px;
}

.ticket-circuit-print .barcode-cell {
    padding: 0;
    vertical-align: middle;
}

.ticket-circuit-print .barcode-cell img {
    max-width: 140px;
    height: 45px;
    display: block;
    margin: 0 auto;
}

.ticket-circuit-print .qrcode-placeholder,
.ticket-circuit-print .barcode-placeholder {
    border: 1px solid #000;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #000;
}

.ticket-circuit-print .qrcode-placeholder {
    width: 120px;
    height: 120px;
}

.ticket-circuit-print .barcode-placeholder {
    width: 140px;
    height: 45px;
}

/* Info table in header */
.ticket-circuit-print .info-row th {
    background-color: #f0f0f0;
    font-size: 11px;
    font-weight: bold;
    padding: 0;
}

.ticket-circuit-print .info-row td {
    font-size: 14px;
    font-weight: bold;
    padding: 2px;
}

/* Thermal Printer Optimization */
@media print {
    body {
        margin: 0;
        padding: 0;
        background: white;
    }
    
    .circuit-print-wrapper {
        flex-direction: row;
        gap: 0;
        margin: 0;
        justify-content: flex-start;
        page-break-after: always;
        page-break-inside: avoid;
    }
    
    .circuit-print-wrapper .circuit-image-section {
        display: flex;
        flex-shrink: 0;
        align-items: center;
    }
    
    .circuit-print-wrapper .circuit-image-section img {
        height: 70mm;
        width: auto;
        object-fit: contain;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .ticket-circuit-print {
        width: auto;
        max-width: none;
        height: auto;
        border: none;
        margin: 0;
        padding: 0;
    }
    
    .ticket-circuit-print table {
        page-break-inside: avoid;
        width: 100%;
    }
    
    .ticket-circuit-print th,
    .ticket-circuit-print td {
        border: 1px solid #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .ticket-circuit-print .section-b {
        background-color: #000 !important;
        color: #fff !important;
    }
    
    .ticket-circuit-print .label-cell {
        background-color: #f0f0f0 !important;
    }
    
    @page {
        size: landscape;
        margin: 1mm;
    }
}
</style>

@foreach($circuits as $circuit)
{{-- Wrapper with flexbox for image + ticket --}}
<div class="ticket circuit-print-wrapper" data-orientation="landscape">
    {{-- Ticket section (RIGHT) - landscape layout for 80mm thermal printer --}}
    <div class="ticket-circuit-print">
        <table>
            <colgroup>
                <col style="width: 4%">
                <col style="width: 11%">
                <col style="width: 11%">
                <col style="width: 11%">
                <col style="width: 11%">
                <col style="width: 11%">
                <col style="width: 11%">
                <col style="width: 11%">
                <col style="width: 19%">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="9">E-KANBAN CUTTING - {{ $circuit->carline ?? '' }}</th>
                </tr>
            </thead>
            <tbody>
                <!-- Row 1: Info Headers -->
                <tr class="info-row">
                    <th colspan="2">CCT NO</th>
                    <th>CUST NO</th>
                    <th>Kind</th>
                    <th>Size</th>
                    <th>Colour</th>
                    <th>C/L</th>
                    <th>SEQ</th>
                    <th>MACHINE</th>
                </tr>
                <!-- Row 2: Info Values -->
                <tr class="info-row">
                    <td colspan="2" class="value-cell">{{ $circuit->cct_no ?? '' }}</td>
                    <td class="value-cell">{{ $circuit->cust_no ?? '' }}</td>
                    <td class="value-cell">{{ $circuit->kind ?? '' }}</td>
                    <td class="value-cell">{{ $circuit->size ?? '' }}</td>
                    <td class="value-cell">{{ $circuit->col ?? '' }}</td>
                    <td class="value-cell">{{ $circuit->cl ?? '' }}</td>
                    <td class="value-cell">{{ $circuit->sequence ?? '' }}</td>
                    <td class="value-cell">{{ $circuit->machine ?? '' }}</td>
                </tr>
                
                <!-- Section A: Row 3-6 -->
                <tr>
                    <td rowspan="4" class="section-a">A</td>
                    <td class="label-cell" style="text-align: left;">Terminal</td>
                    <td class="value-cell" style="text-align: left;" colspan="3">{{ $circuit->terminal_1 ?? '' }}</td>
                    <td class="value-cell">{{ $circuit->gold_1 ?? '' }}</td>
                    <td class="value-cell">{{ $circuit->note_1 ?? '' }}</td>
                    <td class="label-cell" style="text-align: left;">To 1</td>
                    <td class="value-cell">{{ $circuit->t01 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label-cell" style="text-align: left;">Acc 1</td>
                    <td class="value-cell" style="text-align: left;" colspan="3">{{ $circuit->acc_1 ?? '' }}</td>
                    <td class="label-cell" style="text-align: left;">Note</td>
                    <td class="value-cell">{{ $circuit->ta ?? '' }}</td>
                    <td class="label-cell" style="text-align: left;">To 2</td>
                    <td class="value-cell">{{ $circuit->t02 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label-cell" style="text-align: left;">Acc 2</td>
                    <td class="value-cell" style="text-align: left;" colspan="3">{{ $circuit->acc_1a ?? '' }}</td>
                    <td class="label-cell" style="text-align: left;">Strip</td>
                    <td class="value-cell">{{ $circuit->strip_1 ?? '' }}</td>
                    <td class="label-cell" style="text-align: left;">To 3</td>
                    <td class="value-cell">{{ $circuit->t03 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label-cell" style="text-align: left;">Tube</td>
                    <td class="value-cell" style="text-align: left;" colspan="3">{{ $circuit->tube_1 ?? '' }}</td>
                    <td class="label-cell" style="text-align: left;">Mark</td>
                    <td class="value-cell">{{ $circuit->mark_1 ?? '' }}</td>
                    <td class="label-cell" rowspan="2" style="text-align: left;">To Store</td>
                    <td class="value-cell" rowspan="2">{{ $circuit->to_store ?? '' }}</td>
                </tr>
                
                <!-- Section B: Row 7-10 -->
                <tr>
                    <td rowspan="4" class="section-b">B</td>
                    <td class="label-cell" style="text-align: left;">Terminal</td>
                    <td class="value-cell" style="text-align: left;" colspan="3">{{ $circuit->terminal_2 ?? '' }}</td>
                    <td class="value-cell">{{ $circuit->gold_2 ?? '' }}</td>
                    <td class="value-cell">{{ $circuit->note_2 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label-cell" style="text-align: left;">Acc 1</td>
                    <td class="value-cell" style="text-align: left;" colspan="3">{{ $circuit->acc_2 ?? '' }}</td>
                    <td class="label-cell" style="text-align: left;">Note</td>
                    <td class="value-cell">{{ $circuit->tb ?? '' }}</td>
                    <td class="label-cell" style="text-align: left;">CCT Code</td>
                    <td class="value-cell" >{{ $circuit->cct_code ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label-cell" style="text-align: left;">Acc 2</td>
                    <td class="value-cell" style="text-align: left;" colspan="3">{{ $circuit->acc_2a ?? '' }}</td>
                    <td class="label-cell" style="text-align: left;">Strip</td>
                    <td class="value-cell" >{{ $circuit->strip_2 ?? '' }}</td>
                    <td class="label-cell" style="text-align: left;">Qty</td>
                    <td class="value-cell" >{{ $circuit->qty ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label-cell" style="text-align: left;">Tube</td>
                    <td class="value-cell" style="text-align: left;" colspan="3">{{ $circuit->tube_2 ?? '' }}</td>
                    <td class="label-cell" style="text-align: left;">Mark</td>
                    <td class="value-cell">{{ $circuit->mark_2 ?? '' }}</td>
                    <td class="label-cell" style="text-align: left;">Family</td>
                    <td class="value-cell" >{{ $circuit->issue ?? '' }}</td>
                </tr>
                <!-- Barcode Section -->
                <tr>
                    <td colspan="4" class="label-cell">BARCODE KANBAN</td>
                    <td colspan="3" class="value-cell" style="text-align: left;">{{$circuit->family}}</td>
                    <td colspan="2" class="label-cell">BARCODE MESIN</td>
                </tr>
                <tr>
                    <td colspan="4" rowspan="4" class="qrcode-cell">
                        @if(isset($circuit->qr_code_path))
                            <img src="{{ $circuit->qr_code_path }}" alt="QR Code" class="qr-img">
                            <div class="qr-label">{{ $circuit->barcode_kanban ?? "" }}</div>
                        @else
                            <div class="qrcode-placeholder">QR CODE</div>
                        @endif
                    </td>
                    <td colspan="3" class="value-cell" style="text-align: left;">CV. {{ $circuit->conveyor}}</td>
                    <td colspan="2" rowspan="4" class="barcode-cell">
                        @if(isset($circuit->barcode_path))
                            <img src="{{ $circuit->barcode_path }}" alt="Barcode">
                            <div class="qr-label">{{ $circuit->barcode_mesin ?? "" }}</div>
                        @else
                            <div class="barcode-placeholder">BARCODE</div>
                        @endif
                    </td>
                </tr>
                <tr>
                <td colspan="3" class="value-cell" style="text-align: left;">{{ $circuit->release_date ? \Carbon\Carbon::parse($circuit->release_date)->locale('id')->translatedFormat('d F Y') : '' }}</td>
                </tr>
                <tr>
                    <td colspan="3" class="value-cell" style="text-align: left;">{{ $circuit->released_note ?? '' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endforeach
