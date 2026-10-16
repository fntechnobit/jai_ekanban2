<!-- Modal Import Listing Manual -->
<div class="modal fade" id="importListingModal" tabindex="-1" aria-labelledby="importListingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importListingModalLabel">Import Listing Manual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="importListingForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info small">
                        <i class="fa-solid fa-circle-info"></i>
                        Maksimal 1000 baris. Kolom wajib: Tanggal, Conveyor, Assy Number, Qty, Seq.
                        Listing manual dengan Tanggal + Conveyor + Assy Code + Seq yang sama <strong>diperbarui</strong>, bukan ditambah.
                        Bila ada satu baris saja yang salah, <strong>seluruh file ditolak</strong> dan tidak ada yang tersimpan.
                    </div>

                    <div class="mb-3 row">
                        <label for="import_file" class="col-sm-3 col-form-label">File Import <span class="text-danger">*</span>:</label>
                        <div class="col-sm-9">
                            <input type="file" class="form-control form-control-sm" id="import_file" name="file" accept=".xlsx,.xls" required>
                            <small class="form-text text-muted">Format .xlsx / .xls (maks. 10MB)</small>
                            <small class="form-text text-danger d-block file_error"></small>
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label for="rows_start" class="col-sm-3 col-form-label">Rows Start :</label>
                        <div class="col-sm-4">
                            <input type="number" class="form-control form-control-sm" id="rows_start" name="rows_start" value="2" min="2" required>
                            <small class="form-text text-danger rows_start_error"></small>
                        </div>
                        <div class="col-sm-5">
                            <small class="form-text text-muted" style="font-style: italic;">*Baris pertama data di Excel</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-sm me-auto" id="btn-download-template">
                        <i class="fa-solid fa-file-excel"></i> Download Template
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-submit-import">
                        <i class="fa-solid fa-upload"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
