{{-- CSS tiket CUTTING, dipakai bersama print_ticket (CUTTING) dan print_ticket_ej (CUTTING_EJ) --}}
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.circuit-print-wrapper {
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

.circuit-print-wrapper .circuit-image-section {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    background: white;
    height: 100%;
    margin: 0;
    padding: 0;
}

.circuit-print-wrapper .circuit-image-section img {
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

.ticket-circuit-print {
    /* Lebar MENGIKUTI ISI TABEL (colgroup), bukan angka tetap. Kolom 9 melebar
       mengikuti lebar barcode Code 39 (lihat colgroup), dan container yang dikunci
       lebih sempit dari tabel membuat bagian kanan hilang saat html2canvas capture. */
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

.ticket-circuit-print table {
    width: auto;
    height: 100%;
    border-collapse: collapse;
    /* auto (bukan fixed): colgroup jadi lebar minimum, kolom melebar mengikuti isi.
       Dengan fixed, teks panjang (note, ACC, address) dilipat ke bawah dan tabel bisa
       melebihi 572 px sehingga bagian bawah tiket terpotong. Tinggi tiket terkunci,
       jadi yang boleh bertambah hanya panjang tiket di gulungan. */
    table-layout: auto;
}

.ticket-circuit-print th,
.ticket-circuit-print td {
    border: 1px solid #000;
    padding: 2px 4px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.1;
    font-size: 22px;
    box-sizing: border-box;
    white-space: nowrap;
}

/* Semua baris isi sama tinggi: 14 baris x 37 px. Header menyerap sisanya (52 px) supaya
   tinggi baris tetap bilangan bulat dot: 52 + 14 x 37 + border = 572 px. */
.ticket-circuit-print thead th {
    height: 52px;
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 32px;
    padding: 4px;
    border: 2px solid #000;
}

.ticket-circuit-print tbody tr {
    height: 37px;
}

/* Released note boleh 2 baris di dalam satu baris 37 px */
.ticket-circuit-print td.note-cell {
    white-space: normal;
    font-size: 15px;
    line-height: 1;
    min-width: 260px;
}

.ticket-circuit-print .section-label {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 28px;
    width: 40px;
    padding: 2px;
}
    .circuit-punch-strip {
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

    .circuit-punch-strip .punch-circle {
        width: 15mm;
        height: 15mm;
        border-radius: 50%;
        background-color: #000;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        flex-shrink: 0;
    }
.ticket-circuit-print .section-label.black-bg {
    background-color: #000;
    color: white;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.ticket-circuit-print .label-cell {
    background-color: transparent;
    font-weight: 500;
    font-size: 18px;
}

.ticket-circuit-print .value-cell {
    font-weight: bold;
    font-size: 22px;
}

.text-left {
    text-align: left !important;
}

/* QR Kanban 128 px (tiket lama 110 px) di sel 4 baris: label & kode 9 px tanpa jarak.
   PNG QR sudah membawa quiet zone 4 modul sendiri. */
.ticket-circuit-print .qrcode-cell {
    padding: 0;
    vertical-align: middle;
    text-align: center;
}

.ticket-circuit-print .qr-label {
    font-size: 9px;
    font-weight: bold;
    line-height: 1;
}

.ticket-circuit-print .qr-img {
    width: 128px;
    height: 128px;
    display: block;
    margin: 0 auto;
}

.ticket-circuit-print .qr-text {
    font-size: 9px;
    font-weight: bold;
    line-height: 1;
    word-break: break-all;
}

/* Padding horizontal 30px = quiet zone minimum Code 39 (10x bar sempit 3 dot). */
.ticket-circuit-print .barcode-cell {
    padding: 2px 30px;
    vertical-align: middle;
    text-align: center;
}

/* Barcode Code 39 WAJIB tampil 1:1 (ukuran asli PNG = dot printer). Jangan beri
   width %/max-height: barcode 1D yang diperkecil membuat bar melebur saat
   di-threshold hitam-putih untuk thermal 203dpi dan gagal discan. */
.ticket-circuit-print .barcode-cell img {
    width: auto;
    height: auto;
    max-width: none;
    display: block;
    margin: 0 auto;
    image-rendering: pixelated;
}

.ticket-circuit-print .barcode-label {
    font-size: 14px;
    font-weight: bold;
    margin-top: 2px;
}

/* Thermal Printer Optimization */
@media print {
    body {
        margin: 0;
        padding: 0;
        background: white;
    }

    .circuit-print-wrapper {
        flex-direction: row;
        gap: 0;
        margin: 0;
        justify-content: flex-start;
        page-break-after: always;
        page-break-inside: avoid;
    }

    .circuit-print-wrapper .circuit-image-section {
        display: flex;
        flex-shrink: 0;
        align-items: center;
    }

    .circuit-print-wrapper .circuit-image-section img {
        height: 70mm;
        width: auto;
        object-fit: contain;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .ticket-circuit-print {
        width: auto;
        max-width: none;
        height: auto;
        border: none;
        margin: 0;
        padding: 0;
    }

    .ticket-circuit-print table {
        page-break-inside: avoid;
        width: 100%;
    }

    .ticket-circuit-print th,
    .ticket-circuit-print td {
        border: 1px solid #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .ticket-circuit-print .section-label.black-bg {
        background-color: #000 !important;
        color: white !important;
    }

    @page {
        size: landscape;
        margin: 1mm;
    }

    .circuit-punch-strip {
        padding-left: 5mm;
        display: flex;
        width: 30mm;
        min-width: 30mm;
        flex-shrink: 0;
    }

    .circuit-punch-strip .punch-circle {
        background-color: #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
