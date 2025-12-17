@extends('adminx.master')

@section('title', 'Synchronize List Assy')

@section('content')
    <x-page-header menu-code="listing_sync" />

    <section class="content">
        <div class="container-fluid">
            <!-- Statistics Card -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-database"></i> Latest in Listing DB</h5>
                            <p class="card-text h4" id="latest-in-listing">
                                {{ $statistics['latest_in_listing'] ? $statistics['latest_in_listing']->format('Y-m-d H:i:s') : 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-sync-alt"></i> Latest Synced</h5>
                            <p class="card-text h4" id="latest-in-stage">
                                {{ $statistics['latest_in_stage'] ? $statistics['latest_in_stage']->format('Y-m-d H:i:s') : 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-list"></i> Total Records</h5>
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
                    <h3 class="card-title">Synchronize Listing Data</h3>
                </div>
                <div class="card-body">
                    <form id="syncForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="days">Number of Days to Sync <span class="text-danger">*</span></label>
                                    <select class="form-control" id="days" name="days" required>
                                        <option value="1">Last 1 Day</option>
                                        <option value="3">Last 3 Days</option>
                                        <option value="7" selected>Last 7 Days</option>
                                        <option value="14">Last 14 Days</option>
                                        <option value="30">Last 30 Days</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Select how many days of data to synchronize from the listing database.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" id="btn-sync">
                                <i class="fas fa-sync-alt"></i> Start Synchronization
                            </button>
                            <button type="button" class="btn btn-secondary" id="btn-refresh-stats">
                                <i class="fas fa-refresh"></i> Refresh Statistics
                            </button>
                        </div>
                    </form>

                    <!-- Progress Alert -->
                    <div id="sync-progress" class="alert alert-info" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i> Synchronization in progress...
                    </div>

                    <!-- Result Alert -->
                    <div id="sync-result" class="alert" style="display: none;"></div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Information</h3>
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
    </section>
@endsection

@section('script')
    <script>
        $(function () {
            // Handle sync form submission
            $('#syncForm').submit(function(e) {
                e.preventDefault();

                var days = $('#days').val();
                var submitBtn = $('#btn-sync');

                // Show progress
                $('#sync-progress').show();
                $('#sync-result').hide();
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing...');

                $.ajax({
                    url: "{{ route('system.listing-sync.sync') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        days: days
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
                        submitBtn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Start Synchronization');
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
