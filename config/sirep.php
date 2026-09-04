<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sumber data listing
    |--------------------------------------------------------------------------
    |
    | 'api' = ambil dari REST API SIREP (mode utama jai_ekanban2)
    | 'db'  = ambil langsung dari database SIREP lama (perilaku jai_ekanban)
    |
    | Jalur 'db' dipertahankan sebagai cadangan: bila API bermasalah, cukup
    | ubah LISTING_SOURCE di .env tanpa mengubah kode.
    |
    */
    'listing_source' => env('LISTING_SOURCE', 'api'),

    /*
    |--------------------------------------------------------------------------
    | Koneksi API
    |--------------------------------------------------------------------------
    */
    'api' => [
        'base_url'    => env('SIREP_API_BASE_URL', 'http://10.62.230.51/sirep-backend/public/api/shared'),
        'timeout'     => (int) env('SIREP_API_TIMEOUT', 30),
        'retry'       => (int) env('SIREP_API_RETRY', 3),
        'retry_delay' => (int) env('SIREP_API_RETRY_DELAY', 1000), // milidetik
        'token'       => env('SIREP_API_TOKEN'),                   // kosong = API tanpa autentikasi

        // Jumlah permintaan yang dijalankan bersamaan.
        'concurrency' => (int) env('SIREP_API_CONCURRENCY', 8),

        // Rentang tanggal dipecah menjadi jendela sekian hari per permintaan,
        // agar ukuran respons tetap kecil selama API belum menyediakan paginasi.
        'chunk_days'  => (int) env('SIREP_API_CHUNK_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Aturan konversi field
    |--------------------------------------------------------------------------
    |
    | API SIREP memisahkan packing dan pallet menjadi versi 'sea' dan 'air',
    | sedangkan listing_stage hanya punya satu kolom untuk masing-masing.
    | Aturan di bawah menentukan mana yang dipakai.
    |
    | BELUM DIKONFIRMASI tim PPC — nilai ini sengaja dibuat dapat diubah agar
    | dapat disesuaikan setelah hasil uji banding dengan SIREP lama diketahui.
    |
    */
    'mapping' => [
        // Sumber nilai snp/snpa/plt ditentukan oleh field shipping_method.
        // true  = shipping_method menentukan (sea -> *_sea, air -> *_air)
        // false = selalu pakai versi 'sea'
        'use_shipping_method' => true,

        // shipping_method (teks) -> kolom mode (angka)
        'mode_map' => [
            'sea' => 1,
            'air' => 2,
        ],

        // Nilai mode bila shipping_method tidak dikenal
        'mode_default' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Kapasitas & jumlah shift
    |--------------------------------------------------------------------------
    |
    | Aturan dari tim PPC (dikonfirmasi lewat 3 contoh kasus, cap 136):
    |
    |   normal_capacity dari API = kapasitas conveyor untuk SATU shift.
    |   CO1-4 = floor(kapasitas/4), sisa pembagian masuk CO4.
    |   CO5 nominal = round(7/8 × kapasitas/4)  — maksimum CO5 adalah 87,5% CO normal.
    |
    |   is_overtime menentukan KAPASITAS EFEKTIF satu shift, yaitu dasar penentuan
    |   jumlah shift:
    |     is_overtime = true   -> kapasitas efektif = kapasitas + CO5 nominal
    |     is_overtime = false  -> kapasitas efektif = kapasitas
    |
    |   jumlah shift = ceil(qty listing / kapasitas efektif satu shift), dibatasi max_shift
    |
    |   Pengisian CO5 sendiri TIDAK bergantung is_overtime. Bila listing tidak muat di
    |   CO1-4 seluruh shift yang berjalan, CO5 dibuka sebagai lembur implisit:
    |     shift bukan terakhir : CO5 <= nominal (87,5% CO normal)
    |     shift terakhir       : CO5 = seluruh sisa (catch-all)
    |   Hari yang memakai CO5 tanpa penanda lembur ditandai "over tanpa OT" di layar
    |   verifikasi agar diperiksa manual.
    |
    | Contoh acuan dari PPC (kapasitas 136 -> CO1-4 = 34, CO5 nominal = 30):
    |   qty 160, overtime ya    -> 1 shift: CO1-4 34 · CO5 24  (catch-all, tak dibatasi nominal)
    |   qty 160, overtime tidak -> 2 shift: S1 CO1-4 34 · S2 CO1 24
    |   qty 310, overtime ya    -> 2 shift: S1 CO1-4 34 + CO5 30 · S2 CO1-4 34 + CO5 8
    |   qty 310, overtime tidak -> 2 shift: sama seperti di atas (lembur implisit),
    |                              ditandai "over tanpa OT" di layar verifikasi
    |
    */
    'capacity' => [
        // Batas atas jumlah shift dalam satu hari. Menggantikan master_conveyor.shift_qty
        // yang sudah dihapus: jumlah shift kini diturunkan per tanggal, bukan disimpan
        // per conveyor, tetapi tetap perlu batas agar demand ekstrem tidak menghasilkan
        // shift 3, 4, dst yang tidak ada di lapangan.
        'max_shift' => (int) env('SIREP_MAX_SHIFT', 2),

        // Batas CO5 sebagai rasio terhadap CO normal (kapasitas/4).
        // Aturan PPC: 7/8 = 87,5%. Cocok dengan contoh acuan di atas —
        // kapasitas 136 -> round(0.875 × 34) = round(29.75) = 30.
        'co5_ratio' => (float) env('SIREP_CO5_RATIO', 7 / 8),

        // Pembulatan nominal CO5: 'round' atau 'floor'.
        //
        // CATATAN: `overtime_capacity` dari API SIREP TIDAK dipakai sebagai batas CO5.
        // Untuk kapasitas 136 SIREP mengirim 160 (setara CO5 = 24), sedangkan aturan
        // PPC memberi CO5 nominal 30. Field itu hanya informatif.
        'co5_rounding' => env('SIREP_CO5_ROUNDING', 'round'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pengaman rekonsiliasi
    |--------------------------------------------------------------------------
    |
    | API SIREP belum menyediakan penanda perubahan (updated_at/revision) maupun
    | penanda pembatalan. Karena itu sinkronisasi bekerja dengan membandingkan
    | seluruh isi rentang: baris yang hilang dari respons dianggap dibatalkan.
    |
    | Pengaman berikut mencegah gangguan API terbaca sebagai "semua dibatalkan".
    |
    */
    'reconcile' => [
        // Hapus baris staging yang tidak ada lagi di respons API.
        'delete_missing' => env('SIREP_RECONCILE_DELETE', true),

        // Jangan menghapus apa pun bila API mengembalikan 0 baris untuk sebuah
        // conveyor padahal staging sebelumnya berisi data.
        'skip_delete_on_empty' => true,

        // Bila jumlah baris dari API turun lebih dari sekian persen dibanding
        // isi staging saat ini, penghapusan dibatalkan dan dilaporkan sebagai
        // peringatan agar dapat diperiksa manual.
        'max_shrink_percent' => (int) env('SIREP_RECONCILE_MAX_SHRINK', 50),
    ],

];
