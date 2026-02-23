{{-- CIRCUIT CUTTING_TWIST Print Template --}}
{{-- Based on twist.html reference, scaled up for thermal printing (1px = 1 dot at 203dpi) --}}
{{-- 9 columns, landscape, height 576px = 80mm --}}
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.twist-print-wrapper {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 0;
    margin: 0;
    justify-content: flex-start;
    align-items: stretch;
    background: white;
    page-break-after: always;
    height: 576px;
}

.twist-print-wrapper .twist-image-section {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    background: white;
    height: 100%;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.twist-print-wrapper .twist-image-section img {
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

.ticket-twist-print {
    width: 870px;
    min-width: 870px;
    height: 100%;
    flex-shrink: 0;
    background: white;
    margin: 0;
    padding: 0;
    border: 2px solid #000;
    overflow: hidden;
    font-family: Arial, sans-serif;
}

.ticket-twist-print table {
    width: 100%;
    height: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.ticket-twist-print th,
.ticket-twist-print td {
    border: 1px solid #000;
    padding: 2px 4px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.1;
    font-size: 22px;
    box-sizing: border-box;
}

.ticket-twist-print thead th {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 32px;
    padding: 4px;
    border: 2px solid #000;
}

.twist-section-label {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 28px;
    width: 38px;
    padding: 2px;
}

.twist-section-label.black-bg {
    background-color: #000;
    color: white;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.twist-label-cell {
    background-color: transparent;
    font-weight: 500;
    font-size: 18px;
}

.twist-value-cell {
    font-weight: bold;
    font-size: 22px;
}

.text-left {
    text-align: left !important;
}

.twist-qrcode-cell {
    padding: 4px;
    vertical-align: middle;
    text-align: center;
}

.twist-qr-label {
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 2px;
}

.twist-qr-img {
    width: 110px;
    height: 110px;
    display: block;
    margin: 0 auto;
}

.twist-qr-text {
    font-size: 12px;
    font-weight: bold;
    margin-top: 1px;
    word-break: break-all;
}

.twist-barcode-cell {
    padding: 2px;
    vertical-align: middle;
    text-align: center;
}

.twist-barcode-cell img {
    width: 100%;
    max-width: 100%;
    height: 80px;
    display: block;
    box-sizing: border-box;
}

.twist-barcode-label {
    font-size: 14px;
    font-weight: bold;
    margin-top: 2px;
}

.twist-shikake-cell {
    padding: 4px;
    vertical-align: middle;
    text-align: center;
}

.twist-shikake-cell img {
    max-width: 220px;
    height: 70px;
    display: block;
    margin: 0 auto;
}

.twist-shikake-label {
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 2px;
}

.twist-shikake-text {
    font-size: 11px;
    font-weight: bold;
    margin-top: 1px;
    word-break: break-all;
}

/* Thermal Printer Optimization */
@media print {
    body {
        margin: 0;
        padding: 0;
        background: white;
    }

    .twist-print-wrapper {
        flex-direction: row;
        gap: 0;
        margin: 0;
        justify-content: flex-start;
        page-break-after: always;
        page-break-inside: avoid;
    }

    .twist-print-wrapper .twist-image-section {
        display: flex;
        flex-shrink: 0;
        align-items: center;
    }

    .twist-print-wrapper .twist-image-section img {
        height: 70mm;
        width: auto;
        object-fit: contain;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .ticket-twist-print {
        width: auto;
        max-width: none;
        height: auto;
        border: none;
        margin: 0;
        padding: 0;
    }

    .ticket-twist-print table {
        page-break-inside: avoid;
        width: 100%;
    }

    .ticket-twist-print th,
    .ticket-twist-print td {
        border: 1px solid #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .twist-section-label.black-bg {
        background-color: #000 !important;
        color: white !important;
    }

    @page {
        size: landscape;
        margin: 1mm;
    }
}
</style>

@foreach($circuits as $circuit)
<div class="ticket twist-print-wrapper" data-orientation="landscape">
    @if(!empty($circuit->image_path))
    <div class="twist-image-section">
        <img src="{{ asset($circuit->image_path) }}" alt="Circuit Drawing">
    </div>
    @endif

    <div class="ticket-twist-print">
    <table>
        <colgroup>
            <col style="width: 38px">   {{-- Col 1: Section label --}}
            <col style="width: 96px">   {{-- Col 2: Label --}}
            <col style="width: 96px">   {{-- Col 3: Value --}}
            <col style="width: 72px">   {{-- Col 4: Label/Value (MACHINE/SIZE) --}}
            <col style="width: 72px">   {{-- Col 5: Label (MACHINE/COLOR) --}}
            <col style="width: 72px">   {{-- Col 6: Value (SEQ/C-L) --}}
            <col style="width: 96px">   {{-- Col 7: Value --}}
            <col style="width: 96px">   {{-- Col 8: Value --}}
            <col style="width: 96px">  {{-- Col 9: Barcode area --}}
        </colgroup>
        <thead>
            <tr>
                <th colspan="9">E-KANBAN CUTTING TWIST {{ $circuit->carline ?? '' }}</th>
            </tr>
        </thead>
        <tbody>
            <!-- Row 1: Labels -->
            <tr>
                <td colspan="2" class="twist-label-cell">CCT CODE</td>
                <td class="twist-label-cell">CCT NO</td>
                <td colspan="2" class="twist-label-cell">MACHINE</td>
                <td class="twist-label-cell">SEQ</td>
                <td colspan="3" rowspan="2" class="twist-barcode-cell">
                    @if(isset($circuit->barcode_mesin_path))
                        <img src="{{ $circuit->barcode_mesin_path }}" alt="Barcode Mesin">
                        <div class="twist-barcode-label">{{ $circuit->barcode_mesin ?? '' }}</div>
                    @else
                        <div style="font-size:22px;font-weight:bold;">{{ $circuit->barcode_mesin ?? '-' }}</div>
                    @endif
                </td>
            </tr>
            <!-- Row 2: Values -->
            <tr>
                <td colspan="2" class="twist-value-cell">{{ $circuit->cct_code ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->cct_no ?? '-' }}</td>
                <td colspan="2" class="twist-value-cell">{{ $circuit->machine ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->sequence ?? '-' }}</td>
            </tr>

            <!-- Row 3: Customer Info Labels -->
            <tr>
                <td colspan="2" class="twist-label-cell">CUST NO</td>
                <td class="twist-label-cell">KIND</td>
                <td class="twist-label-cell">SIZE</td>
                <td class="twist-label-cell">COL.</td>
                <td class="twist-label-cell">C/L</td>
                <td class="twist-label-cell">QTY</td>
                <td class="twist-label-cell">ISSUE</td>
                <td class="twist-label-cell">M. TWIST</td>
            </tr>
            <!-- Row 4: Customer Info Values -->
            <tr>
                <td colspan="2" class="twist-value-cell">{{ $circuit->cust_no ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->kind ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->size ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->col ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->cl ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->qty ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->issue ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->member_twist ?? '-' }}</td>
            </tr>

            <!-- Section A - Row 1 -->
            <tr>
                <td rowspan="3" class="twist-section-label">A</td>
                <td class="twist-label-cell text-left">TERM.</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->terminal_1 ?? '' }}</td>
                <td class="twist-label-cell text-left">NOTE</td>
                <td class="twist-value-cell text-left">{{ $circuit->note_1 ?? '' }}</td>
                <td colspan="2" class="twist-label-cell">MACH. TWIST</td>
                <td class="twist-label-cell">SEQ</td>
            </tr>
            <!-- Section A - Row 2 -->
            <tr>
                <td class="twist-label-cell text-left">ACC 1</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->acc_1a ?? '' }}</td>
                <td class="twist-label-cell text-left">STRIP</td>
                <td class="twist-value-cell text-left">{{ $circuit->strip_1 ?? '' }}</td>
                <td colspan="2" class="twist-value-cell">{{ $circuit->machine_twist ?? '' }}</td>
                <td class="twist-value-cell">{{ $circuit->sequence_2 ?? '' }}</td>
            </tr>
            <!-- Section A - Row 3 -->
            <tr>
                <td class="twist-label-cell text-left">TUBE</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->tube_1 ?? '' }}</td>
                <td class="twist-label-cell text-left">MARK</td>
                <td class="twist-value-cell text-left">{{ $circuit->mark_1 ?? '' }}</td>
                <td colspan="3" rowspan="2" class="twist-barcode-cell">
                    @if(isset($circuit->barcode_process_path))
                        <img src="{{ $circuit->barcode_process_path }}" alt="Barcode Process">
                        <div class="twist-barcode-label">{{ $circuit->barcode_process ?? '' }}</div>
                    @elseif(!empty($circuit->barcode_process))
                        <div style="font-size:18px;font-weight:bold;">{{ $circuit->barcode_process }}</div>
                    @endif
                </td>
            </tr>

            <!-- Section B - Row 1 -->
            <tr>
                <td rowspan="3" class="twist-section-label black-bg">B</td>
                <td class="twist-label-cell text-left">TERM.</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->terminal_2 ?? '' }}</td>
                <td class="twist-label-cell text-left">NOTE</td>
                <td class="twist-value-cell text-left">{{ $circuit->note_2 ?? '' }}</td>
            </tr>
            <!-- Section B - Row 2 -->
            <tr>
                <td class="twist-label-cell text-left">ACC 1</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->acc_2a ?? '' }}</td>
                <td class="twist-label-cell text-left">STRIP</td>
                <td class="twist-value-cell text-left">{{ $circuit->strip_2 ?? '' }}</td>
                <td colspan="3" class="twist-label-cell">TO STORE</td>
            </tr>
            <!-- Section B - Row 3 -->
            <tr>
                <td class="twist-label-cell text-left">TUBE</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->tube_2 ?? '' }}</td>
                <td class="twist-label-cell text-left">MARK</td>
                <td class="twist-value-cell text-left">{{ $circuit->mark_2 ?? '' }}</td>
                <td colspan="3" class="twist-value-cell">{{ $circuit->to_store ?? '' }}</td>
            </tr>

            <!-- Bottom Section - Row 1 -->
            <tr>
                <td colspan="3" rowspan="4" class="twist-qrcode-cell">
                    <div class="twist-qr-label">QRCODE KANBAN</div>
                    @if(isset($circuit->qr_code_path))
                        <img src="{{ $circuit->qr_code_path }}" alt="QR Kanban" class="twist-qr-img">
                        <div class="twist-qr-text">{{ $circuit->barcode_kanban ?? '' }}</div>
                    @else
                        <div style="width:110px;height:110px;border:1px solid #000;margin:0 auto;font-size:12px;display:flex;align-items:center;justify-content:center;">QR CODE</div>
                    @endif
                </td>
                <td class="twist-label-cell text-left">CV NO</td>
                <td colspan="3" class="twist-value-cell text-left">{{ $circuit->conveyor ?? '-' }}</td>
                <td colspan="2" rowspan="4" class="twist-qrcode-cell">
                    <div class="twist-qr-label" style="font-weight:bold;font-size:20px;">{{ $circuit->shikake_code ?? '-' }}</div>
                    @if(isset($circuit->qr_qrcode_shikake_path))
                        <img src="{{ $circuit->qr_qrcode_shikake_path }}" alt="QR Shikake" class="twist-qr-img">
                        <div class="twist-qr-text">{{ $circuit->qrcode_shikake ?? '' }}</div>
                    @elseif(isset($circuit->qr_shikake_path))
                        <img src="{{ $circuit->qr_shikake_path }}" alt="QR Shikake" class="twist-qr-img">
                        <div class="twist-qr-text">{{ $circuit->barcode_shikake ?? '' }}</div>
                    @elseif(!empty($circuit->barcode_shikake))
                        <div class="twist-qr-text" style="font-size:14px;">{{ $circuit->barcode_shikake }}</div>
                    @endif
                </td>
            </tr>
            <!-- Bottom Section - Row 2 -->
            <tr>
                <td class="twist-label-cell text-left">FAM.</td>
                <td colspan="3" class="twist-value-cell text-left">{{ $circuit->family ?? '-' }}</td>
            </tr>
            <!-- Bottom Section - Row 3 -->
            <tr>
                <td class="twist-label-cell text-left">DATE</td>
                <td colspan="3" class="twist-value-cell text-left">
                    {{ $circuit->release_date ? \Carbon\Carbon::parse($circuit->release_date)->format('d-M-y') : '-' }}
                </td>
            </tr>
            <!-- Bottom Section - Row 4 -->
            <tr>
                <td class="twist-label-cell text-left">NOTE</td>
                <td colspan="3" class="twist-value-cell text-left">{{ $circuit->released_note ?? '' }}</td>
            </tr>
        </tbody>
    </table>
    </div>
</div>
@endforeach
