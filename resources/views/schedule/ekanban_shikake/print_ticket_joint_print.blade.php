{{-- JOINT Process Print Template - PRINT VERSION (104mm x 70mm) --}}
<style>
/* JOINT Print Template - Compact 104mm x 70mm for thermal printer */
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
    margin: 10px auto;
    justify-content: center;
    align-items: flex-start;
}

.joint-print-wrapper .shikake-image-section {
    flex-shrink: 0;
}

.joint-print-wrapper .shikake-image-section img {
    height: 264px;
    width: auto;
    object-fit: contain;
    border: 1px solid #ccc;
}

.ticket-joint-print {
    width: 104mm;
    max-width: 104mm;
    height: 70mm;
    background: white;
    margin: 0;
    padding: 0;
    border: 2px solid #ddd;
    overflow: hidden;
    page-break-after: always;
    font-family: Arial, sans-serif;
}

.ticket-joint-print table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.ticket-joint-print th,
.ticket-joint-print td {
    border: 1px solid #000;
    padding: 1px 2px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.1;
    font-size: 10px;
    height: 4.2mm;
    box-sizing: border-box;
}

.ticket-joint-print thead th {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 14px;
    padding: 4px;
    border: 1px solid #000;
    height: 5mm;
}

.ticket-joint-print .label-cell {
    background-color: transparent;
    font-weight: 500;
    font-size: 8px;
}

.ticket-joint-print .value-cell {
    font-weight: bold;
    font-size: 10px;
}

.ticket-joint-print .qrcode-cell {
    padding: 2px;
    vertical-align: middle;
}

.ticket-joint-print .qrcode-cell img {
    max-width: 45px;
    max-height: 45px;
    display: block;
    margin: 0 auto;
}

.ticket-joint-print .barcode-cell {
    padding: 2px;
    vertical-align: middle;
}

.ticket-joint-print .barcode-cell img {
    max-width: 70px;
    max-height: 28px;
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
    font-size: 8px;
    color: #000;
}

.ticket-joint-print .qrcode-placeholder {
    width: 45px;
    height: 45px;
}

.ticket-joint-print .barcode-placeholder {
    width: 70px;
    height: 28px;
}

/* Thermal Printer Optimization */
@media print {
    body {
        margin: 0;
        padding: 0;
        background: white;
    }
    
    .joint-print-wrapper .shikake-image-section {
        display: none;
    }
    
    .ticket-joint-print {
        width: 104mm;
        height: 70mm;
        border: none;
        margin: 0;
        padding: 0;
        page-break-after: always;
    }
    
    .ticket-joint-print table {
        page-break-inside: avoid;
        width: 100%;
        height: 100%;
    }
    
    .ticket-joint-print th,
    .ticket-joint-print td {
        border: 1px solid #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    @page {
        size: 104mm 70mm;
        margin: 0;
    }
}
</style>

{{-- Wrapper with flexbox for image + ticket --}}
<div class="joint-print-wrapper">
    {{-- Image section (LEFT) - only if image exists --}}
    @if(!empty($shikake->image_path))
    <div class="shikake-image-section">
        <img src="{{ asset($shikake->image_path) }}" alt="Shikake Image">
    </div>
    @endif
    
    {{-- Ticket section (RIGHT) - 104mm x 70mm compact layout --}}
    <div class="ticket-joint-print">
        <table>
            <colgroup>
                <col style="width: 26mm">
                <col style="width: 26mm">
                <col style="width: 26mm">
                <col style="width: 26mm">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="4">EKANBAN JOINT</th>
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
                    <td class="value-cell">{{ $processData->cct_no_1 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_1 ?? '' }}</td>
                    <td colspan="2" rowspan="5" class="barcode-cell">
                        @if(isset($processData->barcode_process_path))
                            <img src="{{ $processData->barcode_process_path }}" alt="Barcode">
                            <div style="font-size: 7px; text-align: center;">{{ $processData->barcode_process ?? '' }}</div>
                        @else
                            <div class="barcode-placeholder">BARCODE</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_2 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_2 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_3 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_3 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_4 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_4 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_5 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_5 ?? '' }}</td>
                </tr>
                
                <!-- Row 9-10: Machine Label/Value -->
                <tr>
                    <td class="label-cell"></td>
                    <td class="label-cell"></td>
                    <td colspan="2" class="label-cell">MACHINE</td>
                </tr>
                <tr>
                    <td class="value-cell"></td>
                    <td class="value-cell"></td>
                    <td colspan="2" class="value-cell">{{ $shikake->machine ?? '' }}</td>
                </tr>
                
                <!-- Row 11-15: QR Code section (rowspan=5) with SEQ/TO MACHINE/QTY/ISSUE/FAMILY -->
                <tr>
                    <td colspan="2" rowspan="5" class="qrcode-cell">
                        <div class="label-cell" style="font-size: 7px; margin-bottom: 2px;">QRCODE KANBAN</div>
                        @if(isset($shikake->qr_code_path))
                            <img src="{{ $shikake->qr_code_path }}" alt="QR Code">
                        @else
                            <div class="qrcode-placeholder">QR</div>
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
                    <td class="value-cell">{{ $shikake->released_date ? \Carbon\Carbon::parse($shikake->released_date)->format('d M y') : '' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
{{-- End of joint-print-wrapper --}}