{{-- DBL CRIMP Process Print Template - PRINT VERSION (landscape, 80mm height) --}}
<style>
/* DBL CRIMP Print Template - Landscape layout for 80mm thermal printer */
/* Native 576px height = 80mm paper width at 203dpi (1:1 no scaling) */
/* Ticket width: 120mm = 987px at 203dpi */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.dbl-crimp-print-wrapper {
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

.dbl-crimp-print-wrapper .shikake-image-section {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    background: white;
    height: 100%;
    margin: 0;
    padding: 0;
}

.dbl-crimp-print-wrapper .shikake-image-section img {
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

.ticket-dbl-crimp-print {
    width: 654px;
    min-width: 654px;
    min-height: 100%;
    flex-shrink: 0;
    background: white;
    margin: 0;
    padding: 0;
    border: 2px solid #000;
    font-family: Arial, sans-serif;
}

.ticket-dbl-crimp-print table {
    width: 100%;
    height: 100%;
    border-collapse: collapse;
    /* auto layout: colgroup widths below act as a MINIMUM per column,
       columns grow past that minimum when a value doesn't fit, instead of
       wrapping to 2 lines and blowing out the row height past the fixed
       80mm print height. */
    table-layout: auto;
}

.ticket-dbl-crimp-print th,
.ticket-dbl-crimp-print td {
    border: 1px solid #000;
    padding: 2px 4px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.2;
    font-size: 18px;
    box-sizing: border-box;
}

.ticket-dbl-crimp-print thead th {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 28px;
    padding: 4px;
    border: 2px solid #000;
}

.ticket-dbl-crimp-print .label-cell {
    background-color: transparent;
    font-weight: 500;
    font-size: 14px;
    white-space: nowrap;
}

.ticket-dbl-crimp-print .value-cell {
    font-weight: bold;
    font-size: 18px;
    white-space: nowrap;
}

.ticket-dbl-crimp-print .qrcode-cell {
    padding: 2px;
    vertical-align: middle;
}

.ticket-dbl-crimp-print .qrcode-cell img {
    max-width: 130px;
    max-height: 130px;
    display: block;
    margin: 0 auto;
}

.ticket-dbl-crimp-print .qr-img {
    width: 130px;
    height: 130px;
    display: block;
    margin: 0 auto;
}

.ticket-dbl-crimp-print .qr-label {
    font-size: 9px;
    font-weight: bold;
    margin-bottom: 2px;
}

.ticket-dbl-crimp-print .barcode-cell {
    padding: 2px;
    vertical-align: middle;
}

.ticket-dbl-crimp-print .barcode-cell img {
    max-width: 180px;
    height: 60px;
    display: block;
    margin: 0 auto;
}

.ticket-dbl-crimp-print .qrcode-placeholder,
.ticket-dbl-crimp-print .barcode-placeholder {
    border: 1px solid #000;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #000;
}

.ticket-dbl-crimp-print .qrcode-placeholder {
    width: 130px;
    height: 130px;
}

.ticket-dbl-crimp-print .barcode-placeholder {
    width: 180px;
    height: 60px;
}

/* Punch strip - right-side spacing with punch circle (same as cutting/twist) */
.dbl-crimp-punch-strip {
    width: 30mm;
    min-width: 30mm;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    background: white;
    height: 100%;
}

.dbl-crimp-punch-strip .punch-circle {
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
    
    .dbl-crimp-print-wrapper {
        flex-direction: row;
        gap: 0;
        margin: 0;
        justify-content: flex-start;
        page-break-after: always;
        page-break-inside: avoid;
    }
    
    .dbl-crimp-print-wrapper .shikake-image-section {
        display: flex;
        flex-shrink: 0;
        align-items: center;
    }
    
    .dbl-crimp-print-wrapper .shikake-image-section img {
        height: 70mm;
        width: auto;
        object-fit: contain;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .ticket-dbl-crimp-print {
        width: auto;
        max-width: none;
        height: auto;
        border: none;
        margin: 0;
        padding: 0;
    }
    
    .ticket-dbl-crimp-print table {
        page-break-inside: avoid;
        /* let the table shrink/grow to its auto-layout content width instead
           of being forced to 100% of an already-truncated container */
        width: auto;
    }
    
    .ticket-dbl-crimp-print th,
    .ticket-dbl-crimp-print td {
        border: 1px solid #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .dbl-crimp-punch-strip {
        display: flex;
        width: 30mm;
        min-width: 30mm;
        flex-shrink: 0;
    }

    .dbl-crimp-punch-strip .punch-circle {
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
<div class="ticket dbl-crimp-print-wrapper" data-orientation="landscape">
    {{-- Image section (LEFT) - only if image exists --}}
    @if(!empty($shikake->image_path))
    <div class="shikake-image-section">
        <img src="{{ asset($shikake->image_path) }}" alt="Shikake Image">
    </div>
    @endif
    
    {{-- Ticket section (RIGHT) - landscape layout for 80mm thermal printer --}}
    <div class="ticket-dbl-crimp-print">
        <table>
            <colgroup>
                <col style="width: 16mm">
                <col style="width: 31mm">
                <col style="width: 16mm">
                <col style="width: 19mm">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="4">EKANBAN DBL CRIMP - {{ $shikake->carline ?? '' }}</th>
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
                    <td colspan="2" class="value-cell">{{ $processData->drawing_no ?? '' }}</td>
                    <td colspan="2" class="value-cell">{{ $processData->address ?? '' }}</td>
                </tr>
                
                <!-- Row 3: CCT-NO / ADDRESS / QR DRWG Labels -->
                <tr>
                    <td class="label-cell">CCT-NO</td>
                    <td class="label-cell">ADDRESS</td>
                    <td colspan="2" class="label-cell">QR DRWG</td>
                </tr>
                <!-- Row 4-5: CCT 1-2 with QR Drawing (qrcode_drawing) -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_1 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->address_1 ?? '' }}</td>
                    <td colspan="2" rowspan="2" class="qrcode-cell">
                        @if(isset($processData->qrcode_drawing_path))
                            <img src="{{ $processData->qrcode_drawing_path }}" alt="QR Drawing" class="qr-img">
                        @else
                            <div class="qrcode-placeholder">QR</div>
                        @endif
                        @if(!empty($processData->qrcode_drawing))
                            <div style="font-size: 8px; margin-top: 1px; font-weight: bold;">{{ $processData->qrcode_drawing }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_2 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->address_2 ?? '' }}</td>
                </tr>

                <!-- Row 6-7: CCT 3-4 + MACHINE Label/Value -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_3 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->address_3 ?? '' }}</td>
                    <td colspan="2" class="label-cell">MACHINE</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_4 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->address_4 ?? '' }}</td>
                    <td colspan="2" class="value-cell">{{ $shikake->machine ?? '' }}</td>
                </tr>
                
                <!-- Row 9-13: QRCODE KANBAN Section -->
                <tr>
                    <td colspan="2" rowspan="5" class="qrcode-cell">
                        <div class="qr-label">QRCODE KANBAN</div>
                        @if(isset($shikake->qr_code_path))
                            <img src="{{ $shikake->qr_code_path }}" alt="QR Code" class="qr-img">
                            <div style="font-size: 8px; margin-top: 1px; font-weight: bold;">{{ $shikake->barcode_kanban ?? '' }}</div>
                        @else
                            <div class="qrcode-placeholder">QR</div>
                        @endif
                    </td>
                    <td class="label-cell">SEQ</td>
                    <td class="label-cell">TO MACH</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $shikake->sequence ?? '' }}</td>
                    <td class="value-cell">{{ $processData->to_machine ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">QTY</td>
                    <td class="label-cell">ISSUE</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $shikake->qty ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->issue ?? '' }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="value-cell">{{ $shikake->family ?? '' }}</td>
                </tr>
                
                <!-- Row 14: Footer - RELEASE NOTE / CV / DATE -->
                <tr>
                    <td colspan="2" class="label-cell">{{ $shikake->released_note ?? 'RELEASE NOTE' }}</td>
                    <td class="value-cell">{{ $shikake->conveyor ? 'CV' . $shikake->conveyor : '' }}</td>
                    <td class="value-cell">{{ $shikake->release_date ? \Carbon\Carbon::parse($shikake->release_date)->format('d-M-y') : '' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Punch strip (RIGHT) - spacing + punch circle, same as cutting/twist --}}
    <div class="dbl-crimp-punch-strip">
        <div class="punch-circle"></div>
    </div>
</div>
{{-- End of dbl-crimp-print-wrapper --}}
