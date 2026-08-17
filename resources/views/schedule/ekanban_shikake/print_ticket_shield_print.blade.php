{{-- SHIELD Process Print Template - PRINT VERSION (landscape, 80mm height) --}}
<style>
/* SHIELD Print Template - Landscape layout for 80mm thermal printer */
/* Native 576px height = 80mm paper width at 203dpi (1:1 no scaling) */
/* Ticket width: 120mm = 987px at 203dpi */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.shield-print-wrapper {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 0;
    margin: 0;
    padding: 0;
    justify-content: flex-start;
    align-items: stretch;
    background: white;
    page-break-after: always;
    min-height: 576px;
}

.shield-print-wrapper .shikake-image-section {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    background: white;
    height: 100%;
    margin: 0;
    padding: 0;
}

.shield-print-wrapper .shikake-image-section img {
    height: 100%;
    width: auto;
    object-fit: contain;
    margin: 0;
    padding: 0;
    display: block;
    /* Enhance drawing visibility for thermal printing */
    filter: contrast(2.0) brightness(0.95);
    -webkit-filter: contrast(2.0) brightness(0.95);
}

.ticket-shield-print {
    width: 642px;
    min-width: 642px;
    min-height: 100%;
    flex-shrink: 0;
    background: white;
    margin: 0;
    padding: 0;
    border: 2px solid #000;
    font-family: Arial, sans-serif;
}

.ticket-shield-print table {
    width: 100%;
    height: 100%;
    border-collapse: collapse;
    /* auto layout: colgroup widths below act as a MINIMUM per column,
       columns grow past that minimum when a value doesn't fit, instead of
       wrapping to 2 lines and blowing out the row height past the fixed
       80mm print height. */
    table-layout: auto;
}

.ticket-shield-print th,
.ticket-shield-print td {
    border: 1px solid #000;
    padding: 2px 4px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.2;
    font-size: 18px;
    box-sizing: border-box;
}

.ticket-shield-print thead th {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 28px;
    padding: 4px;
    border: 2px solid #000;
}

.ticket-shield-print .label-cell {
    background-color: transparent;
    font-weight: 500;
    font-size: 14px;
    white-space: nowrap;
}

.ticket-shield-print .value-cell {
    font-weight: bold;
    font-size: 18px;
    white-space: nowrap;
}

.ticket-shield-print .qrcode-cell {
    padding: 2px;
    vertical-align: middle;
}

.ticket-shield-print .qrcode-cell img {
    max-width: 140px;
    max-height: 140px;
    display: block;
    margin: 0 auto;
}

.ticket-shield-print .qr-img {
    width: 140px;
    height: 140px;
}

.ticket-shield-print .qr-label {
    font-size: 12px;
    font-weight: bold;
    margin-bottom: 4px;
}

.ticket-shield-print .qrcode-placeholder {
    width: 140px;
    height: 140px;
    border: 1px solid #000;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #000;
}

/* Punch strip - right-side spacing with punch circle (same as cutting/twist) */
.shield-punch-strip {
    width: 30mm;
    min-width: 30mm;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    background: white;
    height: 100%;
}

.shield-punch-strip .punch-circle {
    width: 15mm;
    height: 15mm;
    border-radius: 50%;
    background-color: #000;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    flex-shrink: 0;
}

/* Thermal Printer Optimization */
@media print {
    body {
        margin: 0;
        padding: 0;
        background: white;
    }
    
    .shield-print-wrapper {
        flex-direction: row;
        gap: 0;
        margin: 0;
        justify-content: flex-start;
        page-break-after: always;
        page-break-inside: avoid;
    }
    
    .shield-print-wrapper .shikake-image-section {
        display: flex;
        flex-shrink: 0;
        align-items: center;
    }
    
    .shield-print-wrapper .shikake-image-section img {
        height: 70mm;
        width: auto;
        object-fit: contain;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .ticket-shield-print {
        width: auto;
        max-width: none;
        height: auto;
        border: none;
        margin: 0;
        padding: 0;
    }
    
    .ticket-shield-print table {
        page-break-inside: avoid;
        /* let the table shrink/grow to its auto-layout content width instead
           of being forced to 100% of an already-truncated container */
        width: auto;
    }
    
    .ticket-shield-print th,
    .ticket-shield-print td {
        border: 1px solid #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .shield-punch-strip {
        display: flex;
        width: 30mm;
        min-width: 30mm;
        flex-shrink: 0;
    }

    .shield-punch-strip .punch-circle {
        background-color: #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    @page {
        size: landscape;
        margin: 1mm;
    }
}
</style>

{{-- Wrapper with flexbox for image + ticket --}}
<div class="ticket shield-print-wrapper" data-orientation="landscape">
    {{-- Image section (LEFT) - only if image exists --}}
    @if(!empty($shikake->image_path))
    <div class="shikake-image-section">
        <img src="{{ asset($shikake->image_path) }}" alt="Shikake Image">
    </div>
    @endif
    
    {{-- Ticket section (RIGHT) - landscape layout for 80mm thermal printer --}}
    <div class="ticket-shield-print">
        <table>
            <colgroup>
                <col style="width: 20mm">
                <col style="width: 20mm">
                <col style="width: 20mm">
                <col style="width: 20mm">
            </colgroup>
        <thead>
            <tr>
                <th colspan="4">EKANBAN SHIELD WIRE - {{ $shikake->carline ?? '' }}</th>
            </tr>
        </thead>
        <tbody>
            <!-- Row 1: DRAWING NO / ADDRESS Labels -->
            <tr>
                <td colspan="2" class="label-cell">DRAWING NO</td>
                <td colspan="2" class="label-cell">ADDRESS</td>
            </tr>
            <!-- Row 2: DRAWING NO / ADDRESS Values -->
            <tr>
                <td colspan="2" class="value-cell">{{ $processData->shield_no ?? '' }}</td>
                <td colspan="2" class="value-cell">{{ $processData->address ?? '' }}</td>
            </tr>
            
            <!-- Row 3: CCT NO 1 / QR DRWG / QTY / ISSUE Labels -->
            <tr>
                <td class="label-cell">CCT NO 1</td>
                <td class="label-cell">QR DRWG</td>
                <td class="label-cell">QTY</td>
                <td class="label-cell">ISSUE</td>
            </tr>
            <!-- Row 4: CCT value + QR Drawing (rowspan=3) + QTY/ISSUE -->
            <tr>
                <td class="value-cell">{{ $processData->cct_no_1 ?? '' }}</td>
                <td rowspan="3" class="qrcode-cell">
                    @if(isset($processData->qrcode_drawing_path))
                        <img src="{{ $processData->qrcode_drawing_path }}" alt="QR Drawing" class="qr-img">
                    @else
                        <div class="qrcode-placeholder">QR</div>
                    @endif
                    @if(!empty($processData->qrcode_drawing))
                        <div style="font-size: 9px; margin-top: 1px; font-weight: bold;">{{ $processData->qrcode_drawing }}</div>
                    @endif
                </td>
                <td class="value-cell">{{ $shikake->qty ?? '' }}</td>
                <td class="value-cell">{{ $shikake->issue ?? '' }}</td>
            </tr>
            <!-- Row 5: ADDRESS 1 / MACHINE / QRCODE KANBAN Labels -->
            <tr>
                <td class="label-cell">ADDRESS 1</td>
                <td class="label-cell">MACHINE</td>
                <td class="label-cell">QRCODE KANBAN</td>
            </tr>
            <!-- Row 6: ADDRESS 1 value / MACHINE value / QR Kanban (rowspan=3) -->
            <tr>
                <td class="value-cell">{{ $processData->address_no_1_1 ?? '' }}</td>
                <td class="value-cell">{{ $shikake->machine ?? '' }}</td>
                <td rowspan="3" class="qrcode-cell">
                    @if(isset($shikake->qr_code_path))
                        <img src="{{ $shikake->qr_code_path }}" alt="QR Code" class="qr-img">
                    @else
                        <div class="qrcode-placeholder">QR</div>
                    @endif
                    @if(!empty($shikake->barcode_kanban))
                        <div style="font-size: 9px; margin-top: 1px; font-weight: bold;">{{ $shikake->barcode_kanban }}</div>
                    @endif
                </td>
            </tr>
            <!-- Row 7: SEQ / BLADE Labels -->
            <tr>
                <td class="label-cell">SEQ</td>
                <td class="label-cell">BLADE</td>
                <td class="label-cell"></td>
            </tr>
            <!-- Row 8: SEQ / BLADE Values -->
            <tr>
                <td class="value-cell">{{ $shikake->sequence ?? '' }}</td>
                <td class="value-cell">{{ $processData->blade ?? '' }}</td>
                <td class="value-cell"></td>
            </tr>
            
            <!-- Row 10: TO 1-4 Labels -->
            <tr>
                <td class="label-cell">TO 1</td>
                <td class="label-cell">TO 2</td>
                <td class="label-cell">TO 3</td>
                <td class="label-cell">TO 4</td>
            </tr>
            <!-- Row 11: TO 1-4 Values -->
            <tr>
                <td class="value-cell">{{ $processData->to_1 ?? '' }}</td>
                <td class="value-cell">{{ $processData->to_2 ?? '' }}</td>
                <td class="value-cell">{{ $processData->to_3 ?? '' }}</td>
                <td class="value-cell">{{ $processData->to_4 ?? '' }}</td>
            </tr>
            <!-- Row 12: TO 5-8 Labels -->
            <tr>
                <td class="label-cell">TO 5</td>
                <td class="label-cell">TO 6</td>
                <td class="label-cell">TO 7</td>
                <td class="label-cell">TO 8</td>
            </tr>
            <!-- Row 13: TO 5-8 Values -->
            <tr>
                <td class="value-cell">{{ $processData->to_5 ?? '' }}</td>
                <td class="value-cell">{{ $processData->to_6 ?? '' }}</td>
                <td class="value-cell">{{ $processData->to_7 ?? '' }}</td>
                <td class="value-cell">{{ $processData->to_8 ?? '' }}</td>
            </tr>
            
            <!-- Row 14: TO 9 / DATE / CV / FAMILY Labels -->
            <tr>
                <td class="label-cell">TO 9</td>
                <td class="label-cell">DATE</td>
                <td class="label-cell">CV</td>
                <td class="label-cell">FAMILY</td>
            </tr>
            <!-- Row 15: TO 9 / DATE / CV / FAMILY Values -->
            <tr>
                <td class="value-cell">{{ $processData->to_9 ?? '' }}</td>
                <td class="value-cell">{{ $shikake->release_date ? \Carbon\Carbon::parse($shikake->release_date)->format('d-M-y') : '' }}</td>
                <td class="value-cell">{{ $shikake->conveyor ?? '' }}</td>
                <td class="value-cell">{{ $shikake->family ?? '' }}</td>
            </tr>
            
            <!-- Row 16: NOTE / RELEASED NOTE -->
            <tr>
                <td class="label-cell">NOTE</td>
                <td colspan="3" class="value-cell">{{ $shikake->released_note ?? '' }}</td>
            </tr>
        </tbody>
    </table>
    </div>

    {{-- Punch strip (RIGHT) - spacing + punch circle, same as cutting/twist --}}
    <div class="shield-punch-strip">
        <div class="punch-circle"></div>
    </div>
</div>
{{-- End of shield-print-wrapper --}}
