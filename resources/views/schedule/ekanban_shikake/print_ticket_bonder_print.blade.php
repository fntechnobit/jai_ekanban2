{{-- BONDER Process Print Template - PRINT VERSION (landscape, 80mm height) --}}
<style>
/* BONDER Print Template - Landscape layout for 80mm thermal printer */
/* Native 576px height = 80mm paper width at 203dpi (1:1 no scaling) */
/* Ticket width: 130mm = 1070px at 203dpi */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.bonder-print-wrapper {
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

.bonder-print-wrapper .shikake-image-section {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    background: white;
    height: 100%;
    margin: 0;
    padding: 0;
}

.bonder-print-wrapper .shikake-image-section img {
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

.ticket-bonder-print {
    width: 606px;
    min-width: 606px;
    min-height: 100%;
    flex-shrink: 0;
    background: white;
    margin: 0;
    padding: 0;
    border: 2px solid #000;
    font-family: Arial, sans-serif;
}

.ticket-bonder-print table {
    width: 100%;
    height: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.ticket-bonder-print th,
.ticket-bonder-print td {
    border: 1px solid #000;
    padding: 2px 4px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.2;
    font-size: 18px;
    box-sizing: border-box;
}

.ticket-bonder-print thead th {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 28px;
    padding: 4px;
    border: 2px solid #000;
}

.ticket-bonder-print .label-cell {
    background-color: transparent;
    font-weight: 500;
    font-size: 14px;
}

.ticket-bonder-print .value-cell {
    font-weight: bold;
    font-size: 18px;
}

.ticket-bonder-print .qrcode-cell {
    padding: 2px;
    vertical-align: middle;
}

.ticket-bonder-print .qrcode-cell img {
    max-width: 180px;
    max-height: 180px;
    display: block;
    margin: 0 auto;
}

.ticket-bonder-print .qr-img {
    width: 180px;
    height: 180px;
}

.ticket-bonder-print .qr-label {
    font-size: 12px;
    font-weight: bold;
    margin-bottom: 4px;
}

.ticket-bonder-print .barcode-cell {
    padding: 2px;
    vertical-align: middle;
}

.ticket-bonder-print .barcode-cell img {
    max-width: 180px;
    height: 60px;
    display: block;
    margin: 0 auto;
}

.ticket-bonder-print .barcode-navigasi-cell img {
    max-width: 180px;
    height: 60px;
    display: block;
    margin: 0 auto;
}

.ticket-bonder-print .qrcode-placeholder,
.ticket-bonder-print .barcode-placeholder {
    border: 1px solid #000;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #000;
}

.ticket-bonder-print .qrcode-placeholder {
    width: 180px;
    height: 180px;
}

.ticket-bonder-print .barcode-placeholder {
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
    
    .bonder-print-wrapper {
        flex-direction: row;
        gap: 0;
        margin: 0;
        justify-content: flex-start;
        page-break-after: always;
        page-break-inside: avoid;
    }
    
    .bonder-print-wrapper .shikake-image-section {
        display: flex;
        flex-shrink: 0;
        align-items: center;
    }
    
    .bonder-print-wrapper .shikake-image-section img {
        height: 70mm;
        width: auto;
        object-fit: contain;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .ticket-bonder-print {
        width: auto;
        max-width: none;
        height: auto;
        border: none;
        margin: 0;
        padding: 0;
    }
    
    .ticket-bonder-print table {
        page-break-inside: avoid;
        width: 100%;
    }
    
    .ticket-bonder-print th,
    .ticket-bonder-print td {
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
<div class="ticket bonder-print-wrapper" data-orientation="landscape">
    {{-- Image section (LEFT) - only if image exists --}}
    @if(!empty($shikake->image_path))
    <div class="shikake-image-section">
        <img src="{{ asset($shikake->image_path) }}" alt="Shikake Image">
    </div>
    @endif
    
    {{-- Ticket section (RIGHT) - landscape layout for 80mm thermal printer --}}
    <div class="ticket-bonder-print">
        <table>
            <colgroup>
                <col style="width: 14mm">
                <col style="width: 16mm">
                <col style="width: 14mm">
                <col style="width: 16mm">
                <col style="width: 20mm">
                <col style="width: 20mm">
                <col style="width: 20mm">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="7">EKANBAN BONDER - {{ $shikake->carline ?? '' }}</th>
                </tr>
            </thead>
            <tbody>
                <!-- Row 1: BONDER NO / ADDRESS Labels -->
                <tr>
                    <td colspan="4" class="label-cell">BONDER NO</td>
                    <td colspan="3" class="label-cell">ADDRESS</td>
                </tr>
                <!-- Row 2: BONDER NO / ADDRESS Values -->
                <tr>
                    <td colspan="4" class="value-cell">{{ $processData->bonder_no ?? '' }}</td>
                    <td colspan="3" class="value-cell">{{ $processData->address ?? '' }}</td>
                </tr>
                
                <!-- Row 3: CCT NO / BONDER NO Labels + BARCODE PROCESS -->
                <tr>
                    <td class="label-cell">CCT NO</td>
                    <td class="label-cell">BONDER NO</td>
                    <td class="label-cell">CCT NO</td>
                    <td class="label-cell">BONDER NO</td>
                    <td colspan="3" class="label-cell">BARCODE PROCESS</td>
                </tr>
                <!-- Row 4-10: 7 CCT/Bonder pairs (Side A left, Side B right) with Barcode (rowspan=3) -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_1 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_1 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_1 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_1 ?? '-' }}</td>
                    <td colspan="3" rowspan="3" class="barcode-cell">
                        @if(isset($processData->barcode_process_path))
                            <img src="{{ $processData->barcode_process_path }}" alt="Barcode">
                            <div style="font-size: 6px; text-align: center;">{{ $processData->barcode_process ?? '' }}</div>
                        @else
                            <div class="barcode-placeholder">BARCODE</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_2 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_2 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_2 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_2 ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_3 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_3 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_3 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_3 ?? '-' }}</td>
                </tr>
                <!-- Row 6: MACHINE / DIES Labels with pair 4 -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_4 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_4 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_4 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_4 ?? '-' }}</td>
                    <td colspan="2" class="label-cell">MACHINE</td>
                    <td class="label-cell">DIES</td>
                </tr>
                <!-- Row 7: MACHINE / DIES Values with pair 5 -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_5 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_5 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_5 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_5 ?? '-' }}</td>
                    <td colspan="2" class="value-cell">{{ $shikake->machine ?? '' }}</td>
                    <td class="value-cell">{{ $processData->dies ?? '' }}</td>
                </tr>

                <!-- Row 8: TO MACHINE / SEQ Labels with pair 6 -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_6 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_6 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_6 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_6 ?? '-' }}</td>
                    <td colspan="2" class="label-cell">TO MACHINE</td>
                    <td class="label-cell">SEQ</td>
                </tr>
                <!-- Row 9: TO MACHINE / SEQ Values with pair 7 -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_7 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_7 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_7 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_7 ?? '-' }}</td>
                    <td colspan="2" class="value-cell">{{ $processData->to_machine ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->sequence ?? '' }}</td>
                </tr>
                
                <!-- Bottom: QR DRAWING (qrcode_drawing) | BARCODE NAVIGASI | QTY/ISSUE | QRCODE KANBAN -->
                <tr>
                    <td colspan="2" rowspan="4" class="qrcode-cell">
                        <div class="qr-label">QRCODE KANBAN</div>
                        @if(isset($processData->qrcode_drawing_path))
                            <img src="{{ $processData->qrcode_drawing_path }}" alt="QR Drawing" style="width:130px;height:130px;display:block;margin:0 auto;">
                        @else
                            <div class="qrcode-placeholder" style="width:120px;height:120px;">QR DRAWING</div>
                        @endif
                        @if(!empty($processData->qrcode_drawing))
                            <div style="font-size: 9px; margin-top: 1px; font-weight: bold;">{{ $processData->qrcode_drawing }}</div>
                        @endif
                    </td>
                    <td colspan="2" rowspan="2" class="barcode-navigasi-cell">
                        <div class="qr-label">BARCODE NAVIGASI</div>
                        @if(isset($processData->barcode_navigasi_path))
                            <img src="{{ $processData->barcode_navigasi_path }}" alt="Barcode Navigasi" style="max-width:140px;height:45px;">
                        @else
                            <div class="barcode-placeholder">BARCODE</div>
                        @endif
                    </td>
                    <td class="label-cell">QTY</td>
                    <td class="label-cell">ISSUE</td>
                    <td rowspan="4" class="qrcode-cell">
                        <div class="qr-label">QRCODE KANBAN</div>
                        @if(isset($shikake->qr_code_path))
                            <img src="{{ $shikake->qr_code_path }}" alt="QR Code" style="width:95px;height:95px;display:block;margin:0 auto;">
                        @else
                            <div class="qrcode-placeholder" style="width:90px;height:90px;">QR</div>
                        @endif
                        @if(!empty($shikake->barcode_kanban))
                            <div style="font-size: 8px; margin-top: 1px; font-weight: bold;">{{ $shikake->barcode_kanban }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $shikake->qty ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->issue ?? '' }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="value-cell">{{ $shikake->conveyor ? 'CV ' . $shikake->conveyor : '' }}</td>
                    <td colspan="2" class="value-cell">{{ $shikake->family ?? '' }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="value-cell">{{ $shikake->release_date ? \Carbon\Carbon::parse($shikake->release_date)->format('d M y') : '' }}</td>
                    <td colspan="2" class="value-cell">{{ $shikake->released_note ?? '' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
{{-- End of bonder-print-wrapper --}}