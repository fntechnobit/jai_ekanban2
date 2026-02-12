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
    margin: 10px auto;
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
    width: 1070px;
    min-width: 1070px;
    height: 100%;
    flex-shrink: 0;
    background: white;
    margin: 0;
    padding: 0;
    border: 2px solid #ddd;
    overflow: hidden;
    font-family: Arial, sans-serif;
}

.ticket-circuit-print table {
    width: 100%;
    height: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.ticket-circuit-print th,
.ticket-circuit-print td {
    border: 1px solid #000;
    padding: 2px 4px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.2;
    font-size: 16px;
    box-sizing: border-box;
}

.ticket-circuit-print thead th {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 24px;
    padding: 4px;
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
    width: 25px;
}

.ticket-circuit-print .section-b {
    background-color: #000;
    color: #fff;
    font-weight: bold;
    font-size: 18px;
    width: 25px;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.ticket-circuit-print .qrcode-cell {
    padding: 2px;
    vertical-align: middle;
}

.ticket-circuit-print .qrcode-cell img {
    max-width: 140px;
    max-height: 140px;
    display: block;
    margin: 0 auto;
}

.ticket-circuit-print .qr-img {
    width: 140px;
    height: 140px;
}

.ticket-circuit-print .qr-label {
    font-size: 10px;
    font-weight: bold;
    margin-bottom: 2px;
}

.ticket-circuit-print .barcode-cell {
    padding: 2px;
    vertical-align: middle;
}

.ticket-circuit-print .barcode-cell img {
    max-width: 160px;
    height: 50px;
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
    font-size: 10px;
    color: #000;
}

.ticket-circuit-print .qrcode-placeholder {
    width: 140px;
    height: 140px;
}

.ticket-circuit-print .barcode-placeholder {
    width: 160px;
    height: 50px;
}

/* Info table in header */
.ticket-circuit-print .info-row th {
    background-color: #f0f0f0;
    font-size: 11px;
    font-weight: bold;
    padding: 2px;
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
    {{-- Image section (LEFT) - only if image exists --}}
    @if(!empty($circuit->image_path))
    <div class="circuit-image-section">
        <img src="{{ asset($circuit->image_path) }}" alt="Circuit Image">
    </div>
    @endif
    
    {{-- Ticket section (RIGHT) - landscape layout for 80mm thermal printer --}}
    <div class="ticket-circuit-print">
        <table>
            <colgroup>
                <col style="width: 2.5%">
                <col style="width: 7%">
                <col style="width: 11%">
                <col style="width: 6%">
                <col style="width: 7%">
                <col style="width: 7%">
                <col style="width: 11%">
                <col style="width: 16%">
                <col style="width: 16%">
                <col style="width: 16%">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="10">EKANBAN CUTTING - {{ $circuit->carline ?? '' }}</th>
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
                    <th>QTY</th>
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
                    <td class="value-cell">{{ $circuit->qty ?? '' }}</td>
                </tr>
                
                <!-- Section A: Row 3-6 -->
                <tr>
                    <td rowspan="4" class="section-a">A</td>
                    <td class="label-cell">Terminal</td>
                    <td class="value-cell">{{ $circuit->terminal_1 ?? '' }}</td>
                    <td class="value-cell">{{ $circuit->gold_1 ?? '' }}</td>
                    <td class="value-cell" colspan="2">{{ $circuit->note_1 ?? '' }}</td>
                    <td class="label-cell">To 1</td>
                    <td class="value-cell" colspan="3">{{ $circuit->t01 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Acc 1</td>
                    <td class="value-cell">{{ $circuit->acc_1b ?? '' }}</td>
                    <td class="label-cell">Note</td>
                    <td class="value-cell" colspan="2">{{ $circuit->note_1 ?? '' }}</td>
                    <td class="label-cell">To 2</td>
                    <td class="value-cell" colspan="3">{{ $circuit->t02 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Acc 2</td>
                    <td class="value-cell">{{ $circuit->acc_1a ?? '' }}</td>
                    <td class="label-cell">Strip</td>
                    <td class="value-cell" colspan="2">{{ $circuit->strip_1 ?? '' }}</td>
                    <td class="label-cell">To 3</td>
                    <td class="value-cell" colspan="3">{{ $circuit->t03 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Tube</td>
                    <td class="value-cell">{{ $circuit->tube_1 ?? '' }}</td>
                    <td class="label-cell">Mark</td>
                    <td class="value-cell" colspan="2">{{ $circuit->mark_1 ?? '' }}</td>
                    <td class="label-cell">To</td>
                    <td class="value-cell" colspan="3">{{ $circuit->address ?? '' }}</td>
                </tr>
                
                <!-- Section B: Row 7-10 -->
                <tr>
                    <td rowspan="4" class="section-b">B</td>
                    <td class="label-cell">Terminal</td>
                    <td class="value-cell">{{ $circuit->terminal_2 ?? '' }}</td>
                    <td class="value-cell">{{ $circuit->gold_2 ?? '' }}</td>
                    <td class="value-cell" colspan="2">{{ $circuit->note_2 ?? '' }}</td>
                    <td class="label-cell">Cct Code</td>
                    <td class="value-cell" colspan="3">{{ $circuit->cct_code ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Acc 1</td>
                    <td class="value-cell">{{ $circuit->acc_2b ?? '' }}</td>
                    <td class="label-cell">Note</td>
                    <td class="value-cell" colspan="2">{{ $circuit->note_2 ?? '' }}</td>
                    <td class="label-cell">Issue</td>
                    <td class="value-cell" colspan="3">{{ $circuit->issue ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Acc 2</td>
                    <td class="value-cell">{{ $circuit->acc_2a ?? '' }}</td>
                    <td class="label-cell">Strip</td>
                    <td class="value-cell" colspan="2">{{ $circuit->strip_2 ?? '' }}</td>
                    <td class="label-cell">CV</td>
                    <td class="value-cell" colspan="3">{{ $circuit->conveyor ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Tube</td>
                    <td class="value-cell">{{ $circuit->tube_2 ?? '' }}</td>
                    <td class="label-cell">Mark</td>
                    <td class="value-cell" colspan="2">{{ $circuit->mark_2 ?? '' }}</td>
                    <td class="label-cell">Family</td>
                    <td class="value-cell" colspan="3">{{ $circuit->family ?? '' }}</td>
                </tr>
                
                <!-- Barcode Section -->
                <tr>
                    <td colspan="4" class="label-cell">BARCODE KANBAN</td>
                    <td colspan="3" class="label-cell">DATE / NOTE</td>
                    <td colspan="3" class="label-cell">BARCODE MESIN</td>
                </tr>
                <tr>
                    <td colspan="4" rowspan="2" class="qrcode-cell">
                        @if(isset($circuit->qr_code_path))
                            <img src="{{ $circuit->qr_code_path }}" alt="QR Code" class="qr-img">
                            <div class="qr-label">{{ $circuit->barcode_kanban ?? "" }}</div>
                        @else
                            <div class="qrcode-placeholder">QR CODE</div>
                        @endif
                    </td>
                    <td colspan="3" class="value-cell">{{ $circuit->release_date ? \Carbon\Carbon::parse($circuit->release_date)->format('d-M-y') : '' }}</td>
                    <td colspan="3" rowspan="2" class="barcode-cell">
                        @if(isset($circuit->barcode_path))
                            <img src="{{ $circuit->barcode_path }}" alt="Barcode">
                            <div class="qr-label">{{ $circuit->barcode_mesin ?? "" }}</div>
                        @else
                            <div class="barcode-placeholder">BARCODE</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td colspan="3" class="value-cell" style="font-size: 12px;">{{ $circuit->released_note ?? '' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endforeach
