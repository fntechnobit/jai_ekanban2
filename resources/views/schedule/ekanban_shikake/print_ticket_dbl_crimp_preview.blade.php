{{-- DBL CRIMP Process Print Template - PREVIEW VERSION --}}
{{-- Following BONDER pattern with unified table structure from dbl-crm.html reference --}}
<style>
/* DBL CRIMP Preview Template - Wrapper for Image + Ticket */
.dbl-crimp-preview-wrapper {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 0;
    margin: 15px auto;
    justify-content: center;
    align-items: flex-start;
}

.dbl-crimp-preview-wrapper .shikake-image-section {
    flex-shrink: 0;
}

.dbl-crimp-preview-wrapper .shikake-image-section img {
    height: 300px;
    width: auto;
    object-fit: contain;
    border: 1px solid #ccc;
    border-radius: 4px;
}

/* DBL CRIMP Preview Template - 576px for screen readability */
.ticket-dbl-crimp-preview {
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

.ticket-dbl-crimp-preview table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.ticket-dbl-crimp-preview th,
.ticket-dbl-crimp-preview td {
    border: 1px solid #000;
    padding: 4px 6px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.2;
    font-size: 12px;
    box-sizing: border-box;
}

.ticket-dbl-crimp-preview thead th {
    background-color: #fff3cd;
    color: #000;
    font-weight: bold;
    font-size: 18px;
    padding: 8px;
    border: 2px solid #000;
}

.ticket-dbl-crimp-preview .label-cell {
    background-color: #f8f9fa;
    font-weight: 500;
    font-size: 10px;
}

.ticket-dbl-crimp-preview .value-cell {
    font-weight: bold;
    font-size: 12px;
}

.ticket-dbl-crimp-preview .qrcode-cell {
    padding: 8px;
    vertical-align: middle;
}

.ticket-dbl-crimp-preview .qrcode-cell img {
    max-width: 100px;
    max-height: 100px;
    display: block;
    margin: 0 auto;
}

.ticket-dbl-crimp-preview .barcode-cell {
    padding: 8px;
    vertical-align: middle;
}

.ticket-dbl-crimp-preview .barcode-cell img {
    max-width: 120px;
    max-height: 50px;
    display: block;
    margin: 0 auto;
}

.ticket-dbl-crimp-preview .qrcode-placeholder,
.ticket-dbl-crimp-preview .barcode-placeholder {
    border: 1px solid #000;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #000;
}

.ticket-dbl-crimp-preview .qrcode-placeholder {
    width: 100px;
    height: 100px;
}

.ticket-dbl-crimp-preview .barcode-placeholder {
    width: 120px;
    height: 50px;
}
</style>

{{-- Wrapper with flexbox for image + ticket --}}
<div class="dbl-crimp-preview-wrapper">
    {{-- Image section (LEFT) - only if image exists --}}
    @if(!empty($shikake->image_path))
    <div class="shikake-image-section">
        <img src="{{ asset($shikake->image_path) }}" alt="Shikake Image">
    </div>
    @endif
    
    {{-- Ticket section (RIGHT) - 576px unified table structure --}}
    <div class="ticket-dbl-crimp-preview">
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
                            <div style="font-size: 10px; margin-top: 4px;">{{ $processData->barcode_mesin }}</div>
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
                
                <!-- Row 9-13: QRCODE KANBAN Section -->
                <tr>
                    <td colspan="2" rowspan="5" class="qrcode-cell">
                        <div class="label-cell" style="font-size: 10px; margin-bottom: 6px; background: transparent;">QRCODE KANBAN</div>
                        @if(isset($shikake->qr_code_path))
                            <img src="{{ $shikake->qr_code_path }}" alt="QR Code">
                            <div style="font-size: 10px; margin-top: 4px; font-weight: bold;">{{ $shikake->barcode_kanban ?? '' }}</div>
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
{{-- End of dbl-crimp-preview-wrapper --}}
