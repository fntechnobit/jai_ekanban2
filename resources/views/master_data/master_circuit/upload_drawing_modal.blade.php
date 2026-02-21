<!-- Upload Drawing Modal - CUTTING TWIST only -->
<div class="modal fade" id="uploadDrawingModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form id="uploadDrawingForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="drawing_circuit_id">

                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark"><i class="ti ti-file-upload me-1"></i> Upload Drawing / Gambar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- Current Drawing Preview -->
                    <div id="currentDrawingContainer" class="mb-3 text-center" style="display:none;">
                        <label class="form-label fw-semibold">Current Drawing:</label>
                        <div>
                            <img id="currentDrawingPreview" src="" alt="Current Drawing"
                                class="img-fluid rounded border" style="max-height: 200px; cursor:pointer;"
                                onclick="window.open(this.src, '_blank')">
                        </div>
                        <small class="text-muted">Klik gambar untuk memperbesar</small>
                    </div>

                    <div id="noDrawingInfo" class="alert alert-warning py-2 mb-3" style="display:none;">
                        <i class="ti ti-alert-circle me-1"></i> Belum ada drawing untuk data ini.
                    </div>

                    <!-- Upload New Drawing -->
                    <div class="mb-3">
                        <label for="drawing_file" class="form-label fw-semibold">
                            Upload Drawing Baru <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control" id="drawing_file" name="drawing"
                            accept="image/jpeg,image/png,image/webp">
                        <div class="form-text text-muted">Format: JPG, PNG, WEBP. Maks: 5MB</div>
                        <div class="text-danger small mt-1 drawing_error"></div>
                    </div>

                    <!-- New Drawing Preview -->
                    <div id="newDrawingPreviewContainer" class="text-center" style="display:none;">
                        <label class="form-label fw-semibold">Preview:</label>
                        <div>
                            <img id="newDrawingPreview" src="" alt="New Drawing Preview"
                                class="img-fluid rounded border" style="max-height: 180px;">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-warning btn-sm" id="btn-submit-drawing">
                        <i class="fa-solid fa-upload me-1"></i> Upload Drawing
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
