@extends('layouts.master')

@section('title', 'Backup Database')

@section('breadcrumb')
    <x-page-header menu-code="database_backup" />
@endsection

@section('content')
<div class="container-fluid">

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-exclamation me-1"></i>
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <!-- DB Info Card -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <span class="bg-primary bg-opacity-10 text-primary rounded p-2 me-2">
                            <i class="fa-solid fa-database fa-lg"></i>
                        </span>
                        <h6 class="card-title mb-0">Database</h6>
                    </div>
                    <p class="fs-5 fw-semibold mb-0 text-truncate" title="{{ $database }}">{{ $database }}</p>
                    <small class="text-muted">{{ $host }}</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <span class="bg-success bg-opacity-10 text-success rounded p-2 me-2">
                            <i class="fa-solid fa-table fa-lg"></i>
                        </span>
                        <h6 class="card-title mb-0">Total Tables</h6>
                    </div>
                    <p class="fs-5 fw-semibold mb-0">{{ number_format($tableCount) }}</p>
                    <small class="text-muted">tabel dalam database</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <span class="bg-info bg-opacity-10 text-info rounded p-2 me-2">
                            <i class="fa-solid fa-hard-drive fa-lg"></i>
                        </span>
                        <h6 class="card-title mb-0">Ukuran Database</h6>
                    </div>
                    <p class="fs-5 fw-semibold mb-0">{{ $dbSizeMb ?? '0' }} MB</p>
                    <small class="text-muted">estimasi ukuran data + index</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Action Card -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fa-solid fa-download me-1"></i> Download SQL Dump
            </h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                Klik tombol di bawah untuk mengunduh file backup database dalam format <strong>.sql</strong>.
                File ini berisi seluruh struktur tabel dan data yang dapat digunakan untuk restore.
            </p>

            <div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
                <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                <div>
                    <strong>Perhatian:</strong> Proses backup bisa memerlukan beberapa saat tergantung ukuran database.
                    Jangan tutup browser atau refresh halaman selama proses berlangsung.
                </div>
            </div>

            <div class="mb-3">
                <h6 class="text-muted small mb-1">Detail Backup</h6>
                <table class="table table-sm table-borderless" style="max-width: 400px;">
                    <tr>
                        <td class="text-muted ps-0" width="140">Koneksi</td>
                        <td><span class="badge bg-secondary">{{ strtoupper($connection) }}</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Host</td>
                        <td>{{ $host }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Database</td>
                        <td>{{ $database }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">User</td>
                        <td>{{ $username }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Format</td>
                        <td>.sql (mysqldump)</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Opsi</td>
                        <td><small>--single-transaction, --routines, --triggers, --add-drop-table</small></td>
                    </tr>
                </table>
            </div>

            <a href="{{ route('system.database-backup.download') }}"
               class="btn btn-primary"
               id="btn-download"
               onclick="handleDownloadClick(this)">
                <i class="fa-solid fa-file-arrow-down me-1"></i> Download Backup (.sql)
            </a>
            <span id="download-spinner" class="ms-2 text-muted d-none">
                <i class="fa-solid fa-circle-notch fa-spin me-1"></i> Sedang memproses...
            </span>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function handleDownloadClick(btn) {
    const spinner = document.getElementById('download-spinner');
    btn.classList.add('disabled');
    spinner.classList.remove('d-none');

    // Re-enable after 15 seconds to allow repeated downloads
    setTimeout(() => {
        btn.classList.remove('disabled');
        spinner.classList.add('d-none');
    }, 15000);
}
</script>
@endpush
