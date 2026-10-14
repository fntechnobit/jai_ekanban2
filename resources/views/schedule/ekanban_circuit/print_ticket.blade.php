{{-- CIRCUIT CUTTING Print Template --}}
{{-- Based on cutting.html reference, scaled up for thermal printing (1px = 1 dot at 203dpi) --}}
{{-- 9 columns, landscape, height 576px = 80mm --}}
@include('schedule.ekanban_circuit.partials.ticket_cutting_style')

@foreach($circuits as $circuit)
@php
    // Cell BARCODE MESIN = kolom 7+8+9 (72+60+col9). Kolom 9 dilebarkan sampai
    // barcode Code 39 muat 1:1 plus quiet zone 30px di kiri-kanan (+2 border).
    $barcodeCol9 = max(192, ($circuit->barcode_width ?? 0) + 2 * \App\Helpers\BarcodeHelper::CODE39_QUIET_ZONE + 2 - (72 + 60));
@endphp
<div class="ticket circuit-print-wrapper" data-orientation="landscape">
    @if(!empty($circuit->image_path))
    <div class="circuit-image-section">
        <img src="{{ asset($circuit->image_path) }}" alt="Circuit Image">
    </div>
    @endif

    <div class="ticket-circuit-print">
        <table>
            <colgroup>
                <col style="width: 40px">   {{-- Col 1: Section label (5mm) --}}
                <col style="width: 80px">   {{-- Col 2: Label (10mm) --}}
                <col style="width: 120px">  {{-- Col 3: Value (15mm) --}}
                <col style="width: 72px">   {{-- Col 4: KIND --}}
                <col style="width: 72px">   {{-- Col 5: SIZE --}}
                <col style="width: 72px">   {{-- Col 6: COL --}}
                <col style="width: 72px">   {{-- Col 7: CL --}}
                <col style="width: 96px">   {{-- Col 8: SEQ (label CCT CODE muat 1 baris) --}}
                <col style="width: {{ $barcodeCol9 }}px">  {{-- Col 9: MACHINE (min 192, melebar ikut barcode) --}}
            </colgroup>
            <thead>
                <tr>
                    <th colspan="9">E-KANBAN CUTTING {{ $circuit->carline ?? '' }}</th>
                </tr>
            </thead>
            <tbody>
                <!-- Row 1: Labels -->
                <tr>
                    <td colspan="2" class="label-cell">CCT NO</td>
                    <td class="label-cell">CUST NO</td>
                    <td class="label-cell">KIND</td>
                    <td class="label-cell">SIZE</td>
                    <td class="label-cell">COL</td>
                    <td class="label-cell">C/L</td>
                    <td class="label-cell">SEQ</td>
                    <td class="label-cell">MACHINE</td>
                </tr>
                <!-- Row 2: Values -->
                <tr>
                    <td colspan="2" class="value-cell">{{ $circuit->cct_no ?? '-' }}</td>
                    <td class="value-cell">{{ $circuit->cust_no ?? '-' }}</td>
                    <td class="value-cell">{{ $circuit->kind ?? '-' }}</td>
                    <td class="value-cell">{{ $circuit->size ?? '-' }}</td>
                    <td class="value-cell">{{ $circuit->col ?? '-' }}</td>
                    <td class="value-cell">{{ $circuit->cl ?? '-' }}</td>
                    <td class="value-cell">{{ $circuit->sequence ?? '-' }}</td>
                    <td class="value-cell">{{ $circuit->machine ?? '-' }}</td>
                </tr>

                <!-- Section A - Row 1 -->
                <tr>
                    <td rowspan="4" class="section-label">A</td>
                    <td class="label-cell text-left">TERM.</td>
                    <td colspan="3" class="value-cell text-left"@if(!empty($circuit->gold_1)) style="background-color:#000;color:white;-webkit-print-color-adjust:exact;print-color-adjust:exact;"@endif>{{ $circuit->terminal_1 ?? '' }}</td>
                    <td class="label-cell" style="overflow:hidden;">@if(!empty($circuit->note_1))<span style="display:inline-block;font-size:32px;font-weight:bold;line-height:0.8;margin-bottom:-8px;">{{ $circuit->note_1 }}</span>@endif</td>
                    <td class="value-cell">{{ $circuit->seal_1 ?? '' }}</td>
                    <td class="label-cell text-left">TO 1</td>
                    <td class="value-cell text-left">{{ $circuit->t01 ?? '' }}</td>
                </tr>
                <!-- Section A - Row 2 -->
                <tr>
                    <td class="label-cell text-left">ACC 1</td>
                    <td colspan="3" class="value-cell text-left">{{ $circuit->acc_1 ?? '' }}</td>
                    <td class="label-cell text-left">NOTE</td>
                    <td class="value-cell">{{ $circuit->ta ?? '' }}</td>
                    <td class="label-cell text-left">TO 2</td>
                    <td class="value-cell text-left">{{ $circuit->t02 ?? '' }}</td>
                </tr>
                <!-- Section A - Row 3 -->
                <tr>
                    <td class="label-cell text-left">ACC 2</td>
                    <td colspan="3" class="value-cell text-left">{{ $circuit->acc_1a ?? '' }}</td>
                    <td class="label-cell text-left">STRIP</td>
                    <td class="value-cell">{{ $circuit->strip_1 ?? '' }}</td>
                    <td class="label-cell text-left">TO 3</td>
                    <td class="value-cell text-left">{{ $circuit->t03 ?? '' }}</td>
                </tr>
                <!-- Section A - Row 4 -->
                <tr>
                    <td class="label-cell text-left">TUBE</td>
                    <td colspan="3" class="value-cell text-left">{{ $circuit->tube_1 ?? '' }}</td>
                    <td class="label-cell text-left">MARK</td>
                    <td class="value-cell">{{ $circuit->mark_1 ?? '' }}</td>
                    <td class="label-cell text-left">STORE</td>
                    <td class="value-cell text-left">{{ $circuit->to_store ?? '' }}</td>
                </tr>

                <!-- Section B - Row 1 -->
                <tr>
                    <td rowspan="4" class="section-label black-bg">B</td>
                    <td class="label-cell text-left">TERM.</td>
                    <td colspan="3" class="value-cell text-left"@if(!empty($circuit->gold_2)) style="background-color:#000;color:white;-webkit-print-color-adjust:exact;print-color-adjust:exact;"@endif>{{ $circuit->terminal_2 ?? '' }}</td>
                    <td class="label-cell" style="overflow:hidden;">@if(!empty($circuit->note_2))<span style="display:inline-block;font-size:32px;font-weight:bold;line-height:0.8;margin-bottom:-8px;">{{ $circuit->note_2 }}</span>@endif</td>
                    <td class="value-cell">{{ $circuit->seal_2 ?? '' }}</td>
                    <td class="label-cell text-left">ADDR</td>
                    <td class="value-cell text-left">{{ $circuit->address ?? '' }}</td>
                </tr>
                <!-- Section B - Row 2 -->
                <tr>
                    <td class="label-cell text-left">ACC 1</td>
                    <td colspan="3" class="value-cell text-left">{{ $circuit->acc_2 ?? '' }}</td>
                    <td class="label-cell text-left">NOTE</td>
                    <td class="value-cell">{{ $circuit->tb ?? '' }}</td>
                    <td class="label-cell text-left">CCT CODE</td>
                    <td class="value-cell text-left">{{ $circuit->cct_code ?? '' }}</td>
                </tr>
                <!-- Section B - Row 3 -->
                <tr>
                    <td class="label-cell text-left">ACC 2</td>
                    <td colspan="3" class="value-cell text-left">{{ $circuit->acc_2a ?? '' }}</td>
                    <td class="label-cell text-left">STRIP</td>
                    <td class="value-cell">{{ $circuit->strip_2 ?? '' }}</td>
                    <td class="label-cell text-left">QTY</td>
                    <td class="value-cell text-left">{{ $circuit->qty ?? '' }}</td>
                </tr>
                <!-- Section B - Row 4 -->
                <tr>
                    <td class="label-cell text-left">TUBE</td>
                    <td colspan="3" class="value-cell text-left">{{ $circuit->tube_2 ?? '' }}</td>
                    <td class="label-cell text-left">MARK</td>
                    <td class="value-cell">{{ $circuit->mark_2 ?? '' }}</td>
                    <td class="label-cell text-left">ISSUE</td>
                    <td class="value-cell text-left">{{ $circuit->issue ?? '' }}</td>
                </tr>

                <!-- Bottom Section - Row 1 -->
                <tr>
                    <td colspan="3" rowspan="4" class="qrcode-cell">
                        <div class="qr-label">QRCODE KANBAN</div>
                        @if(isset($circuit->qr_code_path))
                            <img src="{{ $circuit->qr_code_path }}" alt="QR Kanban" class="qr-img">
                            <div class="qr-text">{{ $circuit->barcode_kanban ?? '' }}</div>
                        @else
                            <div style="width:128px;height:128px;border:1px solid #000;margin:0 auto;font-size:12px;display:flex;align-items:center;justify-content:center;">QR CODE</div>
                        @endif
                    </td>
                    <td colspan="3" class="value-cell text-left">{{ $circuit->carline ?? '' }}</td>
                    <td colspan="3" rowspan="4" class="barcode-cell">
                        <div class="barcode-label">BARCODE MESIN</div>
                        @if(isset($circuit->barcode_path))
                            <img src="{{ $circuit->barcode_path }}" alt="Barcode Mesin">
                            <div class="barcode-label">{{ $circuit->barcode_data ?? $circuit->barcode_mesin ?? '' }}</div>
                        @elseif(!empty($circuit->barcode_mesin))
                            <div style="font-size:18px;font-weight:bold;">{{ $circuit->barcode_mesin }}</div>
                        @endif
                    </td>
                </tr>
                <!-- Bottom Section - Row 2 -->
                <tr>
                    <td colspan="3" class="value-cell text-left">{{ $circuit->conveyor ?? '' }}</td>
                </tr>
                <!-- Bottom Section - Row 3 -->
                <tr>
                    <td colspan="3" class="value-cell text-left">
                        {{ \Carbon\Carbon::now()->format('d-M-y') }}
                    </td>
                </tr>
                <!-- Bottom Section - Row 4 -->
                <tr>
                    <td colspan="3" class="value-cell text-left note-cell">{{ $circuit->released_note ?? '' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="circuit-punch-strip">
        <div class="punch-circle"></div>
    </div>
</div>
@endforeach
