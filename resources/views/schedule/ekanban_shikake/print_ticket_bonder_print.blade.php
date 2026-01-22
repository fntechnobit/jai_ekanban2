{{-- BONDER Process Print Template - PRINT VERSION (130mm x 70mm) --}}
<style>
/* BONDER Print Template - Compact 130mm x 70mm for thermal printer */
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
    margin: 10px auto;
    justify-content: center;
    align-items: flex-start;
}

.bonder-print-wrapper .shikake-image-section {
    flex-shrink: 0;
}

.bonder-print-wrapper .shikake-image-section img {
    height: 264px;
    width: auto;
    object-fit: contain;
    border: 1px solid #ccc;
}

.ticket-bonder-print {
    width: 130mm;
    max-width: 130mm;
    height: 70mm;
    background: white;
    margin: 0;
    padding: 0;
    border: 1px solid #ddd;
    overflow: hidden;
    page-break-after: always;
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
    padding: 1px 2px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.1;
    font-size: 9px;
    height: 3.5mm;
    box-sizing: border-box;
}

.ticket-bonder-print thead th {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 14px;
    padding: 3px;
    border: 1px solid #000;
    height: 4.5mm;
}

.ticket-bonder-print .label-cell {
    background-color: transparent;
    font-weight: 500;
    font-size: 7px;
}

.ticket-bonder-print .value-cell {
    font-weight: bold;
    font-size: 9px;
}

.ticket-bonder-print .qrcode-cell {
    padding: 2px;
    vertical-align: middle;
}

.ticket-bonder-print .qrcode-cell img {
    max-width: 40px;
    max-height: 40px;
    display: block;
    margin: 0 auto;
}

.ticket-bonder-print .barcode-cell {
    padding: 2px;
    vertical-align: middle;
}

.ticket-bonder-print .barcode-cell img {
    max-width: 55px;
    max-height: 22px;
    display: block;
    margin: 0 auto;
}

.ticket-bonder-print .barcode-navigasi-cell img {
    max-width: 50px;
    max-height: 20px;
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
    font-size: 7px;
    color: #000;
}

.ticket-bonder-print .qrcode-placeholder {
    width: 40px;
    height: 40px;
}

.ticket-bonder-print .barcode-placeholder {
    width: 55px;
    height: 22px;
}

/* Thermal Printer Optimization */
@media print {
    body {
        margin: 0;
        padding: 0;
        background: white;
    }
    
    .bonder-print-wrapper .shikake-image-section {
        display: none;
    }
    
    .ticket-bonder-print {
        width: 130mm;
        height: 70mm;
        border: none;
        margin: 0;
        padding: 0;
        page-break-after: always;
    }
    
    .ticket-bonder-print table {
        page-break-inside: avoid;
        width: 100%;
        height: 100%;
    }
    
    .ticket-bonder-print th,
    .ticket-bonder-print td {
        border: 1px solid #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    @page {
        size: 130mm 70mm;
        margin: 0;
    }
}
</style>

{{-- Wrapper with flexbox for image + ticket --}}
<div class="bonder-print-wrapper">
    {{-- Image section (LEFT) - only if image exists --}}
    @if(!empty($shikake->image_path))
    <div class="shikake-image-section">
        <img src="{{ asset($shikake->image_path) }}" alt="Shikake Image">
    </div>
    @endif
    
    {{-- Ticket section (RIGHT) - 130mm x 70mm compact layout --}}
    <div class="ticket-bonder-print">
        <table>
            <colgroup>
                <col style="width: 16mm">
                <col style="width: 18mm">
                <col style="width: 16mm">
                <col style="width: 18mm">
                <col style="width: 17mm">
                <col style="width: 17mm">
                <col style="width: 18mm">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="7">EKANBAN BONDER</th>
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
                <!-- Row 4-10: 7 CCT/Bonder pairs (Side A left, Side B right) with Barcode (rowspan=7) -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_1 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_1 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_1 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_1 ?? '' }}</td>
                    <td colspan="3" rowspan="7" class="barcode-cell">
                        @if(isset($processData->barcode_process_path))
                            <img src="{{ $processData->barcode_process_path }}" alt="Barcode">
                            <div style="font-size: 6px; text-align: center;">{{ $processData->barcode_process ?? '' }}</div>
                        @else
                            <div class="barcode-placeholder">BARCODE</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_2 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_2 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_2 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_2 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_3 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_3 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_3 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_3 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_4 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_4 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_4 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_4 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_5 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_5 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_5 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_5 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_6 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_6 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_6 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_6 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_7 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_7 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_7 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_7 ?? '' }}</td>
                </tr>
                
                <!-- Row 11-12: MACHINE / DIES Labels & Values -->
                <tr>
                    <td colspan="2" class="label-cell">MACHINE</td>
                    <td class="label-cell">DIES</td>
                    <td colspan="4" class="label-cell"></td>
                </tr>
                <tr>
                    <td colspan="2" class="value-cell">{{ $shikake->machine ?? '' }}</td>
                    <td class="value-cell">{{ $processData->dies ?? '' }}</td>
                    <td colspan="4" class="value-cell"></td>
                </tr>
                
                <!-- Row 13-14: TO MACHINE / SEQ Labels & Values -->
                <tr>
                    <td colspan="2" class="label-cell">TO MACHINE</td>
                    <td class="label-cell">SEQ</td>
                    <td colspan="4" class="label-cell"></td>
                </tr>
                <tr>
                    <td colspan="2" class="value-cell">{{ $processData->to_machine ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->sequence ?? '' }}</td>
                    <td colspan="4" class="value-cell"></td>
                </tr>
                
                <!-- Row 15-17: BARCODE NAVIGASI + QTY/ISSUE + QRCODE KANBAN -->
                <tr>
                    <td colspan="3" rowspan="3" class="barcode-navigasi-cell">
                        <div class="label-cell" style="font-size: 6px; margin-bottom: 1px;">BARCODE NAVIGASI</div>
                        @if(isset($processData->barcode_navigasi_path))
                            <img src="{{ $processData->barcode_navigasi_path }}" alt="Barcode Navigasi">
                        @else
                            <div class="barcode-placeholder">BARCODE</div>
                        @endif
                    </td>
                    <td class="label-cell">QTY</td>
                    <td class="label-cell">ISSUE</td>
                    <td colspan="2" rowspan="4" class="qrcode-cell">
                        <div class="label-cell" style="font-size: 6px; margin-bottom: 1px;">QRCODE KANBAN</div>
                        @if(isset($shikake->qr_code_path))
                            <img src="{{ $shikake->qr_code_path }}" alt="QR Code">
                        @else
                            <div class="qrcode-placeholder">QR</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $shikake->qty ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->issue ?? '' }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="value-cell">{{ $shikake->family ?? '' }}</td>
                </tr>
                
                <!-- Row 18: Footer - CV / DATE / RELEASED NOTE -->
                <tr>
                    <td class="value-cell">{{ $shikake->conveyor ? 'CV ' . $shikake->conveyor : '' }}</td>
                    <td class="value-cell">{{ $shikake->release_date ? \Carbon\Carbon::parse($shikake->release_date)->format('d M y') : '' }}</td>
                    <td colspan="3" class="value-cell">{{ $shikake->released_note ?? '' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
{{-- End of bonder-print-wrapper --}}