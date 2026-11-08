{{-- CIRCUIT CUTTING_MINICOAX Print Template. 1px = 1 dot @203dpi, landscape, tinggi 576px = 80mm.
     8 kolom x 16 baris (2 baris judul 38 px + 14 baris 35 px).
     Kiri: sisi A & B (TERMINAL, STRIP, ACC1-ACC4). Tengah: barcode proses A | ADDRESS STORE |
     barcode proses B. Kanan: mesin, C/L, seq, qty, issue, tanggal, CV | QR kanban | family,
     TO MESIN, note. Barcode proses dan QR kanban tidak pernah berbagi baris supaya scanner
     tidak menangkap kode sebelahnya. --}}
@include('schedule.ekanban_circuit.partials.ticket_special_style')

@foreach($circuits as $circuit)
@php
    // Barcode proses A/B menempati kolom 4+5+6 (110+80+col6). Kolom 6 dilebarkan sampai
    // barcode Code 39 terlebar muat 1:1 plus quiet zone 30px kiri-kanan (+2 border).
    $mcxBarcodeWidth = max($circuit->barcode_process_width ?? 0, $circuit->barcode_process_2_width ?? 0);
    $mcxCol6 = max(100, $mcxBarcodeWidth + 2 * \App\Helpers\BarcodeHelper::CODE39_QUIET_ZONE + 2 - (110 + 80));
    // Judul mengikuti family master circuit (mis. J69P-MINICOAX); kata MINICOAX selalu ada.
    $mcxFamily = $circuit->family ?: ($circuit->carline ?? '');
    $mcxTitle = 'KANBAN CUTTING ' . (stripos($mcxFamily, 'MINICOAX') === false ? trim('MINICOAX ' . $mcxFamily) : $mcxFamily);
@endphp
<div class="ticket special-print-wrapper" data-orientation="landscape">
    <div class="ticket-special-print">
        <table class="rows-16-35">
            <colgroup>
                <col style="width: 40px">   {{-- Col 1: sisi A/B --}}
                <col style="width: 100px">  {{-- Col 2: label TERMINAL/STRIP/ACC --}}
                <col style="width: 210px">  {{-- Col 3: nilai / CUST NO --}}
                <col style="width: 110px">  {{-- Col 4: KIND --}}
                <col style="width: 80px">   {{-- Col 5: SIZE --}}
                <col style="width: {{ $mcxCol6 }}px">  {{-- Col 6: COLOUR (min 100, melebar ikut barcode proses) --}}
                <col style="width: 130px">  {{-- Col 7: C/L, QTY, ISSUE DATE --}}
                <col style="width: 150px">  {{-- Col 8: SEQUENCE, ISSUE NO, CV NO --}}
            </colgroup>
            <tbody>
                {{-- r1-r2: judul | MACH NO --}}
                <tr class="title-row">
                    <td colspan="6" rowspan="2" class="sp-title">{{ $mcxTitle }}</td>
                    <td colspan="2" class="sp-label">MACH NO.</td>
                </tr>
                <tr class="title-row">
                    <td colspan="2" class="sp-big" style="font-size: 28px;">{{ $circuit->machine ?? '-' }}</td>
                </tr>

                {{-- r3-r4: spesifikasi kabel | C/L, SEQUENCE --}}
                <tr>
                    <td colspan="2" class="sp-label">CCT CODE</td>
                    <td class="sp-label">CUST NO.</td>
                    <td class="sp-label">KIND</td>
                    <td class="sp-label">SIZE</td>
                    <td class="sp-label">COLOUR</td>
                    <td class="sp-label">C/L</td>
                    <td class="sp-label">SEQUENCE</td>
                </tr>
                <tr>
                    <td colspan="2" class="sp-value">{{ $circuit->cct_code ?? '-' }}</td>
                    <td class="sp-value">{{ $circuit->cust_no ?? '-' }}</td>
                    <td class="sp-value">{{ $circuit->kind ?? '-' }}</td>
                    <td class="sp-value">{{ $circuit->size ?? '-' }}</td>
                    <td class="sp-value">{{ $circuit->col ?? '-' }}</td>
                    <td class="sp-value">{{ $circuit->cl ?? '-' }}</td>
                    <td class="sp-value">{{ $circuit->sequence ?? '-' }}</td>
                </tr>

                {{-- r5-r10: sisi A | barcode proses A (r5-r8, rata atas) + ADDRESS STORE | QTY..CV + QR --}}
                <tr>
                    <td rowspan="6" class="sp-section">A</td>
                    <td class="sp-label text-left">TERMINAL</td>
                    <td class="sp-value text-left{{ !empty($circuit->gold_1) ? ' sp-inv' : '' }}">{{ $circuit->terminal_1 ?? '' }}</td>
                    <td colspan="3" rowspan="4" class="sp-barcode-cell" style="vertical-align: top;">
                        <div class="sp-barcode-label">BARCODE PROSES A</div>
                        @if(isset($circuit->barcode_process_path))
                            <img src="{{ $circuit->barcode_process_path }}" alt="Barcode Proses A">
                            <div class="sp-barcode-text">{{ $circuit->barcode_process_data }}</div>
                        @elseif(!empty($circuit->barcode_process))
                            <div class="sp-value">{{ $circuit->barcode_process }}</div>
                        @endif
                    </td>
                    <td class="sp-label">QTY</td>
                    <td class="sp-label">ISSUE NO.</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">STRIP</td>
                    <td class="sp-value text-left">{{ $circuit->strip_1 ?? '' }}</td>
                    <td class="sp-value">{{ $circuit->qty ?? '-' }}</td>
                    <td class="sp-value">{{ $circuit->issue ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC1</td>
                    <td class="sp-value text-left">{{ $circuit->acc_1 ?? '' }}</td>
                    <td class="sp-label">ISSUE DATE</td>
                    <td class="sp-label">CV NO.</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC2</td>
                    <td class="sp-value text-left">{{ $circuit->acc_1a ?? '' }}</td>
                    <td class="sp-value">{{ strtoupper(\Carbon\Carbon::now()->format("d M'y")) }}</td>
                    <td class="sp-value">{{ $circuit->conveyor ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC3</td>
                    <td class="sp-value text-left">{{ $circuit->acc_1b ?? '' }}</td>
                    <td colspan="3" class="sp-label">ADDRESS STORE</td>
                    <td colspan="2" rowspan="5" class="sp-qr-cell">
                        <div class="sp-qr-label">QRCODE KANBAN</div>
                        @if(isset($circuit->qr_code_path))
                            <img src="{{ $circuit->qr_code_path }}" alt="QR Kanban" class="sp-qr-img">
                        @endif
                        <div class="sp-qr-text">{{ $circuit->barcode_kanban ?? '' }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC4</td>
                    <td class="sp-value text-left">{{ $circuit->acc_1c ?? '' }}</td>
                    <td colspan="3" rowspan="4" class="sp-big" style="font-size: 40px;">{{ $circuit->to_store ?? '' }}</td>
                </tr>

                {{-- r11-r16: sisi B | ADDRESS STORE (lanjutan) + barcode proses B (r14-r16, rata bawah) | family, TO MESIN, note --}}
                <tr>
                    <td rowspan="6" class="sp-section black-bg">B</td>
                    <td class="sp-label text-left">TERMINAL</td>
                    <td class="sp-value text-left{{ !empty($circuit->gold_2) ? ' sp-inv' : '' }}">{{ $circuit->terminal_2 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">STRIP</td>
                    <td class="sp-value text-left">{{ $circuit->strip_2 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC1</td>
                    <td class="sp-value text-left">{{ $circuit->acc_2 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC2</td>
                    <td class="sp-value text-left">{{ $circuit->acc_2a ?? '' }}</td>
                    <td colspan="3" rowspan="3" class="sp-barcode-cell" style="vertical-align: bottom;">
                        <div class="sp-barcode-label">BARCODE PROSES B</div>
                        @if(isset($circuit->barcode_process_2_path))
                            <img src="{{ $circuit->barcode_process_2_path }}" alt="Barcode Proses B">
                            <div class="sp-barcode-text">{{ $circuit->barcode_process_2_data }}</div>
                        @elseif(!empty($circuit->barcode_process_2))
                            <div class="sp-value">{{ $circuit->barcode_process_2 }}</div>
                        @endif
                    </td>
                    <td class="sp-label text-left">FAMILY</td>
                    <td class="sp-value text-left" style="font-size: 18px;">{{ $circuit->family ?? '' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC3</td>
                    <td class="sp-value text-left">{{ $circuit->acc_2b ?? '' }}</td>
                    <td class="sp-label text-left">TO MESIN</td>
                    <td class="sp-value text-left" style="font-size: 18px;">{{ $circuit->t01 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC4</td>
                    <td class="sp-value text-left">{{ $circuit->acc_2c ?? '' }}</td>
                    <td class="sp-label text-left">NOTE</td>
                    <td class="text-left"><span class="sp-note">{{ $circuit->released_note ?? '' }}</span></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="special-punch-strip">
        <div class="punch-circle"></div>
    </div>
</div>
@endforeach
