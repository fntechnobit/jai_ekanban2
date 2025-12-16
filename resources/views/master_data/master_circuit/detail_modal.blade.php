<!-- Detail Circuit Modal -->
<div class="modal fade" id="detailCircuitModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <form id="circuitDetailForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="circuit_id" name="id">
                
                <div class="modal-header bg-info">
                    <h5 class="modal-title">Detail Circuit</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Conveyor</label>
                                <input type="text" id="conveyor" class="form-control" readonly>
                            </div>

                            <div class="form-group">
                                <label>CCT No</label>
                                <input type="text" name="cct_no" id="cct_no" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Family</label>
                                <input type="text" name="family" id="family" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>QTY</label>
                                <input type="number" name="qty" id="qty" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Issue</label>
                                <input type="text" name="issue" id="issue" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Machine</label>
                                <input type="text" name="machine" id="machine" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Sequence</label>
                                <input type="text" name="sequence" id="sequence" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Barcode Kanban</label>
                                <input type="text" name="barcode_kanban" id="barcode_kanban" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Released Date</label>
                                <input type="date" name="released_date" id="released_date" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Released Note</label>
                                <textarea name="released_note" id="released_note" class="form-control" rows="2"></textarea>
                            </div>

                            <div class="form-group">
                                <label>Customer No</label>
                                <input type="text" name="cust_no" id="cust_no" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Barcode Mesin</label>
                                <input type="text" name="barcode_mesin" id="barcode_mesin" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Address</label>
                                <input type="text" name="address" id="address" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>CCT Code</label>
                                <input type="text" name="cct_code" id="cct_code" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Kind</label>
                                <input type="text" name="kind" id="kind" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Size</label>
                                <input type="text" name="size" id="size" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Col</label>
                                <input type="text" name="col" id="col" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>CL</label>
                                <input type="text" name="cl" id="cl" class="form-control">
                            </div>

                            <hr>
                            <h6 class="font-weight-bold">Terminal 1</h6>
                            
                            <div class="form-group">
                                <label>Terminal 1</label>
                                <input type="text" name="terminal_1" id="terminal_1" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Note 1</label>
                                <input type="text" name="note_1" id="note_1" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Gold 1</label>
                                <input type="text" name="gold_1" id="gold_1" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Strip 1</label>
                                <input type="text" name="strip_1" id="strip_1" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>ACC 1</label>
                                <input type="text" name="acc_1" id="acc_1" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>ACC 1A</label>
                                <input type="text" name="acc_1a" id="acc_1a" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Tube 1</label>
                                <input type="text" name="tube_1" id="tube_1" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Mark 1</label>
                                <input type="text" name="mark_1" id="mark_1" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Remark 1</label>
                                <textarea name="remark_1" id="remark_1" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Terminal 2</h6>
                            
                            <div class="form-group">
                                <label>Terminal 2</label>
                                <input type="text" name="terminal_2" id="terminal_2" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Note 2</label>
                                <input type="text" name="note_2" id="note_2" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Gold 2</label>
                                <input type="text" name="gold_2" id="gold_2" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Strip 2</label>
                                <input type="text" name="strip_2" id="strip_2" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>ACC 2</label>
                                <input type="text" name="acc_2" id="acc_2" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>ACC 2A</label>
                                <input type="text" name="acc_2a" id="acc_2a" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Tube 2</label>
                                <input type="text" name="tube_2" id="tube_2" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Mark 2</label>
                                <input type="text" name="mark_2" id="mark_2" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Remark 2</label>
                                <textarea name="remark_2" id="remark_2" class="form-control" rows="2"></textarea>
                            </div>

                            <hr>
                            <h6 class="font-weight-bold">T Fields</h6>
                            
                            <div class="form-group">
                                <label>TA</label>
                                <input type="text" name="ta" id="ta" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>TB</label>
                                <input type="text" name="tb" id="tb" class="form-control">
                            </div>

                            @for($i = 1; $i <= 6; $i++)
                            <div class="form-group">
                                <label>T{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</label>
                                <input type="text" name="t{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" id="t{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" class="form-control">
                            </div>
                            @endfor

                            <!-- Image Upload -->
                            <div class="form-group">
                                <label>Image</label>
                                <input type="file" name="image" id="imageInput" class="form-control" accept="image/*">
                                <div class="mt-2" id="imagePreviewContainer" style="display: none;">
                                    <img id="imagePreview" src="" alt="Preview" class="img-thumbnail" style="max-width: 100%; max-height: 200px;">
                                </div>
                            </div>

                            <!-- Assy List -->
                            <div class="form-group">
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
