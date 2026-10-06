{{-- BONDER - template tiket (landscape, tinggi 576 dot = kertas thermal 80 mm).
     Dipakai untuk print (raster QZ) dan preview, supaya preview selalu sama dengan hasil cetak.
     7 kolom x 14 baris @ 37 px. Bagian bawah (4 baris): QR Drawing | family/CV/tanggal/note |
     barcode navigasi | address store + QTY/ISSUE | QR Kanban - kode-kode dipisah blok teks. --}}
@include('schedule.ekanban_shikake.partials.ticket_style', ['p' => 'bonder'])
<style>
/* QR Drawing & QR Kanban 130 px (tiket lama 130 dan 95 px). Sel 4 baris = 148 px, jadi
   label & kode di sel QR dikecilkan ke 8 px tanpa jarak; quiet zone sudah ada di PNG. */
.ticket-bonder-print .qrcode-cell.qr-tight {
    padding: 0;
}

.ticket-bonder-print .qr-img,
.ticket-bonder-print .qrcode-placeholder {
    width: 130px;
    height: 130px;
}

.ticket-bonder-print .qr-tight .qr-label,
.ticket-bonder-print .qr-tight .qr-text {
    font-size: 8px;
}

/* Padding horizontal 30px = quiet zone minimum Code 39 (10x bar sempit 3 dot).
   table-layout:auto membuat cell melebar mengikuti barcode. */
.ticket-bonder-print .barcode-cell {
    padding: 2px 30px;
}

/* Padding horizontal 40px = TEPAT 5mm di kertas (203dpi, 8 dot/mm): jarak barcode
   navigasi ke border selalu 5mm karena cell memeluk barcode-nya. JANGAN turunkan di
   bawah 30px (quiet zone minimum Code 39). */
.ticket-bonder-print .barcode-navigasi-cell {
    padding: 4px 40px;
}

/* Barcode Code 39 SELALU tampil di ukuran asli PNG (1 px = 1 dot printer), tidak pernah
   diperkecil/diperbesar: barcode 1D yang diskalakan melebur saat di-threshold hitam-putih
   untuk thermal 203dpi. JANGAN beri width/max-width/height. PNG tidak punya margin putih
   bawaan, jadi seluruh quiet zone berasal dari padding cell di atas. */
.ticket-bonder-print .barcode-cell img,
.ticket-bonder-print .barcode-navigasi-cell img {
    width: auto;
    height: auto;
    max-width: none;
    display: block;
    margin: 0 auto;
    image-rendering: pixelated;
}

.ticket-bonder-print .barcode-navigasi-cell .qr-label {
    margin-bottom: 2px;
}

.ticket-bonder-print .barcode-text {
    font-size: 12px;
    font-weight: bold;
    margin-top: 1px;
}

.ticket-bonder-print .barcode-placeholder {
    width: 180px;
    height: 60px;
    border: 1px solid #000;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}
</style>
@php
    $releaseDate = $shikake->release_date
        ? \Carbon\Carbon::parse($shikake->release_date)->locale('id')->translatedFormat('d-M-y')
        : '';
@endphp

<div class="ticket bonder-print-wrapper" data-orientation="landscape">
    @if(!empty($shikake->image_path))
    <div class="shikake-image-section">
        <img src="{{ asset($shikake->image_path) }}" alt="Shikake Image">
    </div>
    @endif

    <div class="ticket-bonder-print">
        <table>
            <colgroup>
                <col style="width: 21mm">
                <col style="width: 24mm">
                {{-- kolom 3 & 4 menampung BARCODE NAVIGASI (colspan=2): sengaja sempit supaya
                     lebarnya ditentukan barcode + padding, bukan angka di sini (jarak 5mm). --}}
                <col style="width: 21mm">
                <col style="width: 24mm">
                <col style="width: 30mm">
                <col style="width: 30mm">
                <col style="width: 30mm">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="7">E-KANBAN BONDER {{ $shikake->carline ?? '' }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4" class="label-cell">BONDER NO.</td>
                    <td colspan="3" class="label-cell">ADDRESS S/A</td>
                </tr>
                <tr>
                    <td colspan="4" class="value-cell">{{ $processData->bonder_no ?? '' }}</td>
                    <td colspan="3" class="value-cell">{{ $processData->address ?? '' }}</td>
                </tr>

                <tr>
                    <td class="label-cell">CCT NO.</td>
                    <td class="label-cell">BONDER NO</td>
                    <td class="label-cell">CCT NO.</td>
                    <td class="label-cell">BONDER NO</td>
                    <td colspan="3" class="label-cell">BARCODE PROCESS</td>
                </tr>
                {{-- 7 pasangan CCT/Bonder (sisi A kiri, sisi B kanan); kolom kanan berisi
                     barcode process (3 baris), lalu MACHINE/DIES dan TO MACHINE/SEQ --}}
                @for($i = 1; $i <= 7; $i++)
                <tr>
                    <td class="value-cell">{{ $processData->{'cct_no_a_' . $i} ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->{'bonder_no_a_' . $i} ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->{'cct_no_b_' . $i} ?? '-' }}</td>
                    <td class="value-cell">{{ $processData->{'bonder_no_b_' . $i} ?? '-' }}</td>
                    @if($i === 1)
                    <td colspan="3" rowspan="3" class="barcode-cell">
                        @if(isset($processData->barcode_process_path))
                            <img src="{{ $processData->barcode_process_path }}" alt="Barcode">
                            <div class="barcode-text">{{ $processData->barcode_process_data ?? $processData->barcode_process ?? '' }}</div>
                        @else
                            <div class="barcode-placeholder">BARCODE</div>
                        @endif
                    </td>
                    @elseif($i === 4)
                    <td colspan="2" class="label-cell">MACHINE</td>
                    <td class="label-cell">DIES</td>
                    @elseif($i === 5)
                    <td colspan="2" class="value-cell">{{ $shikake->machine ?? '' }}</td>
                    <td class="value-cell">{{ $processData->dies ?? '' }}</td>
                    @elseif($i === 6)
                    <td colspan="2" class="label-cell">TO MACHINE</td>
                    <td class="label-cell">SEQ</td>
                    @elseif($i === 7)
                    <td colspan="2" class="value-cell">{{ $processData->to_machine ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->sequence ?? '' }}</td>
                    @endif
                </tr>
                @endfor

                {{-- Bawah 4 baris. CV & tanggal ikut ditumpuk di kolom 2 karena barcode
                     navigasi butuh 4 baris penuh (label + Code 39 88 dot + teks). --}}
                <tr>
                    <td rowspan="4" class="qrcode-cell qr-tight">
                        <div class="qr-label">QR</div>
                        @if(isset($processData->qrcode_drawing_path))
                            <img src="{{ $processData->qrcode_drawing_path }}" alt="QR Drawing" class="qr-img">
                        @else
                            <div class="qrcode-placeholder">QR DRAWING</div>
                        @endif
                        <div class="qr-text">{{ $processData->qrcode_drawing ?? '' }}</div>
                    </td>
                    <td class="one-cell">{{ $shikake->family ?? '' }}</td>
                    <td colspan="2" rowspan="4" class="barcode-navigasi-cell">
                        <div class="qr-label">BARCODE NAVIGASI</div>
                        @if(isset($processData->barcode_navigasi_path))
                            <img src="{{ $processData->barcode_navigasi_path }}" alt="Barcode Navigasi">
                            <div class="barcode-text">{{ $processData->barcode_navigasi_data ?? $processData->barcode_navigasi ?? '' }}</div>
                        @else
                            <div class="barcode-placeholder">BARCODE</div>
                        @endif
                    </td>
                    <td colspan="2" class="label-cell">ADDRESS STORE</td>
                    <td rowspan="4" class="qrcode-cell qr-tight">
                        <div class="qr-label">QRCODE KANBAN</div>
                        @if(isset($shikake->qr_code_path))
                            <img src="{{ $shikake->qr_code_path }}" alt="QR Code" class="qr-img">
                        @else
                            <div class="qrcode-placeholder">QR</div>
                        @endif
                        <div class="qr-text">{{ $shikake->barcode_kanban ?? '' }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="one-cell">{{ $shikake->conveyor ?? '' }}</td>
                    <td colspan="2" class="store-cell">{{ $processData->address_store ?? '' }}</td>
                </tr>
                <tr>
                    <td class="one-cell">{{ $releaseDate }}</td>
                    <td class="label-cell">QTY</td>
                    <td class="label-cell">ISSUE</td>
                </tr>
                <tr>
                    <td class="one-cell">{{ $shikake->released_note ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->qty ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->issue ?? '' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Punch strip (RIGHT) - jarak 5mm + lingkaran hitam, sama seperti cutting/twist --}}
    <div class="bonder-punch-strip">
        <div class="punch-circle"></div>
    </div>
</div>
