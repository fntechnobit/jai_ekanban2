@extends('layouts.master')

@section('title', 'Conveyor Data')

@section('breadcrumb')
    <x-page-header menu-code="master_conveyor" />
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Conveyor Data</h5>
            <div class="card-tools">
                <button type="button" class="btn btn-primary btn-sm" id="btn-sirep-sync">
                    <i class="fa-solid fa-cloud-arrow-down me-1"></i> Sync Conveyor SIREP
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="cv-info mb-3">
                Daftar conveyor berasal dari <strong>API SIREP</strong> dan tidak dapat ditambah atau dihapus
                dari sini. Tekan <strong>Sync Conveyor SIREP</strong> untuk menariknya. Conveyor yang sudah
                tidak dikirim SIREP otomatis berstatus <span class="badge bg-secondary">Nonaktif</span> dan
                berhenti ikut dijadwalkan maupun diverifikasi &mdash; datanya tidak dihapus.
                Yang masih bisa diubah di sini: Area, Family, Kode Conveyor SIREP, dan Pallet Qty.
            </div>

            <style>
                /* Warna ditulis eksplisit agar tidak mewarisi alert tema yang kontrasnya rendah. */
                .cv-info{
                    background:#EEF4FA; border:1px solid #C9DAEA; border-left:4px solid #2C6FA8;
                    color:#1F3247; border-radius:6px; padding:12px 16px; font-size:.86rem; line-height:1.6;
                }
                .cv-info strong{color:#12283D}
                .cv-info .badge{vertical-align:baseline}
            </style>

            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="filter_area" class="form-label">Area :</label>
                    <select class="form-select select2" id="filter_area">
                        <option value="">- All Area -</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}">{{ $area->area }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_status" class="form-label">Status :</label>
                    <select class="form-select" id="filter_status">
                        <option value="">- Semua Status -</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filter_family" class="form-label">Family :</label>
                    <select class="form-select select2" id="filter_family">
                        <option value="">- All Family -</option>
                        @foreach($families as $family)
                            <option value="{{ $family->id }}">{{ $family->family }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table id="master-conveyor-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Area</th>
                            <th>Conveyor</th>
                            <th>Status</th>
                            <th>Family</th>
                            <th>Capacity/Shift (SIREP)</th>
                            <th>Sinkron Terakhir</th>
                            <th>Pallet Qty</th>
                            <th width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('master_data.master_conveyor.form')

<!-- Hasil sinkronisasi SIREP -->
<div class="modal fade" id="sirepSyncModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-cloud-arrow-down me-2"></i> Sinkronisasi Conveyor dari SIREP
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 small mb-3" id="sirep-sync-message">
                    Memuat pratinjau...
                </div>

                <div class="alert alert-warning py-2 small mb-3">
                    Sinkronisasi <strong>menambah</strong> conveyor baru dari SIREP, <strong>memperbarui</strong>
                    nama dan kapasitas yang sudah ada, dan <strong>menonaktifkan</strong> conveyor yang tidak
                    dikirim lagi oleh SIREP. Tidak ada data yang dihapus. Jumlah shift tidak tersedia di API
                    dan dihitung per tanggal saat generate. Jadwal yang sudah dibuat tidak ikut berubah.
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle" id="sirep-sync-table">
                        <thead class="table-light">
                            <tr>
                                <th>Conveyor (SIREP)</th>
                                <th>Di master</th>
                                <th class="text-end">Normal</th>
                                <th class="text-end">Over (SIREP)</th>
                                <th class="text-end">Kapasitas lama</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                @if(auth()->user()->hasMenuPermission('master_conveyor', 'can_update'))
                    <button type="button" class="btn btn-primary btn-sm" id="btn-sirep-apply" disabled>
                        <i class="fa-solid fa-check me-1"></i> Terapkan ke Master
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script>
        $(function () {
            // Initialize Select2 for filters
            $('#filter_area, #filter_family').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                width: '100%',
                placeholder: function() {
                    return $(this).data('placeholder') || 'Select...';
                }
            });

            // DataTable
            var table = $('#master-conveyor-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('master-data.master-conveyor.datatable') }}",
                    data: function(d) {
                        d.area_id = $('#filter_area').val();
                        d.family_id = $('#filter_family').val();
                        d.status = $('#filter_status').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'area_name', name: 'area.area' },
                    { data: 'conveyor', name: 'conveyor' },
                    { data: 'status_label', name: 'is_active', className: 'text-center' },
                    { data: 'family_names', name: 'family_names', orderable: false },
                    { data: 'capacity_label', name: 'capacity' },
                    { data: 'synced_label', name: 'capacity_synced_at' },
                    { data: 'pallet_qty', name: 'pallet_qty' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                pageLength: 50
            });

            // Filter change events
            $('#filter_area, #filter_family, #filter_status').on('change', function() {
                table.ajax.reload();
            });

            // Initialize Select2 for form
            function initFormSelect2() {
                $('#master_area_id').select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $('#masterConveyorModal'),
                    placeholder: 'Select Area'
                });

                $('#family_ids').select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $('#masterConveyorModal'),
                    placeholder: 'Select Family',
                    allowClear: true
                });
            }

            // ── Sinkronisasi kapasitas dari SIREP ─────────────────────────────
            // Dua langkah: pratinjau dulu (tidak menulis apa pun), baru diterapkan.
            var sirepPreviewUrl = "{{ route('master-data.master-conveyor.sirep-preview') }}";
            var sirepApplyUrl   = "{{ route('master-data.master-conveyor.sirep-apply') }}";

            function renderSirepRows(rows) {
                var body = $('#sirep-sync-table tbody').empty();

                if (!rows || !rows.length) {
                    body.append('<tr><td colspan="7" class="text-center text-muted">Tidak ada data dari SIREP.</td></tr>');
                    return;
                }

                var badge = {
                    baru:     '<span class="badge bg-primary">baru</span>',
                    berubah:  '<span class="badge bg-warning text-dark">berubah</span>',
                    sama:     '<span class="badge bg-success">sama</span>',
                    nonaktif: '<span class="badge bg-secondary">dinonaktifkan</span>'
                };

                rows.forEach(function (r) {
                    body.append(
                        '<tr>' +
                        '<td>' + (r.sirep_name || '<span class="text-muted">&mdash;</span>') + '</td>' +
                        '<td>' + (r.conveyor || '<span class="text-muted">belum ada</span>') + '</td>' +
                        '<td class="text-end">' + (r.normal_capacity ?? '-') + '</td>' +
                        '<td class="text-end">' + (r.overtime_capacity ?? '-') + '</td>' +
                        '<td class="text-end">' + (r.capacity_lama ?? '<span class="text-muted">kosong</span>') + '</td>' +
                        '<td>' + (badge[r.state] || '') + ' <small class="text-muted">' + (r.status || '') + '</small></td>' +
                        '</tr>'
                    );
                });
            }

            $('#btn-sirep-sync').on('click', function () {
                var btn = $(this);
                btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menghubungi SIREP...');

                $('#sirep-sync-message').removeClass('alert-danger').addClass('alert-info').text('Memuat pratinjau...');
                $('#sirep-sync-table tbody').empty();
                $('#btn-sirep-apply').prop('disabled', true);
                $('#sirepSyncModal').modal('show');

                $.get(sirepPreviewUrl)
                    .done(function (res) {
                        var data = res.data || {};
                        $('#sirep-sync-message').text(res.message || data.message || 'Pratinjau siap.');
                        renderSirepRows(data.rows);
                        $('#btn-sirep-apply').prop('disabled', !(data.rows && data.rows.length));
                    })
                    .fail(function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message)
                            || 'Gagal menghubungi API SIREP.';
                        $('#sirep-sync-message').removeClass('alert-info').addClass('alert-danger').text(msg);
                    })
                    .always(function () {
                        btn.prop('disabled', false).html('<i class="fa-solid fa-cloud-arrow-down me-1"></i> Sync Conveyor SIREP');
                    });
            });

            $('#btn-sirep-apply').on('click', function () {
                var btn = $(this);

                Swal.fire({
                    title: 'Terapkan ke master?',
                    text: 'Kapasitas conveyor akan ditimpa nilai dari SIREP. Jadwal yang sudah dibuat tidak berubah.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, terapkan',
                    cancelButtonText: 'Batal'
                }).then(function (hasil) {
                    if (!hasil.isConfirmed) return;

                    btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menerapkan...');

                    $.post(sirepApplyUrl, { _token: '{{ csrf_token() }}' })
                        .done(function (res) {
                            var data = res.data || {};
                            $('#sirep-sync-message').removeClass('alert-danger').addClass('alert-info')
                                .text(res.message || data.message || 'Selesai.');
                            renderSirepRows(data.rows);
                            table.ajax.reload(null, false);
                            Swal.fire('Berhasil!', res.message || 'Kapasitas diperbarui.', 'success');
                        })
                        .fail(function (xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                || 'Gagal menerapkan sinkronisasi.';
                            Swal.fire('Gagal!', msg, 'error');
                        })
                        .always(function () {
                            btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Terapkan ke Master');
                        });
                });
            });

            // Edit Conveyor
            $(document).on('click', '.btn-edit', function () {
                var id = $(this).data('id');
                $.ajax({
                    url: "{{ route('master-data.master-conveyor.index') }}/" + id + "/edit",
                    type: 'GET',
                    success: function (response) {
                        const conveyor = response.data || response;

                        $('#conveyor_id').val(conveyor.id);
                        $('#conveyor').val(conveyor.conveyor);
                        $('#masterConveyorModalLabel').text('Edit Conveyor — ' + conveyor.conveyor);
                        $('#sirep_conveyor_code').val(conveyor.sirep_conveyor_code || '');
                        $('#pallet_qty').val(conveyor.pallet_qty);
                        $('#capacity_display').text(
                            conveyor.capacity
                                ? conveyor.capacity + (conveyor.overtime_capacity ? ' / ' + conveyor.overtime_capacity + ' OT' : '')
                                : 'belum sinkron dari SIREP'
                        );
                        $('#capacity_synced_display').text(conveyor.capacity_synced_label || 'belum pernah');

                        if (conveyor.is_active) {
                            $('#status_display')
                                .attr('class', 'cv-status cv-status-on')
                                .text('Aktif — masih terdaftar di SIREP');
                        } else {
                            $('#status_display')
                                .attr('class', 'cv-status cv-status-off')
                                .text('Nonaktif' + (conveyor.deactivated_label ? ' sejak ' + conveyor.deactivated_label : '')
                                      + ' — tidak ikut dijadwalkan');
                        }
                        
                        initFormSelect2();
                        
                        $('#master_area_id').val(conveyor.master_area_id).trigger('change');
                        $('#family_ids').val(conveyor.family_ids).trigger('change');

                        $('#masterConveyorModalLabel').text('Edit Conveyor');
                        $('.error-text').text('');
                        $('#masterConveyorModal').modal('show');
                    },
                    error: function (xhr) {
                        Swal.fire('Error!', 'Failed to load conveyor data', 'error');
                    }
                });
            });

            // Save Conveyor
            $('#masterConveyorForm').submit(function (e) {
                e.preventDefault();
                $('.error-text').text('');

                var formData = $(this).serialize();
                var conveyorId = $('#conveyor_id').val();
                var url = conveyorId ? "{{ route('master-data.master-conveyor.index') }}/" + conveyorId : "{{ route('master-data.master-conveyor.store') }}";

                if (conveyorId) {
                    formData += '&_method=PUT';
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    success: function (response) {
                        $('#masterConveyorModal').modal('hide');
                        table.ajax.reload();
                        Swal.fire('Success!', response.message, 'success');
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (key, value) {
                                // Handle array field errors
                                var errorKey = key.replace('.', '_');
                                $('.' + errorKey + '_error').text(value[0]);
                            });
                        } else {
                            Swal.fire('Error!', xhr.responseJSON.message || 'Something went wrong', 'error');
                        }
                    }
                });
            });


        });
    </script>
@endsection
