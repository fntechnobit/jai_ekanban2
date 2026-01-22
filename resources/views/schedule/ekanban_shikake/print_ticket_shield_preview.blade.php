{{-- SHIELD Process Print Template - PREVIEW VERSION (576px for screen) --}}
<style>
/* SHIELD Preview Template - Wrapper for Image + Ticket */
.shield-preview-wrapper {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 0;
    margin: 15px auto;
    justify-content: center;
    align-items: flex-start;
}

.shield-preview-wrapper .shikake-image-section {
    flex-shrink: 0;
}

.shield-preview-wrapper .shikake-image-section img {
    height: 320px;
    width: auto;
    object-fit: contain;
    border: 1px solid #ccc;
    border-radius: 4px;
}

/* SHIELD Preview Template - 576px for screen readability */
.ticket-shield-preview {
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

.ticket-shield-preview table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.ticket-shield-preview th,
.ticket-shield-preview td {
    border: 1px solid #000;
    padding: 4px 6px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.2;
    font-size: 12px;
    box-sizing: border-box;
}

.ticket-shield-preview thead th {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 18px;
    padding: 8px;
    border: 2px solid #000;
}

.ticket-shield-preview .label-cell {
    background-color: #f8f9fa;
    font-weight: 500;
    font-size: 10px;
}

.ticket-shield-preview .value-cell {
    font-weight: bold;
    font-size: 12px;
}

.ticket-shield-preview .qrcode-cell {
    padding: 8px;
    vertical-align: middle;
}

.ticket-shield-preview .qrcode-cell img {
    max-width: 70px;
    max-height: 70px;
    display: block;
    margin: 0 auto;
}

.ticket-shield-preview .qrcode-placeholder {
    width: 70px;
    height: 70px;
    border: 1px solid #000;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #000;
}
</style>

{{-- Wrapper with flexbox for image + ticket --}}
<div class="shield-preview-wrapper">
    {{-- Image section (LEFT) - only if image exists --}}
    @if(!empty($shikake->image_path))
    <div class="shikake-image-section">
        <img src="{{ asset($shikake->image_path) }}" alt="Shikake Image">
    </div>
    @endif
    
    {{-- Ticket section (RIGHT) - 576px for screen readability --}}
    <div class="ticket-shield-preview">
        <table>
            <colgroup>
                <col style="width: 25%">
                <col style="width: 25%">
                <col style="width: 25%">
                <col style="width: 25%">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="4">EKANBAN SHIELD WIRE</th>
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
                
                <!-- Row 3: CCT NO 1 / ADDRESS 1 / QTY / ISSUE Labels -->
                <tr>
                    <td class="label-cell">CCT NO 1</td>
                    <td class="label-cell">ADDRESS 1</td>
                    <td class="label-cell">QTY</td>
                    <td class="label-cell">ISSUE</td>
                </tr>
                <!-- Row 4: CCT NO 1 / ADDRESS 1 / QTY / ISSUE Values -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_1 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->address_no_1_1 ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->qty ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->issue ?? '' }}</td>
                </tr>
                
                <!-- Row 5: CCT NO 2 / ADDRESS 2 + QRCODE KANBAN Label -->
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_2 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->address_no_1_2 ?? '' }}</td>
                    <td colspan="2" class="label-cell">QRCODE KANBAN</td>
                </tr>
                
                <!-- Row 6: MACHINE Label + QR Code (rowspan=4) -->
                <tr>
                    <td colspan="2" class="label-cell">MACHINE</td>
                    <td colspan="2" rowspan="4" class="qrcode-cell">
                        @if(isset($shikake->qr_code_path))
                            <img src="{{ $shikake->qr_code_path }}" alt="QR Code">
                        @else
                            <div class="qrcode-placeholder">QR CODE</div>
                        @endif
                    </td>
                </tr>
                <!-- Row 7: MACHINE Value -->
                <tr>
                    <td colspan="2" class="value-cell">{{ $shikake->machine ?? '' }}</td>
                </tr>
                <!-- Row 8: SEQ / BLADE Labels -->
                <tr>
                    <td class="label-cell">SEQ</td>
                    <td class="label-cell">BLADE</td>
                </tr>
                <!-- Row 9: SEQ / BLADE Values -->
                <tr>
                    <td class="value-cell">{{ $shikake->sequence ?? '' }}</td>
                    <td class="value-cell">{{ $processData->blade ?? '' }}</td>
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
</div>
{{-- End of shield-preview-wrapper --}}
