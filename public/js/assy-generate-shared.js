/**
 * Shared helpers for:
 *  1) initAssyGenerateModal — the "Generate Assy Schedule" modal (2-step progress:
 *     clone listing from SIREP, then generate schedule from listing+capacity).
 *     Originally built for Assy Scheduler; now also used by Schedule Verification.
 *  2) initAssyAutoSync — the "sync status badges + silent auto sync/generate on
 *     page load" widget from the Dashboard, reused so other schedule pages open
 *     with already-fresh data instead of requiring a manual visit to Dashboard first.
 */

function initAssyGenerateModal(opts) {
    opts = opts || {};
    var generateUrl = opts.generateUrl;
    var csrfToken = opts.csrfToken;
    var defaultDays = opts.defaultDays || 10;
    var syncStatusUrl = opts.syncStatusUrl || null;
    var onSuccess = opts.onSuccess || function () {};

    $('#generate_conveyor_id').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#generateModal'),
        allowClear: true,
        placeholder: '- All Conveyor -'
    });

    var genStartDate = moment();
    var genEndDate = moment().add(defaultDays, 'days');
    $('#generate_dates').daterangepicker({
        startDate: genStartDate,
        endDate: genEndDate,
        locale: { format: 'DD-MM-YYYY' }
    });

    $('#btn-generate').off('click.assyGenerate').on('click.assyGenerate', function () {
        resetGenerateModal();
        $('#generateModal').modal('show');
    });

    $('#btn-modal-close, #btn-cancel-generate').off('click.assyGenerate').on('click.assyGenerate', function () {
        $('#generateModal').modal('hide');
    });

    $('#btn-close-after-generate').off('click.assyGenerate').on('click.assyGenerate', function () {
        $('#generateModal').modal('hide');
        onSuccess();
    });

    $('#btn-back-to-form').off('click.assyGenerate').on('click.assyGenerate', function () {
        $('#generate-progress-panel').hide();
        $('#generate-form-panel').show();
        $('#btn-modal-close').show();
    });

    $('#generateForm').off('submit.assyGenerate').on('submit.assyGenerate', function (e) {
        e.preventDefault();

        var dates = $('#generate_dates').data('daterangepicker');
        var startDate = dates.startDate.format('YYYY-MM-DD');
        var endDate = dates.endDate.format('YYYY-MM-DD');
        var conveyorId = $('#generate_conveyor_id').val();

        var rangeLabel = dates.startDate.format('DD-MM-YYYY') + ' s/d ' + dates.endDate.format('DD-MM-YYYY');
        $('#progress-date-range').text('Rentang tanggal: ' + rangeLabel);
        $('#generate-form-panel').hide();
        $('#generate-progress-panel').show();
        $('#btn-modal-close').hide();

        resetProgressSteps();

        $.ajax({
            url: generateUrl,
            type: 'POST',
            data: {
                _token: csrfToken,
                start_date: startDate,
                end_date: endDate,
                conveyor_id: conveyorId
            },
            success: function (response) {
                renderProgressResult(response);
                if (response.success) {
                    if (syncStatusUrl) refreshSyncStatusBadges(syncStatusUrl);
                    onSuccess();
                }
            },
            error: function (xhr) {
                var res = xhr.responseJSON || {
                    success: false,
                    step_failed: 'unknown',
                    message: 'Terjadi kesalahan saat menghubungi server.',
                    data: null
                };
                renderProgressResult(res);
            }
        });
    });

    function resetGenerateModal() {
        $('#generate-form-panel').show();
        $('#generate-progress-panel').hide();
        $('#generate-result-banner').hide().html('');
        $('#btn-close-after-generate').hide();
        $('#btn-back-to-form').hide();
        $('#btn-modal-close').show();
        resetProgressSteps();
    }

    function resetProgressSteps() {
        $('#step1-row').css('border-left-color', '#6c757d').css('opacity', '1');
        $('#step1-icon').html('<span class="spinner-border spinner-border-sm text-secondary" role="status"></span>');
        $('#step1-detail').text('Mengambil data terbaru dari API SIREP...');

        $('#step2-row').css('border-left-color', '#6c757d').css('opacity', '0.45');
        $('#step2-icon').html('<i class="fa-solid fa-circle text-secondary f-s-14"></i>');
        $('#step2-detail').text('Menunggu proses step 1...');

        $('#generate-result-banner').hide().html('');
        $('#btn-close-after-generate').hide();
        $('#btn-back-to-form').hide();
    }

    function renderProgressResult(response) {
        var stepFailed = response.step_failed || (response.success ? null : 'unknown');
        var syncDetail = (response.data && response.data.sync_detail) ? response.data.sync_detail : null;
        var generated  = response.data ? (response.data.generated || 0) : 0;
        var msg        = response.message || '';

        if (stepFailed === 'sync_listing' || stepFailed === 'unknown') {
            setStepFail(1, syncDetail
                ? buildSyncText(syncDetail)
                : 'Gagal terhubung ke API SIREP. Proses dihentikan, tidak ada sumber cadangan yang dicoba.');
            setStepSkipped(2, 'Dilewati karena step 1 gagal.');
        } else {
            setStepSuccess(1, syncDetail ? buildSyncText(syncDetail) : 'Data listing berhasil di-clone ke listing_stage.');

            if (stepFailed === 'generate') {
                setStepFail(2, msg);
            } else {
                setStepSuccess(2, generated > 0
                    ? generated + ' schedule berhasil dibuat.'
                    : 'Semua schedule sudah up-to-date, tidak ada data baru.');
            }
        }

        var bannerType = response.success ? 'success' : 'danger';
        var bannerIcon = response.success ? 'fa-circle-check' : 'fa-circle-xmark';
        $('#generate-result-banner')
            .removeClass('alert-success alert-danger alert-warning')
            .addClass('alert-' + bannerType)
            .html('<i class="fa-solid ' + bannerIcon + ' me-2"></i>' + msg)
            .fadeIn(300);

        $('#btn-close-after-generate').show();
        if (!response.success) $('#btn-back-to-form').show();
        $('#btn-modal-close').show();
    }

    function setStepSuccess(num, detail) {
        $('#step' + num + '-row').css('border-left-color', '#198754').css('opacity', '1');
        $('#step' + num + '-icon').html('<i class="fa-solid fa-circle-check text-success f-s-18"></i>');
        $('#step' + num + '-detail').text(detail);
    }

    function setStepFail(num, detail) {
        $('#step' + num + '-row').css('border-left-color', '#dc3545').css('opacity', '1');
        $('#step' + num + '-icon').html('<i class="fa-solid fa-circle-xmark text-danger f-s-18"></i>');
        $('#step' + num + '-detail').text(detail);
    }

    function setStepSkipped(num, detail) {
        $('#step' + num + '-row').css('border-left-color', '#adb5bd').css('opacity', '0.6');
        $('#step' + num + '-icon').html('<i class="fa-solid fa-circle-minus text-secondary f-s-18"></i>');
        $('#step' + num + '-detail').text(detail);
    }

    function buildSyncText(sd) {
        return 'Clone selesai — Total: ' + sd.total_records + ' | Disync: ' + sd.synced + ' | Dilewati: ' + sd.skipped;
    }
}

/**
 * Populate the global "Sinkron: ... / Generate: ..." badges (now living in the
 * navbar, shared across every page) from the dashboard.sync-status endpoint.
 * Public on purpose: called once for every page on load, and again by
 * initAssyAutoSync/manual generate flows after a successful run.
 */
function refreshSyncStatusBadges(syncStatusUrl) {
    $.ajax({
        url: syncStatusUrl,
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            var syncBadge     = $('#badge-last-sync');
            var generateBadge = $('#badge-last-generate');

            if (data.last_sync) {
                $('#last-sync-time').text(data.last_sync);
                syncBadge.removeClass('bg-secondary-subtle text-secondary').addClass('bg-info-subtle text-info');
            } else {
                $('#last-sync-time').text('Belum pernah');
            }

            if (data.last_generate) {
                $('#last-generate-time').text(data.last_generate);
                generateBadge.removeClass('bg-secondary-subtle text-secondary').addClass('bg-success-subtle text-success');
            } else {
                $('#last-generate-time').text('Belum pernah');
            }
        }
    });
}

function initAssyAutoSync(opts) {
    opts = opts || {};
    var syncStatusUrl = opts.syncStatusUrl;
    var generateUrl   = opts.generateUrl;
    var csrfToken     = opts.csrfToken;
    var autoDays      = opts.autoDays || 3;
    var onSuccess     = opts.onSuccess || function () {};

    refreshSyncStatusBadges(syncStatusUrl);
    autoGenerate();

    function autoGenerate() {
        var today = new Date();
        var start = formatLocalDate(today);
        var endObj = new Date(today);
        endObj.setDate(endObj.getDate() + autoDays);
        var end = formatLocalDate(endObj);

        $.ajax({
            url: generateUrl,
            type: 'POST',
            data: {
                _token: csrfToken,
                start_date: start,
                end_date: end,
                conveyor_id: null
            },
            success: function (response) {
                var generated = response.data ? (response.data.generated || 0) : 0;
                if (response.success) {
                    showBanner(true, generated);
                    refreshSyncStatusBadges(syncStatusUrl);
                    onSuccess();
                } else {
                    var isSyncFail = (response.step_failed === 'sync_listing' || response.step_failed === 'unknown');
                    showBanner(false, 0, isSyncFail);
                }
            },
            error: function () {
                showBanner(false, 0, true);
            }
        });
    }

    function showBanner(success, generated, isSyncFail) {
        var banner = $('#assy-generate-banner');
        if (!banner.length) return;

        var msg, bgColor, textColor, iconClass;
        if (success) {
            msg = 'Berhasil generate jadwal assy dengan <strong>' + generated + '</strong> data.';
            bgColor = '#d1e7dd'; textColor = '#0a3622'; iconClass = 'fa-circle-check';
        } else if (isSyncFail) {
            msg = 'Gagal mengambil data listing dari PPC.';
            bgColor = '#f8d7da'; textColor = '#58151c'; iconClass = 'fa-circle-xmark';
        } else {
            msg = 'Gagal melakukan generate jadwal assy.';
            bgColor = '#f8d7da'; textColor = '#58151c'; iconClass = 'fa-circle-xmark';
        }

        var html = '<i class="fa-solid ' + iconClass + ' me-2"></i>' +
            '<span class="flex-grow-1">' + msg + '</span>' +
            '<button type="button" class="btn-close ms-3" id="assy-banner-close" aria-label="Close"></button>';

        if (window._assyBannerTimer) clearTimeout(window._assyBannerTimer);

        banner
            .stop(true, true)
            .attr('class', 'd-flex align-items-center px-3 py-2 rounded mb-3 shadow-sm')
            .css({ display: 'none', 'background-color': bgColor, color: textColor, 'font-size': '0.875rem' })
            .html(html)
            .fadeIn(400);

        $('#assy-banner-close').on('click', function () { banner.fadeOut(200); });
        window._assyBannerTimer = setTimeout(function () { banner.fadeOut(200); }, 8000);
    }

    function formatLocalDate(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }
}
