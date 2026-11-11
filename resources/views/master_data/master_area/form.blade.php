<!-- Modal -->
<div class="modal fade" id="masterAreaModal" tabindex="-1"  aria-labelledby="masterAreaModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" >
        <div class="modal-content">
            <form id="masterAreaForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="masterAreaModalLabel">Add Area Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="area_id" name="area_id">

                    <div class="mb-3">
                        <label for="area">Area <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="area" name="area" required>
                        <span class="text-danger error-text area_error"></span>
                    </div>

                    <div class="mb-1">
                        <label class="d-block">Aturan urutan print cut off</label>
                        {{-- hidden 0 dikirim saat switch OFF (checkbox yang tidak dicentang tidak ikut terkirim) --}}
                        <input type="hidden" name="cutoff_print_rule" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="cutoff_print_rule" name="cutoff_print_rule" value="1" checked>
                            <label class="form-check-label" for="cutoff_print_rule" id="cutoff_print_rule_label">ON</label>
                        </div>
                        <small class="text-muted">ON: kanban cutting dan shikake Cut Off 3, 4, 5 baru bisa diprint setelah Cut Off 1 dan 2 selesai diprint. OFF: semua cut off bisa diprint kapan saja.</small>
                        <span class="text-danger error-text cutoff_print_rule_error"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
