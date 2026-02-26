@extends('layouts.master')

@section('title', 'Dashboard')

@section('breadcrumb')
<div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
    <h1 class="page-title fw-medium fs-18 mb-0">Dashboard</h1>
    <nav>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ url('dashboard') }}"><i class="fa-solid fa-home"></i></a></li>
            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
        </ol>
    </nav>
</div>
@endsection

@section('content')
<div class="container-fluid">

    {{-- Dynamic Flash Banner: Generate Assy Schedule Response --}}
    <div id="assy-generate-banner" style="display:none;"></div>

    <!-- Chart: Kanban Printed per Machine -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-chart-bar text-primary me-2"></i> Kanban Printed per Machine (Top 20)
                    </h5>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btn-refresh-chart" title="Refresh">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 400px;">
                        <canvas id="machineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTables: Kanban per Machine -->
    <div class="row">
        <!-- Cutting -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-scissors text-success me-2"></i> Cutting - Kanban Printed per Machine
                    </h5>
                </div>
                <div class="card-body">
                    <table id="cutting-machine-table" class="table table-bordered table-striped table-sm" style="width:100%">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Machine</th>
                                <th>Conveyor</th>
                                <th width="15%" class="text-end">Kanban Printed</th>
                                <th width="15%" class="text-end">Print Count</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Shikake -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-link text-warning me-2"></i> Shikake - Kanban Printed per Machine
                    </h5>
                </div>
                <div class="card-body">
                    <table id="shikake-machine-table" class="table table-bordered table-striped table-sm" style="width:100%">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Machine</th>
                                <th>Process</th>
                                <th>Conveyor</th>
                                <th width="15%" class="text-end">Kanban Printed</th>
                                <th width="15%" class="text-end">Print Count</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
<script src="{{ asset('assets/vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
$(function () {
    // Auto-generate Assy Schedule for next 3 days on dashboard load
    autoGenerateAssySchedule();

    // ========== CHART ==========
    var ctx = document.getElementById('machineChart').getContext('2d');
    var machineChart = null;

    function loadChart() {
        $.ajax({
            url: "{{ route('dashboard.chart-data') }}",
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                if (machineChart) {
                    machineChart.destroy();
                }

                machineChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Cutting',
                                data: data.cutting,
                                backgroundColor: 'rgba(25, 135, 84, 0.7)',
                                borderColor: 'rgba(25, 135, 84, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Shikake',
                                data: data.shikake,
                                backgroundColor: 'rgba(255, 193, 7, 0.7)',
                                borderColor: 'rgba(255, 193, 7, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top' },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return context.dataset.label + ': ' + context.parsed.y.toLocaleString() + ' kanban';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: { maxRotation: 45, minRotation: 0, font: { size: 11 } }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    callback: function (value) { return value.toLocaleString(); }
                                },
                                title: { display: true, text: 'Jumlah Kanban' }
                            }
                        }
                    }
                });
            },
            error: function () {
                Swal.fire('Error', 'Gagal memuat data chart', 'error');
            }
        });
    }

    loadChart();

    $('#btn-refresh-chart').on('click', function () {
        loadChart();
    });

    // ========== DATATABLES ==========

    // Cutting Machine DataTable
    $('#cutting-machine-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('dashboard.cutting-datatable') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'machine', name: 'machine' },
            { data: 'conveyor_name', name: 'conveyor_name' },
            { data: 'total_printed', name: 'total_printed', className: 'text-end' },
            { data: 'total_print_count', name: 'total_print_count', className: 'text-end' }
        ],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        order: [[3, 'desc']]
    });

    // Shikake Machine DataTable
    $('#shikake-machine-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('dashboard.shikake-datatable') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'machine', name: 'machine' },
            { data: 'process', name: 'process' },
            { data: 'conveyor_name', name: 'conveyor_name' },
            { data: 'total_printed', name: 'total_printed', className: 'text-end' },
            { data: 'total_print_count', name: 'total_print_count', className: 'text-end' }
        ],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        order: [[4, 'desc']]
    });

    // ========== AUTO-GENERATE ASSY SCHEDULE ==========
    function autoGenerateAssySchedule() {
        var today = new Date();
        var startDate = formatDate(today);
        var endDateObj = new Date(today);
        endDateObj.setDate(endDateObj.getDate() + 3);
        var endDate = formatDate(endDateObj);

        $.ajax({
            url: '{{ route("schedule.assy-scheduler.generate") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                start_date: startDate,
                end_date: endDate,
                conveyor_id: null
            },
            success: function(response) {
                var generated = response.data ? (response.data.generated || 0) : 0;
                if (response.success) {
                    showGenerateBanner(true, generated);
                } else {
                    var isSyncFail = (response.step_failed === 'sync_listing' || response.step_failed === 'unknown');
                    showGenerateBanner(false, 0, isSyncFail);
                }
            },
            error: function() {
                showGenerateBanner(false, 0, true);
            }
        });
    }

    function showGenerateBanner(success, generated, isSyncFail) {
        var banner = $('#assy-generate-banner');
        var msg, bgColor, textColor, iconClass;
        if (success) {
            msg       = 'Berhasil generate jadwal assy dengan <strong>' + generated + '</strong> data.';
            bgColor   = '#d1e7dd';
            textColor = '#0a3622';
            iconClass = 'fa-circle-check';
        } else if (isSyncFail) {
            msg       = 'Gagal mengambil data listing dari PPC.';
            bgColor   = '#f8d7da';
            textColor = '#58151c';
            iconClass = 'fa-circle-xmark';
        } else {
            msg       = 'Gagal melakukan generate jadwal assy.';
            bgColor   = '#f8d7da';
            textColor = '#58151c';
            iconClass = 'fa-circle-xmark';
        }

        var html =
            '<i class="fa-solid ' + iconClass + ' me-2"></i>' +
            '<span class="flex-grow-1">' + msg + '</span>' +
            '<button type="button" class="btn-close ms-3" id="assy-banner-close" aria-label="Close"></button>';

        if (window._assyBannerTimer) clearTimeout(window._assyBannerTimer);

        banner
            .stop(true, true)
            .attr('class', 'd-flex align-items-center px-3 py-2 rounded mb-4 shadow-sm')
            .css({ 'display': 'none', 'background-color': bgColor, 'color': textColor, 'font-size': '0.875rem' })
            .html(html)
            .fadeIn(400);

        $('#assy-banner-close').on('click', function() { hideBanner(); });
        window._assyBannerTimer = setTimeout(hideBanner, 8000);
    }

    function hideBanner() {
        var banner = $('#assy-generate-banner');
        if (window._assyBannerTimer) {
            clearTimeout(window._assyBannerTimer);
            window._assyBannerTimer = null;
        }
        banner.fadeOut(400, function() {
            $(this).removeAttr('class').removeAttr('role').removeAttr('style').html('').css('display', 'none');
        });
    }

    function formatDate(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }
});
</script>
@endsection