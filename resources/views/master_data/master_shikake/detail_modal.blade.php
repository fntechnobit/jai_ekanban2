<!-- Detail Shikake Modal -->
<div class="modal fade" id="detailShikakeModal" tabindex="-1" >
    <div class="modal-dialog modal-xl" >
        <div class="modal-content">
            <form id="shikakeDetailForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="shikake_id" name="id">
                
                <div class="modal-header bg-info">
                    <h5 class="modal-title">Detail shikake</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        
                    </button>
                </div>
                
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Conveyor</label>
                                <input type="text" id="conveyor" class="form-control form-control-sm" readonly>
                            </div>

                            <div class="mb-3">
                                <label>CCT Code</label>
                                <input type="text" id="shikake_no" class="form-control form-control-sm" readonly>
                            </div>

                            <div class="mb-3">
                                <label>Barcode</label>
                                <input type="text" name="barcode_kanban" id="barcode_kanban" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Family</label>
                                <input type="text" name="family" id="family" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Process</label>
                                <select name="process" id="process" class="form-select form-control-sm">
                                    <option value="">- Choose Process -</option>
                                    @foreach($processTypes as $processType)
                                        <option value="{{ $processType->value }}">{{ $processType->value }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label>QTY</label>
                                <input type="number" name="qty" id="qty" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Issue</label>
                                <input type="text" name="issue" id="issue" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Machine</label>
                                <input type="text" name="machine" id="machine" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Sequence</label>
                                <input type="number" name="sequence" id="sequence" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Released Date</label>
                                <input type="date" name="released_date" id="released_date" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Released Note</label>
                                <textarea name="released_note" id="released_note" class="form-control form-control-sm" rows="2"></textarea>
                            </div>

                            <div class="mb-3">
                                <label>Store</label>
                                <input type="text" name="store" id="store" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Barcode Mesin</label>
                                <input type="text" name="barcode_mesin" id="barcode_mesin" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Address</label>
                                <input type="text" name="address" id="address" class="form-control form-control-sm">
                            </div>

                            <!-- CCT Fields in compact layout -->
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="small">CCT A</label>
                                        <input type="text" name="cct_a" id="cct_a" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="small">Address A</label>
                                        <input type="text" name="address_a" id="address_a" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="small">CCT B</label>
                                        <input type="text" name="cct_b" id="cct_b" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="small">Address B</label>
                                        <input type="text" name="address_b" id="address_b" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="small">CCT C</label>
                                        <input type="text" name="cct_c" id="cct_c" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="small">Address C</label>
                                        <input type="text" name="address_c" id="address_c" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="small">CCT 4</label>
                                        <input type="text" name="cct_4" id="cct_4" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="small">Address 4</label>
                                        <input type="text" name="address_4" id="address_4" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="small">CCT 5</label>
                                        <input type="text" name="cct_5" id="cct_5" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="small">Address 5</label>
                                        <input type="text" name="address_5" id="address_5" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="small">CCT 6</label>
                                        <input type="text" name="cct_6" id="cct_6" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="small">Address 6</label>
                                        <input type="text" name="address_6" id="address_6" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="small">CCT 7</label>
                                        <input type="text" name="cct_7" id="cct_7" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="small">Address 7</label>
                                        <input type="text" name="address_7" id="address_7" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Barcode Navigasi</label>
                                <input type="text" name="barcode_navigasi" id="barcode_navigasi" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Dies</label>
                                <input type="text" name="dies" id="dies" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Jumlah Kombinasi</label>
                                <input type="number" name="jumlah_kombinasi" id="jumlah_kombinasi" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label>Blade</label>
                                <input type="text" name="blade" id="blade" class="form-control form-control-sm">
                            </div>

                            <!-- T Fields in 3 columns -->
                            <div class="row">
                                @for($i = 1; $i <= 9; $i++)
                                <div class="col-4">
                                    <div class="mb-3">
                                        <label class="small">T{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</label>
                                        <input type="text" name="t{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" id="t{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" class="form-control form-control-sm">
                                    </div>
                                </div>
                                @endfor
                            </div>

                            <div class="mb-3">
                                <label>Joint</label>
                                <input type="text" name="joint" id="joint" class="form-control form-control-sm">
                            </div>

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
