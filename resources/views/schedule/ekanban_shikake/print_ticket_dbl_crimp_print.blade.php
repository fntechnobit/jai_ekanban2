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
    margin: 10px auto;
    justify-content: flex-start;
    align-items: stretch;
    background: white;
    page-break-after: always;
    height: 576px;
}

.dbl-crimp-print-wrapper .shikake-image-section {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    height: 100%;
}

.dbl-crimp-print-wrapper .shikake-image-section img {
    height: 100%;
    width: auto;
    object-fit: contain;
}

.ticket-dbl-crimp-print {
    width: 987px;
    min-width: 987px;
    height: 100%;
    flex-shrink: 0;
    background: white;
    margin: 0;
    padding: 0;
    border: 2px solid #ddd;
    overflow: hidden;
    font-family: Arial, sans-serif;
}

.ticket-dbl-crimp-print table {
    width: 100%;
    height: 100%;
    border-collapse: collapse;
    table-layout: fixed;
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
}

.ticket-dbl-crimp-print .value-cell {
    font-weight: bold;
    font-size: 18px;
}

.ticket-dbl-crimp-print .qrcode-cell {
    padding: 2px;
    vertical-align: middle;
}

.ticket-dbl-crimp-print .qrcode-cell img {
    max-width: 180px;
    max-height: 180px;
    display: block;
    margin: 0 auto;
}

.ticket-dbl-crimp-print .qr-img {
    width: 180px;
    height: 180px;
}

.ticket-dbl-crimp-print .qr-label {
    font-size: 12px;
    font-weight: bold;
    margin-bottom: 4px;
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
    width: 180px;
    height: 180px;
}

.ticket-dbl-crimp-print .barcode-placeholder {
    width: 180px;
    height: 60px;
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
        width: 100%;
    }
    
    .ticket-dbl-crimp-print th,
    .ticket-dbl-crimp-print td {
        border: 1px solid #000 !important;
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
                <col style="width: 25%">
                <col style="width: 25%">
                <col style="width: 25%">
                <col style="width: 25%">
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
                
                <!-- Row 3: CCT NO / ADDRESS / BARCODE MESIN Labels -->
                <tr>
                    <td class="label-cell">CCT NO</td>
                    <td class="label-cell">ADDRESS</td>
                    <td colspan="2" class="label-cell">BARCODE MESIN</td>
                </tr>
                <!-- Row 4-6: CCT 1-3 with BARCODE MESIN (rowspan=3) -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_1 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->address_1 ?? '' }}</td>
                    <td colspan="2" rowspan="3" class="barcode-cell">
                        @if(isset($processData->barcode_mesin_path))
                            <img src="{{ $processData->barcode_mesin_path }}" alt="Barcode Mesin">
                            <div style="font-size: 6px; margin-top: 1px;">{{ $processData->barcode_mesin }}</div>
                        @else
                            <div class="barcode-placeholder">-</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_2 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->address_2 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_3 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->address_3 ?? '' }}</td>
                </tr>
                
                <!-- Row 7: CCT 4 + MACHINE Label -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_4 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->address_4 ?? '' }}</td>
                    <td colspan="2" class="label-cell">MACHINE</td>
                </tr>
                <!-- Row 8: CCT 5 + MACHINE Value -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_5 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->address_5 ?? '' }}</td>
                    <td colspan="2" class="value-cell">{{ $shikake->machine ?? '' }}</td>
                </tr>
                
                <!-- Row 9-13: BARCODE KANBAN Section -->
                <tr>
                    <td colspan="2" rowspan="5" class="qrcode-cell">
                        <div class="qr-label">BARCODE KANBAN</div>
                        @if(isset($shikake->qr_code_path))
                            <img src="{{ $shikake->qr_code_path }}" alt="QR Code" class="qr-img">
                            <div style="font-size: 10px; margin-top: 1px; font-weight: bold;">{{ $shikake->barcode_kanban ?? '' }}</div>
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
</div>
{{-- End of dbl-crimp-print-wrapper --}}
