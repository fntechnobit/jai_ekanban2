<!-- Detail Shikake Modal -->
<div class="modal fade" id="detailShikakeModal" tabindex="-1" >
    <div class="modal-dialog modal-xl" >
        <div class="modal-content">
            <form id="shikakeDetailForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="shikake_id" name="id">
                
                <div class="modal-header bg-info">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Detail Shikake
                        <span id="loading-indicator" class="spinner-border spinner-border-sm ms-2" style="display: none;" role="status" aria-hidden="true"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">
                    <!-- Main Information Section -->
                    <div class="card mb-3">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Main Information</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="row">
                                <!-- Left Column -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Conveyor <span class="text-danger">*</span></label>
                                        <input type="text" id="conveyor" class="form-control form-control-sm" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Process <span class="text-danger">*</span></label>
                                        <select name="process" id="process" class="form-select form-control-sm" onchange="toggleProcessSections()">
                                            <option value="">- Choose Process -</option>
                                            @foreach($processTypes as $processType)
                                                <option value="{{ $processType->value }}">{{ $processType->value }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" id="process-error"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Machine</label>
                                        <input type="text" name="machine" id="machine" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="machine-error"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Family</label>
                                        <input type="text" name="family" id="family" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="family-error"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Sequence</label>
                                        <input type="number" name="sequence" id="sequence" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="sequence-error"></div>
                                    </div>
                                </div>
                                
                                <!-- Right Column -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Qty</label>
                                        <input type="number" name="qty" id="qty" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="qty-error"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Issue</label>
                                        <input type="text" name="issue" id="issue" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="issue-error"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Barcode Kanban</label>
                                        <input type="text" name="barcode_kanban" id="barcode_kanban" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="barcode_kanban-error"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Released Date</label>
                                        <input type="date" name="released_date" id="released_date" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="released_date-error"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Released Note</label>
                                        <textarea name="released_note" id="released_note" class="form-control form-control-sm" rows="2"></textarea>
                                        <div class="invalid-feedback" id="released_note-error"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Process-Specific Sections -->
                    
                    <!-- TWIST Process Section -->
                    <div id="twist-section" class="process-section card mb-3" style="display: none;">
                        <div class="card-header bg-primary text-white py-2">
                            <h6 class="mb-0"><i class="fas fa-cog me-2"></i>TWIST Process Details</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">CCT No <span class="text-danger">*</span></label>
                                        <input type="text" name="process_data[cct_no]" id="twist_cct_no" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="twist_cct_no-error"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">CCT Code <span class="text-danger">*</span></label>
                                        <input type="text" name="process_data[cct_code]" id="twist_cct_code" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="twist_cct_code-error"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Machine Twist</label>
                                        <input type="text" name="process_data[machine_twist]" id="twist_machine_twist" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="twist_machine_twist-error"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Sequence 2</label>
                                        <input type="number" name="process_data[sequence_2]" id="twist_sequence_2" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="twist_sequence_2-error"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Barcode Navigasi</label>
                                        <input type="text" name="process_data[barcode_navigasi]" id="twist_barcode_navigasi" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="twist_barcode_navigasi-error"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Barcode Process</label>
                                        <input type="text" name="process_data[barcode_process]" id="twist_barcode_process" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="twist_barcode_process-error"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Barcode Shikake</label>
                                        <input type="text" name="process_data[barcode_shikake]" id="twist_barcode_shikake" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="twist_barcode_shikake-error"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">To Store</label>
                                        <input type="text" name="process_data[to_store]" id="twist_to_store" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="twist_to_store-error"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Cust No</label>
                                        <input type="text" name="process_data[cust_no]" id="twist_cust_no" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="twist_cust_no-error"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Kind</label>
                                        <input type="text" name="process_data[kind]" id="twist_kind" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="twist_kind-error"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Size</label>
                                        <input type="text" name="process_data[size]" id="twist_size" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="twist_size-error"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Color</label>
                                        <input type="text" name="process_data[color]" id="twist_color" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="twist_color-error"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">CL</label>
                                        <input type="text" name="process_data[cl]" id="twist_cl" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="twist_cl-error"></div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Terminal A</label>
                                                <input type="text" name="process_data[terminal_a]" id="twist_terminal_a" class="form-control form-control-sm">
                                                <div class="invalid-feedback" id="twist_terminal_a-error"></div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Terminal B</label>
                                                <input type="text" name="process_data[terminal_b]" id="twist_terminal_b" class="form-control form-control-sm">
                                                <div class="invalid-feedback" id="twist_terminal_b-error"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">ACC 1 A</label>
                                                <input type="text" name="process_data[acc_1_a]" id="twist_acc_1_a" class="form-control form-control-sm">
                                                <div class="invalid-feedback" id="twist_acc_1_a-error"></div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">ACC 1 AB</label>
                                                <input type="text" name="process_data[acc_1_ab]" id="twist_acc_1_ab" class="form-control form-control-sm">
                                                <div class="invalid-feedback" id="twist_acc_1_ab-error"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Tube A</label>
                                                <input type="text" name="process_data[tube_a]" id="twist_tube_a" class="form-control form-control-sm">
                                                <div class="invalid-feedback" id="twist_tube_a-error"></div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Tube B</label>
                                                <input type="text" name="process_data[tube_b]" id="twist_tube_b" class="form-control form-control-sm">
                                                <div class="invalid-feedback" id="twist_tube_b-error"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Note A</label>
                                                <input type="text" name="process_data[note_a]" id="twist_note_a" class="form-control form-control-sm">
                                                <div class="invalid-feedback" id="twist_note_a-error"></div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Note B</label>
                                                <input type="text" name="process_data[note_b]" id="twist_note_b" class="form-control form-control-sm">
                                                <div class="invalid-feedback" id="twist_note_b-error"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Strip A</label>
                                                <input type="text" name="process_data[strip_a]" id="twist_strip_a" class="form-control form-control-sm">
                                                <div class="invalid-feedback" id="twist_strip_a-error"></div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Strip B</label>
                                                <input type="text" name="process_data[strip_b]" id="twist_strip_b" class="form-control form-control-sm">
                                                <div class="invalid-feedback" id="twist_strip_b-error"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Mark A</label>
                                                <input type="text" name="process_data[mark_a]" id="twist_mark_a" class="form-control form-control-sm">
                                                <div class="invalid-feedback" id="twist_mark_a-error"></div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Mark B</label>
                                                <input type="text" name="process_data[mark_b]" id="twist_mark_b" class="form-control form-control-sm">
                                                <div class="invalid-feedback" id="twist_mark_b-error"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BONDER Process Section -->
                    <div id="bonder-section" class="process-section card mb-3" style="display: none;">
                        <div class="card-header bg-success text-white py-2">
                            <h6 class="mb-0"><i class="fas fa-link me-2"></i>BONDER Process Details</h6>
                        </div>
                        <div class="card-body p-3">
                            <!-- Header Fields -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Bonder No <span class="text-danger">*</span></label>
                                        <input type="text" name="process_data[bonder_no]" id="bonder_bonder_no" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="bonder_bonder_no-error"></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Address</label>
                                        <input type="text" name="process_data[address]" id="bonder_address" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="bonder_address-error"></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Dies</label>
                                        <input type="text" name="process_data[dies]" id="bonder_dies" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="bonder_dies-error"></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">To Machine</label>
                                        <input type="text" name="process_data[to_machine]" id="bonder_to_machine" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="bonder_to_machine-error"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Side A Section -->
                            <div class="card mb-3">
                                <div class="card-header bg-dark text-white py-2">
                                    <h6 class="mb-0">SIDE A</h6>
                                </div>
                                <div class="card-body p-2">
                                    <div class="row">
                                        <!-- Pair 1 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 1</label>
                                                    <input type="text" name="process_data[cct_no_a_1]" id="bonder_cct_no_a_1" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_cct_no_a_1-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder 1</label>
                                                    <input type="text" name="process_data[bonder_no_a_1]" id="bonder_bonder_no_a_1" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_bonder_no_a_1-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Pair 2 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 2</label>
                                                    <input type="text" name="process_data[cct_no_a_2]" id="bonder_cct_no_a_2" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_cct_no_a_2-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder 2</label>
                                                    <input type="text" name="process_data[bonder_no_a_2]" id="bonder_bonder_no_a_2" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_bonder_no_a_2-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Pair 3 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 3</label>
                                                    <input type="text" name="process_data[cct_no_a_3]" id="bonder_cct_no_a_3" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_cct_no_a_3-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder 3</label>
                                                    <input type="text" name="process_data[bonder_no_a_3]" id="bonder_bonder_no_a_3" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_bonder_no_a_3-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Pair 4 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 4</label>
                                                    <input type="text" name="process_data[cct_no_a_4]" id="bonder_cct_no_a_4" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_cct_no_a_4-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder 4</label>
                                                    <input type="text" name="process_data[bonder_no_a_4]" id="bonder_bonder_no_a_4" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_bonder_no_a_4-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Pair 5 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 5</label>
                                                    <input type="text" name="process_data[cct_no_a_5]" id="bonder_cct_no_a_5" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_cct_no_a_5-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder 5</label>
                                                    <input type="text" name="process_data[bonder_no_a_5]" id="bonder_bonder_no_a_5" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_bonder_no_a_5-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Pair 6 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 6</label>
                                                    <input type="text" name="process_data[cct_no_a_6]" id="bonder_cct_no_a_6" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_cct_no_a_6-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder 6</label>
                                                    <input type="text" name="process_data[bonder_no_a_6]" id="bonder_bonder_no_a_6" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_bonder_no_a_6-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Pair 7 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 7</label>
                                                    <input type="text" name="process_data[cct_no_a_7]" id="bonder_cct_no_a_7" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_cct_no_a_7-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder 7</label>
                                                    <input type="text" name="process_data[bonder_no_a_7]" id="bonder_bonder_no_a_7" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_bonder_no_a_7-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Side B Section -->
                            <div class="card mb-3">
                                <div class="card-header bg-secondary text-white py-2">
                                    <h6 class="mb-0">SIDE B</h6>
                                </div>
                                <div class="card-body p-2">
                                    <div class="row">
                                        <!-- Pair 1 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 1</label>
                                                    <input type="text" name="process_data[cct_no_b_1]" id="bonder_cct_no_b_1" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_cct_no_b_1-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder 1</label>
                                                    <input type="text" name="process_data[bonder_no_b_1]" id="bonder_bonder_no_b_1" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_bonder_no_b_1-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Pair 2 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 2</label>
                                                    <input type="text" name="process_data[cct_no_b_2]" id="bonder_cct_no_b_2" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_cct_no_b_2-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder 2</label>
                                                    <input type="text" name="process_data[bonder_no_b_2]" id="bonder_bonder_no_b_2" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_bonder_no_b_2-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Pair 3 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 3</label>
                                                    <input type="text" name="process_data[cct_no_b_3]" id="bonder_cct_no_b_3" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_cct_no_b_3-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder 3</label>
                                                    <input type="text" name="process_data[bonder_no_b_3]" id="bonder_bonder_no_b_3" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_bonder_no_b_3-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Pair 4 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 4</label>
                                                    <input type="text" name="process_data[cct_no_b_4]" id="bonder_cct_no_b_4" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_cct_no_b_4-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder 4</label>
                                                    <input type="text" name="process_data[bonder_no_b_4]" id="bonder_bonder_no_b_4" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_bonder_no_b_4-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Pair 5 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 5</label>
                                                    <input type="text" name="process_data[cct_no_b_5]" id="bonder_cct_no_b_5" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_cct_no_b_5-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder 5</label>
                                                    <input type="text" name="process_data[bonder_no_b_5]" id="bonder_bonder_no_b_5" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_bonder_no_b_5-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Pair 6 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 6</label>
                                                    <input type="text" name="process_data[cct_no_b_6]" id="bonder_cct_no_b_6" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_cct_no_b_6-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder 6</label>
                                                    <input type="text" name="process_data[bonder_no_b_6]" id="bonder_bonder_no_b_6" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_bonder_no_b_6-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Pair 7 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 7</label>
                                                    <input type="text" name="process_data[cct_no_b_7]" id="bonder_cct_no_b_7" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_cct_no_b_7-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder 7</label>
                                                    <input type="text" name="process_data[bonder_no_b_7]" id="bonder_bonder_no_b_7" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_bonder_no_b_7-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Barcode Fields -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Barcode Navigasi</label>
                                        <input type="text" name="process_data[barcode_navigasi]" id="bonder_barcode_navigasi" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="bonder_barcode_navigasi-error"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Barcode Process</label>
                                        <input type="text" name="process_data[barcode_process]" id="bonder_barcode_process" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="bonder_barcode_process-error"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- JOINT Process Section -->
                    <div id="joint-section" class="process-section card mb-3" style="display: none;">
                        <div class="card-header bg-warning text-dark py-2">
                            <h6 class="mb-0"><i class="fas fa-compress-alt me-2"></i>JOINT Process Details</h6>
                        </div>
                        <div class="card-body p-3">
                            <!-- Header Fields -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Bonder No <span class="text-danger">*</span></label>
                                        <input type="text" name="process_data[bonder_no]" id="joint_bonder_no" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="joint_bonder_no-error"></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                                        <input type="text" name="process_data[address]" id="joint_address" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="joint_address-error"></div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Address Store</label>
                                        <input type="text" name="process_data[address_store]" id="joint_address_store" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="joint_address_store-error"></div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">To Machine</label>
                                        <input type="text" name="process_data[to_machine]" id="joint_to_machine" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="joint_to_machine-error"></div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Barcode Process</label>
                                        <input type="text" name="process_data[barcode_process]" id="joint_barcode_process" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="joint_barcode_process-error"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- CCT & Bonder pairs -->
                            <div class="card">
                                <div class="card-header bg-info text-white py-2">
                                    <h6 class="mb-0">CCT No & Bonder No Pairs</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row">
                                        <!-- Pair 1 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 1</label>
                                                    <input type="text" name="process_data[cct_no_1]" id="joint_cct_no_1" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="joint_cct_no_1-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder No 1</label>
                                                    <input type="text" name="process_data[bonder_no_1]" id="joint_bonder_no_1" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="joint_bonder_no_1-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Pair 2 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 2</label>
                                                    <input type="text" name="process_data[cct_no_2]" id="joint_cct_no_2" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="joint_cct_no_2-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder No 2</label>
                                                    <input type="text" name="process_data[bonder_no_2]" id="joint_bonder_no_2" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="joint_bonder_no_2-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Pair 3 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 3</label>
                                                    <input type="text" name="process_data[cct_no_3]" id="joint_cct_no_3" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="joint_cct_no_3-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder No 3</label>
                                                    <input type="text" name="process_data[bonder_no_3]" id="joint_bonder_no_3" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="joint_bonder_no_3-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Pair 4 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 4</label>
                                                    <input type="text" name="process_data[cct_no_4]" id="joint_cct_no_4" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="joint_cct_no_4-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder No 4</label>
                                                    <input type="text" name="process_data[bonder_no_4]" id="joint_bonder_no_4" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="joint_bonder_no_4-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Pair 5 -->
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 5</label>
                                                    <input type="text" name="process_data[cct_no_5]" id="joint_cct_no_5" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="joint_cct_no_5-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder No 5</label>
                                                    <input type="text" name="process_data[bonder_no_5]" id="joint_bonder_no_5" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="joint_bonder_no_5-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SHIELD Process Section -->
                    <div id="shield-section" class="process-section card mb-3" style="display: none;">
                        <div class="card-header bg-danger text-white py-2">
                            <h6 class="mb-0"><i class="fas fa-shield-alt me-2"></i>SHIELD Process Details</h6>
                        </div>
                        <div class="card-body p-3">
                            <!-- Header Fields -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Shield No <span class="text-danger">*</span></label>
                                        <input type="text" name="process_data[shield_no]" id="shield_shield_no" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="shield_shield_no-error"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Address</label>
                                        <input type="text" name="process_data[address]" id="shield_address" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="shield_address-error"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Blade</label>
                                        <input type="text" name="process_data[blade]" id="shield_blade" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="shield_blade-error"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <!-- Left Column: TO fields -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-secondary text-white py-2">
                                            <h6 class="mb-0">TO Fields</h6>
                                        </div>
                                        <div class="card-body p-2">
                                            <div class="row">
                                                <div class="col-4">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">TO 1</label>
                                                        <input type="text" name="process_data[to_1]" id="shield_to_1" class="form-control form-control-sm">
                                                        <div class="invalid-feedback" id="shield_to_1-error"></div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">TO 2</label>
                                                        <input type="text" name="process_data[to_2]" id="shield_to_2" class="form-control form-control-sm">
                                                        <div class="invalid-feedback" id="shield_to_2-error"></div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">TO 3</label>
                                                        <input type="text" name="process_data[to_3]" id="shield_to_3" class="form-control form-control-sm">
                                                        <div class="invalid-feedback" id="shield_to_3-error"></div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">TO 4</label>
                                                        <input type="text" name="process_data[to_4]" id="shield_to_4" class="form-control form-control-sm">
                                                        <div class="invalid-feedback" id="shield_to_4-error"></div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">TO 5</label>
                                                        <input type="text" name="process_data[to_5]" id="shield_to_5" class="form-control form-control-sm">
                                                        <div class="invalid-feedback" id="shield_to_5-error"></div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">TO 6</label>
                                                        <input type="text" name="process_data[to_6]" id="shield_to_6" class="form-control form-control-sm">
                                                        <div class="invalid-feedback" id="shield_to_6-error"></div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">TO 7</label>
                                                        <input type="text" name="process_data[to_7]" id="shield_to_7" class="form-control form-control-sm">
                                                        <div class="invalid-feedback" id="shield_to_7-error"></div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">TO 8</label>
                                                        <input type="text" name="process_data[to_8]" id="shield_to_8" class="form-control form-control-sm">
                                                        <div class="invalid-feedback" id="shield_to_8-error"></div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">TO 9</label>
                                                        <input type="text" name="process_data[to_9]" id="shield_to_9" class="form-control form-control-sm">
                                                        <div class="invalid-feedback" id="shield_to_9-error"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Right Column: CCT & Bonder pairs -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-info text-white py-2">
                                            <h6 class="mb-0">CCT No & Bonder No Pairs</h6>
                                        </div>
                                        <div class="card-body p-2">
                                            <!-- Pair 1 -->
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 1</label>
                                                    <input type="text" name="process_data[cct_no_1]" id="shield_cct_no_1" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="shield_cct_no_1-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder No 1</label>
                                                    <input type="text" name="process_data[bonder_no_1]" id="shield_bonder_no_1" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="shield_bonder_no_1-error"></div>
                                                </div>
                                            </div>
                                            <!-- Pair 2 -->
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT No 2</label>
                                                    <input type="text" name="process_data[cct_no_2]" id="shield_cct_no_2" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="shield_cct_no_2-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder No 2</label>
                                                    <input type="text" name="process_data[bonder_no_2]" id="shield_bonder_no_2" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="shield_bonder_no_2-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DBL CRIMP Process Section -->
                    <div id="dbl-crimp-section" class="process-section card mb-3" style="display: none;">
                        <div class="card-header bg-dark text-white py-2">
                            <h6 class="mb-0"><i class="fas fa-compress me-2"></i>DBL CRIMP Process Details</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Drawing No</label>
                                        <input type="text" name="process_data[drawing_no]" id="dbl_crimp_drawing_no" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="dbl_crimp_drawing_no-error"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Address</label>
                                        <input type="text" name="process_data[address]" id="dbl_crimp_address" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="dbl_crimp_address-error"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Barcode Mesin</label>
                                        <input type="text" name="process_data[barcode_mesin]" id="dbl_crimp_barcode_mesin" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="dbl_crimp_barcode_mesin-error"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">To Machine</label>
                                        <input type="text" name="process_data[to_machine]" id="dbl_crimp_to_machine" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="dbl_crimp_to_machine-error"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- CCT & Address pairs -->
                            <div class="row">
                                <!-- Pair 1 -->
                                <div class="col-md-6">
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">CCT No 1</label>
                                            <input type="text" name="process_data[cct_no_1]" id="dbl_crimp_cct_no_1" class="form-control form-control-sm">
                                            <div class="invalid-feedback" id="dbl_crimp_cct_no_1-error"></div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Address 1</label>
                                            <input type="text" name="process_data[address_1]" id="dbl_crimp_address_1" class="form-control form-control-sm">
                                            <div class="invalid-feedback" id="dbl_crimp_address_1-error"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Pair 2 -->
                                <div class="col-md-6">
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">CCT No 2</label>
                                            <input type="text" name="process_data[cct_no_2]" id="dbl_crimp_cct_no_2" class="form-control form-control-sm">
                                            <div class="invalid-feedback" id="dbl_crimp_cct_no_2-error"></div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Address 2</label>
                                            <input type="text" name="process_data[address_2]" id="dbl_crimp_address_2" class="form-control form-control-sm">
                                            <div class="invalid-feedback" id="dbl_crimp_address_2-error"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Pair 3 -->
                                <div class="col-md-6">
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">CCT No 3</label>
                                            <input type="text" name="process_data[cct_no_3]" id="dbl_crimp_cct_no_3" class="form-control form-control-sm">
                                            <div class="invalid-feedback" id="dbl_crimp_cct_no_3-error"></div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Address 3</label>
                                            <input type="text" name="process_data[address_3]" id="dbl_crimp_address_3" class="form-control form-control-sm">
                                            <div class="invalid-feedback" id="dbl_crimp_address_3-error"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Pair 4 -->
                                <div class="col-md-6">
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">CCT No 4</label>
                                            <input type="text" name="process_data[cct_no_4]" id="dbl_crimp_cct_no_4" class="form-control form-control-sm">
                                            <div class="invalid-feedback" id="dbl_crimp_cct_no_4-error"></div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Address 4</label>
                                            <input type="text" name="process_data[address_4]" id="dbl_crimp_address_4" class="form-control form-control-sm">
                                            <div class="invalid-feedback" id="dbl_crimp_address_4-error"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Pair 5 -->
                                <div class="col-md-6">
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">CCT No 5</label>
                                            <input type="text" name="process_data[cct_no_5]" id="dbl_crimp_cct_no_5" class="form-control form-control-sm">
                                            <div class="invalid-feedback" id="dbl_crimp_cct_no_5-error"></div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Address 5</label>
                                            <input type="text" name="process_data[address_5]" id="dbl_crimp_address_5" class="form-control form-control-sm">
                                            <div class="invalid-feedback" id="dbl_crimp_address_5-error"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Image Upload & Assy List Section -->
                    <div class="card mb-3">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="fas fa-image me-2"></i>Additional Information</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Image</label>
                                        <input type="file" name="image" id="imageInput" class="form-control form-control-sm" accept="image/*">
                                        <div class="invalid-feedback" id="image-error"></div>
                                        <div class="mt-2" id="imagePreviewContainer" style="display: none;">
                                            <img id="imagePreview" src="" alt="Preview" class="img-thumbnail" style="max-width: 100%; max-height: 200px;">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold d-block">Assy List</label>
                                        <div class="card border">
                                            <div class="card-body p-2">
                                                <div id="assyList" style="max-height: 150px; overflow-y: auto;">
                                                    <p class="text-muted mb-0 small">Loading...</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm" id="submit-btn">
                        <span id="submit-spinner" class="spinner-border spinner-border-sm me-2" style="display: none;" role="status" aria-hidden="true"></span>
                        <i class="fa-solid fa-floppy-disk" id="submit-icon"></i> 
                        <span id="submit-text">Update</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.process-section {
    border-left: 4px solid;
}

#twist-section {
    border-left-color: #0d6efd;
}

#bonder-section {
    border-left-color: #198754;
}

#joint-section {
    border-left-color: #ffc107;
}

#shield-section {
    border-left-color: #dc3545;
}

#dbl-crimp-section {
    border-left-color: #212529;
}

.form-control:focus, .form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.invalid-feedback {
    display: block;
}

.form-control.is-invalid {
    border-color: #dc3545;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}
</style>

<script>
function toggleProcessSections() {
    const process = document.getElementById('process').value;
    const sections = document.querySelectorAll('.process-section');
    
    // Hide all sections and disable their inputs
    sections.forEach(section => {
        section.style.display = 'none';
        // Disable all inputs in hidden sections so they don't submit
        section.querySelectorAll('input, select, textarea').forEach(input => {
            input.disabled = true;
        });
    });
    
    // Show specific section based on process and enable its inputs
    if (process) {
        const sectionId = process.toLowerCase().replace(' ', '-') + '-section';
        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
            targetSection.style.display = 'block';
            // Enable inputs in the visible section
            targetSection.querySelectorAll('input, select, textarea').forEach(input => {
                input.disabled = false;
            });
        }
    }
}

// Image preview functionality
document.getElementById('imageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const previewContainer = document.getElementById('imagePreviewContainer');
    const preview = document.getElementById('imagePreview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        previewContainer.style.display = 'none';
    }
});
</script>
