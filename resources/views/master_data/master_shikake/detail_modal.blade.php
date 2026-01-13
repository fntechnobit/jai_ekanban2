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
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Bonder No <span class="text-danger">*</span></label>
                                        <input type="text" name="process_data[bonder_no]" id="bonder_bonder_no" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="bonder_bonder_no-error"></div>
                                    </div>
                                    
                                    <!-- CCT & Bonder pairs in 2 columns -->
                                    <div class="row">
                                        @for($i = 1; $i <= 14; $i++)
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT {{ $i }}</label>
                                                    <input type="text" name="process_data[cct_{{ $i }}]" id="bonder_cct_{{ $i }}" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_cct_{{ $i }}-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder {{ $i }}</label>
                                                    <input type="text" name="process_data[bonder_{{ $i }}]" id="bonder_bonder_{{ $i }}" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="bonder_bonder_{{ $i }}-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        @endfor
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
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Bonder No <span class="text-danger">*</span></label>
                                                <input type="text" name="process_data[bonder_no]" id="joint_bonder_no" class="form-control form-control-sm">
                                                <div class="invalid-feedback" id="joint_bonder_no-error"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                                                <input type="text" name="process_data[address]" id="joint_address" class="form-control form-control-sm">
                                                <div class="invalid-feedback" id="joint_address-error"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- CCT & Bonder pairs in 2 columns -->
                                    <div class="row">
                                        @for($i = 1; $i <= 10; $i++)
                                        <div class="col-md-6">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">CCT {{ $i }}</label>
                                                    <input type="text" name="process_data[cct_{{ $i }}]" id="joint_cct_{{ $i }}" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="joint_cct_{{ $i }}-error"></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Bonder {{ $i }}</label>
                                                    <input type="text" name="process_data[bonder_{{ $i }}]" id="joint_bonder_{{ $i }}" class="form-control form-control-sm">
                                                    <div class="invalid-feedback" id="joint_bonder_{{ $i }}-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        @endfor
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
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Shield No <span class="text-danger">*</span></label>
                                        <input type="text" name="process_data[shield_no]" id="shield_shield_no" class="form-control form-control-sm">
                                        <div class="invalid-feedback" id="shield_shield_no-error"></div>
                                    </div>
                                    
                                    <!-- TO fields in grid -->
                                    <div class="row">
                                        @for($i = 1; $i <= 9; $i++)
                                        <div class="col-4">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">TO {{ $i }}</label>
                                                <input type="text" name="process_data[to_{{ $i }}]" id="shield_to_{{ $i }}" class="form-control form-control-sm">
                                                <div class="invalid-feedback" id="shield_to_{{ $i }}-error"></div>
                                            </div>
                                        </div>
                                        @endfor
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <!-- CCT & Bonder pairs -->
                                    @for($i = 1; $i <= 4; $i++)
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">CCT {{ $i }}</label>
                                            <input type="text" name="process_data[cct_{{ $i }}]" id="shield_cct_{{ $i }}" class="form-control form-control-sm">
                                            <div class="invalid-feedback" id="shield_cct_{{ $i }}-error"></div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Bonder {{ $i }}</label>
                                            <input type="text" name="process_data[bonder_{{ $i }}]" id="shield_bonder_{{ $i }}" class="form-control form-control-sm">
                                            <div class="invalid-feedback" id="shield_bonder_{{ $i }}-error"></div>
                                        </div>
                                    </div>
                                    @endfor
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
                                @for($i = 1; $i <= 5; $i++)
                                <div class="col-md-6">
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">CCT No {{ $i }}</label>
                                            <input type="text" name="process_data[cct_no_{{ $i }}]" id="dbl_crimp_cct_no_{{ $i }}" class="form-control form-control-sm">
                                            <div class="invalid-feedback" id="dbl_crimp_cct_no_{{ $i }}-error"></div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Address {{ $i }}</label>
                                            <input type="text" name="process_data[address_{{ $i }}]" id="dbl_crimp_address_{{ $i }}" class="form-control form-control-sm">
                                            <div class="invalid-feedback" id="dbl_crimp_address_{{ $i }}-error"></div>
                                        </div>
                                    </div>
                                </div>
                                @endfor
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
    
    // Hide all sections
    sections.forEach(section => {
        section.style.display = 'none';
    });
    
    // Show specific section based on process
    if (process) {
        const sectionId = process.toLowerCase().replace(' ', '-') + '-section';
        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
            targetSection.style.display = 'block';
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
