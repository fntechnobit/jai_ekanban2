<!-- View Circuit Modal (Read-Only) -->
<div class="modal fade" id="detailCircuitModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-2">
                <h6 class="modal-title mb-0"><i class="ti ti-eye me-1"></i> Detail Circuit</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-3" style="max-height: 75vh; overflow-y: auto;">
                <!-- Info Utama -->
                <div class="row g-0 mb-2">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0 small">
                            <tr><td class="text-muted" width="35%">Type</td><td id="v_type">-</td></tr>
                            <tr><td class="text-muted">Conveyor</td><td id="v_conveyor">-</td></tr>
                            <tr><td class="text-muted">Carline</td><td id="v_carline">-</td></tr>
                            <tr><td class="text-muted">CCT No</td><td id="v_cct_no">-</td></tr>
                            <tr><td class="text-muted">CCT Code</td><td id="v_cct_code">-</td></tr>
                            <tr><td class="text-muted">Shikake Code</td><td id="v_shikake_code">-</td></tr>
                            <tr><td class="text-muted">Family</td><td id="v_family">-</td></tr>
                            <tr><td class="text-muted">QTY</td><td id="v_qty">-</td></tr>
                            <tr><td class="text-muted">Machine</td><td id="v_machine">-</td></tr>
                            <tr><td class="text-muted">Machine Twist</td><td id="v_machine_twist">-</td></tr>
                            <tr><td class="text-muted">Memory Twist</td><td id="v_memory_twist">-</td></tr>
                            <tr><td class="text-muted">Sequence</td><td id="v_sequence">-</td></tr>
                            <tr><td class="text-muted">Sequence 2</td><td id="v_sequence_2">-</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0 small">
                            <tr><td class="text-muted" width="35%">Released Note</td><td id="v_released_note">-</td></tr>
                            <tr><td class="text-muted">Cust No</td><td id="v_cust_no">-</td></tr>
                            <tr><td class="text-muted">Kind</td><td id="v_kind">-</td></tr>
                            <tr><td class="text-muted">Size</td><td id="v_size">-</td></tr>
                            <tr><td class="text-muted">Col</td><td id="v_col">-</td></tr>
                            <tr><td class="text-muted">C/L</td><td id="v_cl">-</td></tr>
                            <tr><td class="text-muted">To Store</td><td id="v_to_store">-</td></tr>
                            <tr><td class="text-muted">Address</td><td id="v_address">-</td></tr>
                            <tr><td class="text-muted">Barcode Mesin</td><td id="v_barcode_mesin">-</td></tr>
                            <tr><td class="text-muted">Barcode Navigasi</td><td id="v_barcode_navigasi">-</td></tr>
                            <tr><td class="text-muted">Barcode Process</td><td id="v_barcode_process">-</td></tr>
                            <tr><td class="text-muted">Barcode Shikake</td><td id="v_barcode_shikake">-</td></tr>
                            <tr><td class="text-muted">Barcode Twist</td><td id="v_barcode_twist">-</td></tr>
                            <tr><td class="text-muted">QRCode Drawing</td><td id="v_qrcode_drawing">-</td></tr>
                        </table>
                    </div>
                </div>

                <!-- Terminal 1 & 2 -->
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <div class="border rounded p-2">
                            <h6 class="fw-semibold small mb-1 text-primary">Terminal 1</h6>
                            <table class="table table-sm table-borderless mb-0 small">
                                <tr><td class="text-muted" width="30%">Terminal</td><td id="v_terminal_1">-</td></tr>
                                <tr><td class="text-muted">Note</td><td id="v_note_1">-</td></tr>
                                <tr><td class="text-muted">Gold</td><td id="v_gold_1">-</td></tr>
                                <tr><td class="text-muted">Strip</td><td id="v_strip_1">-</td></tr>
                                <tr><td class="text-muted">Acc</td><td id="v_acc_1">-</td></tr>
                                <tr><td class="text-muted">Acc A</td><td id="v_acc_1a">-</td></tr>
                                <tr><td class="text-muted">Tube</td><td id="v_tube_1">-</td></tr>
                                <tr><td class="text-muted">Mark</td><td id="v_mark_1">-</td></tr>
                                <tr><td class="text-muted">TA</td><td id="v_ta">-</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-2">
                            <h6 class="fw-semibold small mb-1 text-primary">Terminal 2</h6>
                            <table class="table table-sm table-borderless mb-0 small">
                                <tr><td class="text-muted" width="30%">Terminal</td><td id="v_terminal_2">-</td></tr>
                                <tr><td class="text-muted">Note</td><td id="v_note_2">-</td></tr>
                                <tr><td class="text-muted">Gold</td><td id="v_gold_2">-</td></tr>
                                <tr><td class="text-muted">Strip</td><td id="v_strip_2">-</td></tr>
                                <tr><td class="text-muted">Acc</td><td id="v_acc_2">-</td></tr>
                                <tr><td class="text-muted">Acc A</td><td id="v_acc_2a">-</td></tr>
                                <tr><td class="text-muted">Tube</td><td id="v_tube_2">-</td></tr>
                                <tr><td class="text-muted">Mark</td><td id="v_mark_2">-</td></tr>
                                <tr><td class="text-muted">TB</td><td id="v_tb">-</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- T Fields & Assy -->
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <div class="border rounded p-2">
                            <h6 class="fw-semibold small mb-1 text-primary">T Fields</h6>
                            <table class="table table-sm table-borderless mb-0 small">
                                <tr><td class="text-muted" width="30%">T01</td><td id="v_t01">-</td></tr>
                                <tr><td class="text-muted">T02</td><td id="v_t02">-</td></tr>
                                <tr><td class="text-muted">T03</td><td id="v_t03">-</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-2">
                            <h6 class="fw-semibold small mb-1 text-primary">Assy List</h6>
                            <div id="v_assy_list" class="small" style="max-height: 100px; overflow-y: auto;">
                                <span class="text-muted">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Drawing Image -->
                <div id="v_drawing_container" class="text-center" style="display:none;">
                    <div class="border rounded p-2">
                        <h6 class="fw-semibold small mb-1 text-primary">Drawing</h6>
                        <img id="v_drawing_img" src="" alt="Drawing" class="img-fluid rounded" style="max-height: 250px; cursor:pointer;" onclick="window.open(this.src,'_blank')">
                    </div>
                </div>
            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
