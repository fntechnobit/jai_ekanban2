@extends('layouts.master')

@section('title', 'Synchronize List Assy')

@section('breadcrumb')
    <x-page-header menu-code="listing_sync" />
@endsection

@section('content')
    <div class="container-fluid">
        <!-- Statistics Card -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa-solid fa-database"></i> Latest in Listing DB</h5>
                        <p class="card-text h4" id="latest-in-listing">
                            {{ $statistics['latest_in_listing'] ? $statistics['latest_in_listing']->format('Y-m-d H:i:s') : 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa-solid fa-arrows-rotate"></i> Latest Synced</h5>
                        <p class="card-text h4" id="latest-in-stage">
                            {{ $statistics['latest_in_stage'] ? $statistics['latest_in_stage']->format('Y-m-d H:i:s') : 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa-solid fa-list"></i> Total Records</h5>
                        <p class="card-text h4" id="total-in-stage">
                            {{ number_format($statistics['total_in_stage']) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sync Form Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Synchronize Listing Data</h5>
            </div>
            <div class="card-body">
                <form id="syncForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" required>
                                <small class="form-text text-muted">
                                    Select the starting date for synchronization.
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" required>
                                <small class="form-text text-muted">
                                    Select the ending date for synchronization.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary btn-sm" id="btn-sync">
                            <i class="fa-solid fa-arrows-rotate"></i> Start Synchronization
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" id="btn-refresh-stats">
                            <i class="fa-solid fa-arrow-rotate-right"></i> Refresh Statistics
                        </button>
                    </div>
                </form>

                <!-- Progress Alert -->
                <div id="sync-progress" class="alert alert-info" style="display: none;">
                    <i class="fa-solid fa-spinner ti-spin"></i> Synchronization in progress...
                </div>

                <!-- Result Alert -->
                <div id="sync-result" class="alert" style="display: none;"></div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Information</h5>
            </div>
            <div class="card-body">
                <h5>About this synchronization:</h5>
                <ul>
                    <li>This feature synchronizes listing data from the external <code>mysql_listing</code> database to the local <code>listing_stage</code> table.</li>
                    <li>Only new records will be synced. Existing records (based on date, conveyor, assycode, and sequence) will be skipped.</li>
                    <li>The synchronization maps the following fields:
                        <ul>
                            <li><code>time</code> → <code>listing_date_time</code></li>
                            <li><code>cv</code> → <code>conveyor</code></li>
                            <li><code>shift</code> → <code>shift</code></li>
                            <li><code>assycode</code> → <code>assycode</code></li>
                            <li><code>assy</code> → <code>assy</code></li>
                            <li><code>qty</code> → <code>qty</code></li>
                            <li><code>seq</code> → <code>seq</code></li>
                            <li><code>plt</code> → <code>plt</code></li>
                            <li><code>mode</code> → <code>mode</code></li>
                            <li><code>snp</code> → <code>snp</code></li>
                            <li><code>snpa</code> → <code>snpa</code></li>
                        </ul>
                    </li>
                    <li>Select the appropriate time range to avoid syncing too much data at once.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(function () {
            // Set default date range (last 7 days)
            var today = new Date();
            var lastWeek = new Date();
            lastWeek.setDate(today.getDate() - 7);
            
            $('#end_date').val(today.toISOString().split('T')[0]);
            $('#start_date').val(lastWeek.toISOString().split('T')[0]);

            // Handle sync form submission
            $('#syncForm').submit(function(e) {
                e.preventDefault();

                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();
                var submitBtn = $('#btn-sync');

                // Validate dates
                if (new Date(startDate) > new Date(endDate)) {
                    Swal.fire('Error!', 'Start date must be before or equal to end date.', 'error');
                    return;
                }

                // Show progress
                $('#sync-progress').show();
                $('#sync-result').hide();
                submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner ti-spin"></i> Syncing...');

                $.ajax({
                    url: "{{ route('system.listing-sync.sync') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        start_date: startDate,
                        end_date: endDate
                    },
                    success: function(response) {
                        $('#sync-progress').hide();
                        
                        if (response.success) {
                            var resultHtml = '<strong>Success!</strong> ' + response.message;
                            
                            if (response.data) {
                                resultHtml += '<br><br><strong>Details:</strong>';
                                resultHtml += '<br>• Date Range: ' + response.data.date_range.from + ' to ' + response.data.date_range.to;
                                resultHtml += '<br>• Total Records Found: ' + response.data.total_records;
                                resultHtml += '<br>• Successfully Synced: ' + response.data.synced;
                                resultHtml += '<br>• Skipped (Already Exists): ' + response.data.skipped;
                                
                                if (response.data.errors && response.data.errors.length > 0) {
                                    resultHtml += '<br><br><strong class="text-danger">Errors:</strong>';
                                    response.data.errors.slice(0, 5).forEach(function(error) {
                                        resultHtml += '<br>• ' + error;
                                    });
                                    if (response.data.errors.length > 5) {
                                        resultHtml += '<br>• ... and ' + (response.data.errors.length - 5) + ' more errors';
                                    }
                                }
                            }
                            
                            $('#sync-result').removeClass('alert-danger').addClass('alert-success')
                                .html(resultHtml).show();
                            
                            // Refresh statistics
                            refreshStatistics();
                        } else {
                            $('#sync-result').removeClass('alert-success').addClass('alert-danger')
                                .html('<strong>Error!</strong> ' + response.message).show();
                        }
                    },
                    error: function(xhr) {
                        $('#sync-progress').hide();
                        var errorMessage = 'An error occurred during synchronization.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        $('#sync-result').removeClass('alert-success').addClass('alert-danger')
                            .html('<strong>Error!</strong> ' + errorMessage).show();
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html('<i class="fa-solid fa-arrows-rotate"></i> Start Synchronization');
                    }
                });
            });

            // Refresh statistics
            $('#btn-refresh-stats').click(function() {
                refreshStatistics();
            });

            function refreshStatistics() {
                $.ajax({
                    url: "{{ route('system.listing-sync.statistics') }}",
                    type: 'GET',
                    success: function(response) {
                        if (response.success && response.data) {
                            var data = response.data;
                            
                            $('#latest-in-listing').text(data.latest_in_listing || 'N/A');
                            $('#latest-in-stage').text(data.latest_in_stage || 'N/A');
                            $('#total-in-stage').text(data.total_in_stage ? data.total_in_stage.toLocaleString() : '0');
                        }
                    },
                    error: function(xhr) {
                        console.error('Failed to refresh statistics:', xhr);
                    }
                });
            }
        });
    </script>
@endsection
