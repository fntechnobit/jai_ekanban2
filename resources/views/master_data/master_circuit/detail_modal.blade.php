<!-- Detail Circuit Modal -->
<div class="modal fade" id="detailCircuitModal" tabindex="-1" >
    <div class="modal-dialog modal-xl" >
        <div class="modal-content">
            <form id="circuitDetailForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="circuit_id" name="id">
                
                <div class="modal-header bg-info">
                    <h5 class="modal-title">Detail Circuit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        
                    </button>
                </div>
                
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Type <span class="text-danger">*</span></label>
                                <select name="type" id="modal_circuit_type" class="form-select form-select-sm" required>
                                    <option value="CUTTING">CUTTING</option>
                                    <option value="CUTTING_TWIST">CUTTING TWIST</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label>Conveyor</label>
                                <input type="text" id="conveyor" class="form-control form-control-sm" readonly>
                            </div>

                            <div class="mb-3">
                                <label>Carline <span class="text-danger">*</span></label>
                                <input type="text" name="carline" id="carline" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>CCT No</label>
                                <input type="text" name="cct_no" id="cct_no" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Family</label>
                                <input type="text" name="family" id="family" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>QTY</label>
                                <input type="number" name="qty" id="qty" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Machine</label>
                                <input type="text" name="machine" id="machine" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Sequence</label>
                                <input type="text" name="sequence" id="sequence" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Released Note</label>
                                <textarea name="released_note" id="released_note" class="form-control form-control-sm" rows="2"></textarea>
                            </div>

                            <div class="mb-3">
                                <label>Customer No</label>
                                <input type="text" name="cust_no" id="cust_no" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3 modal-cutting-only-field">
                                <label>Barcode Mesin</label>
                                <input type="text" name="barcode_mesin" id="barcode_mesin" class="form-control form-control-sm">
                            </div>

                            <!-- CUTTING_TWIST specific fields -->
                            <div class="modal-cutting-twist-fields" style="display: none;">
                                <div class="mb-3">
                                    <label>Machine Twist</label>
                                    <input type="text" name="machine_twist" id="machine_twist" class="form-control form-control-sm">
                                </div>

                                <div class="mb-3">
                                    <label>Sequence 2</label>
                                    <input type="number" name="sequence_2" id="sequence_2" class="form-control form-control-sm">
                                </div>

                                <div class="mb-3">
                                    <label>Barcode Navigasi</label>
                                    <input type="text" name="barcode_navigasi" id="barcode_navigasi" class="form-control form-control-sm">
                                </div>

                                <div class="mb-3">
                                    <label>Barcode Process</label>
                                    <input type="text" name="barcode_process" id="barcode_process" class="form-control form-control-sm">
                                </div>

                                <div class="mb-3">
                                    <label>Barcode Shikake</label>
                                    <input type="text" name="barcode_shikake" id="barcode_shikake" class="form-control form-control-sm">
                                </div>

                                <div class="mb-3">
                                    <label>Memory Twist</label>
                                    <input type="text" name="memory_twist" id="memory_twist" class="form-control form-control-sm">
                                </div>

                                <div class="mb-3">
                                    <label>To Store</label>
                                    <input type="text" name="to_store" id="to_store" class="form-control form-control-sm">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label>Address</label>
                                <input type="text" name="address" id="address" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>CCT Code</label>
                                <input type="text" name="cct_code" id="cct_code" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Kind</label>
                                <input type="text" name="kind" id="kind" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Size</label>
                                <input type="text" name="size" id="size" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Col</label>
                                <input type="text" name="col" id="col" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>CL</label>
                                <input type="text" name="cl" id="cl" class="form-control form-control-sm">
                            </div>

                            <hr>
                            <h6 class="font-weight-bold">Terminal 1</h6>
                            
                            <div class="mb-3">
                                <label>Terminal 1</label>
                                <input type="text" name="terminal_1" id="terminal_1" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Note 1</label>
                                <input type="text" name="note_1" id="note_1" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Gold 1</label>
                                <input type="text" name="gold_1" id="gold_1" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Strip 1</label>
                                <input type="text" name="strip_1" id="strip_1" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>ACC 1</label>
                                <input type="text" name="acc_1a" id="acc_1a" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>ACC 2</label>
                                <input type="text" name="acc_2a" id="acc_2a" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Tube 1</label>
                                <input type="text" name="tube_1" id="tube_1" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Mark 1</label>
                                <input type="text" name="mark_1" id="mark_1" class="form-control form-control-sm">
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Terminal 2</h6>
                            
                            <div class="mb-3">
                                <label>Terminal 2</label>
                                <input type="text" name="terminal_2" id="terminal_2" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Note 2</label>
                                <input type="text" name="note_2" id="note_2" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Gold 2</label>
                                <input type="text" name="gold_2" id="gold_2" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Strip 2</label>
                                <input type="text" name="strip_2" id="strip_2" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>ACC 1</label>
                                <input type="text" name="acc_1b" id="acc_1b" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>ACC 2</label>
                                <input type="text" name="acc_2b" id="acc_2b" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Tube 2</label>
                                <input type="text" name="tube_2" id="tube_2" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Mark 2</label>
                                <input type="text" name="mark_2" id="mark_2" class="form-control form-control-sm">
                            </div>

                            <hr>
                            <h6 class="font-weight-bold">T Fields</h6>
                            
                            @for($i = 1; $i <= 3; $i++)
                            <div class="mb-3">
                                <label>T{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</label>
                                <input type="text" name="t{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" id="t{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" class="form-control form-control-sm">
                            </div>
                            @endfor

                            <!-- Image Upload -->
                            <div class="mb-3">
                                <label>Image</label>
                                <input type="file" name="image" id="imageInput" class="form-control form-control-sm" accept="image/*">
                                <div class="mt-2" id="imagePreviewContainer" style="display: none;">
                                    <img id="imagePreview" src="" alt="Preview" class="img-thumbnail" style="max-width: 100%; max-height: 200px;">
                                </div>
                            </div>

                            <!-- Assy List -->
                            <div class="mb-3">
                                <label class="d-block">Assy List</label>
                                <div class="card">
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
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-floppy-disk"></i> Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
