@extends('layouts.master')

@section('title', 'Dokumentasi')

@section('breadcrumb')
    <x-page-header menu-code="documentation" />
@endsection

@section('content')
{{--
    Panduan alur data: SIREP -> jadwal -> kanban -> saldo.

    Dibangun dengan komponen bawaan template (card, nav-tabs, table, badge) supaya
    menyatu dengan halaman lain. Gaya tambahan seluruhnya diawali .doc agar tidak
    bocor ke halaman lain, dan warnanya mengambil var(--primary) sehingga ikut
    berubah bila tema aplikasi diganti.

    Saat dicetak, seluruh tab ditampilkan (dokumen cetak harus lengkap, bukan hanya
    tab yang sedang dibuka) dan tiap topik mulai di halaman baru.
--}}

<div class="container-fluid doc">
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-0">Alur Data e-Kanban</h5>
                <small class="text-muted">Dari listing SIREP sampai saldo kanban &middot; panduan untuk operator &amp; PPC</small>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i> Cetak / Simpan PDF
            </button>
        </div>

        <div class="card-body pb-0">
            <ul class="nav nav-tabs doc-tabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#doc-ringkasan" type="button"><i class="fa-solid fa-diagram-project me-1"></i> Ringkasan Alur</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#doc-listing" type="button"><span class="doc-step">01</span> Listing SIREP</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#doc-cutoff" type="button"><span class="doc-step">02</span> Shift &amp; Cutoff</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#doc-verifikasi" type="button"><span class="doc-step">03</span> Verifikasi</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#doc-kanban" type="button"><span class="doc-step">04</span> Kanban</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#doc-saldo" type="button"><span class="doc-step">05</span> Saldo</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#doc-periksa" type="button"><i class="fa-solid fa-circle-question me-1"></i> Pemeriksaan &amp; Istilah</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#doc-perubahan" type="button"><i class="fa-solid fa-clock-rotate-left me-1"></i> Perubahan Terbaru</button></li>
            </ul>
        </div>

        <div class="card-body pt-3">
            <div class="tab-content">

            {{-- ══════════════ RINGKASAN ══════════════ --}}
            <div class="tab-pane fade show active" id="doc-ringkasan">
                <h6 class="doc-print-title">Ringkasan Alur</h6>

                <p class="text-muted mb-4" style="max-width:70ch">
                    Lima tahap yang dilalui setiap hari. Hanya satu tahap yang butuh keputusan orang &mdash;
                    empat lainnya berjalan sendiri begitu tombol ditekan.
                </p>

                <div class="doc-scroll mb-4">
                    <svg viewBox="0 0 900 190" xmlns="http://www.w3.org/2000/svg" class="doc-flow"
                         role="img" aria-label="Alur lima tahap: data SIREP, bagi shift dan cutoff, verifikasi, cetak kanban, saldo terbawa ke hari berikutnya.">
                        <defs>
                            <marker id="docar" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto">
                                <path d="M0 0 L10 5 L0 10 z" fill="currentColor" opacity=".45"/>
                            </marker>
                        </defs>

                        <rect x="0"   y="40" width="150" height="76" rx="6" class="doc-box"/>
                        <rect x="188" y="40" width="150" height="76" rx="6" class="doc-box"/>
                        <rect x="376" y="40" width="150" height="76" rx="6" class="doc-box doc-box-key"/>
                        <rect x="564" y="40" width="150" height="76" rx="6" class="doc-box"/>
                        <rect x="752" y="40" width="148" height="76" rx="6" class="doc-box"/>

                        <g class="doc-svg-num">
                            <text x="16" y="59">01</text><text x="204" y="59">02</text><text x="392" y="59">03</text>
                            <text x="580" y="59">04</text><text x="768" y="59">05</text>
                        </g>
                        <g class="doc-svg-title">
                            <text x="16" y="76">Data SIREP</text>
                            <text x="204" y="76">Bagi shift &amp; CO</text>
                            <text x="392" y="76">Verifikasi</text>
                            <text x="580" y="76">Cetak kanban</text>
                            <text x="768" y="76">Saldo</text>
                        </g>
                        <g class="doc-svg-sub">
                            <text x="16" y="94">Conveyor aktif,</text><text x="16" y="108">lalu listing</text>
                            <text x="204" y="94">Shift &amp; cutoff dari</text><text x="204" y="108">kapasitas SIREP</text>
                            <text x="392" y="94">Orang memeriksa</text><text x="392" y="108">dan mengunci</text>
                            <text x="580" y="94">Berapa lembar</text><text x="580" y="108">kartu terbit</text>
                            <text x="768" y="94">Sisa dibawa</text><text x="768" y="108">ke besok</text>
                        </g>

                        <g class="doc-svg-arrow" marker-end="url(#docar)">
                            <path d="M152 78 L184 78"/><path d="M340 78 L372 78"/>
                            <path d="M528 78 L560 78"/><path d="M716 78 L748 78"/>
                        </g>

                        <path d="M826 118 L826 152 L94 152 L94 120" class="doc-svg-loop" marker-end="url(#docar)"/>
                        <rect x="348" y="141" width="224" height="22" rx="4" class="doc-svg-loop-bg"/>
                        <text x="460" y="157" text-anchor="middle" class="doc-svg-loop-txt">saldo hari ini jadi modal awal besok</text>
                    </svg>
                </div>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="alert alert-primary mb-0 py-2 px-3">
                            <div class="fw-semibold mb-1"><i class="fa-solid fa-hand-pointer me-1"></i> Hanya Verifikasi yang butuh orang</div>
                            <div class="small mb-0">Jadwal yang baru dibuat belum mengikat. Seseorang harus memeriksanya lalu menekan
                            <strong>Verify</strong>; sesudah itu jadwal terkunci dan kanban dicetak dari situ.</div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="alert alert-warning mb-0 py-2 px-3">
                            <div class="fw-semibold mb-1"><i class="fa-solid fa-rotate me-1"></i> Hari ini tidak berdiri sendiri</div>
                            <div class="small mb-0">Garis putus-putus pada bagan adalah bagian yang paling sering disalahpahami:
                            sisa dari hari ini menjadi modal awal hari berikutnya.</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════ 01 LISTING ══════════════ --}}
            <div class="tab-pane fade" id="doc-listing">
                <h6 class="doc-print-title"><span class="doc-step">01</span> Listing SIREP</h6>

                <div class="row g-4">
                    <div class="col-lg-7">
                        <p>Sebelum menanyakan apa pun soal permintaan, sistem menanyakan dulu: <em>conveyor apa saja yang aktif hari ini?</em>
                        Urutannya penting &mdash; conveyor yang baru dibuka PPC pagi ini harus sudah dikenali sebelum listing-nya masuk,
                        dan conveyor yang sudah ditutup harus berhenti dijadwalkan sejak saat itu juga.</p>

                        <p>Baru sesudah itu sistem menanyakan: <em>untuk conveyor ini, di rentang tanggal ini, assy apa saja yang harus
                        dibuat dan berapa banyak?</em> Jawabannya disimpan apa adanya &mdash; sistem tidak mengubah satu angka pun.</p>

                        <div class="alert alert-danger py-2 px-3 mb-0">
                            <div class="fw-semibold mb-1"><i class="fa-solid fa-plug-circle-xmark me-1"></i> Kalau SIREP tidak bisa dihubungi, proses berhenti</div>
                            <div class="small mb-0">Sistem tidak mencari sumber cadangan dan tidak memakai angka lama. Lebih baik berhenti
                            dengan pesan jelas daripada membuat jadwal dari data usang.</div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="doc-panel">
                            <div class="doc-panel-title">Satu baris listing, apa adanya</div>
                            <div class="doc-kv">
                                <span>tanggal</span><b>2026-09-03</b>
                                <span>conveyor</span><b>B3-EGI</b>
                                <span>assy</span><b>24011-7YA0A</b>
                                <span>jumlah</span><b>160</b>
                                <span>lembur</span><b><span class="badge bg-warning text-dark">ya</span></b>
                                <span>urutan</span><b>1</b>
                            </div>
                            <p class="small text-muted mb-0 mt-2">Penanda lembur ditetapkan PPC di SIREP, bukan dihitung sistem.
                            Ia <strong>tidak</strong> menentukan pembagian shift maupun jatah CO5 &mdash; lihat tab
                            <em>Shift &amp; Cutoff</em>. Pemakaiannya hanya satu: menilai apakah sebuah hari pantas
                            disebut <em>over</em> di layar verifikasi.</p>
                        </div>
                    </div>
                </div>

                <div class="doc-panel mt-4">
                    <div class="doc-panel-title">Daftar conveyor sepenuhnya milik SIREP</div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-2">
                            <thead class="table-light">
                                <tr><th style="width:38%">Keadaan di SIREP</th><th>Yang terjadi di sini</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Conveyor baru muncul</td><td>Ditambahkan otomatis, langsung bisa dijadwalkan</td></tr>
                                <tr><td>Nama atau kapasitas berubah</td><td>Diperbarui otomatis</td></tr>
                                <tr><td>Conveyor tidak dikirim lagi</td><td><span class="badge bg-secondary">Nonaktif</span> &mdash; berhenti dijadwalkan, <strong>data lama tidak dihapus</strong></td></tr>
                                <tr><td>Conveyor baru, tetapi kapasitasnya masih kosong di SIREP</td><td><span class="badge bg-light text-dark border">Dilewati</span> &mdash; tidak dibuatkan baris master. Tanpa kapasitas ia memang tidak bisa dijadwalkan, dan menambahkannya hanya melahirkan peringatan berulang setiap generate. Begitu SIREP mengisi kapasitasnya, ia masuk sendiri pada sinkronisasi berikutnya.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mb-0">Conveyor tidak bisa ditambah atau dihapus manual. Yang masih diisi orang hanyalah
                    Area, Family, Kode Conveyor SIREP, dan Pallet Qty &mdash; keterangan yang tidak dikirim SIREP.</p>
                </div>

                <div class="doc-panel mt-4">
                    <div class="doc-panel-title">Listing yang hilang dari SIREP dianggap dibatalkan</div>
                    <p class="small mb-2">API SIREP tidak mengirim penanda pembatalan. Karena itu setiap sinkronisasi
                    membandingkan <em>seluruh isi rentang</em>: baris yang tidak lagi muncul dianggap sudah dibatalkan PPC
                    dan ikut dihapus dari staging.</p>
                    <p class="small mb-0 text-muted">Dua pengaman menjaga agar gangguan API tidak terbaca sebagai
                    &ldquo;semua dibatalkan&rdquo;: bila API mengembalikan nol baris untuk sebuah conveyor padahal
                    sebelumnya berisi, tidak ada yang dihapus; dan bila jumlah baris menyusut lebih dari separuh,
                    penghapusan dibatalkan lalu dilaporkan sebagai peringatan untuk diperiksa manual.</p>
                </div>
            </div>

            {{-- 02 CUTOFF --}}
            <div class="tab-pane fade" id="doc-cutoff">
                <h6 class="doc-print-title"><span class="doc-step">02</span> Shift &amp; Cutoff</h6>

                <p style="max-width:72ch">Satu hari kerja dibagi menjadi shift, dan setiap shift dibagi lagi menjadi lima
                cutoff &mdash; potongan waktu penyerahan. <strong>Dua angka dari SIREP</strong> menentukan seluruhnya;
                tidak ada lagi yang diisi manual.</p>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="doc-panel h-100">
                            <div class="doc-panel-title">normal_capacity</div>
                            <p class="small mb-0 text-muted">Kapasitas satu shift tanpa lembur. Angka inilah yang dibagi
                            menjadi CO1&ndash;CO4.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="doc-panel h-100">
                            <div class="doc-panel-title">overtime_capacity</div>
                            <p class="small mb-0 text-muted">Kapasitas satu shift dengan lembur. Ia punya dua peran
                            sekaligus: <strong>batas isi satu shift</strong>, dan <strong>ambang pemecahan shift</strong>.</p>
                        </div>
                    </div>
                </div>

                <div class="doc-panel">
                    <div class="doc-panel-title">Aturannya, dalam empat kalimat</div>
                    <div class="row g-3">
                        <div class="col-md-6 col-xl-3"><div class="doc-rule h-100"><span class="doc-rule-n">1</span><div><strong>CO1</strong> menampung paling besar &mdash; ia mendapat sisa pembagian. CO2&ndash;CO4 masing-masing seperempat <code>normal_capacity</code>.</div></div></div>
                        <div class="col-md-6 col-xl-3"><div class="doc-rule h-100"><span class="doc-rule-n">2</span><div>Hari <strong>satu shift</strong>: CO5 = <code>overtime_capacity &minus; normal_capacity</code>. Satu shift jadi menampung tepat <code>overtime_capacity</code>.</div></div></div>
                        <div class="col-md-6 col-xl-3"><div class="doc-rule h-100"><span class="doc-rule-n">3</span><div>Listing melebihi <code>overtime_capacity</code> &rarr; hari itu <strong>dua shift</strong>. Belum ada angka lembur? dipakai <code>normal_capacity</code>.</div></div></div>
                        <div class="col-md-6 col-xl-3"><div class="doc-rule h-100"><span class="doc-rule-n">4</span><div>Hari <strong>dua shift</strong>: CO5 shift 1 dibatasi <strong>7/8</strong> cutoff normal, CO5 shift 2 menampung seluruh sisa.</div></div></div>
                    </div>
                </div>

                <h6 class="mt-4 mb-2">Urutan pengisian</h6>
                <div class="doc-panel">
                    <div class="doc-calc"><b>1 shift</b>
   S1.CO1 &rarr; S1.CO2 &rarr; S1.CO3 &rarr; S1.CO4 &rarr; S1.CO5

<b>2 shift</b>
   S1.CO1 &rarr; S1.CO2 &rarr; S1.CO3 &rarr; S1.CO4
        &rarr; S2.CO1 &rarr; S2.CO2 &rarr; S2.CO3 &rarr; S2.CO4
        &rarr; S1.CO5   (dibatasi 7/8 cutoff normal)
        &rarr; S2.CO5   (sisa semuanya)</div>
                </div>

                <h6 class="mt-4 mb-2">Contoh: B3-EGI</h6>
                <p class="text-muted small" style="max-width:72ch">normal 135, overtime 160 &rarr; CO1&ndash;CO4 =
                <strong>36</strong>/33/33/33 (sisa pembagian masuk CO1), CO5 satu shift = 160 &minus; 135 =
                <strong>25</strong>, CO5 shift 1 saat dua shift = 7/8 &times; 33,75 = <strong>30</strong>.</p>

                <div class="doc-bars">
                    <div class="doc-barrow">
                        <div class="doc-barlab">Listing 160<br><span class="badge bg-light text-dark border">tepat di ambang</span></div>
                        <div class="doc-bar">
                            <div class="doc-seg" style="flex:36">36</div><div class="doc-seg" style="flex:33">33</div>
                            <div class="doc-seg" style="flex:33">33</div><div class="doc-seg" style="flex:33">33</div>
                            <div class="doc-seg doc-seg-ot" style="flex:25">CO5 25</div>
                        </div>
                    </div>
                    <div class="doc-barrow">
                        <div class="doc-barlab">Listing 310<br><span class="badge bg-warning text-dark">di atas ambang</span></div>
                        <div class="doc-bar">
                            <div class="doc-seg" style="flex:135">shift 1 &middot; CO1&ndash;CO4 = 135</div>
                            <div class="doc-seg doc-seg-ot" style="flex:30">30</div>
                            <div class="doc-seg" style="flex:135">shift 2 &middot; CO1&ndash;CO4 = 135</div>
                            <div class="doc-seg doc-seg-ot" style="flex:10">10</div>
                        </div>
                    </div>
                </div>

                <p class="small text-muted mt-2 mb-0" style="max-width:72ch">Pada baris kedua, sisa setelah CO1&ndash;CO4
                kedua shift adalah 310 &minus; 270 = 40. CO5 shift 1 mengambil jatah nominalnya (30), dan sepuluh sisanya
                jatuh ke CO5 shift 2 &mdash; cutoff penampung.</p>

                <div class="row g-3 mt-1">
                    <div class="col-lg-6">
                        <div class="alert alert-primary py-2 px-3 h-100 mb-0">
                            <div class="fw-semibold mb-1"><i class="fa-solid fa-lightbulb me-1"></i> Kenapa tidak ada celah</div>
                            <div class="small mb-0">Satu shift menampung tepat <code>overtime_capacity</code>, dan ambang
                            pemecahan shift juga <code>overtime_capacity</code>. Jadi tidak ada jumlah listing yang jatuh
                            di antara &ldquo;masih muat satu shift&rdquo; dan &ldquo;harus dua shift&rdquo;.</div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="alert alert-warning py-2 px-3 h-100 mb-0">
                            <div class="fw-semibold mb-1"><i class="fa-solid fa-circle-half-stroke me-1"></i> Belum ada angka lembur? dianggap tanpa lembur</div>
                            <div class="small mb-0">Conveyor yang <code>overtime_capacity</code>-nya belum dikirim SIREP tetap
                            dijadwalkan, tetapi ambangnya memakai <code>normal_capacity</code> &mdash; artinya line itu dianggap
                            belum punya jatah lembur. Hari yang melampaui kapasitas normal pecah menjadi dua shift, bukan
                            menumpuk di CO5. Yang <strong>tidak punya <code>normal_capacity</code></strong> sama sekali
                            tetap dilewati.</div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-secondary py-2 px-3 mt-3 mb-0">
                    <div class="fw-semibold mb-1"><i class="fa-solid fa-circle-info me-1"></i> Penanda lembur tidak menentukan bentuk jadwal</div>
                    <div class="small mb-2"><code>is_overtime</code> dari SIREP <strong>tidak</strong> dipakai menentukan
                    jumlah shift maupun jatah CO5. PPC menetapkannya mendekati hari produksi, sehingga pada tanggal ke depan
                    nilainya mayoritas masih 0 yang berarti &ldquo;belum ditetapkan&rdquo;, bukan &ldquo;tidak lembur&rdquo;.
                    Kalau dipakai menentukan shift, jadwal untuk tanggal yang sama akan berubah tergantung kapan digenerate.</div>
                    <div class="small mb-0">Satu-satunya pemakaiannya ada di layar verifikasi, untuk menghitung ambang
                    <em>over</em> &mdash; lihat tab Verifikasi.</div>
                </div>

                <div class="doc-panel mt-3">
                    <div class="doc-panel-title">CO5 terakhir selalu menampung sisanya</div>
                    <p class="small mb-2">Angka CO5 pada aturan di atas adalah <em>nominal</em> &mdash; rencana. Pada
                    pelaksanaannya, CO5 shift <strong>terakhir yang tidak terkunci</strong> menampung seluruh sisa demand,
                    berapa pun jumlahnya.</p>
                    <p class="small mb-0 text-muted">Alasannya: membuang baris listing jauh lebih berbahaya daripada
                    mencetak satu cutoff di luar rencana &mdash; permintaan yang hilang baru ketahuan di lapangan.
                    Hari yang melampaui nominal tidak disembunyikan, melainkan ditandai <span class="badge bg-danger">over</span>
                    di layar verifikasi supaya diputuskan orang.</p>
                </div>
            </div>

            {{-- ══════════════ 03 VERIFIKASI ══════════════ --}}
            <div class="tab-pane fade" id="doc-verifikasi">
                <h6 class="doc-print-title"><span class="doc-step">03</span> Verifikasi</h6>

                <p style="max-width:72ch">Jadwal yang baru dibuat belum mengikat. Seseorang harus membukanya, memeriksa, lalu menekan
                <strong>Verify</strong>. Sesudah terverifikasi, jadwal terkunci dan kanban dicetak dari situ. Layar verifikasi menampilkan
                asal-usul setiap angka, supaya bisa dinilai apakah datanya masih segar.</p>

                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr><th style="width:34%">Yang tampil di layar</th><th>Artinya</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="fw-semibold">Capacity (SIREP)</span> <span class="doc-num">135</span><br>
                                    <small class="text-muted">18 Aug 2026 10:01</small></td>
                                <td>Kapasitas per shift, beserta kapan terakhir ditarik dari SIREP. Tanggal lama berarti angkanya mungkin sudah tidak berlaku.</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning text-dark">OT: ya</span> &nbsp;/&nbsp; <span class="badge bg-secondary">OT: tidak</span></td>
                                <td>Penanda lembur dari SIREP untuk hari itu. <strong>Tidak mengubah jadwal</strong> &mdash;
                                    jadwalnya sudah terbentuk sebelum penanda ini dilihat. Perannya hanya menentukan ambang
                                    yang dipakai menilai <em>over</em> pada baris ini.</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger">belum sinkron</span></td>
                                <td>Kapasitas belum pernah ditarik dari SIREP. Conveyor ini <strong>dilewati</strong> saat pembuatan jadwal &mdash; hubungi PPC.</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger">! over tanpa OT</span></td>
                                <td>Listing melebihi kapasitas normal padahal lembur tidak dinyatakan. Jadwal tetap dibuat, tapi perlu dikonfirmasi.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="doc-panel mb-3">
                    <div class="doc-panel-title">Bagaimana tanda &ldquo;over&rdquo; dihitung</div>
                    <div class="doc-calc">ambang nominal = jumlah shift &times; ( kapasitas + CO5 nominal bila OT dinyatakan )

over  bila  listing hari itu &gt; ambang nominal</div>
                    <p class="small mb-0 mt-2 text-muted">Tanpa penanda lembur, CO5 dianggap tidak tersedia sehingga
                    ambangnya hanya shift &times; kapasitas. Ini disengaja: memakai ambang ber-CO5 pada hari yang lemburnya
                    belum dinyatakan akan <em>menyembunyikan</em> hari yang sebenarnya over. Tandanya muncul pada baris
                    shift terakhir, karena di situlah kelebihannya benar-benar ditampung.</p>
                </div>

                <div class="doc-panel">
                    <div class="doc-panel-title">Baris yang sudah terkunci menyimpan angkanya sendiri</div>
                    <p class="small mb-2">Saat jadwal diverifikasi, nilai SIREP yang berlaku <em>saat itu</em> ikut disimpan:
                    kapasitas, penanda lembur, dan waktu penarikan listing.</p>
                    <p class="small mb-0 text-muted">Tanpa ini, baris lama akan menampilkan kapasitas SIREP <strong>terkini</strong>
                    setiap kali sinkronisasi dijalankan &mdash; seolah jadwal yang sudah final melanggar aturan kapasitas,
                    padahal ia dibangun dari angka pada waktu yang berbeda. Baris yang dikunci sebelum fitur ini ada tidak
                    punya simpanan tersebut dan tetap menampilkan nilai terkini.</p>
                </div>

                <div class="alert alert-warning py-2 px-3 mb-4">
                    <div class="fw-semibold mb-1"><i class="fa-solid fa-triangle-exclamation me-1"></i> Tanda &ldquo;over tanpa OT&rdquo; bukan kesalahan sistem</div>
                    <div class="small mb-0">Itu tanda dua informasi dari SIREP saling bertentangan: jumlah yang diminta tidak muat dalam
                    kapasitas normal, tetapi lembur tidak dinyatakan. Sistem tetap menjadwalkan seluruhnya agar tidak ada permintaan yang
                    hilang, dan menandainya supaya diperiksa manusia.</div>
                </div>

                <h6 class="mt-4 mb-2">Memindahkan item sebelum dikunci</h6>
                <p class="small" style="max-width:72ch">Di layar detail, item dapat diseret ke cutoff, shift, atau tanggal lain
                bila urutan hasil perhitungan tidak cocok dengan kenyataan lapangan. Item yang berpindah tetap mengingat asalnya
                dan ditampilkan dengan lencana tanggal &middot; shift &middot; cutoff asal.</p>

                <h6 class="mt-4 mb-2">Membuka kembali jadwal (Unverify)</h6>
                <p class="small" style="max-width:72ch">Unverify bukan sekadar membuka kunci. Empat hal terjadi berurutan,
                seluruhnya dalam satu transaksi &mdash; kalau salah satu gagal, tidak ada yang berubah sama sekali:</p>

                <div class="doc-panel mb-3">
                    <div class="row g-3">
                        <div class="col-md-6 col-xl-3"><div class="doc-rule h-100"><span class="doc-rule-n">1</span><div>Item yang dulu <strong>dipindahkan ke sini</strong> dikembalikan ke jadwal asalnya.</div></div></div>
                        <div class="col-md-6 col-xl-3"><div class="doc-rule h-100"><span class="doc-rule-n">2</span><div><strong>Saldo dikembalikan</strong> sebesar yang dulu benar-benar dicatat, dibaca dari buku mutasi &mdash; bukan dihitung ulang.</div></div></div>
                        <div class="col-md-6 col-xl-3"><div class="doc-rule h-100"><span class="doc-rule-n">3</span><div>Kanban yang sudah terbit untuk jadwal itu <strong>dihapus</strong>. Nomor urut barcode tidak pernah ikut mundur.</div></div></div>
                        <div class="col-md-6 col-xl-3"><div class="doc-rule h-100"><span class="doc-rule-n">4</span><div>Jadwalnya <strong>dibangun ulang</strong> dari listing asli, seolah belum pernah disentuh.</div></div></div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="alert alert-danger py-2 px-3 h-100 mb-0">
                            <div class="fw-semibold mb-1"><i class="fa-solid fa-circle-exclamation me-1"></i> Item pindahan bisa hilang</div>
                            <div class="small mb-0">Bila jadwal <em>asal</em> item pindahan sudah terverifikasi, item itu tidak
                            dapat dikembalikan &mdash; jadwal asal sudah terkunci. Layar menghitungnya lebih dulu dan
                            memperingatkan sebelum unverify dijalankan, lalu melaporkan berapa yang hilang.</div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="alert alert-primary py-2 px-3 h-100 mb-0">
                            <div class="fw-semibold mb-1"><i class="fa-solid fa-lock me-1"></i> Shift lain tidak ikut terganggu</div>
                            <div class="small mb-0">Hanya shift yang dibuka yang dibangun ulang; shift lain diperlakukan
                            terkunci dan bagiannya tidak dialokasikan dua kali. Jumlah shift hari itu tetap dihitung dari
                            demand <strong>penuh</strong>, supaya hari dua shift tidak menyusut jadi satu shift.</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════ 04 KANBAN ══════════════ --}}
            <div class="tab-pane fade" id="doc-kanban">
                <h6 class="doc-print-title"><span class="doc-step">04</span> Kanban</h6>

                <p style="max-width:72ch">Satu assy dirakit dari banyak komponen &mdash; circuit dan shikake. Masing-masing punya kanban
                sendiri, dan masing-masing punya <strong>isi per kartu</strong> yang tetap. Kalau satu kartu berisi 12 unit, maka kebutuhan
                160 unit memerlukan 14 kartu &mdash; karena 13 kartu hanya menutup 156. Kartu tidak pernah diterbitkan setengah; selalu utuh,
                dan kelebihannya menjadi saldo.</p>

                <div class="doc-panel mb-4">
                    <div class="doc-panel-title">Kartu dihitung per cutoff, bukan per hari</div>
                    <p class="small mb-2">Perhitungannya berjalan cutoff demi cutoff mengikuti urutan jadwal. Untuk setiap
                    cutoff: buka kartu satu per satu sampai barang yang tersedia cukup menutup kebutuhan cutoff itu, lalu
                    kebutuhannya diambil. Kelebihannya tidak dikembalikan &mdash; ia langsung menjadi modal cutoff berikutnya,
                    lalu shift berikutnya, lalu hari berikutnya.</p>
                    <div class="doc-calc">untuk tiap cutoff berurutan:
    selama tersedia &lt; kebutuhan cutoff   &rarr;  terbitkan 1 kartu (tersedia += isi kartu)
    tersedia &minus;= kebutuhan cutoff        &rarr;  selebihnya terbawa ke cutoff berikutnya</div>
                    <p class="small text-muted mb-0 mt-2">Karena itu jumlah kartu satu hari tidak selalu sama dengan
                    kebutuhan hari dibagi isi kartu: kartu terakhir hari kemarin ikut menentukan.</p>
                </div>

                <div class="doc-panel">
                    <div class="doc-panel-title">Membaca barcode kanban</div>

                    <div class="doc-scroll">
                        <div class="doc-bc">
                            <span class="doc-bc-1">Q</span><span class="doc-bc-2">QB36</span><span class="doc-bc-3">014</span><span class="doc-bc-4">20</span><span class="doc-bc-5">0030</span>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-6 col-md"><div class="doc-bckey doc-bck-1"><b>Q</b>Kode carline</div></div>
                        <div class="col-6 col-md"><div class="doc-bckey doc-bck-2"><b>QB36</b>Kode komponen</div></div>
                        <div class="col-6 col-md"><div class="doc-bckey doc-bck-3"><b>014</b>Kartu ke-14 shift ini</div></div>
                        <div class="col-6 col-md"><div class="doc-bckey doc-bck-4"><b>20</b>Isi per kartu</div></div>
                        <div class="col-6 col-md"><div class="doc-bckey doc-bck-5"><b>0030</b>Nomor urut berjalan</div></div>
                    </div>

                    <p class="small text-muted mb-2 mt-3">Tiga angka di tengah adalah nomor kartu dalam shift ini beserta
                    totalnya (kartu ke-14 dari sekian), sedangkan empat angka terakhir adalah nomor urut berjalan yang
                    <strong>hanya bergerak naik</strong>. Ia dihitung terpisah untuk setiap pasangan conveyor &amp; komponen,
                    dan setelah 9999 berputar kembali ke 0001 &mdash; jarak sejauh itu sudah jauh melewati umur kartu di lapangan.</p>
                    <p class="small text-muted mb-0">Yang tidak boleh terjadi adalah nomor ini <em>dimundurkan</em>. Unverify
                    menghapus kanban tetapi sengaja tidak mengembalikan nomor urutnya; kalau dikembalikan, barcode baru akan
                    kembar dengan kartu yang sudah beredar dan pemindaian tidak bisa dipercaya lagi.</p>
                </div>
            </div>

            {{-- ══════════════ 05 SALDO ══════════════ --}}
            <div class="tab-pane fade" id="doc-saldo">
                <h6 class="doc-print-title"><span class="doc-step">05</span> Saldo</h6>

                <p style="max-width:72ch">Karena kartu selalu utuh, hampir selalu ada kelebihan. Kelebihan itu <strong>tidak dibuang</strong>
                &mdash; ia menjadi saldo dan mengurangi kebutuhan kartu di hari berikutnya. Inilah bagian yang paling sering terlihat
                &ldquo;salah&rdquo; padahal benar.</p>

                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="doc-panel h-100">
                            <div class="doc-panel-title">Dua hari berturut-turut, isi kartu 12 unit</div>
                            <div class="doc-calc"><b>3 September</b>   kebutuhan 160
                buka 14 kartu &times; 12  = 168 unit tersedia
                dipakai 160              <span class="doc-up">sisa 8  &rarr; jadi saldo</span>

<b>4 September</b>   kebutuhan 144
                <span class="doc-up">saldo awal 8</span>  &rarr; tinggal perlu 136
                buka 12 kartu &times; 12  = 144
                tersedia 8 + 144 = 152   <span class="doc-up">sisa 8  &rarr; jadi saldo</span>

<b>Rekap 2 hari</b>  kebutuhan 160 + 144      = <b>304</b>
                diproduksi 26 kartu &times; 12 = <b>312</b>
                <b>saldo akhir = 312 &minus; 304 = 8</b></div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="alert alert-primary py-2 px-3">
                            <div class="fw-semibold mb-1"><i class="fa-solid fa-circle-question me-1"></i> Kenapa 8 dan bukan 4?</div>
                            <div class="small mb-0">Karena 304 dibagi 12 memang bersisa 4 &mdash; tetapi 4 itu adalah <em>bagian yang
                            terpakai</em> dari kartu terakhir, bukan yang tersisa. Kartu terakhir berisi 12; empat unit terpakai,
                            <strong>delapan menganggur</strong>. Delapan unit itulah barang nyata yang ada di rak, dan itulah yang
                            dicatat sebagai saldo.</div>
                        </div>

                        <div class="doc-panel mb-0">
                            <div class="doc-panel-title">Tiga hal yang mengubah saldo</div>
                            <table class="table table-sm align-middle mb-2">
                                <thead class="table-light"><tr><th>Kejadian</th><th class="text-center">Efek</th></tr></thead>
                                <tbody>
                                    <tr><td>Generate kanban</td><td class="text-center"><span class="badge bg-primary">+ / &minus;</span></td></tr>
                                    <tr><td>Addition di layar cutting</td><td class="text-center"><span class="badge bg-success">+</span></td></tr>
                                    <tr><td>Defect di layar cutting</td><td class="text-center"><span class="badge bg-danger">&minus;</span></td></tr>
                                </tbody>
                            </table>
                            <p class="small text-muted mb-0">Setiap perubahan tercatat beserta saldo sebelum dan sesudahnya.
                            Kalau saldo tidak sama dengan hasil penjumlahan ketiganya, berarti ada yang mengubah saldo tanpa
                            jejak &mdash; dan itu selalu bisa dilacak.</p>
                        </div>
                    </div>
                </div>

                <div class="doc-panel mt-4">
                    <div class="doc-panel-title">Buku mutasi: kenapa saldo bisa dikembalikan dengan tepat</div>
                    <p class="small mb-2">Setiap kali kanban dibuat, sistem mencatat satu baris berisi kebutuhan yang dilayani,
                    jumlah yang diproduksi, serta saldo sebelum dan sesudahnya. Saat jadwal dibuka kembali (unverify), saldo
                    dikembalikan <strong>sebesar catatan itu</strong>.</p>
                    <p class="small mb-0 text-muted">Sebelumnya pengembalian dihitung ulang dari jumlah kartu dikurangi kebutuhan.
                    Cara itu meleset pada cutoff yang seluruhnya dilayani saldo &mdash; tidak ada kartu terbit, tetapi saldo tetap
                    berkurang. Karena selisihnya kecil dan berulang, saldo merosot perlahan tanpa ketahuan. Dengan catatan
                    mutasi, yang dikembalikan persis yang dulu diambil.</p>
                </div>

                <div class="doc-panel mt-3">
                    <div class="doc-panel-title">Menelusuri saldo: menu Balance History</div>
                    <p class="small mb-2">Untuk satu item pada satu tanggal, laporan menyusun ulang pergerakan saldo hari itu
                    dari ketiga buku mutasi:</p>
                    <div class="doc-calc">saldo H-1  +  produksi kanban  &minus;  kebutuhan  +  addition  &minus;  defect   =   saldo hari ini</div>
                    <p class="small mb-0 mt-2 text-muted">Kolom pemeriksaan menampilkan selisih persamaan di atas. Nol berarti
                    hari itu utuh; selain nol menunjukkan persis di tanggal dan item mana rantainya terputus &mdash; jauh lebih
                    berguna daripada mereset saldo dan kehilangan jejaknya.</p>
                </div>
            </div>

            {{-- ══════════════ PEMERIKSAAN & ISTILAH ══════════════ --}}
            <div class="tab-pane fade" id="doc-periksa">
                <h6 class="doc-print-title">Pemeriksaan &amp; Istilah</h6>

                <h6 class="text-uppercase text-muted small fw-bold mb-1" style="letter-spacing:.08em">Sebelum melapor</h6>
                <p class="text-muted" style="max-width:70ch">Tiga pemeriksaan ini menjelaskan hampir semua kejanggalan yang dilaporkan.
                Kerjakan berurutan.</p>

                <div class="row g-3 mb-4">
                    <div class="col-lg-4">
                        <div class="doc-check h-100">
                            <span class="doc-check-n">1</span>
                            <h6>Periksa tanggal sinkron kapasitas</h6>
                            <p>Di layar verifikasi, kolom <strong>Capacity</strong> menampilkan tanggal kecil di bawah angkanya &mdash;
                            waktu kapasitas terakhir ditarik dari SIREP.</p>
                            <div class="doc-check-do"><span>Kalau tanggalnya sudah lama</span>
                            minta admin menekan <em>Sync Conveyor SIREP</em> di menu Conveyor Data, lalu buat ulang jadwalnya.</div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="doc-check h-100">
                            <span class="doc-check-n">2</span>
                            <h6>Bandingkan listing dengan kapasitas lembur</h6>
                            <p>Bentuk jadwal yang berubah &mdash; misalnya mendadak menjadi dua shift &mdash; selalu berasal dari
                            satu perbandingan: listing hari itu melampaui <code>overtime_capacity</code> atau tidak. Penanda
                            <strong>OT</strong> tidak ada hubungannya.</p>
                            <div class="doc-check-do"><span>Kalau jumlah shift terasa keliru</span>
                            cocokkan total listing hari itu dengan kapasitas lembur conveyornya; bila kapasitasnya yang
                            salah, perbaikannya di SIREP.</div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="doc-check h-100">
                            <span class="doc-check-n">3</span>
                            <h6>Jangan reset saldo untuk merapikan</h6>
                            <p>Reset menghapus modal awal seluruh item sekaligus, termasuk riwayat yang menjelaskan asal saldo.
                            Selisihnya baru terlihat berhari-hari kemudian.</p>
                            <div class="doc-check-do"><span>Kalau saldo perlu diluruskan</span>
                            ada pemeriksaan khusus yang menghitung ulang dari catatan mutasi dan menunjukkan titik saldo terputus.</div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-danger py-2 px-3">
                    <div class="fw-semibold mb-1"><i class="fa-solid fa-ban me-1"></i> Reset saldo bukan tombol perapian</div>
                    <div class="small mb-0">Gunakan hanya bila memang diminta, dan catat kapan dilakukan &mdash; supaya kalau ada selisih
                    di kemudian hari, waktunya bisa ditelusuri.</div>
                </div>

                <hr class="my-4">

                <h6 class="text-uppercase text-muted small fw-bold mb-3" style="letter-spacing:.08em">Istilah</h6>
                <div class="row g-0 doc-gloss">
                    <div class="col-md-6"><dl class="doc-gitem"><dt>Listing</dt><dd>Daftar permintaan dari PPC lewat SIREP: assy apa, berapa, tanggal berapa.</dd></dl></div>
                    <div class="col-md-6"><dl class="doc-gitem"><dt>Cutoff (CO)</dt><dd>Potongan waktu penyerahan dalam satu shift. CO1&ndash;CO4 dari kapasitas normal; CO5 adalah jatah tambahan, dan CO5 shift terakhir menampung seluruh sisa.</dd></dl></div>
                    <div class="col-md-6"><dl class="doc-gitem"><dt>normal_capacity</dt><dd>Kapasitas satu shift tanpa lembur. Dibagi menjadi CO1&ndash;CO4.</dd></dl></div>
                    <div class="col-md-6"><dl class="doc-gitem"><dt>Jumlah shift</dt><dd>Satu atau dua. Dihitung sendiri: listing melebihi <code>overtime_capacity</code> berarti dua shift.</dd></dl></div>
                    <div class="col-md-6"><dl class="doc-gitem"><dt>overtime_capacity</dt><dd>Kapasitas satu shift dengan lembur, sekaligus ambang pemecahan shift. Bila belum dikirim SIREP, dipakai <code>normal_capacity</code>.</dd></dl></div>
                    <div class="col-md-6"><dl class="doc-gitem"><dt>Isi per kartu</dt><dd>Jumlah unit dalam satu lembar kanban. Tetap per komponen, tidak pernah setengah.</dd></dl></div>
                    <div class="col-md-6"><dl class="doc-gitem"><dt>Saldo (sisa)</dt><dd>Kelebihan dari kartu terakhir yang terbawa ke hari berikutnya.</dd></dl></div>
                    <div class="col-md-6"><dl class="doc-gitem"><dt>Verifikasi</dt><dd>Persetujuan manusia atas jadwal. Sesudahnya jadwal terkunci dan kanban dicetak.</dd></dl></div>
                    <div class="col-md-6"><dl class="doc-gitem"><dt>Nomor urut</dt><dd>Empat angka terakhir barcode. Hanya bergerak naik, berputar setelah 9999, dan tidak pernah dimundurkan.</dd></dl></div>
                    <div class="col-md-6"><dl class="doc-gitem"><dt>Unverify</dt><dd>Membuka kembali jadwal terkunci: item pindahan dikembalikan, saldo dipulihkan dari buku mutasi, kanban dihapus, jadwal dibangun ulang dari listing asli.</dd></dl></div>
                    <div class="col-md-6"><dl class="doc-gitem"><dt>Buku mutasi</dt><dd>Catatan setiap perubahan saldo beserta nilai sebelum dan sesudahnya. Dasar pemulihan saldo dan laporan Balance History.</dd></dl></div>
                    <div class="col-md-6"><dl class="doc-gitem"><dt>Over</dt><dd>Listing hari itu melampaui ambang nominal. Jadwalnya tetap dibuat utuh; tandanya untuk diperiksa orang.</dd></dl></div>
                </div>
            </div>


            {{-- PERUBAHAN TERBARU --}}
            <div class="tab-pane fade" id="doc-perubahan">
                <h6 class="doc-print-title">Perubahan Terbaru</h6>

                <p class="text-muted" style="max-width:70ch">Yang berubah belakangan dan berpengaruh langsung ke cara kerja
                sehari-hari. Diurutkan dari yang paling terasa di lapangan.</p>

                <div class="doc-panel mb-3">
                    <div class="doc-panel-title">Jumlah shift dihitung dari data SIREP</div>
                    <p class="small mb-2">Kolom Jumlah Shift di Master Conveyor <strong>dihapus</strong>. Sekarang shift
                    ditentukan sendiri: listing harian yang melebihi <code>overtime_capacity</code> memecah hari itu
                    menjadi dua shift.</p>
                    <p class="small mb-0 text-muted">Tidak ada lagi angka shift yang perlu diisi atau dijaga
                    kebenarannya. Kalau jadwal terasa terlalu padat, yang perlu diperiksa adalah kapasitas di SIREP.</p>
                </div>

                <div class="doc-panel mb-3">
                    <div class="doc-panel-title">CO5 satu shift mengikuti angka SIREP</div>
                    <p class="small mb-2">Dulu jatah CO5 selalu 7/8 cutoff normal. Sekarang hanya berlaku untuk CO5 shift
                    pertama pada hari dua shift; pada hari satu shift, CO5 = <code>overtime_capacity &minus; normal_capacity</code>.</p>
                    <div class="doc-calc">B3-EGI  normal 135  overtime 160   CO5 satu shift = <b>25</b>
B3-ENG  normal  78  overtime  96   CO5 satu shift = <b>18</b></div>
                </div>

                <div class="doc-panel mb-3">
                    <div class="doc-panel-title">Conveyor tanpa angka lembur tetap terjadwal</div>
                    <p class="small mb-2">Saat ini hanya dua conveyor yang punya <code>overtime_capacity</code> dari SIREP.
                    Sisanya memakai <code>normal_capacity</code> sebagai ambang &mdash; dianggap belum punya jatah lembur,
                    tetapi tetap dijadwalkan.</p>
                    <p class="small mb-0 text-muted">Pilihan ini diambil setelah membandingkan dengan alternatifnya. Kalau
                    conveyor tanpa angka lembur dipaksa satu shift, CO5 B1-J42U pernah menampung 560 unit &mdash; empat belas
                    kali cutoff normal, jadwal yang mustahil dikerjakan tapi tampak wajar. Dengan cara sekarang, beban pecah
                    ke dua shift dan CO5 kembali wajar.</p>
                </div>

                <div class="doc-panel mb-3">
                    <div class="doc-panel-title">Sisa pembagian ada di CO1</div>
                    <p class="small mb-2">Cutoff yang menampung paling besar adalah <strong>CO1</strong>, bukan CO4.
                    Hanya berpengaruh pada conveyor yang kapasitasnya tidak habis dibagi empat.</p>
                    <div class="doc-calc">B3-EGI  cap 135    dulu 33/33/33/<b>36</b>    kini <b>36</b>/33/33/33
B3-ENG  cap  78    dulu 19/19/19/<b>21</b>    kini <b>21</b>/19/19/19</div>
                </div>

                <div class="doc-panel mb-3">
                    <div class="doc-panel-title">Daftar conveyor mengikuti SIREP</div>
                    <p class="small mb-0">Conveyor tidak lagi bisa ditambah atau dihapus manual. Yang baru di SIREP masuk
                    otomatis, yang sudah tidak dikirim berstatus <span class="badge bg-secondary">Nonaktif</span> dan berhenti
                    dijadwalkan &mdash; datanya tidak dihapus. Kapasitas juga ditarik dari SIREP; yang tersisa untuk diisi
                    orang hanya Area, Family, Kode Conveyor SIREP, dan Pallet Qty.</p>
                </div>

                <div class="doc-panel mb-3">
                    <div class="doc-panel-title">Conveyor tanpa kapasitas tidak lagi ditambahkan</div>
                    <p class="small mb-0">SIREP mengirim sebagian conveyor dengan kapasitas kosong. Sebelumnya semuanya
                    dibuatkan baris master, lalu muncul sebagai peringatan &ldquo;kapasitas belum tersinkron&rdquo; pada setiap
                    generate padahal memang tidak bisa dijadwalkan. Kini conveyor seperti itu dilewati dan ditandai
                    <span class="badge bg-light text-dark border">dilewati</span> pada pratinjau sinkronisasi; begitu SIREP
                    mengisi kapasitasnya, ia masuk sendiri.</p>
                </div>

                <div class="doc-panel mb-3">
                    <div class="doc-panel-title">Sinkron &amp; generate tidak lagi berjalan berlipat</div>
                    <p class="small mb-2">Membuka halaman jadwal memicu generate otomatis, sementara tombol
                    <strong>Generate</strong> memicu proses kedua yang sama beratnya. Keduanya menggarap tabel yang sama,
                    sehingga bila berjalan bersamaan lamanya berlipat sampai timeout.</p>
                    <p class="small mb-0 text-muted">Sekarang hanya satu proses per rentang tanggal yang boleh berjalan.
                    Proses otomatis juga dilewati bila rentang yang sama baru saja selesai &mdash; ditampilkan sebagai
                    pesan biru <em>&ldquo;sinkronisasi otomatis dilewati&rdquo;</em>, bukan kegagalan. Penekanan tombol
                    manual tidak pernah dilewati; bila terhalang proses lain, hasilnya jujur disebut dilewati, bukan berhasil.</p>
                </div>

                <div class="doc-panel mb-3">
                    <div class="doc-panel-title">Pesan gagal generate menyebut sebab yang benar</div>
                    <p class="small mb-0">Dulu setiap kegagalan &mdash; termasuk kesalahan di dalam sistem sendiri &mdash;
                    dilaporkan sebagai &ldquo;gagal mengambil data listing dari PPC&rdquo;, sehingga penelusuran mengarah ke
                    pihak yang tidak bersalah. Sekarang pesan yang tampil adalah sebab sebenarnya, dan langkah mana yang berhenti.</p>
                </div>

                <div class="doc-panel mb-3">
                    <div class="doc-panel-title">Versi aplikasi tampil di footer</div>
                    <p class="small mb-0">Footer menampilkan versi kode yang sedang berjalan beserta waktu pembaruannya.
                    Gunanya untuk memastikan perbaikan benar-benar sudah terpasang di server &mdash; bila nomornya belum
                    berubah, yang berjalan masih kode lama.</p>
                </div>

                <div class="doc-panel mb-3">
                    <div class="doc-panel-title">Riwayat cetak kanban</div>
                    <p class="small mb-0">Dua menu baru di bawah eKanban Print: <strong>History Print Cutting</strong> dan
                    <strong>History Print Shikake</strong>, untuk menelusuri kanban yang pernah dicetak.</p>
                </div>

                <div class="doc-panel mb-3">
                    <div class="doc-panel-title">Tipe mesin pada Master Machine</div>
                    <p class="small mb-0">Setiap mesin kini punya tipe &mdash; BONDER, DBL CRIMP, JOINT, SHIELD, CUTTING,
                    atau TWIST. Terisi otomatis dari pemakaian yang tercatat; mesin yang tidak punya padanan dibiarkan kosong
                    untuk diisi manual.</p>
                </div>

                <div class="alert alert-primary py-2 px-3 mb-0">
                    <div class="fw-semibold mb-1"><i class="fa-solid fa-shield-halved me-1"></i> Saldo kanban lebih terjaga</div>
                    <div class="small mb-0">Reset saldo tidak lagi menghapus jejak asal-usulnya dan tidak pernah menurunkan
                    nomor urut barcode. Tersedia juga pemeriksaan yang menghitung ulang saldo dari catatan mutasi dan
                    menunjukkan persis di titik mana saldo terputus.</div>
                </div>
            </div>

            </div>{{-- /tab-content --}}
        </div>

        <div class="card-footer bg-transparent">
            <small class="text-muted">Angka contoh diambil dari conveyor B3-EGI (normal 135, lembur 160) dan shikake BONDER isi 12 unit, tanggal 3&ndash;4 September 2026. Bila kapasitas di SIREP berubah, angka contoh ini ikut tidak berlaku &mdash; yang mengikat adalah aturannya, bukan angkanya.</small>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Semua aturan diawali .doc agar tidak mempengaruhi halaman lain.
   Warna aksen mengambil var(--primary) sehingga ikut berubah bila tema diganti. */
.doc .doc-tabs{border-bottom:1px solid var(--bs-border-color);gap:2px;flex-wrap:wrap}
.doc .doc-tabs .nav-link{
    border:0;border-bottom:2px solid transparent;border-radius:0;
    color:var(--bs-secondary-color);font-size:.875rem;font-weight:500;
    padding:.55rem .85rem;white-space:nowrap;
}
.doc .doc-tabs .nav-link:hover{color:rgba(var(--primary),1);background:transparent}
.doc .doc-tabs .nav-link.active{
    color:rgba(var(--primary),1);background:transparent;
    border-bottom-color:rgba(var(--primary),1);
}
.doc .doc-step{
    display:inline-block;font-size:.7rem;font-weight:700;letter-spacing:.03em;
    background:rgba(var(--primary),.12);color:rgba(var(--primary),1);
    padding:1px 6px;border-radius:4px;margin-right:.4rem;
}
.doc .doc-print-title{display:none}

/* Panel netral — dipakai untuk blok data di dalam tab. */
.doc .doc-panel{
    background:var(--bs-tertiary-bg);border:1px solid var(--bs-border-color);
    border-radius:.5rem;padding:1rem 1.1rem;
}
.doc .doc-panel-title{
    font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
    color:var(--bs-secondary-color);margin-bottom:.75rem;
}
.doc .doc-num{font-weight:600;font-variant-numeric:tabular-nums}

.doc .doc-kv{display:grid;grid-template-columns:auto 1fr;gap:.35rem 1rem;font-size:.85rem}
.doc .doc-kv span{color:var(--bs-secondary-color)}
.doc .doc-kv b{font-weight:600;font-variant-numeric:tabular-nums}

/* Kartu aturan bernomor. */
.doc .doc-rule{
    display:flex;gap:.7rem;font-size:.85rem;height:100%;
    background:var(--bs-body-bg);border:1px solid var(--bs-border-color);
    border-radius:.5rem;padding:.85rem .9rem;
}
.doc .doc-rule-n{
    flex:0 0 24px;height:24px;border-radius:50%;display:grid;place-items:center;
    background:rgba(var(--primary),.12);color:rgba(var(--primary),1);
    font-size:.75rem;font-weight:700;
}

/* Batang pembagian cutoff. */
.doc .doc-bars{display:flex;flex-direction:column;gap:.8rem}
.doc .doc-barrow{display:grid;grid-template-columns:120px 1fr;gap:.8rem;align-items:center}
.doc .doc-barlab{font-size:.78rem;color:var(--bs-secondary-color);text-align:right;line-height:1.5}
.doc .doc-bar{display:flex;gap:3px;height:38px}
.doc .doc-seg{
    display:flex;align-items:center;justify-content:center;border-radius:4px;min-width:0;
    font-size:.78rem;font-weight:600;font-variant-numeric:tabular-nums;
    background:rgba(var(--primary),.13);color:rgba(var(--primary),1);
}
.doc .doc-seg-ot{background:rgba(255,193,7,.28);color:#7a5600}
.doc .doc-seg-off{background:transparent;border:1px dashed var(--bs-border-color);color:var(--bs-secondary-color);font-weight:400}

/* Anatomi barcode. */
.doc .doc-bc{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:1.75rem;font-weight:700;letter-spacing:.05em;display:flex}
.doc .doc-bc span{padding:2px 3px;border-bottom:3px solid transparent}
.doc .doc-bc-1{color:rgba(var(--primary),1);border-bottom-color:rgba(var(--primary),1)}
.doc .doc-bc-2{color:var(--bs-body-color);border-bottom-color:var(--bs-secondary-color)}
.doc .doc-bc-3{color:#198754;border-bottom-color:#198754}
.doc .doc-bc-4{color:var(--bs-secondary-color);border-bottom-color:var(--bs-border-color)}
.doc .doc-bc-5{color:#b0560f;border-bottom-color:#b0560f}
.doc .doc-bckey{border-top:2px solid var(--bs-border-color);padding-top:.4rem;font-size:.78rem;color:var(--bs-secondary-color)}
.doc .doc-bckey b{display:block;font-family:ui-monospace,monospace;font-size:.8rem;color:var(--bs-body-color)}
.doc .doc-bck-1{border-top-color:rgba(var(--primary),1)}
.doc .doc-bck-2{border-top-color:var(--bs-secondary-color)}
.doc .doc-bck-3{border-top-color:#198754}
.doc .doc-bck-5{border-top-color:#b0560f}

/* Blok perhitungan. */
.doc .doc-calc{
    font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.8rem;line-height:1.85;
    white-space:pre;overflow-x:auto;color:var(--bs-secondary-color);margin:0;
}
.doc .doc-calc b{color:var(--bs-body-color);font-weight:700}
.doc .doc-up{color:#198754;font-weight:600}

/* Kartu pemeriksaan. */
.doc .doc-check{
    background:var(--bs-body-bg);border:1px solid var(--bs-border-color);
    border-radius:.5rem;padding:1rem;display:flex;flex-direction:column;
}
.doc .doc-check h6{font-size:.92rem;font-weight:600;margin:0 0 .4rem}
.doc .doc-check p{font-size:.83rem;color:var(--bs-secondary-color);margin:0 0 .6rem}
.doc .doc-check-n{
    width:24px;height:24px;border-radius:50%;display:grid;place-items:center;
    background:rgba(255,193,7,.28);color:#7a5600;font-size:.75rem;font-weight:700;margin-bottom:.6rem;
}
.doc .doc-check-do{
    margin-top:auto;padding-top:.6rem;border-top:1px solid var(--bs-border-color);font-size:.81rem;
}
.doc .doc-check-do span{display:block;font-weight:600;color:var(--bs-body-color)}

/* Istilah. */
.doc .doc-gitem{padding:.7rem 1.5rem .7rem 0;border-top:1px solid var(--bs-border-color)}
.doc .doc-gitem dt{font-weight:600;font-size:.9rem;margin-bottom:.1rem}
.doc .doc-gitem dd{margin:0;font-size:.83rem;color:var(--bs-secondary-color)}

/* Diagram alur. */
.doc .doc-scroll{overflow-x:auto}
.doc .doc-flow{display:block;min-width:860px;color:var(--bs-body-color)}
.doc .doc-box{fill:var(--bs-body-bg);stroke:var(--bs-border-color);stroke-width:1}
.doc .doc-box-key{stroke:rgba(var(--primary),1);stroke-width:2}
.doc .doc-svg-num{font-size:10px;font-weight:700;fill:rgba(var(--primary),1)}
.doc .doc-svg-title{font-size:13.5px;font-weight:600;fill:var(--bs-body-color)}
.doc .doc-svg-sub{font-size:11px;fill:var(--bs-secondary-color)}
.doc .doc-svg-arrow{stroke:currentColor;stroke-width:1.5;fill:none;opacity:.45}
.doc .doc-svg-loop{stroke:#b0560f;stroke-width:1.5;fill:none;stroke-dasharray:5 4}
.doc .doc-svg-loop-bg{fill:var(--bs-body-bg)}
.doc .doc-svg-loop-txt{font-size:11px;fill:#b0560f}

@media (max-width:575.98px){
    .doc .doc-barrow{grid-template-columns:1fr;gap:.3rem}
    .doc .doc-barlab{text-align:left}
}

/* ── Cetak / simpan PDF (A4) ─────────────────────────────────────────────
   Dokumen cetak harus LENGKAP, jadi seluruh tab ditampilkan, bukan hanya
   yang sedang dibuka. Tiap topik mulai di halaman baru dengan judulnya. */
@media print{
    @page{size:A4 portrait;margin:14mm 12mm}
    html,body{-webkit-print-color-adjust:exact;print-color-adjust:exact;background:#fff!important}

    .dark-sidebar,.app-header,.header-wrapper,.go-top,.loader-wrapper,
    .breadcrumb-main,.page-header,.app-footer,.card-header .btn,footer{display:none!important}
    .app-wrapper,.app-content,.container-fluid,.main-content,.card,.card-body,.card-footer{
        margin:0!important;padding:0!important;width:100%!important;max-width:none!important;
        border:0!important;box-shadow:none!important;background:#fff!important;
    }
    .doc{font-size:10pt;line-height:1.5}
    .doc .doc-tabs{display:none!important}
    .doc .tab-pane{display:block!important;opacity:1!important;visibility:visible!important}
    .doc .tab-pane + .tab-pane{break-before:page;page-break-before:always;padding-top:4mm}
    .doc .doc-print-title{
        display:block!important;font-size:14pt;font-weight:700;
        padding-bottom:5px;margin:0 0 14px;border-bottom:1.5pt solid rgba(var(--primary),1);
    }
    .doc .card-header{display:block!important;padding:0 0 10px!important;border-bottom:1pt solid #bbb!important;margin-bottom:14px!important}

    .doc .doc-panel,.doc .doc-check,.doc .doc-rule,.doc .alert,
    .doc .doc-bars,.doc .doc-barrow,.doc .doc-calc,.doc .doc-gitem,
    .doc .doc-bckey,.doc .doc-scroll,.doc table,.doc tr{break-inside:avoid;page-break-inside:avoid}
    .doc h5,.doc h6{break-after:avoid;page-break-after:avoid}
    .doc thead{display:table-header-group}

    .doc .doc-scroll{overflow:visible!important}
    .doc .doc-flow{min-width:0;width:100%;height:auto}
    .doc .doc-calc{overflow:visible!important;font-size:8pt}
    .doc .doc-bc{font-size:15pt}
    .doc .table-responsive{overflow:visible!important}
    .doc .doc-panel{background:#F6F8F9!important;border:.75pt solid #C9D2D8!important}
    .doc .doc-check,.doc .doc-rule{border:.75pt solid #C9D2D8!important}
    .doc .card-footer{padding-top:10px!important;border-top:.75pt solid #bbb!important;margin-top:14px!important}
    .doc a{text-decoration:none;color:inherit}
}
</style>
@endpush
