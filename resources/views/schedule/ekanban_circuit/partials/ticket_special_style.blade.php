{{-- CSS bersama tiket MINICOAX, ANTENA FEEDER dan BATTERY CABLE. 1px = 1 dot @203dpi,
     landscape: tinggi 576 px = lebar kepala cetak 80 mm, panjang tiket mengikuti isi.
     Tinggi baris diatur per template (class rows-* di <table>). --}}
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.special-print-wrapper {
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

.ticket-special-print {
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
    color: #000;
}

.ticket-special-print table {
    /* auto: colgroup = lebar minimum, kolom melebar mengikuti isi. Tinggi tiket
       terkunci 576 px, jadi teks panjang memperpanjang tiket, bukan menambah baris. */
    width: auto;
    height: 100%;
    border-collapse: collapse;
    table-layout: auto;
}

.ticket-special-print th,
.ticket-special-print td {
    border: 1px solid #000;
    padding: 2px 4px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.1;
    font-size: 20px;
    box-sizing: border-box;
    white-space: nowrap;
}

/* Tinggi baris per template: jumlah baris x tinggi + border = 572 px */
.ticket-special-print table.rows-14-40 tr { height: 40px; }
.ticket-special-print table.rows-16-35 tr { height: 35px; }
.ticket-special-print table.rows-16-35 tr.title-row { height: 38px; }
.ticket-special-print table.rows-14-37 tbody tr { height: 37px; }

.ticket-special-print thead th {
    height: 52px;
    font-weight: bold;
    font-size: 32px;
    padding: 4px 8px;
    border: 2px solid #000;
}

/* Label tanpa latar abu-abu: abu-abu muda hilang saat di-threshold hitam-putih thermal */
.ticket-special-print .sp-label {
    font-weight: bold;
    font-size: 16px;
}

.ticket-special-print .sp-value {
    font-weight: bold;
    font-size: 22px;
}

.ticket-special-print .sp-title {
    font-weight: bold;
    font-size: 30px;
    line-height: 1.05;
    padding: 2px 10px;
}

/* Nilai besar: kode tujuan / part. line-height 1 supaya muat di jatah barisnya. */
.ticket-special-print .sp-big {
    font-weight: bold;
    line-height: 1;
}

.ticket-special-print .sp-section {
    font-weight: bold;
    font-size: 28px;
    width: 40px;
    padding: 2px;
}

.ticket-special-print .sp-inv,
.ticket-special-print .sp-section.black-bg {
    background-color: #000;
    color: #fff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* Released note: dilipat dalam lebar maksimum 360 px (data terpanjang 49 karakter = 2 baris) */
.ticket-special-print .sp-note {
    display: block;
    white-space: normal;
    max-width: 360px;
    font-size: 14px;
    font-weight: bold;
    line-height: 1.05;
    text-align: left;
}

/* Teks yang boleh dilipat di dalam lebar maksimum (max-width diberi per pemakaian), dipotong
   di spasi atau setelah tanda hubung; kode tanpa celah dipotong di mana saja. Lebar tabel
   mengikuti isi, jadi tanpa max-width teks panjang memperlebar kolom, bukan dilipat. */
.ticket-special-print .sp-wrap {
    display: block;
    white-space: normal;
    word-break: normal;
    overflow-wrap: anywhere;
    line-height: 1.1;
    margin: 0 auto;
}

.text-left {
    text-align: left !important;
}

/* QR: label & isi kode di dalam sel yang sama. PNG QR sudah membawa quiet zone sendiri. */
.ticket-special-print .sp-qr-cell {
    padding: 0 4px;
    vertical-align: middle;
    text-align: center;
}

.ticket-special-print .sp-qr-label,
.ticket-special-print .sp-qr-text {
    font-size: 12px;
    font-weight: bold;
    line-height: 1;
}

.ticket-special-print .sp-qr-text {
    word-break: break-all;
}

.ticket-special-print .sp-qr-img {
    width: 128px;
    height: 128px;
    display: block;
    margin: 0 auto;
}

/* Padding horizontal 30px = quiet zone minimum Code 39 (10x bar sempit 3 dot). */
.ticket-special-print .sp-barcode-cell {
    padding: 2px 30px;
    vertical-align: middle;
    text-align: center;
}

/* Barcode Code 39 WAJIB tampil 1:1 (ukuran asli PNG = dot printer). Jangan beri
   width %/max-height: barcode 1D yang diperkecil membuat bar melebur saat
   di-threshold hitam-putih untuk thermal 203dpi dan gagal discan. */
.ticket-special-print .sp-barcode-cell img {
    width: auto;
    height: auto;
    max-width: none;
    display: block;
    margin: 2px auto;
    image-rendering: pixelated;
}

.ticket-special-print .sp-barcode-label {
    font-size: 13px;
    font-weight: bold;
    line-height: 1.1;
}

.ticket-special-print .sp-barcode-text {
    font-size: 14px;
    font-weight: bold;
    line-height: 1.1;
}

.special-punch-strip {
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

.special-punch-strip .punch-circle {
    width: 15mm;
    height: 15mm;
    border-radius: 50%;
    background-color: #000;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    flex-shrink: 0;
}

@media print {
    .special-print-wrapper {
        page-break-after: always;
        page-break-inside: avoid;
    }

    .ticket-special-print th,
    .ticket-special-print td {
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
