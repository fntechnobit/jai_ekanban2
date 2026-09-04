@extends('layouts.master')

@section('title', 'Samakan Saldo Kanban')

@section('breadcrumb')
    <x-page-header menu-code="balance_reset" />
@endsection

@section('content')
<div class="container-fluid">

    {{-- Status koneksi ke sistem pembanding --}}
    <div class="alert {{ $reference['ok'] ? 'alert-info' : 'alert-danger' }} d-flex align-items-center gap-2">
        <i class="fa-solid {{ $reference['ok'] ? 'fa-plug-circle-check' : 'fa-plug-circle-xmark' }}"></i>
        <div>
            <strong>Sistem pembanding:</strong>
            {{ $reference['database'] ?? 'belum diatur' }} — {{ $reference['message'] }}
        </div>
    </div>

    <div class="row g-3">
        {{-- ============ KIRI: form ============ --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fa-solid fa-scale-balanced"></i> Pengaturan Penyamaan</h5>
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <label for="cutoff_date" class="form-label">
                            Tanggal acuan <span class="text-danger">*</span>
                        </label>
                        <input type="date" id="cutoff_date" class="form-control form-control-sm"
                               value="{{ now()->toDateString() }}">
                        <small class="form-text text-muted">
                            Saldo diambil dari kondisi sistem pembanding pada <strong>akhir tanggal ini</strong>.
                            Seluruh kanban di sistem ini yang tanggalnya <strong>setelah</strong> tanggal acuan
                            akan dihapus dan jadwalnya dibuka kembali.
                        </small>
                    </div>

                    <div class="mb-2 d-flex justify-content-between align-items-center">
                        <label class="form-label mb-0">Conveyor <span class="text-danger">*</span></label>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" id="btnSelectAll">Pilih semua</button>
                            <button type="button" class="btn btn-outline-secondary" id="btnSelectNone">Kosongkan</button>
                        </div>
                    </div>

                    <div class="border rounded p-2 mb-3" style="max-height:280px; overflow-y:auto">
                        @forelse ($conveyors as $cv)
                            @php
                                $bedaKapasitas = $cv['available'] && $cv['capacity'] !== $cv['ref_capacity'];
                            @endphp
                            <div class="form-check d-flex align-items-start gap-2 py-1">
                                <input class="form-check-input cv-check" type="checkbox"
                                       value="{{ $cv['id'] }}" id="cv{{ $cv['id'] }}"
                                       {{ $cv['available'] ? '' : 'disabled' }}>
                                <label class="form-check-label flex-grow-1" for="cv{{ $cv['id'] }}">
                                    <span class="fw-semibold">{{ $cv['conveyor'] }}</span>
                                    @unless ($cv['available'])
                                        <span class="badge bg-secondary ms-1">tidak ada di pembanding</span>
                                    @endunless
                                    @if ($bedaKapasitas)
                                        <span class="badge bg-warning text-dark ms-1"
                                              title="Kapasitas/shift berbeda — saldo tetap bisa disamakan, tapi hasil generate berikutnya akan tetap berbeda sampai master diselaraskan.">
                                            kapasitas beda
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            sini {{ $cv['capacity'] }} ·
                                            pembanding {{ $cv['ref_capacity'] }}
                                        </small>
                                    @endif
                                </label>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Tidak ada conveyor.</p>
                        @endforelse
                    </div>

                    <div class="mb-3">
                        <label for="note" class="form-label">Catatan <small class="text-muted">(opsional)</small></label>
                        <input type="text" id="note" class="form-control form-control-sm" maxlength="500"
                               placeholder="mis. penyamaan setelah v2 tertinggal 6 hari">
                    </div>

                    <button type="button" class="btn btn-primary w-100" id="btnPreview">
                        <i class="fa-solid fa-magnifying-glass-chart"></i> Lihat Pratinjau
                    </button>
                    <small class="d-block text-muted text-center mt-2">
                        Pratinjau tidak mengubah data apa pun.
                    </small>
                </div>
            </div>
        </div>

        {{-- ============ KANAN: hasil pratinjau ============ --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fa-solid fa-list-check"></i> Pratinjau Perubahan</h5>
                    <span class="badge bg-light text-dark" id="previewMeta"></span>
                </div>
                <div class="card-body" id="previewBody">
                    <p class="text-muted mb-0">
                        Pilih tanggal acuan dan conveyor, lalu tekan <strong>Lihat Pratinjau</strong>.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ Riwayat ============ --}}
    <div class="card mt-3">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Penyamaan</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Waktu</th><th>Tanggal acuan</th><th>Conveyor</th>
                            <th class="text-end">Circuit</th><th class="text-end">Shikake</th>
                            <th class="text-end">Kanban dihapus</th>
                            <th>Oleh</th><th>Status</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($history as $h)
                            <tr>
                                <td>{{ $h->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $h->cutoff_date?->format('Y-m-d') }}</td>
                                <td><small>{{ $h->conveyorNames() }}</small></td>
                                <td class="text-end">{{ number_format($h->circuits_updated) }}</td>
                                <td class="text-end">{{ number_format($h->shikakes_updated) }}</td>
                                <td class="text-end">{{ number_format($h->kanban_deleted) }}</td>
                                <td><small>{{ $h->creator->name ?? '-' }}</small></td>
                                <td>
                                    @if ($h->status === 'undone')
                                        <span class="badge bg-secondary">dibatalkan</span>
                                    @else
                                        <span class="badge bg-success">diterapkan</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($h->status !== 'undone')
                                        <button class="btn btn-sm btn-outline-danger btn-undo" data-id="{{ $h->id }}">
                                            <i class="fa-solid fa-rotate-left"></i> Batalkan
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-3">Belum pernah dijalankan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const fmt  = n => Number(n || 0).toLocaleString('id-ID');
    let lastPreview = null;

    const selected = () => [...document.querySelectorAll('.cv-check:checked')].map(c => +c.value);

    document.getElementById('btnSelectAll').onclick = () =>
        document.querySelectorAll('.cv-check:not(:disabled)').forEach(c => c.checked = true);
    document.getElementById('btnSelectNone').onclick = () =>
        document.querySelectorAll('.cv-check').forEach(c => c.checked = false);

    async function post(url, payload) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
            body: JSON.stringify(payload)
        });
        return {ok: res.ok, data: await res.json().catch(() => ({}))};
    }

    document.getElementById('btnPreview').onclick = async function () {
        const cutoff = document.getElementById('cutoff_date').value;
        const cvs    = selected();

        if (!cutoff)      return Swal.fire('Belum lengkap', 'Tanggal acuan wajib diisi.', 'warning');
        if (!cvs.length)  return Swal.fire('Belum lengkap', 'Pilih minimal satu conveyor.', 'warning');

        const body = document.getElementById('previewBody');
        body.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

        const {ok, data} = await post('{{ route('system.balance-reset.preview') }}', {
            cutoff_date: cutoff, conveyor_ids: cvs
        });

        if (!ok || !data.ok) {
            body.innerHTML = `<div class="alert alert-danger mb-0">${data.message || 'Gagal memuat pratinjau.'}</div>`;
            return;
        }

        lastPreview = data;
        render(data);
    };

    function render(d) {
        const t = d.totals;
        document.getElementById('previewMeta').textContent = `acuan ${d.cutoff} · sumber ${d.reference}`;

        let html = `
        <div class="row g-2 mb-3">
          ${tile('Saldo circuit diubah', t.circuit, 'primary')}
          ${tile('Saldo shikake diubah', t.shikake, 'primary')}
          ${tile('Kanban dihapus', t.kanban, t.kanban ? 'danger' : 'secondary')}
          ${tile('Jadwal dibuka', t.schedule, t.schedule ? 'warning' : 'secondary')}
        </div>`;

        (d.warnings || []).forEach(w => {
            html += `<div class="alert alert-warning py-2"><i class="fa-solid fa-triangle-exclamation"></i> ${w}</div>`;
        });

        html += `<div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-3">
          <thead class="table-light"><tr>
            <th>Conveyor</th><th class="text-end">Saldo beda</th><th class="text-end">Nomor urut naik</th>
            <th class="text-end">Total sisa (sini → acuan)</th><th class="text-end">Kanban dihapus</th>
          </tr></thead><tbody>`;

        d.conveyors.forEach(c => {
            const ci = c.items.circuit, sh = c.items.shikake;
            const sisaBeda = ci.sisa_beda + sh.sisa_beda;
            const nuNaik   = ci.nomor_urut_naik + sh.nomor_urut_naik;
            const lokal    = ci.sisa_lokal + sh.sisa_lokal;
            const acuan    = ci.sisa_acuan + sh.sisa_acuan;
            const tanpa    = ci.tanpa_acuan + sh.tanpa_acuan;

            html += `<tr>
              <td><strong>${c.conveyor}</strong>
                  ${tanpa ? `<br><small class="text-muted">${fmt(tanpa)} item tanpa acuan — dibiarkan</small>` : ''}</td>
              <td class="text-end ${sisaBeda ? 'text-danger fw-semibold' : 'text-muted'}">${fmt(sisaBeda)}</td>
              <td class="text-end ${nuNaik ? 'text-warning fw-semibold' : 'text-muted'}">${fmt(nuNaik)}</td>
              <td class="text-end">${fmt(lokal)} <i class="fa-solid fa-arrow-right text-muted mx-1"></i> ${fmt(acuan)}</td>
              <td class="text-end ${c.purge.kanban ? 'text-danger' : 'text-muted'}">${fmt(c.purge.kanban)}
                  ${c.purge.printed ? `<br><small class="text-danger">${fmt(c.purge.printed)} sudah dicetak</small>` : ''}</td>
            </tr>`;
        });

        html += `</tbody></table></div>`;

        const adaPerubahan = t.circuit + t.shikake + t.kanban + t.schedule > 0;

        html += adaPerubahan
            ? `<button class="btn btn-danger w-100" id="btnApply">
                 <i class="fa-solid fa-scale-balanced"></i> Terapkan Penyamaan
               </button>`
            : `<div class="alert alert-success mb-0"><i class="fa-solid fa-circle-check"></i>
                 Tidak ada yang perlu diubah — kedua sistem sudah sama pada tanggal acuan ini.
               </div>`;

        document.getElementById('previewBody').innerHTML = html;

        if (adaPerubahan) document.getElementById('btnApply').onclick = confirmApply;
    }

    function tile(label, value, tone) {
        return `<div class="col-6 col-xl-3">
          <div class="border rounded p-2 text-center h-100">
            <div class="h4 mb-0 text-${tone}">${fmt(value)}</div>
            <small class="text-muted">${label}</small>
          </div></div>`;
    }

    async function confirmApply() {
        const t = lastPreview.totals;

        const {value: confirm} = await Swal.fire({
            title: 'Terapkan penyamaan?',
            html: `<div class="text-start small">
                     <p class="mb-2">Tindakan ini akan:</p>
                     <ul class="mb-2">
                       <li>menimpa <strong>${fmt(t.circuit + t.shikake)}</strong> baris saldo</li>
                       <li>menghapus <strong>${fmt(t.kanban)}</strong> kartu kanban setelah ${lastPreview.cutoff}</li>
                       <li>membuka kembali <strong>${fmt(t.schedule)}</strong> jadwal terverifikasi</li>
                     </ul>
                     <p class="mb-2 text-danger">Kartu kanban yang sudah dicetak untuk tanggal tersebut
                     tidak lagi berlaku dan perlu dicetak ulang.</p>
                     <p class="mb-1">Ketik <strong>SAMAKAN</strong> untuk melanjutkan:</p>
                   </div>`,
            input: 'text',
            inputPlaceholder: 'SAMAKAN',
            showCancelButton: true,
            confirmButtonText: 'Terapkan',
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'Batal',
            inputValidator: v => v !== 'SAMAKAN' ? 'Ketik SAMAKAN tepat seperti itu.' : undefined
        });

        if (confirm !== 'SAMAKAN') return;

        Swal.fire({title: 'Menyamakan…', allowOutsideClick: false, didOpen: () => Swal.showLoading()});

        const {ok, data} = await post('{{ route('system.balance-reset.apply') }}', {
            cutoff_date : document.getElementById('cutoff_date').value,
            conveyor_ids: selected(),
            note        : document.getElementById('note').value || null,
            confirm     : 'SAMAKAN'
        });

        if (ok && data.success) {
            await Swal.fire('Selesai', data.message, 'success');
            location.reload();
        } else {
            Swal.fire('Gagal', data.message || 'Penyamaan tidak dapat dijalankan.', 'error');
        }
    }

    document.querySelectorAll('.btn-undo').forEach(btn => {
        btn.onclick = async function () {
            const res = await Swal.fire({
                title: 'Batalkan penyamaan ini?',
                html: `<div class="text-start small">
                         Saldo dikembalikan ke nilai sebelum penyamaan.
                         <strong>Kanban yang sudah terhapus tidak dibuat ulang</strong> —
                         jadwalnya perlu diverifikasi kembali seperti biasa.
                       </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Batalkan penyamaan',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'Tutup'
            });

            if (!res.isConfirmed) return;

            const {ok, data} = await post(`{{ url('system/balance-reset') }}/${this.dataset.id}/undo`, {});

            if (ok && data.success) {
                await Swal.fire('Selesai', data.message, 'success');
                location.reload();
            } else {
                Swal.fire('Gagal', data.message || 'Pembatalan tidak dapat dijalankan.', 'error');
            }
        };
    });
})();
</script>
@endsection
