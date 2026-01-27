{{-- DBL CRIMP Process Print Template - PRINT VERSION --}}
{{-- Following BONDER pattern with unified table structure from dbl-crm.html reference --}}
{{-- Dimensions: 120mm x 70mm for thermal printer --}}
<style>
/* DBL CRIMP Print Template - Wrapper for Image + Ticket */
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
    justify-content: center;
    align-items: flex-start;
}

.dbl-crimp-print-wrapper .shikake-image-section {
    flex-shrink: 0;
}

.dbl-crimp-print-wrapper .shikake-image-section img {
    height: 264px;
    width: auto;
    object-fit: contain;
    border: 1px solid #ccc;
}

/* DBL CRIMP Print Template - 120mm x 70mm for thermal printer */
.ticket-dbl-crimp-print {
    width: 120mm;
    max-width: 120mm;
    height: 70mm;
    background: white;
    margin: 0;
    padding: 0;
    border: 1px solid #ddd;
    overflow: hidden;
    page-break-after: always;
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
    padding: 1px 2px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.1;
    font-size: 9px;
    height: 4.4mm;
    box-sizing: border-box;
}

.ticket-dbl-crimp-print thead th {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 14px;
    padding: 3px;
    border: 1px solid #000;
    height: 4.5mm;
}

.ticket-dbl-crimp-print .label-cell {
    background-color: transparent;
    font-weight: 500;
    font-size: 7px;
}

.ticket-dbl-crimp-print .value-cell {
    font-weight: bold;
    font-size: 9px;
}

.ticket-dbl-crimp-print .qrcode-cell {
    padding: 2px;
    vertical-align: middle;
}

.ticket-dbl-crimp-print .qrcode-cell img {
    max-width: 50px;
    max-height: 50px;
    display: block;
    margin: 0 auto;
}

.ticket-dbl-crimp-print .barcode-cell {
    padding: 2px;
    vertical-align: middle;
}

.ticket-dbl-crimp-print .barcode-cell img {
    max-width: 70px;
    max-height: 30px;
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
    font-size: 7px;
    color: #000;
}

.ticket-dbl-crimp-print .qrcode-placeholder {
    width: 50px;
    height: 50px;
}

.ticket-dbl-crimp-print .barcode-placeholder {
    width: 70px;
    height: 30px;
}

/* Thermal Printer Optimization */
@media print {
    body {
        margin: 0;
        padding: 0;
        background: white;
    }
    
    .dbl-crimp-print-wrapper .shikake-image-section {
        display: none;
    }
    
    .ticket-dbl-crimp-print {
        width: 120mm;
        height: 70mm;
        border: none;
        margin: 0;
        padding: 0;
        page-break-after: always;
    }
    
    .ticket-dbl-crimp-print table {
        page-break-inside: avoid;
        width: 100%;
        height: 100%;
    }
    
    .ticket-dbl-crimp-print th,
    .ticket-dbl-crimp-print td {
        border: 1px solid #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    @page {
        size: 120mm 70mm;
        margin: 0;
    }
}
</style>

{{-- Wrapper with flexbox for image + ticket --}}
<div class="dbl-crimp-print-wrapper">
    {{-- Image section (LEFT) - only if image exists, hidden on print --}}
    @if(!empty($shikake->image_path))
    <div class="shikake-image-section">
        <img src="{{ asset($shikake->image_path) }}" alt="Shikake Image">
    </div>
    @endif
    
    {{-- Ticket section (RIGHT) - 120mm x 70mm unified table structure --}}
    <div class="ticket-dbl-crimp-print">
        <table>
            <colgroup>
                <col style="width: 29.6mm">
                <col style="width: 29.6mm">
                <col style="width: 29.6mm">
                <col style="width: 29.6mm">
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
                        <div class="label-cell" style="font-size: 6px; margin-bottom: 1px;">BARCODE KANBAN</div>
                        @if(isset($shikake->qr_code_path))
                            <img src="{{ $shikake->qr_code_path }}" alt="QR Code">
                            <div style="font-size: 6px; margin-top: 1px; font-weight: bold;">{{ $shikake->barcode_kanban ?? '' }}</div>
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
