<!-- Edit Circuit Modal -->
<div class="modal fade" id="editCircuitModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editCircuitForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="edit_circuit_id" name="id">

                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title mb-0"><i class="ti ti-pencil me-1"></i> Edit Circuit</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-3" style="max-height: 75vh; overflow-y: auto;">
                    <div class="row g-2">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label small mb-0">Conveyor</label>
                                <input type="text" id="edit_conveyor" class="form-control form-control-sm bg-light" readonly>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-0">Carline</label>
                                <input type="text" name="carline" id="edit_carline" class="form-control form-control-sm">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-0">CCT No</label>
                                <input type="text" name="cct_no" id="edit_cct_no" class="form-control form-control-sm">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-0">CCT Code</label>
                                <input type="text" name="cct_code" id="edit_cct_code" class="form-control form-control-sm">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-0">Shikake Code</label>
                                <input type="text" name="shikake_code" id="edit_shikake_code" class="form-control form-control-sm">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-0">Family</label>
                                <input type="text" name="family" id="edit_family" class="form-control form-control-sm">
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">QTY</label>
                                        <input type="number" name="qty" id="edit_qty" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Machine</label>
                                        <input type="text" name="machine" id="edit_machine" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">M. Twist</label>
                                        <input type="text" name="machine_twist" id="edit_machine_twist" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Memory Twist</label>
                                        <input type="text" name="memory_twist" id="edit_memory_twist" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Sequence</label>
                                        <input type="text" name="sequence" id="edit_sequence" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Sequence 2</label>
                                        <input type="text" name="sequence_2" id="edit_sequence_2" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label small mb-0">Released Note</label>
                                <input type="text" name="released_note" id="edit_released_note" class="form-control form-control-sm">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-0">Cust No</label>
                                <input type="text" name="cust_no" id="edit_cust_no" class="form-control form-control-sm">
                            </div>
                            <div class="row g-2">
                                <div class="col-4">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Kind</label>
                                        <input type="text" name="kind" id="edit_kind" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Size</label>
                                        <input type="text" name="size" id="edit_size" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Col</label>
                                        <input type="text" name="col" id="edit_col" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-4">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">C/L</label>
                                        <input type="text" name="cl" id="edit_cl" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">To Store</label>
                                        <input type="text" name="to_store" id="edit_to_store" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Address</label>
                                        <input type="text" name="address" id="edit_address" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>

                            <!-- Drawing Upload (CUTTING_TWIST only) -->
                            <div id="edit_drawing_section" class="border rounded p-2 mt-2" style="display:none;">
                                <label class="form-label small mb-1 fw-semibold text-primary">Drawing</label>
                                <input type="file" name="image" id="edit_drawing_file" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                <div id="edit_drawing_preview_container" class="text-center mt-2" style="display:none;">
                                    <img id="edit_drawing_preview" src="" alt="Drawing" class="img-fluid rounded" style="max-height: 180px;">
                                </div>
                                <div class="row g-2 mt-1">
                                    <div class="col-6">
                                        <div class="mb-2">
                                            <label class="form-label small mb-0">Barcode Twist</label>
                                            <input type="text" name="barcode_twist" id="edit_barcode_twist" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-2">
                                            <label class="form-label small mb-0">QRCode Drawing</label>
                                            <input type="text" name="qrcode_drawing" id="edit_qrcode_drawing" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Barcode Fields -->
                    <div class="row g-2 mt-1">
                        <div class="col-12">
                            <div class="border rounded p-2">
                                <h6 class="fw-semibold small mb-1 text-primary">Barcodes</h6>
                                <div class="row g-2">
                                    <div class="col-md-3 col-6">
                                        <label class="form-label small mb-0">Barcode Mesin</label>
                                        <input type="text" name="barcode_mesin" id="edit_barcode_mesin" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <label class="form-label small mb-0">Barcode Navigasi</label>
                                        <input type="text" name="barcode_navigasi" id="edit_barcode_navigasi" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <label class="form-label small mb-0">Barcode Process</label>
                                        <input type="text" name="barcode_process" id="edit_barcode_process" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <label class="form-label small mb-0">Barcode Shikake</label>
                                        <input type="text" name="barcode_shikake" id="edit_barcode_shikake" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Terminal 1 & 2 compact -->
                    <div class="row g-2 mt-1">
                        <div class="col-md-6">
                            <div class="border rounded p-2">
                                <h6 class="fw-semibold small mb-1 text-primary">Terminal 1</h6>
                                <div class="row g-1">
                                    <div class="col-6 mb-1">
                                        <label class="form-label small mb-0">Terminal</label>
                                        <input type="text" name="terminal_1" id="edit_terminal_1" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-1">
                                        <label class="form-label small mb-0">Note</label>
                                        <input type="text" name="note_1" id="edit_note_1" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-1">
                                        <label class="form-label small mb-0">Gold</label>
                                        <input type="text" name="gold_1" id="edit_gold_1" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-1">
                                        <label class="form-label small mb-0">Strip</label>
                                        <input type="text" name="strip_1" id="edit_strip_1" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-1">
                                        <label class="form-label small mb-0">Acc</label>
                                        <input type="text" name="acc_1" id="edit_acc_1" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-1">
                                        <label class="form-label small mb-0">Acc A</label>
                                        <input type="text" name="acc_1a" id="edit_acc_1a" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-1">
                                        <label class="form-label small mb-0">Tube</label>
                                        <input type="text" name="tube_1" id="edit_tube_1" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-1">
                                        <label class="form-label small mb-0">Mark</label>
                                        <input type="text" name="mark_1" id="edit_mark_1" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-2">
                                <h6 class="fw-semibold small mb-1 text-primary">Terminal 2</h6>
                                <div class="row g-1">
                                    <div class="col-6 mb-1">
                                        <label class="form-label small mb-0">Terminal</label>
                                        <input type="text" name="terminal_2" id="edit_terminal_2" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-1">
                                        <label class="form-label small mb-0">Note</label>
                                        <input type="text" name="note_2" id="edit_note_2" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-1">
                                        <label class="form-label small mb-0">Gold</label>
                                        <input type="text" name="gold_2" id="edit_gold_2" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-1">
                                        <label class="form-label small mb-0">Strip</label>
                                        <input type="text" name="strip_2" id="edit_strip_2" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-1">
                                        <label class="form-label small mb-0">Acc</label>
                                        <input type="text" name="acc_2" id="edit_acc_2" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-1">
                                        <label class="form-label small mb-0">Acc A</label>
                                        <input type="text" name="acc_2a" id="edit_acc_2a" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-1">
                                        <label class="form-label small mb-0">Tube</label>
                                        <input type="text" name="tube_2" id="edit_tube_2" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-1">
                                        <label class="form-label small mb-0">Mark</label>
                                        <input type="text" name="mark_2" id="edit_mark_2" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-submit-edit">
                        <i class="ti ti-device-floppy me-1"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
