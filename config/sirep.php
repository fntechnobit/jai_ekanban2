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

        // Batas fase koneksi saja. Tanpa ini, host SIREP yang mati membuat setiap
        // permintaan menunggu sampai `timeout` penuh lalu diulang `retry` kali —
        // satu generate bisa habis puluhan detik hanya untuk gagal.
        'connect_timeout' => (int) env('SIREP_API_CONNECT_TIMEOUT', 5),
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
    | Seluruh angka berasal dari API SIREP. Master conveyor tidak menyimpan
    | kapasitas maupun jumlah shift.
    |
    |   normal_capacity    kapasitas satu shift tanpa lembur
    |   overtime_capacity  kapasitas satu shift dengan lembur, sekaligus AMBANG
    |                      pemecahan shift
    |
    | Pembagian cutoff
    |   CO1     = normal_capacity - 3 x floor(normal_capacity/4)   (menampung terbesar)
    |   CO2-CO4 = floor(normal_capacity/4)
    |
    | Jumlah shift
    |   qty listing harian > overtime_capacity  ->  2 shift
    |   selain itu                              ->  1 shift
    |
    | Kapasitas CO5
    |   1 shift : overtime_capacity - normal_capacity  (dari data SIREP)
    |   2 shift : shift pertama dibatasi 7/8 CO normal,
    |             shift terakhir menampung seluruh sisa
    |
    | Satu shift menampung tepat overtime_capacity, sehingga ambang pemecahan shift
    | dan kapasitas yang tersedia benar-benar berimpit — tidak ada qty yang jatuh di
    | celah antara keduanya.
    |
    | Urutan pengisian
    |   1 shift : S1.CO1 -> S1.CO2 -> S1.CO3 -> S1.CO4 -> S1.CO5
    |   2 shift : S1.CO1..CO4 -> S2.CO1..CO4 -> S1.CO5 (<= 7/8) -> S2.CO5 (sisa semua)
    |
    | PENANDA is_overtime TIDAK DIPAKAI untuk keputusan apa pun.
    |   Ia ditetapkan PPC mendekati hari produksi. Pada data 10 Sep 2026: baris yang
    |   ditarik pada hari-H bernilai 1 sebanyak 33,5%, sedangkan yang ditarik 4-8 hari
    |   di muka hanya 4,5%-13,8%. Contoh paling tajam C1: 28/28 pada tarikan hari-H
    |   versus 0/17 pada tarikan maju. Jadi nilai 0 pada tanggal ke depan berarti
    |   "belum ditetapkan", bukan "tidak lembur" — memakainya membuat hasil generate
    |   berubah tergantung kapan dijalankan. Nilainya tetap disimpan dan ditampilkan
    |   sebagai keterangan.
    |
    | Conveyor tanpa normal_capacity ATAU tanpa overtime_capacity DILEWATI saat
    | generate, dengan pesan jelas, sampai PPC melengkapinya di SIREP.
    |
    | Contoh acuan dari PPC (normal 136, overtime 160 -> CO1-CO4 = 34):
    |   qty 160  ->  1 shift: CO1-4 34 · CO5 = 160-136 = 24
    |   qty 310  ->  2 shift: S1 CO1-4 34 + CO5 30 · S2 CO1-4 34 + CO5 8
    |
    */
    'capacity' => [
        // Batas CO5 shift pertama pada hari DUA shift, sebagai rasio terhadap
        // CO normal. Aturan PPC: 7/8 = 87,5%. Untuk hari SATU shift batas ini tidak
        // dipakai — yang berlaku adalah overtime_capacity dari SIREP.
        'co5_ratio' => (float) env('SIREP_CO5_RATIO', 7 / 8),

        // Pembulatan nominal CO5 dua shift: 'round' atau 'floor'.
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
