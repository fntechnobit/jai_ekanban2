{{-- CIRCUIT CUTTING_JAM (JAM Twist) Print Template --}}
{{-- 1 kanban = 1 pasang circuit (dipotong & di-twist di mesin JAM). 1px = 1 dot at 203dpi --}}
{{-- 11 kolom, landscape, tinggi 576px = 80mm. Header 36 + 10 baris x 36 + 4 baris bawah x 42 --}}
{{-- Kanan atas ke bawah: barcode mesin 1 | MACHINE, SEQ | barcode mesin 2 | STORE | Qty, Issue, ADDR + QR drawing.
     Bawah: QR mesin | CV, Family, Date, Note | QR kanban | Qty, Issue, ADDR | QR drawing. Setiap kode
     dipisah blok teks dari kode di sebelah/atas/bawahnya supaya scanner tidak menangkap kode lain. --}}
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.jam-print-wrapper {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 0;
    margin: 0;
    padding: 0;
    justify-content: flex-start;
    align-items: stretch;
    background: white;
    page-break-after: always;
    height: 576px;
}

.ticket-jam-print {
    /* Lebar mengikuti isi tabel (max-content), bukan angka tetap: isi yang meluber
       keluar kotak container hilang saat html2canvas capture. */
    width: max-content;
    height: 100%;
    flex-shrink: 0;
    background: white;
    margin: 0;
    padding: 0;
    border: 2px solid #000;
    overflow: visible;
    font-family: Arial, sans-serif;
}

.ticket-jam-print table {
    /* auto: colgroup = lebar minimum, kolom melebar mengikuti isi. Tinggi tiket terkunci,
       jadi teks panjang memperpanjang tiket, bukan melipat baris. */
    width: auto;
    height: 100%;
    border-collapse: collapse;
    table-layout: auto;
}

.ticket-jam-print th,
.ticket-jam-print td {
    border: 1px solid #000;
    padding: 2px 4px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.1;
    font-size: 22px;
    box-sizing: border-box;
    white-space: nowrap;
}

/* Header 36 px + 10 baris x 36 px + 4 baris bawah (QR) x 42 px + border = 572 px.
   Baris bawah lebih tinggi supaya QR 128 px muat bersama label dan isinya dengan jarak
   dari garis. Nilai circuit memakai 2 baris (rowspan). */
.ticket-jam-print thead th {
    height: 36px;
    font-weight: bold;
    font-size: 26px;
    padding: 4px 8px;
    border: 2px solid #000;
}

.ticket-jam-print thead th.jam-twist-no {
    font-size: 22px;
}

.ticket-jam-print thead th.jam-twist-no span {
    font-size: 28px;
    margin-left: 6px;
}

.ticket-jam-print tbody tr {
    height: 36px;
}

.ticket-jam-print tbody tr.jam-bottom {
    height: 42px;
}

.ticket-jam-print .jam-label {
    font-weight: 500;
    font-size: 18px;
}

/* line-height 1: nilai 30 px di sel 1 baris harus muat 36 px
   (30 + padding 4 + border), kalau tidak baris itu melebar dan tabel > 572 px. */
.ticket-jam-print .jam-value {
    font-weight: bold;
    font-size: 30px;
    line-height: 1;
}

.ticket-jam-print .jam-value-sm {
    font-weight: bold;
    font-size: 22px;
}

/* SIZE putih di atas blok hitam = penanda kabel CIVUS 0.35; size/kind lain tanpa blok hitam */
.ticket-jam-print .jam-inv {
    background-color: #000;
    color: #fff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.ticket-jam-print .jam-section {
    font-weight: bold;
    font-size: 28px;
    padding: 2px;
}

.ticket-jam-print .jam-section.black-bg {
    background-color: #000;
    color: #fff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* Released note boleh 2 baris di dalam satu baris */
.ticket-jam-print td.note-cell {
    white-space: normal;
    font-size: 15px;
    line-height: 1;
    min-width: 200px;
}

.text-left {
    text-align: left !important;
}

/* Tiga QR (mesin, kanban, drawing) 128 px di sel 4 baris x 42 px (168 px): label 12 px
   di atas dan isi 12 px di bawah, masing-masing berjarak 5 px dari garis sel.
   Ketiganya dipisah blok teks. */
.ticket-jam-print .jam-qr-cell {
    padding: 5px 4px;
    vertical-align: middle;
    text-align: center;
}

.ticket-jam-print .jam-qr-label,
.ticket-jam-print .jam-qr-text {
    font-size: 12px;
    font-weight: bold;
    line-height: 1;
}

.ticket-jam-print .jam-qr-label {
    margin-bottom: 2px;
}

.ticket-jam-print .jam-qr-text {
    margin-top: 2px;
}

.ticket-jam-print .jam-qr-img {
    width: 128px;
    height: 128px;
    display: block;
    margin: 0 auto;
}

/* Padding horizontal 30px = quiet zone minimum Code 39 (10x bar sempit 3 dot).
   Kedua barcode rata tengah (mendatar & menurun) di selnya; jarak antar barcode dijaga
   oleh baris MACHINE/SEQ di antaranya (>= 116 dot), bukan lagi oleh rata atas/bawah. */
.ticket-jam-print .jam-barcode-cell {
    padding: 2px 30px;
    text-align: center;
    vertical-align: middle;
}

/* Barcode Code 39 WAJIB tampil 1:1 (ukuran asli PNG = dot printer). Jangan beri
   width %/max-height: barcode 1D yang diperkecil membuat bar melebur saat
   di-threshold hitam-putih untuk thermal 203dpi dan gagal discan. */
.ticket-jam-print .jam-barcode-cell img {
    width: auto;
    height: auto;
    max-width: none;
    display: block;
    margin: 2px auto;
    image-rendering: pixelated;
}

.ticket-jam-print .jam-barcode-label {
    font-size: 16px;
    font-weight: bold;
    line-height: 1.1;
}

.jam-punch-strip {
    /* Jarak 5mm antara tabel kanban dan lingkaran blok hitam; strip tetap 30mm. */
    padding-left: 5mm;
    width: 30mm;
    min-width: 30mm;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    background: white;
    height: 100%;
}

.jam-punch-strip .punch-circle {
    width: 15mm;
    height: 15mm;
    border-radius: 50%;
    background-color: #000;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    flex-shrink: 0;
}

@media print {
    .jam-print-wrapper {
        page-break-after: always;
        page-break-inside: avoid;
    }

    .ticket-jam-print th,
    .ticket-jam-print td {
        border: 1px solid #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    @page {
        size: landscape;
        margin: 1mm;
    }
}
</style>

@php
    // Blok hitam di SIZE hanya untuk kabel CIVUS 0.35 (penanda dari user); "0,35" juga dikenali.
    $isCivus035 = fn ($kind, $size) => strcasecmp(trim((string) $kind), 'CIVUS') === 0
        && abs((float) str_replace(',', '.', (string) $size) - 0.35) < 0.0001;
@endphp
@foreach($circuits as $circuit)
@php
    // Cell barcode = kolom 9+10+11 (96+col10+140). Kolom 10 dilebarkan sampai barcode Code 39
    // terlebar muat 1:1 plus quiet zone 30px kiri-kanan (+2 border).
    $jamBarcodeWidth = max($circuit->barcode_mesin_width ?? 0, $circuit->barcode_mesin_2_width ?? 0);
    $jamCol10 = max(110, $jamBarcodeWidth + 2 * \App\Helpers\BarcodeHelper::CODE39_QUIET_ZONE + 2 - (96 + 140));
    $jamTitle = trim('E-KANBAN CUTTING JAM TWIST ' . ($circuit->carline_name ?: $circuit->carline));
    $goldStyle = 'background-color:#000;color:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;';
@endphp
<div class="ticket jam-print-wrapper" data-orientation="landscape">
    <div class="ticket-jam-print">
        <table>
            {{-- colgroup = lebar minimum; kolom melebar sendiri bila isinya lebih panjang --}}
            <colgroup>
                <col style="width: 40px">   {{-- Col 1: Section A/B --}}
                <col style="width: 96px">   {{-- Col 2: Cct Code / Terminal label (1+2 = QR mesin 136 px) --}}
                <col style="width: 76px">   {{-- Col 3: CCT NO / Terminal value / CV No. --}}
                <col style="width: 96px">   {{-- Col 4: CUST NO / Terminal value --}}
                <col style="width: 96px">   {{-- Col 5: KIND / Strip label --}}
                <col style="width: 76px">   {{-- Col 6: SIZE / Strip value --}}
                <col style="width: 90px">   {{-- Col 7: COLOUR / Note label / QR kanban --}}
                <col style="width: 70px">   {{-- Col 8: C/L / Note value / QR kanban --}}
                <col style="width: 96px">   {{-- Col 9: barcode / MACHINE / STORE, Qty, ADDR label --}}
                <col style="width: {{ $jamCol10 }}px">  {{-- Col 10: barcode / MACHINE / nilai (min 110, melebar ikut barcode) --}}
                <col style="width: 140px">  {{-- Col 11: barcode / SEQ / QR drawing --}}
            </colgroup>
            <thead>
                <tr>
                    <th colspan="8">{{ $jamTitle }}</th>
                    <th colspan="3" class="jam-twist-no">TWIST NO :<span>{{ $circuit->shikake_code ?? '' }}</span></th>
                </tr>
            </thead>
            <tbody>
                {{-- Circuit 1 (baris 1-3): label 1 baris + nilai 2 baris | barcode mesin 1, rata tengah --}}
                <tr>
                    <td colspan="2" class="jam-label">Cct Code</td>
                    <td class="jam-label">CCT NO 1</td>
                    <td class="jam-label">CUST NO 1</td>
                    <td class="jam-label">KIND 1</td>
                    <td class="jam-label">SIZE 1</td>
                    <td class="jam-label">COLOUR 1</td>
                    <td class="jam-label">C/L 1</td>
                    <td colspan="3" rowspan="3" class="jam-barcode-cell">
                        <div class="jam-barcode-label">BARCODE MESIN 1</div>
                        @if(isset($circuit->barcode_mesin_path))
                            <img src="{{ $circuit->barcode_mesin_path }}" alt="Barcode Mesin 1">
                            <div class="jam-barcode-label">{{ $circuit->barcode_mesin_data }}</div>
                        @else
                            <div class="jam-value-sm">{{ $circuit->barcode_mesin ?? '' }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td colspan="2" rowspan="2" class="jam-value">{{ $circuit->cct_code ?? '-' }}</td>
                    <td rowspan="2" class="jam-value">{{ $circuit->cct_no ?? '-' }}</td>
                    <td rowspan="2" class="jam-value">{{ $circuit->cust_no ?? '-' }}</td>
                    <td rowspan="2" class="jam-value">{{ $circuit->kind ?? '-' }}</td>
                    <td rowspan="2" class="jam-value{{ $isCivus035($circuit->kind, $circuit->size) ? ' jam-inv' : '' }}">{{ $circuit->size ?? '-' }}</td>
                    <td rowspan="2" class="jam-value">{{ $circuit->col ?? '-' }}</td>
                    <td rowspan="2" class="jam-value">{{ $circuit->cl ?? '-' }}</td>
                </tr>
                <tr></tr>

                {{-- Circuit 2 (baris 4-6) | MACHINE & SEQ: blok teks pemisah kedua barcode mesin --}}
                <tr>
                    <td colspan="2" class="jam-label">Cct Code</td>
                    <td class="jam-label">CCT NO 2</td>
                    <td class="jam-label">CUST NO 2</td>
                    <td class="jam-label">KIND 2</td>
                    <td class="jam-label">SIZE 2</td>
                    <td class="jam-label">COLOUR 2</td>
                    <td class="jam-label">C/L 2</td>
                    <td colspan="2" class="jam-label">MACHINE</td>
                    <td class="jam-label">SEQ.</td>
                </tr>
                <tr>
                    <td colspan="2" rowspan="2" class="jam-value">{{ $circuit->cct_code_2 ?? '-' }}</td>
                    <td rowspan="2" class="jam-value">{{ $circuit->cct_no_2 ?? '-' }}</td>
                    <td rowspan="2" class="jam-value">{{ $circuit->cust_no_2 ?? '-' }}</td>
                    <td rowspan="2" class="jam-value">{{ $circuit->kind_2 ?? '-' }}</td>
                    <td rowspan="2" class="jam-value{{ $isCivus035($circuit->kind_2, $circuit->size_2) ? ' jam-inv' : '' }}">{{ $circuit->size_2 ?? '-' }}</td>
                    <td rowspan="2" class="jam-value">{{ $circuit->col_2 ?? '-' }}</td>
                    <td rowspan="2" class="jam-value">{{ $circuit->cl_2 ?? '-' }}</td>
                    <td colspan="2" rowspan="2" class="jam-value">{{ $circuit->machine ?? '-' }}</td>
                    <td rowspan="2" class="jam-value">{{ $circuit->sequence ?? '-' }}</td>
                </tr>
                <tr></tr>

                {{-- Terminal A (baris 7-8) dan B (baris 9-10) | barcode mesin 2 (baris 7-9, rata tengah) | STORE (baris 10) --}}
                <tr>
                    <td rowspan="2" class="jam-section">A</td>
                    <td rowspan="2" class="jam-label text-left">Terminal</td>
                    <td colspan="2" rowspan="2" class="jam-value text-left"@if(!empty($circuit->gold_1)) style="{{ $goldStyle }}"@endif>{{ $circuit->terminal_1 ?? '' }}@include('schedule.ekanban_circuit.partials.term_star', ['star' => $circuit->note_1 ?? null, 'size' => 100, 'text' => 30])</td>
                    <td rowspan="2" class="jam-label">Strip</td>
                    <td rowspan="2" class="jam-value">{{ $circuit->strip_1 ?? '' }}</td>
                    <td rowspan="2" class="jam-label">Note</td>
                    <td rowspan="2" class="jam-value">{{ $circuit->ta ?? '' }}</td>
                    <td colspan="3" rowspan="3" class="jam-barcode-cell">
                        <div class="jam-barcode-label">BARCODE MESIN 2</div>
                        @if(isset($circuit->barcode_mesin_2_path))
                            <img src="{{ $circuit->barcode_mesin_2_path }}" alt="Barcode Mesin 2">
                            <div class="jam-barcode-label">{{ $circuit->barcode_mesin_2_data }}</div>
                        @else
                            <div class="jam-value-sm">{{ $circuit->barcode_mesin_2 ?? '' }}</div>
                        @endif
                    </td>
                </tr>
                <tr></tr>
                <tr>
                    <td rowspan="2" class="jam-section black-bg">B</td>
                    <td rowspan="2" class="jam-label text-left">Terminal</td>
                    <td colspan="2" rowspan="2" class="jam-value text-left"@if(!empty($circuit->gold_2)) style="{{ $goldStyle }}"@endif>{{ $circuit->terminal_2 ?? '' }}@include('schedule.ekanban_circuit.partials.term_star', ['star' => $circuit->note_2 ?? null, 'size' => 100, 'text' => 30])</td>
                    <td rowspan="2" class="jam-label">Strip</td>
                    <td rowspan="2" class="jam-value">{{ $circuit->strip_2 ?? '' }}</td>
                    <td rowspan="2" class="jam-label">Note</td>
                    <td rowspan="2" class="jam-value">{{ $circuit->tb ?? '' }}</td>
                </tr>
                <tr>
                    <td class="jam-label text-left">STORE</td>
                    <td colspan="2" class="jam-value-sm">{{ $circuit->to_store ?? '' }}</td>
                </tr>

                {{-- Bawah (baris 11-14): QR barcode mesin (isi dari MTC) | CV/Family/Date/Note | QR barcode kanban
                     (format barcode shikake, kode Twist No) | Qty, Issue, ADDR | QR drawing --}}
                <tr class="jam-bottom">
                    <td colspan="2" rowspan="4" class="jam-qr-cell">
                        <div class="jam-qr-label">QRCODE MESIN</div>
                        @if(isset($circuit->qr_mesin_path))
                            <img src="{{ $circuit->qr_mesin_path }}" alt="QR Mesin" class="jam-qr-img">
                        @endif
                        <div class="jam-qr-text">{{ $circuit->qrcode_mesin ?? '' }}</div>
                    </td>
                    <td class="jam-label text-left">CV No.</td>
                    <td colspan="3" class="jam-value-sm text-left">{{ $circuit->conveyor ?? '' }}</td>
                    <td colspan="2" rowspan="4" class="jam-qr-cell">
                        <div class="jam-qr-label">QRCODE KANBAN</div>
                        @if(isset($circuit->qr_code_path))
                            <img src="{{ $circuit->qr_code_path }}" alt="QR Kanban" class="jam-qr-img">
                        @endif
                        <div class="jam-qr-text">{{ $circuit->barcode_kanban ?? '' }}</div>
                    </td>
                    <td class="jam-label">Qty</td>
                    <td class="jam-label">Issue</td>
                    <td rowspan="4" class="jam-qr-cell">
                        <div class="jam-qr-label">QR DRAWING</div>
                        @if(isset($circuit->qr_drawing_path))
                            <img src="{{ $circuit->qr_drawing_path }}" alt="QR Drawing" class="jam-qr-img">
                        @endif
                        <div class="jam-qr-text">{{ $circuit->qrcode_drawing ?? '' }}</div>
                    </td>
                </tr>
                <tr class="jam-bottom">
                    <td class="jam-label text-left">Family</td>
                    <td colspan="3" class="jam-value-sm text-left">{{ $circuit->family ?? '' }}</td>
                    <td rowspan="2" class="jam-value">{{ $circuit->qty ?? '' }}</td>
                    <td rowspan="2" class="jam-value">{{ $circuit->issue ?? '' }}</td>
                </tr>
                <tr class="jam-bottom">
                    <td class="jam-label text-left">Date</td>
                    <td colspan="3" class="jam-value-sm text-left">{{ \Carbon\Carbon::now()->format('d-M-y') }}</td>
                </tr>
                <tr class="jam-bottom">
                    <td class="jam-label text-left">Note</td>
                    <td colspan="3" class="text-left note-cell"><b>{{ $circuit->released_note ?? '' }}</b></td>
                    <td class="jam-label text-left">ADDR</td>
                    <td class="jam-value-sm">{{ $circuit->address ?? '' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="jam-punch-strip">
        <div class="punch-circle"></div>
    </div>
</div>
@endforeach
