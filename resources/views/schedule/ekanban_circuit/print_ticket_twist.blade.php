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
    /* Lebar MENGIKUTI ISI TABEL (colgroup), bukan angka tetap.
       Pelajaran dari bug lama "kolom kanan hilang" (BONDER): lebar container
       yang dikunci lebih kecil dari isi tabel membuat tabel meluber, dan bagian
       yang meluber itu ikut hilang saat html2canvas meng-capture kotak elemen.
       Dengan max-content, container selalu memeluk tabel berapa pun colgroup-nya. */
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

.ticket-twist-print table {
    /* auto: colgroup jadi lebar minimum dan kolom melebar mengikuti isi. Tinggi tiket
       terkunci 576 px, jadi teks panjang memperpanjang tiket, bukan melipat baris. */
    width: auto;
    height: 100%;
    border-collapse: collapse;
    table-layout: auto;
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
    white-space: nowrap;
}

/* Semua baris isi sama tinggi: 18 baris x 30 px. Header 30 px:
   30 + 18 x 30 + border = 572 px. */
.ticket-twist-print tbody tr {
    height: 30px;
}

/* Released note boleh 2 baris di dalam satu baris 30 px */
.ticket-twist-print td.note-cell {
    white-space: normal;
    font-size: 13px;
    line-height: 1;
    min-width: 230px;
}

/* TO STORE dicetak besar, boleh terlipat di dalam 5 baris jatahnya */
.ticket-twist-print td.store-big {
    white-space: normal;
    font-size: 26px;
    font-weight: bold;
    line-height: 1.2;
    word-break: break-all;
}

.ticket-twist-print thead th {
    height: 30px;
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 26px;
    padding: 0 6px;
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

.twist-punch-strip {
        /* Jarak 5mm antara tabel kanban dan lingkaran blok hitam.
           box-sizing: border-box membuat lebar strip tetap 30mm,
           lingkaran hanya bergeser 5mm ke kanan. */
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

    .twist-punch-strip .punch-circle {
        width: 15mm;
        height: 15mm;
        border-radius: 50%;
        background-color: #000;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        flex-shrink: 0;
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
    padding: 1px;
    vertical-align: middle;
    text-align: center;
}
/* QR Kanban menempel di dasar selnya supaya menjauh dari barcode twist di atasnya */
.twist-qrcode-cell.qr-bottom {
    vertical-align: bottom;
}
.twist-qr-img {
    /* QR Drawing 165 px dan QR Kanban 160 px (tiket lama 155 px), masing-masing di sel
       6 baris. Ukuran QR tidak boleh di bawah ukuran lama. */
    height: 165px;
    width: 165px;
    display: block;
    margin: 0 auto;
}
.twist-qr-img.qr-kanban {
    height: 160px;
    width: 160px;
}



/* Padding horizontal 30px = quiet zone minimum Code 39 (10x bar sempit 3 dot).
   Selector diawali .ticket-twist-print supaya mengalahkan padding umum
   ".ticket-twist-print td" (1px 3px) - tanpa itu quiet zone hilang. */
.ticket-twist-print .twist-barcode-cell {
    padding: 1px 30px;
    vertical-align: middle;
    text-align: center;
}

/* Barcode Code 39 WAJIB tampil 1:1 (ukuran asli PNG = dot printer). Jangan beri
   width %/max-height: barcode 1D yang diperkecil membuat bar melebur saat
   di-threshold hitam-putih untuk thermal 203dpi dan gagal discan. */
.ticket-twist-print .twist-barcode-cell img {
    width: auto;
    height: auto;
    max-width: none;
    display: block;
    margin: 0 auto;
    image-rendering: pixelated;
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

    .twist-punch-strip {
        padding-left: 5mm;
        display: flex;
        width: 30mm;
        min-width: 30mm;
        flex-shrink: 0;
    }

    .twist-punch-strip .punch-circle {
        background-color: #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

@foreach($circuits as $circuit)
@php
    // Cell barcode mesin & twist = kolom 7+8+9 (92+92+col9). Kolom 9 dilebarkan
    // sampai barcode Code 39 terlebar muat 1:1 plus quiet zone 30px kiri-kanan.
    $twistBarcodeWidth = max($circuit->barcode_mesin_width ?? 0, $circuit->barcode_twist_width ?? 0);
    $twistCol9 = max(180, $twistBarcodeWidth + 2 * \App\Helpers\BarcodeHelper::CODE39_QUIET_ZONE + 2 - (92 + 92));
@endphp
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
            <col style="width: 172px">  {{-- Col 3: Value + QR Drawing 165 px --}}
            <col style="width: 92px">   {{-- Col 4: Label/Value --}}
            <col style="width: 92px">   {{-- Col 5: Label/Value --}}
            <col style="width: 92px">   {{-- Col 6: Value --}}
            <col style="width: 92px">   {{-- Col 7: Value --}}
            <col style="width: 92px">   {{-- Col 8: Value --}}
            <col style="width: {{ $twistCol9 }}px">  {{-- Col 9: Barcode/QR area (min 180, melebar ikut barcode) --}}
        </colgroup>
        <thead>
            <tr>
                {{-- Judul mengikuti family master circuit, bukan carline listing PPC --}}
                <th colspan="7">E-KANBAN CUTTING TWIST {{ $circuit->family ?: ($circuit->carline ?? '') }}</th>
                <th colspan="2">{{ $circuit->shikake_code ?? '' }}</th>
            </tr>
        </thead>
        <tbody>
            @php
                $toStore = $circuit->to_store ?? '';
                $dashPos = strpos($toStore, '-');
                $toStoreLine1 = $dashPos !== false ? substr($toStore, 0, $dashPos) : $toStore;
                $toStoreLine2 = $dashPos !== false ? substr($toStore, $dashPos) : '';
            @endphp
            {{-- Kolom kanan (7-9) dari atas ke bawah: barcode mesin (3 baris) | QTY/ISSUE/M.TWS + ADDR (3 baris teks)
                 | barcode twist (3 baris) | MACH. TWIST/SEQ + TO 1 (3 baris teks) | QR kanban (6 baris).
                 Setiap kode dipisahkan minimal 3 baris teks supaya scanner tidak menangkap kode sebelahnya. --}}
            <tr>
                <td colspan="2" class="twist-label-cell">CCT CODE</td>
                <td class="twist-label-cell">CCT NO</td>
                <td colspan="2" class="twist-label-cell">MACHINE</td>
                <td class="twist-label-cell">SEQ</td>
                <td colspan="3" rowspan="3" class="twist-barcode-cell">
                    @if(isset($circuit->barcode_mesin_path))
                        <img src="{{ $circuit->barcode_mesin_path }}" alt="Barcode Mesin">
                        <div class="twist-barcode-label">{{ $circuit->barcode_mesin_data ?? $circuit->barcode_mesin ?? '' }}</div>
                    @else
                        <div style="font-size:22px;font-weight:bold;">{{ $circuit->barcode_mesin ?? '-' }}</div>
                    @endif
                </td>
            </tr>
            <tr>
                <td colspan="2" class="twist-value-cell">{{ $circuit->cct_code ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->cct_no ?? '-' }}</td>
                <td colspan="2" class="twist-value-cell">{{ $circuit->machine ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->sequence ?? '-' }}</td>
            </tr>
            <tr>
                <td colspan="2" class="twist-label-cell">CUST NO</td>
                <td class="twist-label-cell">KIND</td>
                <td class="twist-label-cell">SIZE</td>
                <td class="twist-label-cell">COLOR</td>
                <td class="twist-label-cell">C/L</td>
            </tr>
            <tr>
                <td colspan="2" class="twist-value-cell">{{ $circuit->cust_no ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->kind ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->size ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->col ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->cl ?? '-' }}</td>
                <td class="twist-label-cell">QTY</td>
                <td class="twist-label-cell">ISSUE</td>
                <td class="twist-label-cell">M. TWS</td>
            </tr>
            {{-- section A --}}
            <tr>
                <td rowspan="4" class="twist-section-label">A</td>
                <td class="twist-label-cell text-left">TERM.</td>
                <td colspan="2" class="twist-value-cell text-left"@if(!empty($circuit->gold_1)) style="background-color:#000;color:white;"@endif>{{ $circuit->terminal_1 ?? '' }}</td>
                <td class="twist-label-cell" style="text-align:center;overflow:hidden;">@if(!empty($circuit->note_1))<span style="display:inline-block;font-size:28px;font-weight:bold;line-height:0.8;margin-bottom:-6px;">{{ $circuit->note_1 }}</span>@endif</td>
                <td class="twist-value-cell text-left"></td>
                <td class="twist-value-cell">{{ $circuit->qty ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->issue ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->memory_twist ?? '-' }}</td>
            </tr>
            <tr>
                <td class="twist-label-cell text-left">ACC 1</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->acc_1a ?? '' }}</td>
                <td class="twist-label-cell text-left">NOTE</td>
                <td class="twist-value-cell text-left">{{ $circuit->ta ?? '' }}</td>
                <td class="twist-label-cell">ADDR.</td>
                <td colspan="2" class="twist-value-cell">{{ $circuit->address ?? '' }}</td>
            </tr>
            <tr>
                <td class="twist-label-cell text-left">ACC 2</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->acc_1 ?? '' }}</td>
                <td class="twist-label-cell text-left">STRIP</td>
                <td class="twist-value-cell text-left">{{ $circuit->strip_1 ?? '' }}</td>
                <td colspan="3" rowspan="3" class="twist-barcode-cell">
                    @if(isset($circuit->barcode_twist_path))
                        <img src="{{ $circuit->barcode_twist_path }}" alt="Barcode Twist">
                        <div class="twist-barcode-label">{{ $circuit->barcode_twist_data ?? $circuit->barcode_twist ?? '' }}</div>
                    @elseif(!empty($circuit->barcode_twist))
                        <div style="font-size:18px;font-weight:bold;">{{ $circuit->barcode_twist }}</div>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="twist-label-cell text-left">TUBE</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->tube_1 ?? '' }}</td>
                <td class="twist-label-cell text-left">MARK</td>
                <td class="twist-value-cell text-left">{{ $circuit->mark_1 ?? '' }}</td>
            </tr>
            {{-- section B --}}
            <tr>
                <td rowspan="4" class="twist-section-label black-bg">B</td>
                <td class="twist-label-cell text-left">TERM.</td>
                <td colspan="2" class="twist-value-cell text-left"@if(!empty($circuit->gold_2)) style="background-color:#000;color:white;"@endif>{{ $circuit->terminal_2 ?? '' }}</td>
                <td class="twist-label-cell text-left" style="overflow:hidden;">@if(!empty($circuit->note_2))<span style="display:inline-block;font-size:28px;font-weight:bold;line-height:0.8;margin-bottom:-6px;">{{ $circuit->note_2 }}</span>@endif</td>
                <td class="twist-value-cell text-left"></td>
            </tr>
            <tr>
                <td class="twist-label-cell text-left">ACC 1</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->acc_2a ?? '' }}</td>
                <td class="twist-label-cell text-left">NOTE</td>
                <td class="twist-value-cell text-left">{{ $circuit->tb ?? '' }}</td>
                <td colspan="2" class="twist-label-cell">MACH. TWIST</td>
                <td class="twist-label-cell">SEQ</td>
            </tr>
            <tr>
                <td class="twist-label-cell text-left">ACC 2</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->acc_2 ?? '' }}</td>
                <td class="twist-label-cell text-left">STRIP</td>
                <td class="twist-value-cell text-left">{{ $circuit->strip_2 ?? '' }}</td>
                <td colspan="2" class="twist-value-cell">{{ $circuit->machine_twist ?? '' }}</td>
                <td class="twist-value-cell">{{ $circuit->sequence_2 ?? '' }}</td>
            </tr>
            <tr>
                <td class="twist-label-cell text-left">TUBE</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->tube_2 ?? '' }}</td>
                <td class="twist-label-cell text-left">MARK</td>
                <td class="twist-value-cell text-left">{{ $circuit->mark_2 ?? '' }}</td>
                <td class="twist-label-cell">DATE</td>
                <td colspan="2" class="twist-value-cell">{{ \Carbon\Carbon::now()->format('d-M-y') }}</td>
            </tr>
            {{-- bawah: TO STORE | QR drawing | blok teks (TO 1-3, CV, family, note) | QR kanban.
                 Blok teks di tengah memisahkan kedua QR. Nilainya rata kiri dan selebar kolom 5-7
                 supaya note lega; QR kanban cukup kolom 8-9. --}}
            <tr>
                <td colspan="2" class="twist-label-cell">TO STORE</td>
                <td rowspan="6" class="twist-qrcode-cell">
                    @if(isset($circuit->qr_qrcode_drawing_path))
                        <img src="{{ $circuit->qr_qrcode_drawing_path }}" alt="QR Drawing" class="twist-qr-img">
                    @else
                        <div style="font-size:12px">{{ $circuit->qrcode_drawing ?? 'DRW' }}</div>
                    @endif
                </td>
                <td class="twist-label-cell">TO 1</td>
                <td colspan="3" class="twist-value-cell text-left">{{ $circuit->t01 ?? '' }}</td>
                <td colspan="2" rowspan="6" class="twist-qrcode-cell qr-bottom">
                    @if(isset($circuit->qr_code_path))
                        <img src="{{ $circuit->qr_code_path }}" alt="QR Kanban" class="twist-qr-img qr-kanban">
                    @endif
                    @if(!empty($circuit->barcode_kanban))
                        <div style="font-size:10px;font-weight:bold;line-height:1;margin:0;">{{ $circuit->barcode_kanban }}</div>
                    @endif
                </td>
            </tr>
            <tr>
                <td colspan="2" rowspan="5" class="twist-value-cell store-big">{{ $toStoreLine1 }}<br>{{ $toStoreLine2 }}</td>
                <td class="twist-label-cell">TO 2</td>
                <td colspan="3" class="twist-value-cell text-left">{{ $circuit->t02 ?? '' }}</td>
            </tr>
            <tr>
                <td class="twist-label-cell">TO 3</td>
                <td colspan="3" class="twist-value-cell text-left">{{ $circuit->t03 ?? '' }}</td>
            </tr>
            <tr>
                <td class="twist-label-cell">CV NO</td>
                <td colspan="3" class="twist-value-cell text-left">{{ $circuit->conveyor ?? '-' }}</td>
            </tr>
            <tr>
                <td class="twist-label-cell">FAMILY</td>
                <td colspan="3" class="twist-value-cell text-left">{{ $circuit->family ?? '-' }}</td>
            </tr>
            <tr>
                <td class="twist-label-cell">NOTE</td>
                <td colspan="3" class="twist-value-cell text-left note-cell">{{ $circuit->released_note ?? '' }}</td>
            </tr>
        </tbody>
    </table>
    </div>
    <div class="twist-punch-strip">
        <div class="punch-circle"></div>
    </div>
</div>
@endforeach
