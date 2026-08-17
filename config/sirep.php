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
    | Aturan dari tim PPC:
    |   - normal_capacity dari API = kapasitas conveyor untuk SATU shift
    |   - qty listing >= 2 × kapasitas  ->  conveyor berjalan 2 shift
    |   - is_overtime = true            ->  hari itu ada CO5 / kapasitas over
    |
    */
    'capacity' => [
        // true  = jumlah shift dihitung dari volume demand harian (aturan PPC)
        // false = pakai nilai statis master_conveyor.shift_qty (perilaku jai_ekanban)
        'dynamic_shift' => env('SIREP_DYNAMIC_SHIFT', true),

        // Pembulatan nominal CO5: 'round' (aturan internal saat ini) atau 'floor'.
        //
        // Nilai overtime_capacity dari SIREP setara dengan kapasitas + floor(0.875 × kap/4):
        //   140 -> 170 · 300 -> 365 · 120 -> 146
        // Aturan internal memakai round, yang untuk kapasitas 100 menghasilkan 22
        // (SIREP: 21). Selisihnya satu unit dan hanya berlaku pada kapasitas tertentu.
        // Ubah ke 'floor' setelah tim PPC mengonfirmasi mana yang mengikat.
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
