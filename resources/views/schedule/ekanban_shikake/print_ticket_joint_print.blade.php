{{-- JOINT Process Print Template - PRINT VERSION (landscape, 80mm height) --}}
<style>
/* JOINT Print Template - Landscape layout for 80mm thermal printer */
/* Native 576px height = 80mm paper width at 203dpi (1:1 no scaling) */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.joint-print-wrapper {
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
    height: 576px;
}

.joint-print-wrapper .shikake-image-section {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    background: white;
    height: 100%;
    margin: 0;
    padding: 0;
}

.joint-print-wrapper .shikake-image-section img {
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

.ticket-joint-print {
    width: 567px;
    min-width: 567px;
    height: 100%;
    flex-shrink: 0;
    background: white;
    margin: 0;
    padding: 0;
    border: 2px solid #000;
    overflow: hidden;
    font-family: Arial, sans-serif;
}

.ticket-joint-print table {
    width: 100%;
    height: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.ticket-joint-print th,
.ticket-joint-print td {
    border: 1px solid #000;
    padding: 2px 4px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.2;
    font-size: 18px;
    box-sizing: border-box;
}

.ticket-joint-print thead th {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 28px;
    padding: 4px;
    border: 2px solid #000;
}

.ticket-joint-print .label-cell {
    background-color: transparent;
    font-weight: 500;
    font-size: 14px;
}

.ticket-joint-print .value-cell {
    font-weight: bold;
    font-size: 18px;
}

.ticket-joint-print .qrcode-cell {
    padding: 2px;
    vertical-align: middle;
}

.ticket-joint-print .qrcode-cell img {
    max-width: 180px;
    max-height: 180px;
    display: block;
    margin: 0 auto;
}

.ticket-joint-print .qr-img {
    width: 180px;
    height: 180px;
}

.ticket-joint-print .qr-label {
    font-size: 12px;
    font-weight: bold;
    margin-bottom: 4px;
}

.ticket-joint-print .barcode-cell {
    padding: 2px;
    vertical-align: middle;
}

.ticket-joint-print .barcode-cell img {
    max-width: 180px;
    height: 60px;
    display: block;
    margin: 0 auto;
}

.ticket-joint-print .qrcode-placeholder,
.ticket-joint-print .barcode-placeholder {
    border: 1px solid #000;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #000;
}

.ticket-joint-print .qrcode-placeholder {
    width: 180px;
    height: 180px;
}

.ticket-joint-print .barcode-placeholder {
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
    
    .joint-print-wrapper {
        flex-direction: row;
        gap: 0;
        margin: 0;
        justify-content: flex-start;
        page-break-after: always;
        page-break-inside: avoid;
    }
    
    .joint-print-wrapper .shikake-image-section {
        display: flex;
        flex-shrink: 0;
        align-items: center;
    }
    
    .joint-print-wrapper .shikake-image-section img {
        height: 70mm;
        width: auto;
        object-fit: contain;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .ticket-joint-print {
        width: auto;
        max-width: none;
        height: auto;
        border: none;
        margin: 0;
        padding: 0;
    }
    
    .ticket-joint-print table {
        page-break-inside: avoid;
        width: 100%;
    }
    
    .ticket-joint-print th,
    .ticket-joint-print td {
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
<div class="ticket joint-print-wrapper" data-orientation="landscape">
    {{-- Image section (LEFT) - only if image exists --}}
    @if(!empty($shikake->image_path))
    <div class="shikake-image-section">
        <img src="{{ asset($shikake->image_path) }}" alt="Shikake Image">
    </div>
    @endif
    
    {{-- Ticket section (RIGHT) - landscape layout for 80mm thermal printer --}}
    <div class="ticket-joint-print">
        <table>
            <colgroup>
                <col style="width: 19%">
                <col style="width: 38%">
                <col style="width: 19%">
                <col style="width: 24%">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="4">EKANBAN JOINT - {{ $shikake->carline ?? '' }}</th>
                </tr>
            </thead>
            <tbody>
                <!-- Row 1: BONDER / ADDRESS Labels -->
                <tr>
                    <td colspan="2" class="label-cell">BONDER</td>
                    <td colspan="2" class="label-cell">ADDRESS</td>
                </tr>
                <!-- Row 2: BONDER / ADDRESS Values -->
                <tr>
                    <td colspan="2" class="value-cell">{{ $processData->bonder_no ?? '' }}</td>
                    <td colspan="2" class="value-cell">{{ $processData->address ?? '' }}</td>
                </tr>
                
                <!-- Row 3: CCT NO / BONDER NO / BARCODE MESIN Labels -->
                <tr>
                    <td class="label-cell">CCT NO</td>
                    <td class="label-cell">BONDER NO</td>
                    <td colspan="2" class="label-cell">BARCODE MESIN</td>
                </tr>
                <!-- Row 4-8: CCT NO and Bonder values with Barcode (5 pairs, rowspan=5) -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_1 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_1 ?? '-' }}</td>
                    <td colspan="2" rowspan="3" class="barcode-cell">
                        @if(isset($processData->barcode_process_path))
                            <img src="{{ $processData->barcode_process_path }}" alt="Barcode">
                            <div style="font-size: 10px; text-align: center;">{{ $processData->barcode_process ?? '' }}</div>
                        @else
                            <div class="barcode-placeholder">BARCODE</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_2 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_2 ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_3 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_3 ?? '-' }}</td>
                </tr>
                
                <!-- Row 9-10: Machine Label/Value -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_4 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_4 ?? '-' }}</td>
                    <td colspan="2" class="label-cell">MACHINE</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_5 ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_5 ?? '-' }}</td>
                    <td colspan="2" class="value-cell">{{ $shikake->machine ?? '' }}</td>
                </tr>
                
                <!-- Row 11-15: QR Code section (rowspan=5) with SEQ/TO MACHINE/QTY/ISSUE/FAMILY -->
                <tr>
                    <td colspan="2" rowspan="5" class="qrcode-cell">
                        <div class="qr-label">QRCODE KANBAN</div>
                        @if(isset($shikake->qr_code_path))
                            <img src="{{ $shikake->qr_code_path }}" alt="QR Code" class="qr-img">
                        @else
                            <div class="qrcode-placeholder">QR</div>
                        @endif
                        @if(!empty($shikake->barcode_kanban))
                            <div style="font-size: 10px; margin-top: 1px; font-weight: bold;">{{ $shikake->barcode_kanban }}</div>
                        @endif
                    </td>
                    <td class="label-cell">SEQ</td>
                    <td class="label-cell">TO MACHINE</td>
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
                
                <!-- Row 16: Footer - SENT TO STORE / CV / DATE -->
                <tr>
                    <td colspan="2" class="value-cell">{{ $processData->address_store ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->conveyor ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->release_date ? \Carbon\Carbon::parse($shikake->release_date)->format('d M y') : '' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
{{-- End of joint-print-wrapper --}}