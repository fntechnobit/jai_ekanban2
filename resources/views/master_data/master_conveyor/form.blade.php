<!-- Modal -->
<div class="modal fade" id="masterConveyorModal" tabindex="-1"  aria-labelledby="masterConveyorModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" >
        <div class="modal-content">
            <form id="masterConveyorForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="masterConveyorModalLabel">Edit Conveyor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="conveyor_id" name="conveyor_id">

                    <div class="mb-3">
                        <label for="master_area_id">Area</label>
                        <select class="form-select form-select-sm" id="master_area_id" name="master_area_id" style="width: 100%;">
                            <option value="">Select Area</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->area }}</option>
                            @endforeach
                        </select>
                        <span class="text-danger error-text master_area_id_error"></span>
                    </div>

                    <div class="mb-3">
                        <label for="conveyor">Conveyor</label>
                        <input type="text" class="form-control form-control-sm" id="conveyor" readonly disabled>
                        <small class="text-muted">Nama berasal dari SIREP dan diperbarui otomatis saat sinkronisasi.</small>
                    </div>

                    <div class="mb-3">
                        <label for="family_ids">Family</label>
                        <select class="form-select form-select-sm" id="family_ids" name="family_ids[]" multiple style="width: 100%;">
                            @foreach($families as $family)
                                <option value="{{ $family->id }}">{{ $family->family }}</option>
                            @endforeach
                        </select>
                        <span class="text-danger error-text family_ids_error"></span>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sirep_conveyor_code">Kode Conveyor SIREP</label>
                                <input type="text" class="form-control form-control-sm" id="sirep_conveyor_code"
                                       name="sirep_conveyor_code" placeholder="Kosongkan bila sama dengan nama conveyor">
                                <small class="text-muted">
                                    Diisi bila nama conveyor di SIREP berbeda dengan nama di sini.
                                    Nilai inilah yang dicocokkan saat sinkronisasi kapasitas.
                                </small>
                                <span class="text-danger error-text sirep_conveyor_code_error"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pallet_qty">Pallet Qty.</label>
                                <input type="number" class="form-control form-control-sm" id="pallet_qty" name="pallet_qty" min="1">
                                <span class="text-danger error-text pallet_qty_error"></span>
                            </div>
                        </div>
                    </div>

                    <div class="cv-status-row">
                        <span class="cv-status-lab">Status</span>
                        <span id="status_display" class="cv-status cv-status-on">Aktif</span>
                    </div>

                    <div class="cv-panel">
                        <div class="cv-panel-grid">
                            <div>
                                <span class="cv-panel-lab">Kapasitas / shift &mdash; dari SIREP</span>
                                <span class="cv-panel-val" id="capacity_display">&mdash;</span>
                            </div>
                            <div class="cv-panel-right">
                                <span class="cv-panel-lab">Sinkron terakhir</span>
                                <span class="cv-panel-val cv-panel-val-sm" id="capacity_synced_display">&mdash;</span>
                            </div>
                        </div>
                        <p class="cv-panel-note">
                            Kapasitas dan jumlah shift tidak diisi di sini. Kapasitas ditarik dari API SIREP
                            lewat tombol <strong>Sync Conveyor SIREP</strong>, dan jumlah shift dihitung per
                            tanggal dari qty listing serta penanda lembur SIREP.
                        </p>
                    </div>

                    <style>
                        /* Semua warna ditulis eksplisit — panel ini sebelumnya memakai
                           .alert-secondary yang di tema ini berlatar ungu pekat sehingga
                           teksnya nyaris tidak terbaca. */
                        .cv-status-row{
                            display:flex; align-items:center; gap:12px;
                            padding:10px 0 14px; border-bottom:1px solid #E3E9EF; margin-bottom:14px;
                        }
                        .cv-status-lab{
                            font-size:.72rem; font-weight:700; letter-spacing:.08em;
                            text-transform:uppercase; color:#6B7C8C;
                        }
                        .cv-status{ font-size:.84rem; font-weight:600; padding:3px 12px; border-radius:999px; }
                        .cv-status-on{ background:#E3F5EA; color:#1B6B42; border:1px solid #B7E0C7; }
                        .cv-status-off{ background:#F0F2F4; color:#5A6874; border:1px solid #D5DCE3; }

                        .cv-panel{
                            background:#F5F8FA; border:1px solid #D3DFE8;
                            border-left:4px solid #2C6FA8; border-radius:6px; padding:14px 16px;
                        }
                        .cv-panel-grid{
                            display:flex; justify-content:space-between; align-items:flex-start;
                            gap:16px; flex-wrap:wrap;
                        }
                        .cv-panel-right{ text-align:right; }
                        .cv-panel-lab{
                            display:block; font-size:.7rem; font-weight:700; letter-spacing:.07em;
                            text-transform:uppercase; color:#6B7C8C; margin-bottom:3px;
                        }
                        .cv-panel-val{
                            display:block; font-size:1.15rem; font-weight:700; color:#12283D;
                            font-variant-numeric:tabular-nums;
                        }
                        .cv-panel-val-sm{ font-size:.92rem; font-weight:600; color:#33475B; }
                        .cv-panel-note{
                            margin:12px 0 0; padding-top:11px; border-top:1px solid #DFE7ED;
                            font-size:.8rem; line-height:1.6; color:#42576B;
                        }
                        .cv-panel-note strong{ color:#12283D; }
                    </style>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-save">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
