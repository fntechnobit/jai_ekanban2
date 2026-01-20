{{-- SHIELD Process Print Template - PRINT VERSION (120mm×70mm for thermal printer) --}}
<style>
/* SHIELD Print Template - Container for thermal printing */
.ticket-shield-print {
    width: 120mm;
    max-width: 120mm;
    height: 70mm;
    background: white;
    margin: 0;
    padding: 0;
    border: 1px solid #ddd;
    overflow: hidden;
    font-family: Arial, sans-serif;
    page-break-after: always;
}

.ticket-shield-print table {
    width: 100%;
    height: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.ticket-shield-print th,
.ticket-shield-print td {
    border: 1px solid #000;
    padding: 1px 2px;
    text-align: center;
    vertical-align: middle;
    line-height: 1;
    font-size: 8px;
    height: 3.9mm;
    box-sizing: border-box;
}

.ticket-shield-print thead th {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 10px;
    padding: 2px;
    border: 1px solid #000;
    height: 4mm;
}

.ticket-shield-print .label-cell {
    background-color: transparent;
    font-weight: 500;
    font-size: 6px;
}

.ticket-shield-print .value-cell {
    font-weight: bold;
    font-size: 8px;
}

.ticket-shield-print .qrcode-cell {
    padding: 2px;
    vertical-align: middle;
}

.ticket-shield-print .qrcode-cell img {
    max-width: 50px;
    max-height: 50px;
    display: block;
    margin: 0 auto;
}

.ticket-shield-print .qrcode-placeholder {
    width: 50px;
    height: 50px;
    border: 1px solid #000;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
    color: #000;
}

/* Thermal Printer Optimization */
@media print {
    .ticket-shield-print {
        width: 120mm;
        height: 70mm;
        border: none;
        margin: 0;
        padding: 0;
        page-break-after: always;
    }
    
    .ticket-shield-print table {
        page-break-inside: avoid;
    }
    
    .ticket-shield-print th,
    .ticket-shield-print td {
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

{{-- Ticket container - 120mm×70mm for thermal printer --}}
<div class="ticket-shield-print">
    <table>
        <colgroup>
            <col style="width: 30mm">
            <col style="width: 30mm">
            <col style="width: 30mm">
            <col style="width: 30mm">
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
                <td class="value-cell">{{ $shikake->released_date ? \Carbon\Carbon::parse($shikake->released_date)->format('d-M-y') : '' }}</td>
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
{{-- End of ticket-shield-print --}}
