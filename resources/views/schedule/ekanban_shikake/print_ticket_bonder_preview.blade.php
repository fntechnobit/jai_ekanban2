{{-- BONDER Process Print Template - PREVIEW VERSION (576px for screen) --}}
<style>
/* BONDER Preview Template - Wrapper for Image + Ticket */
.bonder-preview-wrapper {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 0;
    margin: 15px auto;
    justify-content: center;
    align-items: flex-start;
}

.bonder-preview-wrapper .shikake-image-section {
    flex-shrink: 0;
}

.bonder-preview-wrapper .shikake-image-section img {
    height: 320px;
    width: auto;
    object-fit: contain;
    border: 1px solid #ccc;
    border-radius: 4px;
}

/* BONDER Preview Template - 576px for screen readability */
.ticket-bonder-preview {
    width: 576px;
    min-width: 576px;
    flex-shrink: 0;
    background: white;
    margin: 0;
    padding: 0;
    border: 2px solid #ddd;
    overflow: visible;
    font-family: Arial, sans-serif;
}

.ticket-bonder-preview table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.ticket-bonder-preview th,
.ticket-bonder-preview td {
    border: 1px solid #000;
    padding: 4px 6px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.2;
    font-size: 12px;
    box-sizing: border-box;
}

.ticket-bonder-preview thead th {
    background-color: #fff3cd;
    color: #000;
    font-weight: bold;
    font-size: 18px;
    padding: 8px;
    border: 2px solid #000;
}

.ticket-bonder-preview .label-cell {
    background-color: #f8f9fa;
    font-weight: 500;
    font-size: 10px;
}

.ticket-bonder-preview .value-cell {
    font-weight: bold;
    font-size: 12px;
}

.ticket-bonder-preview .qrcode-cell {
    padding: 8px;
    vertical-align: middle;
}

.ticket-bonder-preview .qrcode-cell img {
    max-width: 80px;
    max-height: 80px;
    display: block;
    margin: 0 auto;
}

.ticket-bonder-preview .barcode-cell {
    padding: 8px;
    vertical-align: middle;
}

.ticket-bonder-preview .barcode-cell img {
    max-width: 110px;
    max-height: 45px;
    display: block;
    margin: 0 auto;
}

.ticket-bonder-preview .barcode-navigasi-cell {
    padding: 6px;
    vertical-align: middle;
}

.ticket-bonder-preview .barcode-navigasi-cell img {
    max-width: 100px;
    max-height: 40px;
    display: block;
    margin: 0 auto;
}

.ticket-bonder-preview .qrcode-placeholder,
.ticket-bonder-preview .barcode-placeholder {
    border: 1px solid #000;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #000;
}

.ticket-bonder-preview .qrcode-placeholder {
    width: 80px;
    height: 80px;
}

.ticket-bonder-preview .barcode-placeholder {
    width: 110px;
    height: 45px;
}
</style>

{{-- Wrapper with flexbox for image + ticket --}}
<div class="bonder-preview-wrapper">
    {{-- Image section (LEFT) - only if image exists --}}
    @if(!empty($shikake->image_path))
    <div class="shikake-image-section">
        <img src="{{ asset($shikake->image_path) }}" alt="Shikake Image">
    </div>
    @endif
    
    {{-- Ticket section (RIGHT) - 576px for screen readability --}}
    <div class="ticket-bonder-preview">
        <table>
            <colgroup>
                <col style="width: 12%">
                <col style="width: 14%">
                <col style="width: 12%">
                <col style="width: 14%">
                <col style="width: 16%">
                <col style="width: 16%">
                <col style="width: 16%">
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
                <!-- Row 4-10: 7 CCT/Bonder pairs (Side A left, Side B right) with Barcode (rowspan=7) -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_1 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_1 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_1 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_1 ?? '' }}</td>
                    <td colspan="3" rowspan="3" class="barcode-cell">
                        @if(isset($processData->barcode_process_path))
                            <img src="{{ $processData->barcode_process_path }}" alt="Barcode">
                            <div style="font-size: 9px; text-align: center; margin-top: 4px;">{{ $processData->barcode_process ?? '' }}</div>
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
                <!-- Row 6: MACHINE / DIES Labels with pair 6 -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_4 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_4 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_4 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_4 ?? '' }}</td>
                    <td colspan="2" class="label-cell">MACHINE</td>
                    <td class="label-cell">DIES</td>
                </tr>
                <!-- Row 7: MACHINE / DIES Values with pair 6 -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_5 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_5 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_5 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_5 ?? '' }}</td>
                    <td colspan="2" class="value-cell">{{ $shikake->machine ?? '' }}</td>
                    <td class="value-cell">{{ $processData->dies ?? '' }}</td>
                </tr>
                
                <!-- Row 8: TO MACHINE / SEQ Labels with pair 7 -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_6 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_6 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_6 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_6 ?? '' }}</td>
                    <td colspan="2" class="label-cell">TO MACHINE</td>
                    <td class="label-cell">SEQ</td>
                </tr>
                <!-- Row 9: TO MACHINE / SEQ Values with pair 7 -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_a_7 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_a_7 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->cct_no_b_7 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->bonder_no_b_7 ?? '' }}</td>
                    <td colspan="2" class="value-cell">{{ $processData->to_machine ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->sequence ?? '' }}</td>
                </tr>
                
                <!-- Bottom: QR DRAWING (qrcode_drawing) | BARCODE NAVIGASI | QTY/ISSUE | QRCODE KANBAN -->
                <tr>
                    <td colspan="2" rowspan="4" class="qrcode-cell">
                        <div class="label-cell" style="font-size: 9px; margin-bottom: 4px; background: transparent;">QRCODE KANBAN</div>
                        @if(isset($processData->qrcode_drawing_path))
                            <img src="{{ $processData->qrcode_drawing_path }}" alt="QR Drawing">
                        @else
                            <div class="qrcode-placeholder">QR DRAWING</div>
                        @endif
                        @if(!empty($processData->qrcode_drawing))
                            <div style="font-size: 9px; margin-top: 4px; font-weight: bold;">{{ $processData->qrcode_drawing }}</div>
                        @endif
                    </td>
                    <td colspan="2" rowspan="2" class="barcode-navigasi-cell">
                        <div class="label-cell" style="font-size: 9px; margin-bottom: 4px; background: transparent;">BARCODE NAVIGASI</div>
                        @if(isset($processData->barcode_navigasi_path))
                            <img src="{{ $processData->barcode_navigasi_path }}" alt="Barcode Navigasi">
                        @else
                            <div class="barcode-placeholder">BARCODE</div>
                        @endif
                    </td>
                    <td class="label-cell">QTY</td>
                    <td class="label-cell">ISSUE</td>
                    <td rowspan="4" class="qrcode-cell">
                        <div class="label-cell" style="font-size: 9px; margin-bottom: 4px; background: transparent;">QRCODE KANBAN</div>
                        @if(isset($shikake->qr_code_path))
                            <img src="{{ $shikake->qr_code_path }}" alt="QR Code">
                        @else
                            <div class="qrcode-placeholder">QR</div>
                        @endif
                        @if(!empty($shikake->barcode_kanban))
                            <div style="font-size: 9px; margin-top: 4px; font-weight: bold;">{{ $shikake->barcode_kanban }}</div>
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
{{-- End of bonder-preview-wrapper --}}
