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
    |   CO1 = kapasitas - 3 x floor(kapasitas/4)  (menampung terbesar)
    |   CO2-CO4 = floor(kapasitas/4)
    |   CO5 nominal = round(7/8 × kapasitas/4)  — maksimum CO5 adalah 87,5% CO normal.
    |
    |   JUMLAH SHIFT adalah data master (master_conveyor.shift_qty), BUKAN hasil
    |   hitungan. Alokasi selalu mulai dari shift 1; shift 2 hanya dipakai bila
    |   conveyor itu memang dua shift.
    |
    |   is_overtime menentukan boleh atau tidaknya CO5 dibuka:
    |     is_overtime = true   -> CO5 tersedia
    |     is_overtime = false  -> CO5 tertutup, kelebihan mengalir ke CO1 shift berikutnya
    |
    |   Urutan pengisian
    |     1 shift : S1.CO1 -> S1.CO2 -> S1.CO3 -> S1.CO4 -> S1.CO5
    |     2 shift : S1.CO1..CO4 -> S2.CO1..CO4 -> S1.CO5 (<= 7/8) -> S2.CO5 (sisa semua)
    |
    |   Bila listing tetap tidak muat walau seluruh shift penuh dan hari itu tidak
    |   lembur, CO5 shift terakhir tetap menampung sisanya supaya tidak ada listing
    |   yang hilang; layar verifikasi menandainya "over tanpa OT".
    |
    | Contoh acuan dari PPC (kapasitas 136 -> CO1-4 = 34, CO5 nominal = 30):
    |   shift_qty 1, qty 160, overtime ya    -> S1 CO1-4 34 · CO5 24
    |   shift_qty 2, qty 160, overtime tidak -> S1 CO1-4 34 · S2 CO1 24
    |   shift_qty 2, qty 310, overtime ya    -> S1 CO1-4 34 + CO5 30 · S2 CO1-4 34 + CO5 8
    |
    */
    'capacity' => [
        // Batas atas nilai master_conveyor.shift_qty — bukan penentu jumlah shift,
        // karena jumlah shift ditetapkan per conveyor di master.
        //
        // Tidak ada shift 3 di lapangan, jadi 2 juga dijepit sebagai batas keras di
        // ShiftCapacityCalculator::MAX_SHIFT. Nilai di sini hanya boleh menurunkannya.
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
