{{-- CIRCUIT CUTTING_MINICOAX Print Template. 1px = 1 dot @203dpi, landscape, tinggi 576px = 80mm.
     8 kolom. Dari atas ke bawah:
       judul + MACH NO                                   1 baris 36 px
       spesifikasi kabel                                 2 baris 29 px
       sisi A | QR proses A | QTY..TO MESIN | QR kanban  6 baris 29 px
       ADDRESS STORE selebar tiket: label 24 px, barcode Code 39 70 px, isi 24 px
       sisi B | QR proses B | alamat, family, note       6 baris 29 px
     Barcode alamat (to_store) panjangnya ~40 dot per karakter, jadi diberi seluruh
     lebar tiket; dengan begitu panjang tiket ditentukan barcode itu saja. QR proses A dan QR
     kanban rata atas, QR proses B rata bawah, sehingga tiap QR berjarak >= 40 dot dan satu
     baris teks dari barcode. QR proses A dan QR kanban dipisah blok teks. --}}
@include('schedule.ekanban_circuit.partials.ticket_special_style')

@foreach($circuits as $circuit)
@php
    // Judul mengikuti family master circuit (mis. J69P-MINICOAX); kata MINICOAX selalu ada.
    // Satu baris; huruf mengecil untuk family panjang supaya sel judul tidak melebarkan tiket.
    $mcxFamily = trim($circuit->family ?: ($circuit->carline ?? ''));
    $mcxTitle = trim((stripos($mcxFamily, 'MINICOAX') === false ? 'KANBAN CUTTING MINICOAX ' : 'KANBAN CUTTING ') . $mcxFamily);
    $mcxTitleSize = mb_strlen($mcxTitle) <= 30 ? 28 : (mb_strlen($mcxTitle) <= 40 ? 22 : 18);
    // Barcode alamat menempati kolom 1-8 (40+100+210+80+70+120+215+col8) dan harus tampil 1:1
    // dengan quiet zone 30 px kiri-kanan (+2 border); kolom 8 melebar bila alamatnya panjang.
    $mcxCol8 = max(150, ($circuit->barcode_store_width ?? 0) + 2 * \App\Helpers\BarcodeHelper::CODE39_QUIET_ZONE + 2 - (40 + 100 + 210 + 80 + 70 + 120 + 215));
@endphp
<div class="ticket special-print-wrapper" data-orientation="landscape">
    <div class="ticket-special-print">
        <table class="rows-mcx">
            <colgroup>
                <col style="width: 40px">   {{-- Col 1: sisi A/B --}}
                <col style="width: 100px">  {{-- Col 2: label TERMINAL/STRIP/ACC --}}
                <col style="width: 210px">  {{-- Col 3: nilai / CUST NO --}}
                <col style="width: 80px">   {{-- Col 4: KIND        } QR proses A/B --}}
                <col style="width: 70px">   {{-- Col 5: SIZE        } --}}
                <col style="width: 120px">  {{-- Col 6: COLOUR / label --}}
                <col style="width: 215px">  {{-- Col 7: C/L / nilai --}}
                <col style="width: {{ $mcxCol8 }}px">  {{-- Col 8: SEQUENCE / QR kanban (min 150, melebar ikut barcode alamat) --}}
            </colgroup>
            <tbody>
                {{-- judul | MACH NO --}}
                <tr class="title-row">
                    <td colspan="6" class="sp-title" style="font-size: {{ $mcxTitleSize }}px;">{{ $mcxTitle }}</td>
                    <td colspan="2" class="sp-big" style="font-size: 26px;"><span class="sp-label" style="margin-right: 8px;">MACH NO.</span>{{ $circuit->machine ?? '-' }}</td>
                </tr>

                {{-- spesifikasi kabel --}}
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

                {{-- sisi A | QR proses A (rata atas) | QTY, ISSUE NO, ISSUE DATE, CV NO, TO MESIN | QR kanban (rata atas) --}}
                <tr>
                    <td rowspan="6" class="sp-section">A</td>
                    <td class="sp-label text-left">TERMINAL</td>
                    <td class="sp-value text-left{{ !empty($circuit->gold_1) ? ' sp-inv' : '' }}">{{ $circuit->terminal_1 ?? '' }}@include('schedule.ekanban_circuit.partials.term_star', ['star' => $circuit->note_1 ?? null])</td>
                    <td colspan="2" rowspan="6" class="sp-qr-cell" style="vertical-align: top;">
                        <div class="sp-qr-label">QR PROSES A</div>
                        @if(!empty($circuit->qr_process_path))
                            <img src="{{ $circuit->qr_process_path }}" alt="QR Proses A" class="sp-qr-img">
                        @endif
                        <div class="sp-qr-text">{{ $circuit->barcode_process ?? '' }}</div>
                    </td>
                    <td rowspan="2" class="sp-label">QTY</td>
                    <td rowspan="2" class="sp-big" style="font-size: 40px;">{{ $circuit->qty ?? '-' }}</td>
                    <td rowspan="6" class="sp-qr-cell" style="vertical-align: top;">
                        <div class="sp-qr-label">QRCODE KANBAN</div>
                        @if(!empty($circuit->qr_code_path))
                            <img src="{{ $circuit->qr_code_path }}" alt="QR Kanban" class="sp-qr-img">
                        @endif
                        <div class="sp-qr-text">{{ $circuit->barcode_kanban ?? '' }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="sp-label text-left">STRIP</td>
                    <td class="sp-value text-left">{{ $circuit->strip_1 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC1</td>
                    <td class="sp-value text-left">{{ $circuit->acc_1 ?? '' }}</td>
                    <td class="sp-label">ISSUE NO.</td>
                    <td class="sp-value">{{ $circuit->issue ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC2</td>
                    <td class="sp-value text-left">{{ $circuit->acc_1a ?? '' }}</td>
                    <td class="sp-label">ISSUE DATE</td>
                    <td class="sp-value">{{ strtoupper(\Carbon\Carbon::now()->format("d M'y")) }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC3</td>
                    <td class="sp-value text-left">{{ $circuit->acc_1b ?? '' }}</td>
                    <td class="sp-label">CV NO.</td>
                    <td class="sp-value">{{ $circuit->conveyor ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC4</td>
                    <td class="sp-value text-left">{{ $circuit->acc_1c ?? '' }}</td>
                    <td class="sp-label">TO MESIN</td>
                    <td class="sp-value">{{ $circuit->t01 ?? '' }}</td>
                </tr>

                {{-- ADDRESS STORE selebar tiket: label | barcode Code 39 1:1 | isi barcode --}}
                <tr class="addr-text">
                    <td colspan="8" class="sp-label">ADDRESS STORE</td>
                </tr>
                <tr class="addr-bar">
                    <td colspan="8" class="sp-barcode-cell">
                        @if(!empty($circuit->barcode_store_path))
                            <img src="{{ $circuit->barcode_store_path }}" alt="Barcode Address Store" style="margin: 0 auto;">
                        @endif
                    </td>
                </tr>
                <tr class="addr-text">
                    <td colspan="8" class="sp-value" style="font-size: 18px;">{{ $circuit->barcode_store_data ?? '' }}</td>
                </tr>

                {{-- sisi B | QR proses B (rata bawah) | alamat lengkap, family, note --}}
                <tr>
                    <td rowspan="6" class="sp-section black-bg">B</td>
                    <td class="sp-label text-left">TERMINAL</td>
                    <td class="sp-value text-left{{ !empty($circuit->gold_2) ? ' sp-inv' : '' }}">{{ $circuit->terminal_2 ?? '' }}@include('schedule.ekanban_circuit.partials.term_star', ['star' => $circuit->note_2 ?? null])</td>
                    <td colspan="2" rowspan="6" class="sp-qr-cell" style="vertical-align: bottom;">
                        <div class="sp-qr-label">QR PROSES B</div>
                        @if(!empty($circuit->qr_process_2_path))
                            <img src="{{ $circuit->qr_process_2_path }}" alt="QR Proses B" class="sp-qr-img">
                        @endif
                        <div class="sp-qr-text">{{ $circuit->barcode_process_2 ?? '' }}</div>
                    </td>
                    {{-- Isi To Store lengkap (barcode hanya memuat bagian yang bisa di-encode Code 39) --}}
                    <td rowspan="2" class="sp-label">ADDR. STORE</td>
                    <td colspan="2" rowspan="2" class="sp-value text-left"><span class="sp-wrap" style="max-width: 350px; margin: 0;">{{ $circuit->to_store ?? '' }}</span></td>
                </tr>
                <tr>
                    <td class="sp-label text-left">STRIP</td>
                    <td class="sp-value text-left">{{ $circuit->strip_2 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC1</td>
                    <td class="sp-value text-left">{{ $circuit->acc_2 ?? '' }}</td>
                    <td rowspan="2" class="sp-label">FAMILY</td>
                    {{-- Family dilipat (maks. 2 baris) supaya kolom tidak melebar --}}
                    <td colspan="2" rowspan="2" class="sp-value text-left"><span class="sp-wrap" style="font-size: 20px; max-width: 350px; margin: 0;">{{ $circuit->family ?? '' }}</span></td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC2</td>
                    <td class="sp-value text-left">{{ $circuit->acc_2a ?? '' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">ACC3</td>
                    <td class="sp-value text-left">{{ $circuit->acc_2b ?? '' }}</td>
                    <td rowspan="2" class="sp-label">NOTE</td>
                    <td colspan="2" rowspan="2" class="text-left"><span class="sp-note" style="max-width: 350px; font-size: 16px;">{{ $circuit->released_note ?? '' }}</span></td>
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
