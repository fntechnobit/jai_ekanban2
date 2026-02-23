@extends('layouts.master')

@section('title', 'Dashboard')

@section('breadcrumb')
<div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
    <div>
        <nav>
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ url('dashboard') }}"><i class="fa-solid fa-home"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
        </nav>
        <h1 class="page-title fw-medium fs-18 mb-0">Dashboard</h1>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">

    {{-- Dynamic Flash Banner: Generate Assy Schedule Response --}}
    <div id="assy-generate-banner" style="display:none;"></div>

    <!-- Kanban Printing Statistics -->
    <div class="row">
        <div class="col-xxl-4 col-lg-4 col-md-6">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <p class="mb-2 text-muted">Circuits Printed</p>
                            <h3 class="mb-1 fw-semibold">{{ number_format($printingStats['circuits_printed']) }}</h3>
                            <span class="badge bg-light-info text-info">Last 7 Days</span>
                        </div>
                        <div class="text-light-info h-50 w-50 d-flex-center b-r-15">
                            <i class="fa-solid fa-print f-s-30"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4 col-lg-4 col-md-6">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <p class="mb-2 text-muted">Shikakes Printed</p>
                            <h3 class="mb-1 fw-semibold">{{ number_format($printingStats['shikakes_printed']) }}</h3>
                            <span class="badge bg-light-success text-success">Last 7 Days</span>
                        </div>
                        <div class="text-light-success h-50 w-50 d-flex-center b-r-15">
                            <i class="fa-solid fa-print f-s-30"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4 col-lg-4 col-md-6">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <p class="mb-2 text-muted">Total Print Count</p>
                            <h3 class="mb-1 fw-semibold">{{ number_format($printingStats['total_print_count']) }}</h3>
                            <span class="badge bg-light-warning text-warning">Last 7 Days</span>
                        </div>
                        <div class="text-light-warning h-50 w-50 d-flex-center b-r-15">
                            <i class="fa-solid fa-copy f-s-30"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Overview -->
    <div class="row">
        <div class="col-xxl-3 col-lg-3 col-md-6">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <p class="mb-2 text-muted">Total Schedules</p>
                            <h3 class="mb-1 fw-semibold">{{ number_format($scheduleOverview['total_schedules']) }}</h3>
                            <span class="badge bg-light-primary text-primary">Last 7 Days</span>
                        </div>
                        <div class="text-light-primary h-50 w-50 d-flex-center b-r-15">
                            <i class="fa-solid fa-calendar f-s-30"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-lg-3 col-md-6">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <p class="mb-2 text-muted">Verified Schedules</p>
                            <h3 class="mb-1 fw-semibold">{{ number_format($scheduleOverview['verified_schedules']) }}</h3>
                            <span class="badge bg-light-success text-success">Completed</span>
                        </div>
                        <div class="text-light-success h-50 w-50 d-flex-center b-r-15">
                            <i class="fa-solid fa-circle-check f-s-30"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-lg-3 col-md-6">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <p class="mb-2 text-muted">Pending Verification</p>
                            <h3 class="mb-1 fw-semibold">{{ number_format($scheduleOverview['pending_schedules']) }}</h3>
                            <span class="badge bg-light-danger text-danger">Action Required</span>
                        </div>
                        <div class="text-light-danger h-50 w-50 d-flex-center b-r-15">
                            <i class="fa-solid fa-circle-exclamation f-s-30"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-lg-3 col-md-6">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <p class="mb-2 text-muted">Total Assy Items</p>
                            <h3 class="mb-1 fw-semibold">{{ number_format($scheduleOverview['total_assy_items']) }}</h3>
                            <span class="badge bg-light-info text-info">All Items</span>
                        </div>
                        <div class="text-light-info h-50 w-50 d-flex-center b-r-15">
                            <i class="fa-solid fa-boxes f-s-30"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Printing Trend Chart -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fa-solid fa-chart-line me-2"></i>
                        Printing Trend (Last 7 Days)
                    </h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="printingTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Feed -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fa-solid fa-history me-2"></i>
                        Recent Activity (Last 7 Days)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Schedule Creations -->
                        <div class="col-md-4">
                            <h6 class="text-primary fw-semibold mb-3">
                                <i class="fa-solid fa-plus me-1"></i> Schedule Creations
                            </h6>
                            <div class="activity-timeline">
                                @forelse($recentActivity['creations'] as $activity)
                                <div class="d-flex align-items-start mb-3">
                                    <div class="text-light-primary h-35 w-35 d-flex-center b-r-50 flex-shrink-0">
                                        <i class="fa-solid fa-user f-s-18"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1 f-s-14">{{ $activity['user_name'] }}</h6>
                                            <small class="text-muted">{{ $activity['timestamp']->format('M d, H:i') }}</small>
                                        </div>
                                        <p class="mb-0 text-muted f-s-13">
                                            Created schedule for <strong>{{ $activity['conveyor'] }}</strong><br>
                                            <small>Date: {{ $activity['schedule_date'] }}, Shift: {{ $activity['shift'] }}</small>
                                        </p>
                                    </div>
                                </div>
                                @empty
                                <p class="text-muted">No recent creations</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Verifications -->
                        <div class="col-md-4">
                            <h6 class="text-success fw-semibold mb-3">
                                <i class="fa-solid fa-circle-check me-1"></i> Verifications
                            </h6>
                            <div class="activity-timeline">
                                @forelse($recentActivity['verifications'] as $activity)
                                <div class="d-flex align-items-start mb-3">
                                    <div class="text-light-success h-35 w-35 d-flex-center b-r-50 flex-shrink-0">
                                        <i class="fa-solid fa-check f-s-18"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1 f-s-14">{{ $activity['user_name'] }}</h6>
                                            <small class="text-muted">{{ $activity['timestamp']->format('M d, H:i') }}</small>
                                        </div>
                                        <p class="mb-0 text-muted f-s-13">
                                            Verified schedule for <strong>{{ $activity['conveyor'] }}</strong><br>
                                            <small>Date: {{ $activity['schedule_date'] }}, Shift: {{ $activity['shift'] }}</small>
                                        </p>
                                    </div>
                                </div>
                                @empty
                                <p class="text-muted">No recent verifications</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Prints -->
                        <div class="col-md-4">
                            <h6 class="text-info fw-semibold mb-3">
                                <i class="fa-solid fa-print me-1"></i> Kanban Prints
                            </h6>
                            <div class="activity-timeline">
                                @forelse($recentActivity['prints'] as $activity)
                                <div class="d-flex align-items-start mb-3">
                                    <div class="text-light-info h-35 w-35 d-flex-center b-r-50 flex-shrink-0">
                                        <i class="fa-solid fa-print f-s-18"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1 f-s-14">{{ $activity['user_name'] }}</h6>
                                            <small class="text-muted">{{ $activity['timestamp']->format('M d, H:i') }}</small>
                                        </div>
                                        <p class="mb-0 text-muted f-s-13">
                                            Printed {{ $activity['type'] === 'print_circuit' ? 'Circuit' : 'Shikake' }} for <strong>{{ $activity['conveyor'] }}</strong><br>
                                            <small>Date: {{ $activity['schedule_date'] }}, Shift: {{ $activity['shift'] }}</small>
                                        </p>
                                    </div>
                                </div>
                                @empty
                                <p class="text-muted">No recent prints</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<script src="{{ asset('assets/vendor/chartjs/chart.umd.min.js') }}"></script>
@endsection

@section('script')
<script>
$(document).ready(function() {
    // Auto-generate Assy Schedule for next 3 days on dashboard load
    autoGenerateAssySchedule();

    // Fetch printing trend data via AJAX
    $.ajax({
        url: '{{ route("dashboard.printing-trend") }}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            // Initialize the chart with fetched data
            var canvas = document.getElementById('printingTrendChart');
            if (canvas && data) {
                var ctx = canvas.getContext('2d');
                
                var printingTrendChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels || [],
                        datasets: [
                            {
                                label: 'Circuits',
                                data: data.circuits || [],
                                borderColor: 'rgb(23, 162, 184)',
                                backgroundColor: 'rgba(23, 162, 184, 0.1)',
                                tension: 0.4,
                                fill: true
                            },
                            {
                                label: 'Shikakes',
                                data: data.shikakes || [],
                                borderColor: 'rgb(40, 167, 69)',
                                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                tension: 0.4,
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading printing trend data:', error);
            // Optionally show a message to the user
            $('#printingTrendChart').parent().html('<p class="text-center text-muted">Unable to load chart data</p>');
        }
    });

    /**
     * Auto-generate Assy Schedule for next 3 days
     */
    function autoGenerateAssySchedule() {
        // Get today's date
        var today = new Date();
        var startDate = formatDate(today);
        
        // Calculate 3 days from today
        var endDateObj = new Date(today);
        endDateObj.setDate(endDateObj.getDate() + 3);
        var endDate = formatDate(endDateObj);
        
        console.log('Auto-generating Assy Schedule from ' + startDate + ' to ' + endDate);
        
        // Call the generate endpoint
        $.ajax({
            url: '{{ route("schedule.assy-scheduler.generate") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                start_date: startDate,
                end_date: endDate,
                conveyor_id: null // Generate for all conveyors
            },
            beforeSend: function() {
                console.log('Generating assy schedules...');
            },
            success: function(response) {
                var generated = response.data ? (response.data.generated || 0) : 0;
                if (response.success) {
                    showGenerateBanner(true, generated);
                    showNavbarNotification(response.message, response.data);
                } else {
                    var isSyncFail = (response.step_failed === 'sync_listing' || response.step_failed === 'unknown');
                    showGenerateBanner(false, 0, isSyncFail);
                }
            },
            error: function(xhr, status, error) {
                showGenerateBanner(false, 0, true);
            }
        });
    }
    
    /**
     * Show flash banner with generate result between navbar and content
     * success : true = berhasil, false = gagal
     * generated : jumlah schedule yang dibuat
     * isSyncFail : true = gagal di step listing (PPC)
     */
    function showGenerateBanner(success, generated, isSyncFail) {
        var banner = $('#assy-generate-banner');

        var msg, bgColor, textColor, iconClass;
        if (success) {
            msg       = 'Berhasil generate jadwal assy dengan <strong>' + generated + '</strong> data.';
            bgColor   = '#d1e7dd';  // hijau muda
            textColor = '#0a3622';
            iconClass = 'fa-circle-check';
        } else if (isSyncFail) {
            msg       = 'Gagal mengambil data listing dari PPC.';
            bgColor   = '#f8d7da';  // merah muda
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
            '<button type="button" class="btn-close ms-3" id="assy-banner-close" aria-label="Close" style="filter: invert(0);"></button>';

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
            // Hapus semua class dan style agar div benar-benar kosong dan tidak meninggalkan ruang
            $(this).removeAttr('class').removeAttr('role').removeAttr('style').html('').css('display', 'none');
        });
    }

    /**
     * Helper function to format date as YYYY-MM-DD
     */
    function formatDate(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }
    
    /**
     * Show notification in navbar header
     */
    function showNavbarNotification(message, data) {
        var notificationBadge = $('#notificationBadge');
        var notificationContent = $('#notificationContent');
        var notificationList = $('#notificationList');
        
        // Show badge indicator
        notificationBadge.show();
        
        // Hide empty message
        notificationContent.hide();
        
        // Create notification item
        var timestamp = new Date().toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit'
        });
        
        var detailsHtml = '';
        if (data && data.generated > 0) {
            detailsHtml = '<small class="text-muted d-block mt-1">' +
                '<i class="fa-solid fa-calendar-plus me-1"></i>' + data.generated + ' schedule dibuat' +
                '</small>';
        }
        
        var notificationItem = 
            '<div class="notification-item p-3 border-bottom" style="background: #f8f9fa;">' +
                '<div class="d-flex align-items-start">' +
                    '<div class="flex-shrink-0">' +
                        '<span class="h-40 w-40 d-flex-center b-r-50 bg-success text-white">' +
                            '<i class="fa-solid fa-calendar-check"></i>' +
                        '</span>' +
                    '</div>' +
                    '<div class="flex-grow-1 ms-3">' +
                        '<h6 class="mb-1">Assy Schedule Generated</h6>' +
                        '<p class="mb-0 text-muted f-s-13">' + message + '</p>' +
                        detailsHtml +
                        '<small class="text-muted d-block mt-1">' +
                            '<i class="fa-regular fa-clock me-1"></i>' + timestamp +
                        '</small>' +
                    '</div>' +
                '</div>' +
            '</div>';
        
        // Add notification to list
        notificationList.prepend(notificationItem);
        notificationList.show();
        
        // Auto-hide badge after 10 seconds
        setTimeout(function() {
            notificationBadge.fadeOut();
        }, 10000);
    }
});

</script>
@endsection
