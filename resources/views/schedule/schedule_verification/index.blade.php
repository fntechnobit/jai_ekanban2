@extends('layout')

@section('title', 'Schedule Verification')

@section('css')
<link rel="stylesheet" href="{{ url('css/schedule-verification.css') }}">
@endsection

@section('content')
    <x-page-header menu-code="schedule_verification" />

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Schedule List</h3>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-5">
                            <label for="filter_dates">Dates:</label>
                            <input type="text" class="form-control" id="filter_dates" readonly
                                   placeholder="Select date range">
                        </div>
                        <div class="col-md-4">
                            <label for="filter_conveyor_id">Conveyor:</label>
                            <select class="form-control select2" id="filter_conveyor_id" style="width: 100%;">
                                <option value="">- All Conveyor -</option>
                                @foreach($conveyors as $conveyor)
                                    <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label><br>
                            <button type="button" class="btn btn-info" id="btn-filter">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <button type="button" class="btn btn-secondary" id="btn-reset">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="schedule-verification-table" class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th width="5%">Num.</th>
                                    <th width="10%">Conveyor</th>
                                    <th width="12%">Dates</th>
                                    <th width="8%">Shift</th>
                                    <th width="8%">Capacity</th>
                                    <th width="8%">Listing</th>
                                    <th width="35%">Assy</th>
                                    <th width="8%">Status</th>
                                    <th width="6%">#</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Verification Modal -->
    <div class="modal fade" id="verificationModal" tabindex="-1" role="dialog" aria-labelledby="verificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="verificationModalLabel">
                        <i class="fas fa-check-circle"></i> Assy Schedule Verification
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Header Info -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="info-header d-flex flex-wrap gap-2">
                                <span class="badge badge-primary p-2" id="modal-conveyor"></span>
                                <span class="badge badge-info p-2" id="modal-date"></span>
                                <span class="badge badge-warning p-2" id="modal-shift"></span>
                                <span class="badge badge-success p-2" id="modal-capacity"></span>
                                <span class="badge badge-secondary p-2" id="modal-assy-count"></span>
                                <span class="badge badge-dark p-2" id="modal-total-listing"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Shifts Container with Cut-offs -->
                        <div class="col-md-6">
                            <div id="shifts-container">
                                <!-- Shifts and cut-offs will be loaded dynamically -->
                            </div>
                        </div>
                        
                        <!-- Available Assy Data -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">Generated Assy Data</h6>
                                    <div class="date-filter-controls d-flex mt-2">
                                        <input type="text" id="available-date" class="form-control form-control-sm" 
                                               style="width: 140px;" placeholder="Select date">
                                        <select id="available-shift" class="form-control form-control-sm ml-2" style="width: 100px;">
                                            <option value="1">Shift 1</option>
                                            <option value="2">Shift 2</option>
                                        </select>
                                        <button type="button" id="btn-refresh-available" class="btn btn-sm btn-light ml-2">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <!-- Results summary -->
                                    <div id="available-results-info" class="text-muted small mb-2" style="display: none;">
                                        Showing <span id="results-start">1</span>-<span id="results-end">20</span> of <span id="results-total">0</span> items
                                    </div>
                                    
                                    <!-- Loading indicator -->
                                    <div id="available-loading" class="text-center py-3" style="display: none;">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </div>
                                    
                                    <div id="available-assy-container">
                                        <!-- Available assy items grouped by cut-off will be loaded dynamically -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="btn-verify-schedule" style="display:none;">
                        <i class="fas fa-check"></i> Verify
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ url('js/schedule-verification.js') }}"></script>
    <script>
        // Set route URLs for the external JavaScript file
        routeUrls = {
            datatable: "{{ route('schedule.schedule-verification.datatable') }}",
            details: "{{ route('schedule.schedule-verification.details') }}",
            availableAssy: "{{ route('schedule.schedule-verification.available-assy') }}",
            verify: "{{ route('schedule.schedule-verification.verify') }}",
            unverify: "{{ route('schedule.schedule-verification.unverify') }}",
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
@endsection
