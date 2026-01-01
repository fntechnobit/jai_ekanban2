@extends('layout')

@section('title', 'Dashboard')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Dashboard</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Kanban Printing Statistics -->
        <div class="row">
            <div class="col-lg-4 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ number_format($printingStats['circuits_printed']) }}</h3>
                        <p>Circuits Printed (Last 7 Days)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-print"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ number_format($printingStats['shikakes_printed']) }}</h3>
                        <p>Shikakes Printed (Last 7 Days)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-print"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ number_format($printingStats['total_print_count']) }}</h3>
                        <p>Total Print Count (Last 7 Days)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-copy"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Overview -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ number_format($scheduleOverview['total_schedules']) }}</h3>
                        <p>Total Schedules (Last 7 Days)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ number_format($scheduleOverview['verified_schedules']) }}</h3>
                        <p>Verified Schedules</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ number_format($scheduleOverview['pending_schedules']) }}</h3>
                        <p>Pending Verification</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ number_format($scheduleOverview['total_assy_items']) }}</h3>
                        <p>Total Assy Items</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Printing Trend Chart -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line mr-1"></i>
                            Printing Trend (Last 7 Days)
                        </h3>
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
                        <h3 class="card-title">
                            <i class="fas fa-history mr-1"></i>
                            Recent Activity (Last 7 Days)
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Schedule Creations -->
                            <div class="col-md-4">
                                <h5 class="text-primary"><i class="fas fa-plus-circle"></i> Schedule Creations</h5>
                                <div class="timeline">
                                    @forelse($recentActivity['creations'] as $activity)
                                    <div>
                                        <i class="fas fa-user bg-primary"></i>
                                        <div class="timeline-item">
                                            <span class="time"><i class="fas fa-clock"></i> {{ $activity['timestamp']->format('M d, H:i') }}</span>
                                            <h3 class="timeline-header">{{ $activity['user_name'] }}</h3>
                                            <div class="timeline-body">
                                                Created schedule for <strong>{{ $activity['conveyor'] }}</strong><br>
                                                Date: {{ $activity['schedule_date'] }}, Shift: {{ $activity['shift'] }}
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <p class="text-muted">No recent creations</p>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Verifications -->
                            <div class="col-md-4">
                                <h5 class="text-success"><i class="fas fa-check-circle"></i> Verifications</h5>
                                <div class="timeline">
                                    @forelse($recentActivity['verifications'] as $activity)
                                    <div>
                                        <i class="fas fa-check bg-success"></i>
                                        <div class="timeline-item">
                                            <span class="time"><i class="fas fa-clock"></i> {{ $activity['timestamp']->format('M d, H:i') }}</span>
                                            <h3 class="timeline-header">{{ $activity['user_name'] }}</h3>
                                            <div class="timeline-body">
                                                Verified schedule for <strong>{{ $activity['conveyor'] }}</strong><br>
                                                Date: {{ $activity['schedule_date'] }}, Shift: {{ $activity['shift'] }}
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <p class="text-muted">No recent verifications</p>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Prints -->
                            <div class="col-md-4">
                                <h5 class="text-info"><i class="fas fa-print"></i> Kanban Prints</h5>
                                <div class="timeline">
                                    @forelse($recentActivity['prints'] as $activity)
                                    <div>
                                        <i class="fas fa-print bg-info"></i>
                                        <div class="timeline-item">
                                            <span class="time"><i class="fas fa-clock"></i> {{ $activity['timestamp']->format('M d, H:i') }}</span>
                                            <h3 class="timeline-header">{{ $activity['user_name'] }}</h3>
                                            <div class="timeline-body">
                                                Printed {{ $activity['type'] === 'print_circuit' ? 'Circuit' : 'Shikake' }} for <strong>{{ $activity['conveyor'] }}</strong><br>
                                                Date: {{ $activity['schedule_date'] }}, Shift: {{ $activity['shift'] }}
                                            </div>
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
</section>
@endsection

@section('script')
<script>
$(document).ready(function() {
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
});
</script>
@endsection
