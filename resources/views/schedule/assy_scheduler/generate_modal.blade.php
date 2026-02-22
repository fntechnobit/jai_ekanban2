<!-- Generate Schedule Modal -->
<div class="modal fade" id="generateModal" tabindex="-1" aria-labelledby="generateModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generateModalLabel">
                    <i class="fa-solid fa-gear"></i> Generate Assy Schedule
                </h5>
                <button type="button" class="btn-close" id="btn-modal-close" aria-label="Close"></button>
            </div>

            {{-- ─── FORM PANEL ─── --}}
            <div id="generate-form-panel">
                <form id="generateForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="generate_dates" class="form-label">Date Range <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="generate_dates" required readonly>
                            <small class="form-text text-muted">Pilih rentang tanggal untuk generate schedule (default: 3 hari ke depan)</small>
                        </div>

                        <div class="mb-3">
                            <label for="generate_conveyor_id" class="form-label">Conveyor <span class="text-muted fw-normal">(Opsional)</span></label>
                            <select class="form-select form-select-sm" id="generate_conveyor_id" name="generate_conveyor_id">
                                <option value="">- Semua Conveyor -</option>
                                @foreach($conveyors as $conveyor)
                                    <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Kosongkan untuk generate semua conveyor</small>
                        </div>

                        <div class="alert alert-info d-flex gap-2 align-items-start py-2">
                            <i class="fa-solid fa-circle-info mt-1 flex-shrink-0"></i>
                            <div class="f-s-13">
                                <strong>Alur Generate:</strong>
                                <ol class="mb-0 ps-3 mt-1">
                                    <li>Clone data terbaru dari <em>database listing</em> ke <em>listing_stage</em></li>
                                    <li>Generate assy schedule berdasarkan data listing dan kapasitas conveyor</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" id="btn-cancel-generate">
                            <i class="fa-solid fa-xmark"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm" id="btn-submit-generate">
                            <i class="fa-solid fa-gear"></i> Generate
                        </button>
                    </div>
                </form>
            </div>

            {{-- ─── PROGRESS PANEL ─── --}}
            <div id="generate-progress-panel" style="display:none;">
                <div class="modal-body">
                    <p class="text-muted f-s-13 mb-3" id="progress-date-range"></p>

                    {{-- Step 1 --}}
                    <div class="d-flex align-items-start gap-3 mb-3 p-3 rounded" id="step1-row"
                         style="background:#f8f9fa; border-left: 4px solid #6c757d; transition: border-color .3s;">
                        <div class="flex-shrink-0 pt-1" id="step1-icon">
                            <span class="spinner-border spinner-border-sm text-secondary" role="status"></span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold f-s-14">
                                <i class="fa-solid fa-database me-1"></i> Step 1: Clone Data Listing
                            </div>
                            <div class="text-muted f-s-13 mt-1" id="step1-detail">Mengambil data terbaru dari database listing...</div>
                        </div>
                    </div>

                    {{-- Step 2 --}}
                    <div class="d-flex align-items-start gap-3 mb-3 p-3 rounded" id="step2-row"
                         style="background:#f8f9fa; border-left: 4px solid #6c757d; opacity: 0.45; transition: opacity .3s, border-color .3s;">
                        <div class="flex-shrink-0 pt-1" id="step2-icon">
                            <i class="fa-solid fa-circle text-secondary f-s-14"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold f-s-14">
                                <i class="fa-solid fa-calendar-check me-1"></i> Step 2: Generate Assy Schedule
                            </div>
                            <div class="text-muted f-s-13 mt-1" id="step2-detail">Menunggu proses step 1...</div>
                        </div>
                    </div>

                    {{-- Result banner --}}
                    <div id="generate-result-banner" class="alert mb-0" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-close-after-generate" style="display:none;">
                        <i class="fa-solid fa-xmark"></i> Tutup
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-back-to-form" style="display:none;">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
