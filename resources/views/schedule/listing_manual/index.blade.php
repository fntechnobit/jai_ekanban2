@extends('layouts.master')

@section('title', 'Listing Manual')

@section('breadcrumb')
    <x-page-header menu-code="listing_manual" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="card-title mb-0"><i class="fa-solid fa-keyboard"></i> Listing Manual</h5>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <input type="text" class="form-control form-control-sm" id="filter_dates" readonly
                               placeholder="Pilih rentang tanggal" style="width: 220px;">
                        <select class="form-select form-select-sm" id="filter_conveyor" style="width: 180px;">
                            <option value=""></option>
                            @foreach($conveyors as $conveyor)
                                <option value="{{ $conveyor->conveyor }}">{{ $conveyor->conveyor }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-secondary btn-sm" id="btn-reset" title="Reset Filter">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </button>
                        @if(auth()->user()->hasMenuPermission('listing_manual', 'can_create'))
                            <button type="button" class="btn btn-success btn-sm" id="btn-import">
                                <i class="fa-solid fa-file-import"></i> Import
                            </button>
                            <button type="button" class="btn btn-primary btn-sm" id="btn-add">
                                <i class="fa-solid fa-plus"></i> Tambah Listing
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info small mb-3">
                    <i class="fa-solid fa-circle-info"></i>
                    Listing di sini dijadwalkan <strong>bersama listing SIREP</strong> pada tanggal dan conveyor yang sama;
                    urutan alokasinya ke cutoff mengikuti <strong>Seq</strong>. Sinkronisasi SIREP tidak mengubah maupun
                    menghapusnya. Jadwal baru terbentuk setelah <strong>Generate</strong>, dan listing yang jadwalnya
                    sudah diverifikasi terkunci sampai di-unverify.
                </div>

                <div class="table-responsive">
                    <table id="listing-manual-table" class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th width="3%">No</th>
                                <th width="7%">Tanggal</th>
                                <th width="8%">Conveyor</th>
                                <th width="4%" class="text-center">Seq</th>
                                <th>Assy</th>
                                <th width="8%">Carline</th>
                                <th width="5%" class="text-center">Qty</th>
                                <th width="14%">Kemasan</th>
                                <th>Keterangan</th>
                                <th width="11%">Status Jadwal</th>
                                <th width="9%">Diinput</th>
                                <th width="6%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('schedule.listing_manual.form')
    @include('schedule.listing_manual.import_modal')
@endsection

@section('script')
    <script>
        $(function () {
            var CSRF = '{{ csrf_token() }}';
            // Generate memakai endpoint Assy Scheduler, jadi tawarannya hanya untuk yang
            // boleh menekan tombol Generate di sana.
            var CAN_GENERATE = @json(auth()->user()->hasMenuPermission('assy_scheduler', 'can_create'));
            var ROUTES = {
                base: "{{ url('schedule/listing-manual') }}",
                store: "{{ route('schedule.listing-manual.store') }}",
                datatable: "{{ route('schedule.listing-manual.datatable') }}",
                assyOptions: "{{ route('schedule.listing-manual.assy-options') }}",
                dayListing: "{{ route('schedule.listing-manual.day-listing') }}",
                importUrl: "{{ route('schedule.listing-manual.import') }}",
                template: "{{ route('schedule.listing-manual.download-template') }}",
                generate: "{{ route('schedule.assy-scheduler.generate') }}"
            };

            var $modal = $('#listingModal');

            function esc(value) {
                return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
            }

            function fmtDate(ymd) {
                return moment(ymd, 'YYYY-MM-DD').format('DD-MM-YYYY');
            }

            // ── Filter & tabel ───────────────────────────────────────────────
            $('#filter_conveyor').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: '- All Conveyor -'
            });

            function defaultRange() {
                return { start: moment().subtract(7, 'days'), end: moment().add(30, 'days') };
            }

            $('#filter_dates').daterangepicker({
                startDate: defaultRange().start,
                endDate: defaultRange().end,
                locale: { format: 'DD-MM-YYYY' }
            });

            var table = $('#listing-manual-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: ROUTES.datatable,
                    data: function (d) {
                        var range = $('#filter_dates').data('daterangepicker');
                        d.start_date = range.startDate.format('YYYY-MM-DD');
                        d.end_date = range.endDate.format('YYYY-MM-DD');
                        d.conveyor = $('#filter_conveyor').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'tanggal', name: 'listing_stage.listing_date_time', searchable: false },
                    { data: 'conveyor', name: 'listing_stage.conveyor' },
                    { data: 'seq', name: 'listing_stage.seq', className: 'text-center', searchable: false },
                    { data: 'assy_label', name: 'listing_stage.assy' },
                    { data: 'carline', name: 'listing_stage.carline', defaultContent: '-' },
                    { data: 'qty', name: 'listing_stage.qty', className: 'text-center', searchable: false },
                    { data: 'kemasan', name: 'kemasan', orderable: false, searchable: false },
                    { data: 'remark', name: 'listing_stage.remark', defaultContent: '-' },
                    { data: 'status_jadwal', name: 'status_jadwal', orderable: false, searchable: false },
                    { data: 'input_by', name: 'u.name' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                ordering: false,
                pageLength: 50
            });

            $('#filter_conveyor').on('change', function () { table.ajax.reload(); });
            $('#filter_dates').on('apply.daterangepicker', function () { table.ajax.reload(); });

            $('#btn-reset').on('click', function () {
                var range = defaultRange();
                $('#filter_conveyor').val('').trigger('change.select2');
                $('#filter_dates').data('daterangepicker').setStartDate(range.start);
                $('#filter_dates').data('daterangepicker').setEndDate(range.end);
                table.ajax.reload();
            });

            // ── Hasil simpan & tawaran generate ──────────────────────────────
            // Satu panggilan generate untuk seluruh tanggal yang terdampak, SELALU semua
            // conveyor: langkah sinkron generate menghapus listing SIREP (beserta jadwal
            // pending-nya) di rentang itu untuk semua conveyor, sedangkan filter conveyor
            // hanya membatasi yang disusun ulang — conveyor lain akan kehilangan jadwal
            // pending-nya sampai generate berikutnya.
            function generatePlan(targets) {
                targets = (targets || []).filter(function (t) { return t.conveyor_id; });

                if (!targets.length) {
                    return null;
                }

                var dates = targets.map(function (t) { return t.date; }).sort();
                var start = dates[0];
                var end = dates[dates.length - 1];

                return {
                    start_date: start,
                    end_date: end,
                    conveyor_id: '',
                    label: 'tanggal ' + fmtDate(start) + (start !== end ? ' s/d ' + fmtDate(end) : '')
                };
            }

            function runGenerate(plan) {
                Swal.fire({
                    title: 'Generate jadwal...',
                    html: 'Menarik listing SIREP dan menyusun jadwal <strong>' + esc(plan.label) + '</strong>. Mohon tunggu.',
                    allowOutsideClick: false,
                    didOpen: function () { Swal.showLoading(); }
                });

                $.ajax({
                    url: ROUTES.generate,
                    type: 'POST',
                    data: {
                        _token: CSRF,
                        start_date: plan.start_date,
                        end_date: plan.end_date,
                        conveyor_id: plan.conveyor_id
                    }
                }).done(function (res) {
                    Swal.fire({
                        icon: res.skipped ? 'info' : 'success',
                        title: res.skipped ? 'Generate dilewati' : 'Jadwal tersusun',
                        text: res.message
                    });
                }).fail(function (xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Generate gagal',
                        text: (xhr.responseJSON && xhr.responseJSON.message) || 'Terjadi kesalahan saat generate.'
                    });
                }).always(function () {
                    table.ajax.reload(null, false);
                });
            }

            function showResult(title, message, warnings, targets) {
                warnings = warnings || [];
                var html = '<p>' + esc(message) + '</p>';

                if (warnings.length) {
                    html += '<div class="alert alert-warning text-start small"><ul class="mb-0 ps-3">'
                        + warnings.map(function (w) { return '<li>' + esc(w) + '</li>'; }).join('')
                        + '</ul></div>';
                }

                var plan = generatePlan(targets);
                var icon = warnings.length ? 'warning' : 'success';

                if (!plan) {
                    Swal.fire({ icon: icon, title: title, html: html });
                    return;
                }

                if (!CAN_GENERATE) {
                    html += '<p class="small text-muted mb-0">Jadwal ' + esc(plan.label)
                        + ' tersusun ulang pada generate berikutnya.</p>';
                    Swal.fire({ icon: icon, title: title, html: html });
                    return;
                }

                html += '<p class="mb-1">Susun ulang jadwal <strong>' + esc(plan.label) + '</strong> sekarang?</p>'
                    + '<p class="small text-muted mb-0">Sama seperti tombol Generate di Assy Scheduler (semua conveyor): '
                    + 'listing SIREP ditarik ulang lalu jadwal yang belum diverifikasi disusun ulang.</p>';

                Swal.fire({
                    icon: icon,
                    title: title,
                    html: html,
                    showCancelButton: true,
                    confirmButtonText: 'Generate sekarang',
                    cancelButtonText: 'Nanti'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        runGenerate(plan);
                    }
                });
            }

            // ── Form ─────────────────────────────────────────────────────────
            $('#conveyor').select2({
                theme: 'bootstrap-5',
                dropdownParent: $modal,
                placeholder: 'Pilih conveyor'
            });

            $('#assy').select2({
                theme: 'bootstrap-5',
                dropdownParent: $modal,
                placeholder: 'Cari atau ketik assy number',
                tags: true,
                minimumInputLength: 0,
                ajax: {
                    url: ROUTES.assyOptions,
                    delay: 250,
                    data: function (params) {
                        return { conveyor: $('#conveyor').val(), q: params.term || '' };
                    },
                    processResults: function (res) {
                        return { results: (res && res.results) || [] };
                    },
                    // Daftar assy bergantung conveyor; tanpa conveyor tidak ada yang ditanyakan.
                    transport: function (params, success, failure) {
                        if (!$('#conveyor').val()) {
                            success({ results: [] });
                            return { abort: function () {} };
                        }
                        return $.ajax(params).then(success).fail(failure);
                    }
                },
                createTag: function (params) {
                    var term = $.trim(params.term);
                    return term ? { id: term, text: term + ' (ketik manual)', newTag: true } : null;
                }
            });

            // Assy dari riwayat listing membawa nilai acuannya; assy master/ketikan
            // belum punya, jadi kolom acuan dikosongkan dan diisi server bila ada.
            $('#assy').on('select2:select', function (e) {
                var data = e.params.data || {};
                var fromHistory = data.assycode !== undefined;

                $('#assycode').val(fromHistory ? data.assycode : '');
                $('#carline').val(fromHistory ? (data.carline || '') : '');
                $('#snp').val(fromHistory ? data.snp : '');
                $('#snpa').val(fromHistory ? data.snpa : '');
                $('#plt').val(fromHistory ? data.plt : '');
                $('#mode').val(fromHistory && data.mode ? String(data.mode) : '');

                if (!fromHistory) {
                    $('#assycode').trigger('focus');
                }
            });

            function setAssy(value) {
                var $assy = $('#assy').empty();

                if (value) {
                    $assy.append(new Option(value, value, true, true));
                }

                $assy.trigger('change.select2');
            }

            function renderDayListing(data) {
                var currentId = String($('#listing_id').val() || '');
                var rows = data.rows || [];
                var html;

                if (!rows.length) {
                    html = '<span class="text-muted">Belum ada listing pada tanggal & conveyor ini.</span>';
                } else {
                    html = '<table class="table table-sm table-bordered mb-1"><thead><tr>'
                        + '<th class="text-center">Seq</th><th>Sumber</th><th>Assy</th><th class="text-end">Qty</th>'
                        + '</tr></thead><tbody>';

                    rows.forEach(function (row) {
                        var mine = currentId && String(row.id) === currentId;
                        html += '<tr' + (mine ? ' class="table-warning" title="Listing yang sedang diedit"' : '') + '>'
                            + '<td class="text-center">' + esc(row.seq) + '</td>'
                            + '<td>' + (row.manual
                                ? '<span class="badge bg-primary">Manual</span>'
                                : '<span class="badge bg-secondary">SIREP</span>') + '</td>'
                            + '<td>' + esc(row.assy) + ' <span class="text-muted">' + esc(row.assycode) + '</span></td>'
                            + '<td class="text-end">' + esc(row.qty) + '</td>'
                            + '</tr>';
                    });

                    html += '</tbody></table>';
                }

                var info = 'Total <strong>' + Number(data.total_qty || 0).toLocaleString('id-ID') + '</strong> pcs';

                if (data.capacity) {
                    info += ' · kapasitas/shift normal ' + esc(data.capacity)
                        + (data.overtime_capacity ? ', lembur ' + esc(data.overtime_capacity) : '');
                } else {
                    info += ' · <span class="text-danger">kapasitas SIREP belum tersinkron</span>';
                }

                $('#day-listing').html(html + '<div class="text-muted">' + info + '</div>');

                // Tambah baru: usulkan seq sesudah listing terakhir hari itu.
                if (!currentId && $('#seq').val() === '') {
                    var maxSeq = rows.reduce(function (max, row) { return Math.max(max, Number(row.seq) || 0); }, 0);
                    $('#seq').val(maxSeq + 1);
                }
            }

            var dayRequest = null;

            function loadDayListing() {
                var date = $('#listing_date').val();
                var conveyor = $('#conveyor').val();

                if (dayRequest) {
                    dayRequest.abort();
                }

                if (!date || !conveyor) {
                    $('#day-listing').html('<span class="text-muted">Pilih tanggal dan conveyor untuk melihat listing hari itu.</span>');
                    return;
                }

                $('#day-listing').html('<span class="text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Memuat...</span>');

                dayRequest = $.get(ROUTES.dayListing, { date: date, conveyor: conveyor })
                    .done(function (res) { renderDayListing(res.data || {}); })
                    .fail(function (xhr) {
                        if (xhr.statusText !== 'abort') {
                            $('#day-listing').html('<span class="text-danger">Gagal memuat listing hari itu.</span>');
                        }
                    });
            }

            $('#listing_date, #conveyor').on('change', loadDayListing);

            function resetForm() {
                $('#listingForm')[0].reset();
                $('#listing_id').val('');
                $('.error-text').text('');
                $('#conveyor').val('').trigger('change.select2');
                setAssy('');
            }

            $('#btn-add').on('click', function () {
                resetForm();
                $('#listingModalLabel').text('Tambah Listing Manual');
                $('#listing_date').val(moment().format('YYYY-MM-DD'));
                loadDayListing();
                $modal.modal('show');
            });

            $(document).on('click', '.btn-edit', function () {
                var id = $(this).data('id');

                $.get(ROUTES.base + '/' + id).done(function (res) {
                    var d = res.data;

                    resetForm();
                    $('#listing_id').val(d.id);
                    $('#listing_date').val(d.listing_date);
                    $('#conveyor').val(d.conveyor).trigger('change.select2');
                    setAssy(d.assy);
                    $('#assycode').val(d.assycode);
                    $('#qty').val(d.qty);
                    $('#seq').val(d.seq);
                    $('#carline').val(d.carline || '');
                    $('#snp').val(d.snp);
                    $('#snpa').val(d.snpa);
                    $('#plt').val(d.plt);
                    $('#mode').val(d.mode ? String(d.mode) : '');
                    $('#remark').val(d.remark || '');

                    $('#listingModalLabel').text('Edit Listing Manual');
                    loadDayListing();
                    $modal.modal('show');
                }).fail(function (xhr) {
                    Swal.fire('Error!', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal memuat listing.', 'error');
                });
            });

            $('#listingForm').on('submit', function (e) {
                e.preventDefault();
                $('.error-text').text('');

                var id = $('#listing_id').val();
                var data = $(this).serialize() + (id ? '&_method=PUT' : '');
                var $btn = $('#btn-save').prop('disabled', true);

                $.ajax({
                    url: id ? ROUTES.base + '/' + id : ROUTES.store,
                    type: 'POST',
                    data: data
                }).done(function (res) {
                    $modal.modal('hide');
                    table.ajax.reload(null, false);
                    showResult('Tersimpan', res.message, res.data.warnings, res.data.regenerate);
                }).fail(function (xhr) {
                    var json = xhr.responseJSON || {};

                    if (xhr.status === 422 && json.errors && !Array.isArray(json.errors)) {
                        $.each(json.errors, function (key, messages) {
                            $('.' + key + '_error').text(messages[0]);
                        });
                    } else {
                        Swal.fire('Error!', json.message || 'Gagal menyimpan listing.', 'error');
                    }
                }).always(function () {
                    $btn.prop('disabled', false);
                });
            });

            $(document).on('click', '.btn-delete', function () {
                var id = $(this).data('id');

                Swal.fire({
                    title: 'Hapus listing ini?',
                    text: 'Baris jadwal listing ini yang belum diverifikasi ikut terhapus.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal'
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: ROUTES.base + '/' + id,
                        type: 'POST',
                        data: { _token: CSRF, _method: 'DELETE' }
                    }).done(function (res) {
                        table.ajax.reload(null, false);
                        showResult('Terhapus', res.message, res.data.warnings, res.data.regenerate);
                    }).fail(function (xhr) {
                        Swal.fire('Error!', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus listing.', 'error');
                    });
                });
            });

            // ── Import ───────────────────────────────────────────────────────
            $('#btn-import').on('click', function () {
                $('#importListingForm')[0].reset();
                $('#importListingForm .text-danger').text('');
                $('#importListingModal').modal('show');
            });

            $('#btn-download-template').on('click', function () {
                window.location.href = ROUTES.template;
            });

            $('#importListingForm').on('submit', function (e) {
                e.preventDefault();
                $('#importListingForm .text-danger').text('');

                var $btn = $('#btn-submit-import').prop('disabled', true)
                    .html('<i class="fa-solid fa-spinner fa-spin"></i> Importing...');

                $.ajax({
                    url: ROUTES.importUrl,
                    type: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false
                }).done(function (res) {
                    $('#importListingModal').modal('hide');
                    table.ajax.reload(null, false);
                    showResult('Import berhasil', res.message, res.data.warnings, res.data.regenerate);
                }).fail(function (xhr) {
                    var json = xhr.responseJSON || {};

                    // Galat per baris (array) dari importer, atau galat field form (objek).
                    if (Array.isArray(json.errors)) {
                        var list = json.errors.slice(0, 15).map(function (m) { return '<li>' + esc(m) + '</li>'; }).join('');
                        var more = json.errors.length > 15 ? '<li>... dan ' + (json.errors.length - 15) + ' baris lain</li>' : '';
                        Swal.fire({
                            icon: 'error',
                            title: 'Import dibatalkan',
                            html: '<p>' + esc(json.message) + '</p><ul class="text-start small ps-3">' + list + more + '</ul>',
                            width: 700
                        });
                        return;
                    }

                    if (json.errors) {
                        $.each(json.errors, function (key, messages) {
                            $('#importListingForm .' + key + '_error').text(messages[0]);
                        });
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Import gagal',
                        html: '<div class="text-start" style="white-space: pre-line;">' + esc(json.message || 'Terjadi kesalahan.') + '</div>'
                    });
                }).always(function () {
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-upload"></i> Import');
                });
            });
        });
    </script>
@endsection
