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
    padding: 1px 3px;
    text-align: center;
    vertical-align: middle;
    line-height: 1;
    font-size: 18px;
    box-sizing: border-box;
}

.ticket-twist-print thead th {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 26px;
    padding: 2px;
    border: 2px solid #000;
}

.twist-section-label {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 22px;
    width: 38px;
    padding: 1px;
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
    font-size: 15px;
}

.twist-value-cell {
    font-weight: bold;
    font-size: 18px;
}

.text-left {
    text-align: left !important;
}

.twist-qrcode-cell {
    padding: 2px;
    vertical-align: middle;
    text-align: center;
    overflow: hidden;
    max-width: 0; /* force cell to respect colgroup width */
}

.twist-qr-label {
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 2px;
}

.twist-qr-img {
    max-width: 100%;
    width: 90px;
    height: auto;
    aspect-ratio: 1 / 1;
    display: block;
    margin: 0 auto;
    object-fit: contain;
}



.twist-barcode-cell {
    padding: 2px;
    vertical-align: middle;
    text-align: center;
}

.twist-barcode-cell img {
    width: 95%;
    max-height: 18mm;
    height: auto;
    object-fit: contain;
    display: block;
    margin: 0 auto;
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
            <col style="width: 46px">   {{-- Col 1: Section label --}}
            <col style="width: 92px">   {{-- Col 2: Label --}}
            <col style="width: 92px">   {{-- Col 3: Value --}}
            <col style="width: 92px">   {{-- Col 4: Label/Value --}}
            <col style="width: 92px">   {{-- Col 5: Label/Value --}}
            <col style="width: 92px">   {{-- Col 6: Value --}}
            <col style="width: 92px">   {{-- Col 7: Value --}}
            <col style="width: 92px">   {{-- Col 8: Value --}}
            <col style="width: 180px">  {{-- Col 9: Barcode/QR area --}}
        </colgroup>
        <thead>
            <tr>
                <th colspan="7">E-KANBAN CUTTING TWIST {{ $circuit->carline ?? '' }}</th>
                <th colspan="2">{{ $circuit->shikake_code ?? '' }}</th>
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
                <td class="twist-label-cell">COLOR</td>
                <td class="twist-label-cell">C/L</td>
                <td class="twist-label-cell">QTY</td>
                <td class="twist-label-cell">ISSUE</td>
                <td class="twist-label-cell">M. TWS</td>
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
                <td class="twist-value-cell">{{ $circuit->memory_twist ?? '-' }}</td>
            </tr>

            <!-- Section A - Row 1 -->
            <tr>
                <td rowspan="4" class="twist-section-label">A</td>
                <td class="twist-label-cell text-left">TERM.</td>
                <td colspan="2" class="twist-value-cell text-left"@if(!empty($circuit->gold_1)) style="background-color:#000;color:white;-webkit-print-color-adjust:exact;print-color-adjust:exact;"@endif>{{ $circuit->terminal_1 ?? '' }}</td>
                <td class="twist-label-cell" style="text-align:center;overflow:hidden;">@if(!empty($circuit->note_1))<span style="display:inline-block;font-size:28px;font-weight:bold;line-height:0.8;margin-bottom:-6px;">{{ $circuit->note_1 }}</span>@endif</td>
                <td class="twist-value-cell text-left"></td>
                <td colspan="2" class="twist-label-cell">MACH. TWIST</td>
                <td class="twist-label-cell">SEQ</td>
            </tr>
            <!-- Section A - Row 2 -->
            <tr>
                <td class="twist-label-cell text-left">ACC 1</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->acc_1a ?? '' }}</td>
                <td class="twist-label-cell text-left">NOTE</td>
                <td class="twist-value-cell text-left">{{ $circuit->ta ?? '' }}</td>
                <td colspan="2" class="twist-value-cell">{{ $circuit->machine_twist ?? '' }}</td>
                <td class="twist-value-cell">{{ $circuit->sequence_2 ?? '' }}</td>
            </tr>
            <!-- Section A - Row 3 -->
            <tr>
                <td class="twist-label-cell text-left">ACC 2</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->acc_1 ?? '' }}</td>
                <td class="twist-label-cell text-left">STRIP</td>
                <td class="twist-value-cell text-left">{{ $circuit->strip_1 ?? '' }}</td>
                <td colspan="3" rowspan="2" class="twist-barcode-cell">
                    @if(isset($circuit->barcode_twist_path))
                        <img src="{{ $circuit->barcode_twist_path }}" alt="Barcode Twist">
                        <div class="twist-barcode-label">{{ $circuit->barcode_twist ?? '' }}</div>
                    @elseif(!empty($circuit->barcode_twist))
                        <div style="font-size:18px;font-weight:bold;">{{ $circuit->barcode_twist }}</div>
                    @endif
                </td>
            </tr>
            <!-- Section A - Row 4 -->
            <tr>
                <td class="twist-label-cell text-left">TUBE</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->tube_1 ?? '' }}</td>
                <td class="twist-label-cell text-left">MARK</td>
                <td class="twist-value-cell text-left">{{ $circuit->mark_1 ?? '' }}</td>
            </tr>

            <!-- Section B - Row 1 -->
            <tr>
                <td rowspan="4" class="twist-section-label black-bg">B</td>
                <td class="twist-label-cell text-left">TERM.</td>
                <td colspan="2" class="twist-value-cell text-left"@if(!empty($circuit->gold_2)) style="background-color:#000;color:white;-webkit-print-color-adjust:exact;print-color-adjust:exact;"@endif>{{ $circuit->terminal_2 ?? '' }}</td>
                <td class="twist-label-cell text-left" style="overflow:hidden;">@if(!empty($circuit->note_2))<span style="display:inline-block;font-size:28px;font-weight:bold;line-height:0.8;margin-bottom:-6px;">{{ $circuit->note_2 }}</span>@endif</td>
                <td class="twist-value-cell text-left"></td>
                <td class="twist-label-cell">ADDR.</td>
                <td colspan="2" class="twist-value-cell">{{ $circuit->address ?? '' }}</td>
            </tr>
            <!-- Section B - Row 2 -->
            <tr>
                <td class="twist-label-cell text-left">ACC 1</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->acc_2a ?? '' }}</td>
                <td class="twist-label-cell text-left">NOTE</td>
                <td class="twist-value-cell text-left">{{ $circuit->tb ?? '' }}</td>
                <td class="twist-label-cell">TO 1</td>
                <td colspan="2" class="twist-value-cell">{{ $circuit->t01 ?? '' }}</td>
            </tr>
            <!-- Section B - Row 3 -->
            <tr>
                <td class="twist-label-cell text-left">ACC 2</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->acc_2 ?? '' }}</td>
                <td class="twist-label-cell text-left">STRIP</td>
                <td class="twist-value-cell text-left">{{ $circuit->strip_2 ?? '' }}</td>
                <td class="twist-label-cell">TO 2</td>
                <td colspan="2" class="twist-value-cell">{{ $circuit->t02 ?? '' }}</td>
            </tr>
            <!-- Section B - Row 4 -->
            <tr>
                <td class="twist-label-cell text-left">TUBE</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->tube_2 ?? '' }}</td>
                <td class="twist-label-cell text-left">MARK</td>
                <td class="twist-value-cell text-left">{{ $circuit->mark_2 ?? '' }}</td>
                <td class="twist-label-cell">TO 3</td>
                <td colspan="2" class="twist-value-cell">{{ $circuit->t03 ?? '' }}</td>
            </tr>

            @php
                $toStore = $circuit->to_store ?? '';
                $dashPos = strpos($toStore, '-');
                $toStoreLine1 = $dashPos !== false ? substr($toStore, 0, $dashPos) : $toStore;
                $toStoreLine2 = $dashPos !== false ? substr($toStore, $dashPos) : '';
            @endphp
            <!-- Bottom Section - Row 1 -->
            <tr style="height: 20px;">
                <td colspan="2" class="twist-label-cell" style="text-align:center;padding:1px;">TO STORE</td>
                <td colspan="1" rowspan="4" class="twist-qrcode-cell">
                    @if(isset($circuit->qr_qrcode_drawing_path))
                        <img src="{{ $circuit->qr_qrcode_drawing_path }}" alt="QR Drawing" class="twist-qr-img">
                    @elseif(!empty($circuit->qrcode_drawing))
                        <div style="width:100%;max-width:88px;aspect-ratio:1/1;border:1px solid #ccc;margin:0 auto;font-size:10px;display:flex;align-items:center;justify-content:center;word-break:break-all;padding:2px;">{{ $circuit->qrcode_drawing }}</div>
                    @else
                        <div style="width:100%;max-width:88px;aspect-ratio:1/1;border:1px solid #000;margin:0 auto;font-size:12px;display:flex;align-items:center;justify-content:center;">DRW</div>
                    @endif
                </td>
                <td class="twist-label-cell text-left" style="padding:1px 3px;">CV NO</td>
                <td colspan="3" class="twist-value-cell text-left" style="padding:1px 3px;">{{ $circuit->conveyor ?? '-' }}</td>
                <td colspan="2" rowspan="4" class="twist-qrcode-cell">
                    <div class="twist-qr-label">SHIKAKE CODE</div>
                    @if(isset($circuit->qr_code_path))
                        <img src="{{ $circuit->qr_code_path }}" alt="QR Kanban" class="twist-qr-img">
                    @else
                        <div style="width:100%;max-width:90px;aspect-ratio:1/1;border:1px solid #000;margin:0 auto;font-size:12px;display:flex;align-items:center;justify-content:center;">QR</div>
                    @endif
                    @if(!empty($circuit->barcode_kanban))
                        <div style="font-size:11px;font-weight:bold;margin-top:2px;word-break:break-all;line-height:1.2;">{{ $circuit->barcode_kanban }}</div>
                    @endif
                </td>
            </tr>
            <!-- Bottom Section - Row 2 -->
            <tr style="height: 20px;">
                <td colspan="2" rowspan="3" class="twist-value-cell" style="font-size:26px;font-weight:bold;text-align:center;vertical-align:middle;word-break:break-all;line-height:1.2;">{{ $toStoreLine1 }}<br>{{ $toStoreLine2 }}</td>
                <td class="twist-label-cell text-left" style="padding:1px 3px;">FAMILY</td>
                <td colspan="3" class="twist-value-cell text-left" style="padding:1px 3px;">{{ $circuit->family ?? '-' }}</td>
            </tr>
            <!-- Bottom Section - Row 3 -->
            <tr style="height: 20px;">
                <td class="twist-label-cell text-left" style="padding:1px 3px;">DATE</td>
                <td colspan="3" class="twist-value-cell text-left" style="padding:1px 3px;">
                    {{ \Carbon\Carbon::now()->format('d-M-y') }}
                </td>
            </tr>
            <!-- Bottom Section - Row 4 -->
            <tr style="height: 20px;">
                <td class="twist-label-cell text-left" style="padding:1px 3px;">NOTE</td>
                <td colspan="3" class="twist-value-cell text-left" style="padding:1px 3px;">{{ $circuit->released_note ?? '' }}</td>
            </tr>
        </tbody>
    </table>
    </div>
</div>
@endforeach
