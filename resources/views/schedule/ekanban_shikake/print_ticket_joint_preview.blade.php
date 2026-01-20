{{-- JOINT Process Print Template - PREVIEW VERSION (576px for screen) --}}
<style>
/* JOINT Preview Template - Wrapper for Image + Ticket */
.joint-preview-wrapper {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 0;
    margin: 15px auto;
    justify-content: center;
    align-items: flex-start;
}

.joint-preview-wrapper .shikake-image-section {
    flex-shrink: 0;
}

.joint-preview-wrapper .shikake-image-section img {
    height: 280px;
    width: auto;
    object-fit: contain;
    border: 1px solid #ccc;
    border-radius: 4px;
}

/* JOINT Preview Template - 576px for screen readability */
.ticket-joint-preview {
    width: 576px;
    min-width: 576px;
    flex-shrink: 0;
    background: white;
    margin: 0;
    padding: 0;
    border: 2px solid #ddd;
    overflow: hidden;
    font-family: Arial, sans-serif;
}

.ticket-joint-preview table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.ticket-joint-preview th,
.ticket-joint-preview td {
    border: 1px solid #000;
    padding: 4px 6px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.2;
    font-size: 14px;
    box-sizing: border-box;
}

.ticket-joint-preview thead th {
    background-color: #d1ecf1;
    color: #000;
    font-weight: bold;
    font-size: 18px;
    padding: 8px;
    border: 2px solid #000;
}

.ticket-joint-preview .label-cell {
    background-color: #f8f9fa;
    font-weight: 500;
    font-size: 11px;
}

.ticket-joint-preview .value-cell {
    font-weight: bold;
    font-size: 14px;
}

.ticket-joint-preview .qrcode-cell {
    padding: 8px;
    vertical-align: middle;
}

.ticket-joint-preview .qrcode-cell img {
    max-width: 90px;
    max-height: 90px;
    display: block;
    margin: 0 auto;
}

.ticket-joint-preview .barcode-cell {
    padding: 8px;
    vertical-align: middle;
}

.ticket-joint-preview .barcode-cell img {
    max-width: 130px;
    max-height: 50px;
    display: block;
    margin: 0 auto;
}

.ticket-joint-preview .qrcode-placeholder,
.ticket-joint-preview .barcode-placeholder {
    border: 1px solid #000;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #000;
}

.ticket-joint-preview .qrcode-placeholder {
    width: 90px;
    height: 90px;
}

.ticket-joint-preview .barcode-placeholder {
    width: 130px;
    height: 50px;
}
</style>

{{-- Wrapper with flexbox for image + ticket --}}
<div class="joint-preview-wrapper">
    {{-- Image section (LEFT) - only if image exists --}}
    @if(!empty($shikake->image_path))
    <div class="shikake-image-section">
        <img src="{{ asset($shikake->image_path) }}" alt="Shikake Image">
    </div>
    @endif
    
    {{-- Ticket section (RIGHT) - 576px for screen readability --}}
    <div class="ticket-joint-preview">
        <table>
            <colgroup>
                <col style="width: 25%">
                <col style="width: 25%">
                <col style="width: 25%">
                <col style="width: 25%">
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
                            <div style="font-size: 10px; text-align: center; margin-top: 4px;">{{ $processData->barcode_process ?? '' }}</div>
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
                        <div class="label-cell" style="font-size: 10px; margin-bottom: 4px; background: transparent;">QRCODE KANBAN</div>
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
{{-- End of joint-preview-wrapper --}}
