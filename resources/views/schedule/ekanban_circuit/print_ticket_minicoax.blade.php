{{-- CIRCUIT CUTTING_MINICOAX Print Template. 1px = 1 dot @203dpi, landscape, tinggi 576px = 80mm.
     8 kolom x 16 baris (2 baris judul 38 px + 14 baris 35 px).
     Kiri: sisi A (baris 5-10) & B (baris 11-16): TERMINAL, STRIP, ACC1-ACC4.
     Tengah: QR proses A sejajar sisi A, QR proses B sejajar sisi B.
     Kanan: QTY, issue, tanggal, CV, family, TO MESIN, note | QR address store & QR kanban.
     Empat QR dipisah blok teks (mendatar) dan rata atas/rata bawah (menurun, jarak antar
     gambar QR ~130 dot) supaya scanner tidak menangkap kode sebelahnya. --}}
@include('schedule.ekanban_circuit.partials.ticket_special_style')

@foreach($circuits as $circuit)
@php
    // Judul mengikuti family master circuit (mis. J69P-MINICOAX); kata MINICOAX selalu ada.
    // Sel judul (kolom 1-6) lebarnya ~630 px isi, muat ~33 karakter huruf 30px. Judul lebih
    // panjang dipecah 2 baris (baris 2 = family) supaya tiket tidak memanjang; 2 baris x 30px
    // masih muat di 2 baris judul (76 px). Family yang sangat panjang memakai huruf lebih kecil.
    $mcxFamily = trim($circuit->family ?: ($circuit->carline ?? ''));
    $mcxHead = stripos($mcxFamily, 'MINICOAX') === false ? 'KANBAN CUTTING MINICOAX' : 'KANBAN CUTTING';
    $mcxTwoLine = mb_strlen($mcxHead . ' ' . $mcxFamily) > 33 && $mcxFamily !== '';
    $mcxTitleSize = !$mcxTwoLine ? 30 : (mb_strlen($mcxFamily) <= 33 ? 30 : (mb_strlen($mcxFamily) <= 40 ? 26 : 22));
@endphp
<div class="ticket special-print-wrapper" data-orientation="landscape">
    <div class="ticket-special-print">
        <table class="rows-16-35">
            <colgroup>
                <col style="width: 40px">   {{-- Col 1: sisi A/B --}}
                <col style="width: 100px">  {{-- Col 2: label TERMINAL/STRIP/ACC --}}
                <col style="width: 210px">  {{-- Col 3: nilai / CUST NO --}}
                <col style="width: 80px">   {{-- Col 4: KIND        } QR proses A/B --}}
                <col style="width: 80px">   {{-- Col 5: SIZE        } --}}
                {{-- Kolom ber-width tetap hanya melebar bila isinya tidak bisa dilipat: family yang
                     dilipat tetap di 160 px, cukup untuk 2 baris family terpanjang (27 karakter). --}}
                <col style="width: 160px">  {{-- Col 6: COLOUR / QTY, ISSUE DATE, FAMILY --}}
                <col style="width: 130px">  {{-- Col 7: C/L / ISSUE NO, CV NO, TO MESIN --}}
                <col style="width: 150px">  {{-- Col 8: SEQUENCE / QR address store & QR kanban --}}
            </colgroup>
            <tbody>
                {{-- r1-r2: judul | MACH NO --}}
                <tr class="title-row">
                    <td colspan="6" rowspan="2" class="sp-title" style="font-size: {{ $mcxTitleSize }}px;">
                        @if($mcxTwoLine)
                            {{ $mcxHead }}<br>{{ $mcxFamily }}
                        @else
                            {{ trim($mcxHead . ' ' . $mcxFamily) }}
                        @endif
                    </td>
                    <td colspan="2" class="sp-label">MACH NO.</td>
                </tr>
                <tr class="title-row">
                    <td colspan="2" class="sp-big" style="font-size: 28px;">{{ $circuit->machine ?? '-' }}</td>
                </tr>

                {{-- r3-r4: spesifikasi kabel --}}
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

                {{-- r5-r10: sisi A | QR proses A (rata atas) | QTY, ISSUE NO, ISSUE DATE, CV NO | QR address store (rata atas) --}}
                <tr>
                    <td rowspan="6" class="sp-section">A</td>
                    <td class="sp-label text-left">TERMINAL</td>
                    <td class="sp-value text-left{{ !empty($circuit->gold_1) ? ' sp-inv' : '' }}">{{ $circuit->terminal_1 ?? '' }}</td>
                    <td colspan="2" rowspan="6" class="sp-qr-cell" style="vertical-align: top; padding-top: 4px;">
                        <div class="sp-qr-label">QR PROSES A</div>
                        @if(!empty($circuit->qr_process_path))
                            <img src="{{ $circuit->qr_process_path }}" alt="QR Proses A" class="sp-qr-img">
                        @endif
                        <div class="sp-qr-text">{{ $circuit->barcode_process ?? '' }}</div>
                    </td>
                    <td class="sp-label">QTY</td>
                    <td class="sp-label">ISSUE NO.</td>
                    <td rowspan="6" class="sp-qr-cell" style="vertical-align: top; padding-top: 4px;">
                        <div class="sp-qr-label">ADDRESS STORE</div>
                        @if(!empty($circuit->qr_store_path))
                            <img src="{{ $circuit->qr_store_path }}" alt="QR Address Store" class="sp-qr-img">
                        @endif
                        <div class="sp-qr-text sp-wrap" style="max-width: 140px;">{{ $circuit->to_store ?? '' }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="sp-label text-left">STRIP</td>
                    <td class="sp-value text-left">{{ $circuit->strip_1 ?? '' }}</td>
                    <td rowspan="2" class="sp-big" style="font-size: 32px;">{{ $circuit->qty ?? '-' }}</td>
                    <td rowspan="2" class="sp-big" style="font-size: 26px;">{{ $circuit->issue ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC1</td>
                    <td class="sp-value text-left">{{ $circuit->acc_1 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC2</td>
                    <td class="sp-value text-left">{{ $circuit->acc_1a ?? '' }}</td>
                    <td class="sp-label">ISSUE DATE</td>
                    <td class="sp-label">CV NO.</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC3</td>
                    <td class="sp-value text-left">{{ $circuit->acc_1b ?? '' }}</td>
                    <td rowspan="2" class="sp-big" style="font-size: 24px;">{{ strtoupper(\Carbon\Carbon::now()->format("d M'y")) }}</td>
                    <td rowspan="2" class="sp-big" style="font-size: 24px;">{{ $circuit->conveyor ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC4</td>
                    <td class="sp-value text-left">{{ $circuit->acc_1c ?? '' }}</td>
                </tr>

                {{-- r11-r16: sisi B | QR proses B (rata bawah) | FAMILY, TO MESIN, NOTE | QR kanban (rata bawah) --}}
                <tr>
                    <td rowspan="6" class="sp-section black-bg">B</td>
                    <td class="sp-label text-left">TERMINAL</td>
                    <td class="sp-value text-left{{ !empty($circuit->gold_2) ? ' sp-inv' : '' }}">{{ $circuit->terminal_2 ?? '' }}</td>
                    <td colspan="2" rowspan="6" class="sp-qr-cell" style="vertical-align: bottom; padding-bottom: 4px;">
                        <div class="sp-qr-label">QR PROSES B</div>
                        @if(!empty($circuit->qr_process_2_path))
                            <img src="{{ $circuit->qr_process_2_path }}" alt="QR Proses B" class="sp-qr-img">
                        @endif
                        <div class="sp-qr-text">{{ $circuit->barcode_process_2 ?? '' }}</div>
                    </td>
                    <td class="sp-label">FAMILY</td>
                    <td class="sp-label">TO MESIN</td>
                    <td rowspan="6" class="sp-qr-cell" style="vertical-align: bottom; padding-bottom: 4px;">
                        <div class="sp-qr-label">QRCODE KANBAN</div>
                        @if(!empty($circuit->qr_code_path))
                            <img src="{{ $circuit->qr_code_path }}" alt="QR Kanban" class="sp-qr-img">
                        @endif
                        <div class="sp-qr-text">{{ $circuit->barcode_kanban ?? '' }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="sp-label text-left">STRIP</td>
                    <td class="sp-value text-left">{{ $circuit->strip_2 ?? '' }}</td>
                    {{-- Family dilipat (maks. 2 baris untuk family terpanjang 27 karakter) supaya kolom tidak melebar --}}
                    <td rowspan="2" class="sp-value"><span class="sp-wrap" style="font-size: 16px;">{{ $circuit->family ?? '' }}</span></td>
                    <td rowspan="2" class="sp-value" style="font-size: 18px;">{{ $circuit->t01 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC1</td>
                    <td class="sp-value text-left">{{ $circuit->acc_2 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC2</td>
                    <td class="sp-value text-left">{{ $circuit->acc_2a ?? '' }}</td>
                    <td colspan="2" class="sp-label text-left">NOTE</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC3</td>
                    <td class="sp-value text-left">{{ $circuit->acc_2b ?? '' }}</td>
                    <td colspan="2" rowspan="2" class="text-left" style="vertical-align: top;"><span class="sp-note" style="max-width: 270px;">{{ $circuit->released_note ?? '' }}</span></td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC4</td>
                    <td class="sp-value text-left">{{ $circuit->acc_2c ?? '' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="special-punch-strip">
        <div class="punch-circle"></div>
    </div>
</div>
@endforeach
