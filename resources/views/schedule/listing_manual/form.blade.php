<!-- Modal Tambah/Edit Listing Manual -->
<div class="modal fade" id="listingModal" tabindex="-1" aria-labelledby="listingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="listingForm" autocomplete="off">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="listingModalLabel">Tambah Listing Manual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="listing_id">

                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label for="listing_date" class="form-label">Tanggal <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm" id="listing_date" name="listing_date" required>
                                    <span class="text-danger small error-text listing_date_error"></span>
                                </div>
                                <div class="col-md-6">
                                    <label for="conveyor" class="form-label">Conveyor <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="conveyor" name="conveyor" required style="width: 100%;">
                                        <option value=""></option>
                                        @foreach($conveyors as $conveyor)
                                            <option value="{{ $conveyor->conveyor }}">{{ $conveyor->conveyor }}</option>
                                        @endforeach
                                    </select>
                                    <span class="text-danger small error-text conveyor_error"></span>
                                </div>

                                <div class="col-md-8">
                                    <label for="assy" class="form-label">Assy Number <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="assy" name="assy" required style="width: 100%;"></select>
                                    <small class="text-muted">Pilih conveyor dulu. Assy baru yang belum ada di daftar bisa diketik langsung.</small>
                                    <div class="small text-warning-emphasis d-none" id="assy-new-hint">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Assy baru: jadwal terbentuk, tetapi kanban baru keluar
                                        setelah master circuit/shikake dengan kolom assy ini diimport — lakukan sebelum shift-nya diverifikasi.
                                    </div>
                                    <span class="text-danger small error-text assy_error d-block"></span>
                                </div>
                                <div class="col-md-4">
                                    <label for="assycode" class="form-label">Assy Code</label>
                                    <input type="text" class="form-control form-control-sm" id="assycode" name="assycode" maxlength="255">
                                    <small class="text-muted">Kosong = dari listing terakhir; assy baru = Assy Number.</small>
                                    <span class="text-danger small error-text assycode_error d-block"></span>
                                </div>

                                <div class="col-md-4">
                                    <label for="qty" class="form-label">Qty <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control form-control-sm" id="qty" name="qty" min="1" step="1" required>
                                    <span class="text-danger small error-text qty_error"></span>
                                </div>
                                <div class="col-md-4">
                                    <label for="seq" class="form-label">Seq <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control form-control-sm" id="seq" name="seq" min="0" step="1" required>
                                    <small class="text-muted">Urutan alokasi ke cutoff.</small>
                                    <span class="text-danger small error-text seq_error d-block"></span>
                                </div>
                                <div class="col-md-4">
                                    <label for="carline" class="form-label">Carline</label>
                                    <input type="text" class="form-control form-control-sm" id="carline" name="carline" maxlength="255">
                                    <span class="text-danger small error-text carline_error"></span>
                                </div>

                                <div class="col-6 col-md-3">
                                    <label for="snp" class="form-label">SNP</label>
                                    <input type="number" class="form-control form-control-sm" id="snp" name="snp" min="0" step="1">
                                    <span class="text-danger small error-text snp_error"></span>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label for="snpa" class="form-label">SNPA</label>
                                    <input type="number" class="form-control form-control-sm" id="snpa" name="snpa" min="0" step="1">
                                    <span class="text-danger small error-text snpa_error"></span>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label for="plt" class="form-label">PLT</label>
                                    <input type="number" class="form-control form-control-sm" id="plt" name="plt" min="0" step="1">
                                    <span class="text-danger small error-text plt_error"></span>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label for="mode" class="form-label">Mode</label>
                                    <select class="form-select form-select-sm" id="mode" name="mode">
                                        <option value="">-</option>
                                        @foreach(config('sirep.mapping.mode_map', []) as $label => $value)
                                            <option value="{{ $value }}">{{ strtoupper($label) }}</option>
                                        @endforeach
                                    </select>
                                    <span class="text-danger small error-text mode_error"></span>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted">Carline, SNP, SNPA, PLT dan Mode yang dikosongkan diisi dari listing terakhir assy yang sama.</small>
                                </div>

                                <div class="col-12">
                                    <label for="remark" class="form-label">Keterangan</label>
                                    <input type="text" class="form-control form-control-sm" id="remark" name="remark" maxlength="255"
                                           placeholder="Mis. tambahan order di luar SIREP">
                                    <span class="text-danger small error-text remark_error"></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="border rounded p-2 h-100">
                                <div class="fw-semibold small mb-1">
                                    <i class="fa-solid fa-list-ol"></i> Listing hari itu, urut alokasi
                                </div>
                                <div id="day-listing" class="small">
                                    <span class="text-muted">Pilih tanggal dan conveyor untuk melihat listing hari itu.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-save">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
