@extends('layouts.master')

@section('title', 'Schedule Verification')

@section('breadcrumb')
    <x-page-header menu-code="schedule_verification" />
@endsection

@section('css')
<link rel="stylesheet" href="{{ url('css/schedule-verification.css') }}">
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fa-solid fa-list me-2"></i> Schedule List</h5>
                <div class="d-flex align-items-center gap-2">
                    <!-- Filters -->
                    <input type="text" class="form-control form-control-sm" id="filter_dates" readonly
                           placeholder="Select date range" style="width: 220px;">
                    <select class="form-select form-select-sm select2" id="filter_conveyor_id" style="width: 180px;">
                        <option value="">- All Conveyor -</option>
                        @foreach($conveyors as $conveyor)
                            <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm select2" id="filter_status" style="width: 160px;">
                        <option value="">- All Status -</option>
                        <option value="verified">Verified</option>
                        <option value="pending">Pending</option>
                        <option value="no_data">No Data</option>
                    </select>
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-reset" title="Reset Filter">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">

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

<!-- Verification Modal -->
<div class="modal fade" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="verificationModalLabel">
                    <i class="fa-solid fa-circle-check me-2"></i> Assy Schedule Verification
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Header Info -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="info-header d-flex flex-wrap gap-2">
                            <span class="badge bg-primary p-2" id="modal-conveyor"></span>
                            <span class="badge bg-info p-2" id="modal-date"></span>
                            <span class="badge bg-warning p-2" id="modal-shift"></span>
                            <span class="badge bg-success p-2" id="modal-capacity"></span>
                            <span class="badge bg-secondary p-2" id="modal-assy-count"></span>
                            <span class="badge bg-dark p-2" id="modal-total-listing"></span>
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
                                        <select id="available-date" class="form-select form-select-sm" style="width: 160px;">
                                            <option value="">-- Pilih Tanggal --</option>
                                        </select>
                                        <select id="available-shift" class="form-select form-select-sm ms-2" style="width: 140px;">
                                            <option value="all">Semua Shift</option>
                                        </select>
                                        <button type="button" id="btn-refresh-available" class="btn btn-sm btn-light ms-2">
                                            <i class="fa-solid fa-arrows-rotate"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <!-- Info & Loading -->
                                    <div id="available-info" class="text-muted small mb-2">
                                        <span>Pilih tanggal untuk memuat data sumber</span>
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
                    <button type="button" class="btn btn-success btn-sm" id="btn-verify-schedule" style="display:none;">
                        <i class="fa-solid fa-check"></i> Verify
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ url('js/schedule-verification.js') }}?v={{ time() }}"></script>
    <script>
        // Set route URLs for the external JavaScript file
        routeUrls = {
            datatable: "{{ route('schedule.schedule-verification.datatable') }}",
            details: "{{ route('schedule.schedule-verification.details') }}",
            availableAssy: "{{ route('schedule.schedule-verification.available-assy') }}",
            availableDates: "{{ route('schedule.schedule-verification.available-dates') }}",
            verify: "{{ route('schedule.schedule-verification.verify') }}",
            unverify: "{{ route('schedule.schedule-verification.unverify') }}",
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
@endsection
