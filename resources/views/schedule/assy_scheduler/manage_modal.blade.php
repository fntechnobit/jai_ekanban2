<!-- Manage Assy Schedule Modal -->
<div class="modal fade" id="manageModal" tabindex="-1" role="dialog" aria-labelledby="manageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="manageModalLabel">
                    <i class="fas fa-cogs"></i> Manage Assy Schedule
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
                            <span class="badge badge-primary p-2" id="manage-conveyor-info">Conveyor AT11</span>
                            <span class="badge badge-info p-2" id="manage-date-info">3 November 2025</span>
                            <span class="badge badge-warning p-2" id="manage-shifts-info">2 Shift</span>
                            <span class="badge badge-success p-2" id="manage-capacity-info">110 Capacity / Shift</span>
                            <span class="badge badge-secondary p-2" id="manage-assy-count">3 Assy</span>
                            <span class="badge badge-dark p-2" id="manage-listing-count">220 Listing</span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Shifts Container -->
                    <div class="col-md-6">
                        <div class="row" id="shifts-container">
                            <!-- Shifts will be loaded dynamically -->
                        </div>
                    </div>
                    
                    <!-- Available Assy Data -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Generated Assy Data</h6>
                                <div class="date-filter-controls d-flex">
                                    <input type="text" id="available-date-range" class="form-control form-control-sm" 
                                           style="width: 180px;" placeholder="Select date range">
                                    <button type="button" id="btn-refresh-available" class="btn btn-sm btn-light ml-2">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <!-- Results summary -->
                                <div id="available-results-info" class="text-muted small mb-2" style="display: none;">
                                    Showing <span id="results-start">1</span>-<span id="results-end">20</span> of <span id="results-total">0</span> items
                                </div>
                                
                                <!-- Loading indicator -->
                                <div id="available-loading" class="text-center py-3" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                                
                                <div id="available-assy-container" class="available-drop-zone" style="min-height: 250px;">
                                    <!-- Available assy items will be loaded dynamically -->
                                </div>
                                
                                <!-- Pagination controls -->
                                <div id="available-pagination" class="d-flex justify-content-between align-items-center mt-2" style="display: none !important;">
                                    <button type="button" id="btn-prev-page" class="btn btn-sm btn-outline-primary" disabled>
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </button>
                                    <span id="pagination-info" class="small text-muted">Page 1 of 1</span>
                                    <button type="button" id="btn-next-page" class="btn btn-sm btn-outline-primary" disabled>
                                        Next <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btn-save-manage">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.assy-item {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 4px;
    padding: 8px;
    margin: 4px 0;
    cursor: move;
    display: flex;
    justify-content: between;
    align-items: center;
}

.assy-item .assy-code {
    flex: 1;
    font-weight: 500;
}

.assy-item .assy-qty {
    margin-left: 8px;
}

.assy-item:hover {
    background: #bbdefb;
    border-color: #1976d2;
}

.assy-placeholder {
    background: #f5f5f5;
    border: 2px dashed #ccc;
    height: 40px;
    margin: 4px 0;
    border-radius: 4px;
}

.shift-drop-zone {
    border: 2px dashed transparent;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.shift-drop-zone.ui-sortable-helper {
    border-color: #2196f3;
    background-color: #f3f9ff;
}

.available-drop-zone {
    border: 2px dashed transparent;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.available-drop-zone.ui-sortable-helper {
    border-color: #ff9800;
    background-color: #fff8f0;
}

.shift-capacity-info {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}

.shift-capacity-info .badge {
    font-size: 10px;
}

.info-header {
    gap: 8px;
}

.info-header .badge {
    font-size: 12px;
}
</style>