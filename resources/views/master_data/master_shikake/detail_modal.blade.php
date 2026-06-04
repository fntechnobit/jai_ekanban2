<!-- View Shikake Modal (Read-Only) -->
<div class="modal fade" id="detailShikakeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-2">
                <h6 class="modal-title mb-0"><i class="ti ti-eye me-1"></i> Detail Shikake</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-3" style="max-height: 75vh; overflow-y: auto;">
                <!-- Main Info -->
                <div class="row g-0 mb-2">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0 small">
                            <tr><td class="text-muted" width="35%">Process</td><td id="v_sk_process">-</td></tr>
                            <tr><td class="text-muted">Conveyor</td><td id="v_sk_conveyor">-</td></tr>
                            <tr><td class="text-muted">Carline</td><td id="v_sk_carline">-</td></tr>
                            <tr><td class="text-muted">Machine</td><td id="v_sk_machine">-</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0 small">
                            <tr><td class="text-muted" width="35%">Family</td><td id="v_sk_family">-</td></tr>
                            <tr><td class="text-muted">QTY</td><td id="v_sk_qty">-</td></tr>
                            <tr><td class="text-muted">Sequence</td><td id="v_sk_sequence">-</td></tr>
                            <tr><td class="text-muted">Released Note</td><td id="v_sk_released_note">-</td></tr>
                        </table>
                    </div>
                </div>

                <!-- TWIST Section -->
                <div id="v_sk_twist_section" class="mb-2" style="display:none;">
                    <div class="border rounded p-2" style="border-left: 4px solid #0d6efd !important;">
                        <h6 class="fw-semibold small mb-1 text-primary">TWIST Details</h6>
                        <div class="row g-0">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless mb-0 small">
                                    <tr><td class="text-muted" width="35%">CCT No</td><td id="v_sk_twist_cct_no">-</td></tr>
                                    <tr><td class="text-muted">CCT Code</td><td id="v_sk_twist_cct_code">-</td></tr>
                                    <tr><td class="text-muted">Machine Twist</td><td id="v_sk_twist_machine_twist">-</td></tr>
                                    <tr><td class="text-muted">Sequence 2</td><td id="v_sk_twist_sequence_2">-</td></tr>
                                    <tr><td class="text-muted">Barcode Nav</td><td id="v_sk_twist_barcode_navigasi">-</td></tr>
                                    <tr><td class="text-muted">Barcode Process</td><td id="v_sk_twist_barcode_process">-</td></tr>
                                    <tr><td class="text-muted">Barcode Shikake</td><td id="v_sk_twist_barcode_shikake">-</td></tr>
                                    <tr><td class="text-muted">To Store</td><td id="v_sk_twist_to_store">-</td></tr>
                                    <tr><td class="text-muted">Cust No</td><td id="v_sk_twist_cust_no">-</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless mb-0 small">
                                    <tr><td class="text-muted" width="35%">Kind</td><td id="v_sk_twist_kind">-</td></tr>
                                    <tr><td class="text-muted">Size</td><td id="v_sk_twist_size">-</td></tr>
                                    <tr><td class="text-muted">Color</td><td id="v_sk_twist_color">-</td></tr>
                                    <tr><td class="text-muted">CL</td><td id="v_sk_twist_cl">-</td></tr>
                                    <tr><td class="text-muted">Terminal A / B</td><td><span id="v_sk_twist_terminal_a">-</span> / <span id="v_sk_twist_terminal_b">-</span></td></tr>
                                    <tr><td class="text-muted">ACC 1 A / AB</td><td><span id="v_sk_twist_acc_1_a">-</span> / <span id="v_sk_twist_acc_1_ab">-</span></td></tr>
                                    <tr><td class="text-muted">Tube A / B</td><td><span id="v_sk_twist_tube_a">-</span> / <span id="v_sk_twist_tube_b">-</span></td></tr>
                                    <tr><td class="text-muted">Note A / B</td><td><span id="v_sk_twist_note_a">-</span> / <span id="v_sk_twist_note_b">-</span></td></tr>
                                    <tr><td class="text-muted">Strip A / B</td><td><span id="v_sk_twist_strip_a">-</span> / <span id="v_sk_twist_strip_b">-</span></td></tr>
                                    <tr><td class="text-muted">Mark A / B</td><td><span id="v_sk_twist_mark_a">-</span> / <span id="v_sk_twist_mark_b">-</span></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BONDER Section -->
                <div id="v_sk_bonder_section" class="mb-2" style="display:none;">
                    <div class="border rounded p-2" style="border-left: 4px solid #198754 !important;">
                        <h6 class="fw-semibold small mb-1 text-success">BONDER Details</h6>
                        <table class="table table-sm table-borderless mb-1 small">
                            <tr>
                                <td class="text-muted" width="18%">Bonder No</td><td id="v_sk_bonder_no" width="32%">-</td>
                                <td class="text-muted" width="18%">Address</td><td id="v_sk_bonder_address">-</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Dies</td><td id="v_sk_bonder_dies">-</td>
                                <td class="text-muted">To Machine</td><td id="v_sk_bonder_to_machine">-</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Barcode Nav</td><td id="v_sk_bonder_barcode_navigasi">-</td>
                                <td class="text-muted">Barcode Process</td><td id="v_sk_bonder_barcode_process">-</td>
                            </tr>
                            <tr>
                                <td class="text-muted">QRCode Drawing</td><td id="v_sk_bonder_qrcode_drawing" colspan="3">-</td>
                            </tr>
                        </table>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="border rounded p-1"><h6 class="small fw-semibold mb-1 text-center">Side A</h6>
                                <table class="table table-sm table-bordered mb-0 small text-center"><thead class="table-light"><tr><th>CCT No</th><th>Bonder</th></tr></thead><tbody id="v_sk_bonder_side_a"></tbody></table></div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-1"><h6 class="small fw-semibold mb-1 text-center">Side B</h6>
                                <table class="table table-sm table-bordered mb-0 small text-center"><thead class="table-light"><tr><th>CCT No</th><th>Bonder</th></tr></thead><tbody id="v_sk_bonder_side_b"></tbody></table></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- JOINT Section -->
                <div id="v_sk_joint_section" class="mb-2" style="display:none;">
                    <div class="border rounded p-2" style="border-left: 4px solid #0dcaf0 !important;">
                        <h6 class="fw-semibold small mb-1 text-info">JOINT Details</h6>
                        <table class="table table-sm table-borderless mb-1 small">
                            <tr>
                                <td class="text-muted" width="18%">Bonder No</td><td id="v_sk_joint_bonder_no" width="32%">-</td>
                                <td class="text-muted" width="18%">Address</td><td id="v_sk_joint_address">-</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Address Store</td><td id="v_sk_joint_address_store">-</td>
                                <td class="text-muted">To Machine</td><td id="v_sk_joint_to_machine">-</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Barcode Process</td><td id="v_sk_joint_barcode_process" colspan="3">-</td>
                            </tr>
                            <tr>
                                <td class="text-muted">QRCode Drawing</td><td id="v_sk_joint_qrcode_drawing" colspan="3">-</td>
                            </tr>
                        </table>
                        <table class="table table-sm table-bordered mb-0 small text-center">
                            <thead class="table-light"><tr><th>CCT No 1</th><th>Bonder 1</th><th>CCT No 2</th><th>Bonder 2</th><th>CCT No 3</th></tr></thead>
                            <tbody><tr>
                                <td id="v_sk_joint_cct_1">-</td><td id="v_sk_joint_bonder_1">-</td>
                                <td id="v_sk_joint_cct_2">-</td><td id="v_sk_joint_bonder_2">-</td>
                                <td id="v_sk_joint_cct_3">-</td>
                            </tr><tr>
                                <td id="v_sk_joint_bonder_3">-</td>
                                <td id="v_sk_joint_cct_4">-</td><td id="v_sk_joint_bonder_4">-</td>
                                <td id="v_sk_joint_cct_5">-</td><td id="v_sk_joint_bonder_5">-</td>
                            </tr></tbody>
                        </table>
                    </div>
                </div>

                <!-- SHIELD Section -->
                <div id="v_sk_shield_section" class="mb-2" style="display:none;">
                    <div class="border rounded p-2" style="border-left: 4px solid #dc3545 !important;">
                        <h6 class="fw-semibold small mb-1 text-danger">SHIELD Details</h6>
                        <table class="table table-sm table-borderless mb-1 small">
                            <tr>
                                <td class="text-muted" width="18%">Shield No</td><td id="v_sk_shield_no" width="32%">-</td>
                                <td class="text-muted" width="18%">Address</td><td id="v_sk_shield_address">-</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Blade</td><td id="v_sk_shield_blade">-</td>
                                <td class="text-muted">To Machine</td><td id="v_sk_shield_to_machine">-</td>
                            </tr>
                            <tr>
                                <td class="text-muted">QRCode Drawing</td><td id="v_sk_shield_qrcode_drawing" colspan="3">-</td>
                            </tr>
                        </table>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <span class="small fw-semibold">TO: </span>
                                <span class="small" id="v_sk_shield_to_list">-</span>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-bordered mb-0 small text-center">
                                    <thead class="table-light"><tr><th>CCT No</th><th>Address</th></tr></thead>
                                    <tbody id="v_sk_shield_pairs"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DBL CRIMP Section -->
                <div id="v_sk_dbl_crimp_section" class="mb-2" style="display:none;">
                    <div class="border rounded p-2" style="border-left: 4px solid #212529 !important;">
                        <h6 class="fw-semibold small mb-1">DBL CRIMP Details</h6>
                        <table class="table table-sm table-borderless mb-1 small">
                            <tr>
                                <td class="text-muted" width="18%">Drawing No</td><td id="v_sk_dbl_drawing_no" width="32%">-</td>
                                <td class="text-muted" width="18%">Address</td><td id="v_sk_dbl_address">-</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Barcode Mesin</td><td id="v_sk_dbl_barcode_mesin">-</td>
                                <td class="text-muted">To Machine</td><td id="v_sk_dbl_to_machine">-</td>
                            </tr>
                            <tr>
                                <td class="text-muted">QRCode Drawing</td><td id="v_sk_dbl_qrcode_drawing" colspan="3">-</td>
                            </tr>
                        </table>
                        <table class="table table-sm table-bordered mb-0 small text-center">
                            <thead class="table-light"><tr><th>CCT No</th><th>Address</th></tr></thead>
                            <tbody id="v_sk_dbl_pairs"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Assy List -->
                <div class="border rounded p-2 mb-2">
                    <h6 class="fw-semibold small mb-1 text-primary">Assy List</h6>
                    <div id="v_sk_assy_list" class="small" style="max-height: 80px; overflow-y: auto;">
                        <span class="text-muted">-</span>
                    </div>
                </div>

                <!-- Drawing Image -->
                <div id="v_sk_drawing_container" class="text-center" style="display:none;">
                    <div class="border rounded p-2">
                        <h6 class="fw-semibold small mb-1 text-primary">Drawing</h6>
                        <img id="v_sk_drawing_img" src="" alt="Drawing" class="img-fluid rounded" style="max-height: 250px; cursor:pointer;" onclick="window.open(this.src,'_blank')">
                    </div>
                </div>
            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
