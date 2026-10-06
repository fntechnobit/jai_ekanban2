{{--
    CSS dasar tiket e-kanban shikake (Shield, Bonder, Dbl Crimp, Joint).
    $p = prefix kelas per proses: shield | bonder | dbl-crimp | joint
         wrapper  .{p}-print-wrapper   (punya kelas .ticket, yang di-capture print_machine)
         tiket    .ticket-{p}-print
         punch    .{p}-punch-strip

    Aturan tata letak (berlaku untuk keempat tiket):
    - Tinggi tiket terkunci 576 px = 576 dot = area cetak kertas thermal 80 mm. Yang boleh
      bertambah hanya panjang tiket di gulungan, jadi teks satu baris TIDAK boleh terlipat
      (white-space: nowrap) dan kontainer memakai width: max-content.
    - Semua baris isi sama tinggi: 14 baris x 37 px. Header menyerap sisanya (52 px) supaya
      tinggi baris tetap bilangan bulat dot dan garis tabel tercetak tajam:
      52 + 14 x 37 + border = 572 px (+ border kontainer 2 x 2 = 576).
    - QR dan barcode menempati beberapa baris utuh dan tidak boleh bersebelahan/berdekatan;
      ukuran QR tidak boleh di bawah ukuran tiket lama.
    - Strip punch 30 mm + lingkaran hitam 15 mm di sisi kanan wajib ada.
    - Reset CSS dibatasi di dalam wrapper supaya template ini aman dipakai juga untuk
      preview di modal tanpa mengubah tampilan halaman.
--}}
<style>
.{{ $p }}-print-wrapper,
.{{ $p }}-print-wrapper * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.{{ $p }}-print-wrapper {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    justify-content: flex-start;
    align-items: stretch;
    height: 576px;
    background: white;
    page-break-after: always;
}

.{{ $p }}-print-wrapper .shikake-image-section {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    background: white;
    height: 100%;
}

.{{ $p }}-print-wrapper .shikake-image-section img {
    height: 100%;
    width: auto;
    object-fit: contain;
    display: block;
    /* Enhance drawing visibility for thermal printing */
    filter: contrast(2.0) brightness(0.95);
    -webkit-filter: contrast(2.0) brightness(0.95);
}

.ticket-{{ $p }}-print {
    /* Lebar mengikuti isi, JANGAN dikunci px tetap: isi yang meluber dari kotak ini
       hilang tanpa error saat html2canvas meng-capture tiket. */
    width: max-content;
    min-height: 100%;
    flex-shrink: 0;
    background: white;
    border: 2px solid #000;
    font-family: Arial, sans-serif;
}

.ticket-{{ $p }}-print table {
    width: auto;
    height: 100%;
    border-collapse: collapse;
    table-layout: auto;
}

.ticket-{{ $p }}-print th,
.ticket-{{ $p }}-print td {
    border: 1px solid #000;
    padding: 2px 4px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.2;
    font-size: 18px;
}

.ticket-{{ $p }}-print thead th {
    height: 52px;
    font-weight: bold;
    font-size: 28px;
    padding: 4px 12px;
    border: 2px solid #000;
    white-space: nowrap;
}

.ticket-{{ $p }}-print tbody tr {
    height: 37px;
}

.ticket-{{ $p }}-print .label-cell {
    font-weight: 500;
    font-size: 14px;
    white-space: nowrap;
}

.ticket-{{ $p }}-print .value-cell {
    font-weight: bold;
    font-size: 18px;
    white-space: nowrap;
}

/* Address store: dibaca petugas store, dicetak lebih besar */
.ticket-{{ $p }}-print .store-cell {
    font-weight: bold;
    font-size: 24px;
    white-space: nowrap;
}

/* Family / CV / tanggal / note yang ditumpuk satu baris per nilai */
.ticket-{{ $p }}-print .one-cell {
    font-weight: bold;
    font-size: 16px;
    white-space: nowrap;
}

/* Hanya sel yang mencakup beberapa baris (rowspan) yang boleh terlipat */
.ticket-{{ $p }}-print .stack-cell {
    font-weight: bold;
    font-size: 15px;
    white-space: normal;
    overflow-wrap: break-word;
    min-width: 120px;
}

.ticket-{{ $p }}-print .qrcode-cell {
    padding: 1px;
}

/* PNG QR sudah membawa quiet zone 4 modul sendiri, jadi teks boleh rapat ke gambar */
.ticket-{{ $p }}-print .qr-img {
    display: block;
    margin: 0 auto;
}

.ticket-{{ $p }}-print .qr-label,
.ticket-{{ $p }}-print .qr-text {
    font-weight: bold;
    line-height: 1;
}

.ticket-{{ $p }}-print .qr-label {
    font-size: 12px;
}

.ticket-{{ $p }}-print .qr-text {
    font-size: 9px;
}

.ticket-{{ $p }}-print .qrcode-placeholder {
    border: 1px solid #000;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.{{ $p }}-punch-strip {
    /* Jarak 5mm antara tabel kanban dan lingkaran blok hitam; lebar strip tetap 30mm */
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

.{{ $p }}-punch-strip .punch-circle {
    width: 15mm;
    height: 15mm;
    border-radius: 50%;
    background-color: #000;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    flex-shrink: 0;
}

@media print {
    .{{ $p }}-print-wrapper {
        page-break-after: always;
        page-break-inside: avoid;
    }

    .ticket-{{ $p }}-print th,
    .ticket-{{ $p }}-print td,
    .{{ $p }}-punch-strip .punch-circle {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
