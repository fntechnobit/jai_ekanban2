@extends('layouts.master')

@section('title', isset($circuit) ? 'Detail Circuit' : 'Create Circuit')

@section('breadcrumb')
    <x-page-header menu-code="master_circuit" />
@endsection

@section('content')
    <div class="container-fluid">
            <form id="circuitForm" enctype="multipart/form-data">
                @csrf
                @if(isset($circuit))
                    @method('PUT')
                @endif
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ isset($circuit) ? 'Detail Circuit' : 'Create New Circuit' }}</h3>
                        <div class="card-tools">
                            <a href="{{ route('master-data.master-circuit.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Conveyor <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" value="{{ $circuit->getRelation('conveyor') ? $circuit->getRelation('conveyor')->conveyor : $circuit->conveyor }}" readonly>
                                </div>

                                <div class="mb-3">
                                    <label>Carline <span class="text-danger">*</span></label>
                                    <input type="text" name="carline" class="form-control form-control-sm" value="{{ $circuit->carline ?? '' }}" required>
                                </div>

                                <div class="mb-3">
                                    <label>CCT No</label>
                                    <input type="text" name="cct_no" class="form-control form-control-sm" value="{{ $circuit->cct_no ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Family</label>
                                    <input type="text" name="family" class="form-control form-control-sm" value="{{ $circuit->family ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>QTY</label>
                                    <input type="number" name="qty" class="form-control form-control-sm" value="{{ $circuit->qty ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Machine Sequence</label>
                                    <input type="text" name="machine_sequence" class="form-control form-control-sm" value="{{ $circuit->machine_sequence ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Released Note</label>
                                    <textarea name="released_note" class="form-control form-control-sm" rows="2">{{ $circuit->released_note ?? '' }}</textarea>
                                </div>

                                <div class="mb-3">
                                    <label>Kanban</label>
                                    <input type="text" name="kanban" class="form-control form-control-sm" value="{{ $circuit->kanban ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Customer No</label>
                                    <input type="text" name="cust_no" class="form-control form-control-sm" value="{{ $circuit->cust_no ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Barcode Mesin</label>
                                    <input type="text" name="barcode_mesin" class="form-control form-control-sm" value="{{ $circuit->barcode_mesin ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Address</label>
                                    <input type="text" name="address" class="form-control form-control-sm" value="{{ $circuit->address ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>CCT Code</label>
                                    <input type="text" name="cct_code" class="form-control form-control-sm" value="{{ $circuit->cct_code ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Kind</label>
                                    <input type="text" name="kind" class="form-control form-control-sm" value="{{ $circuit->kind ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Size</label>
                                    <input type="text" name="size" class="form-control form-control-sm" value="{{ $circuit->size ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Col</label>
                                    <input type="text" name="col" class="form-control form-control-sm" value="{{ $circuit->col ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>CL</label>
                                    <input type="text" name="cl" class="form-control form-control-sm" value="{{ $circuit->cl ?? '' }}">
                                </div>

                                <!-- Terminal 1 Section -->
                                <h5 class="mt-3">Terminal 1</h5>
                                <div class="mb-3">
                                    <label>Terminal 1</label>
                                    <input type="text" name="terminal_1" class="form-control form-control-sm" value="{{ $circuit->terminal_1 ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Note 1</label>
                                    <input type="text" name="note_1" class="form-control form-control-sm" value="{{ $circuit->note_1 ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Gold 1</label>
                                    <input type="text" name="gold_1" class="form-control form-control-sm" value="{{ $circuit->gold_1 ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Strip 1</label>
                                    <input type="text" name="strip_1" class="form-control form-control-sm" value="{{ $circuit->strip_1 ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>ACC 1</label>
                                    <input type="text" name="acc_1" class="form-control form-control-sm" value="{{ $circuit->acc_1 ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>ACC 1A</label>
                                    <input type="text" name="acc_1a" class="form-control form-control-sm" value="{{ $circuit->acc_1a ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Tube 1</label>
                                    <input type="text" name="tube_1" class="form-control form-control-sm" value="{{ $circuit->tube_1 ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Mark 1</label>
                                    <input type="text" name="mark_1" class="form-control form-control-sm" value="{{ $circuit->mark_1 ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Remark 1</label>
                                    <textarea name="remark_1" class="form-control form-control-sm" rows="2">{{ $circuit->remark_1 ?? '' }}</textarea>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="col-md-6">
                                <!-- Terminal 2 Section -->
                                <h5>Terminal 2</h5>
                                <div class="mb-3">
                                    <label>Terminal 2</label>
                                    <input type="text" name="terminal_2" class="form-control form-control-sm" value="{{ $circuit->terminal_2 ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Note 2</label>
                                    <input type="text" name="note_2" class="form-control form-control-sm" value="{{ $circuit->note_2 ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Gold 2</label>
                                    <input type="text" name="gold_2" class="form-control form-control-sm" value="{{ $circuit->gold_2 ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Strip 2</label>
                                    <input type="text" name="strip_2" class="form-control form-control-sm" value="{{ $circuit->strip_2 ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>ACC 2</label>
                                    <input type="text" name="acc_2" class="form-control form-control-sm" value="{{ $circuit->acc_2 ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>ACC 2A</label>
                                    <input type="text" name="acc_2a" class="form-control form-control-sm" value="{{ $circuit->acc_2a ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Tube 2</label>
                                    <input type="text" name="tube_2" class="form-control form-control-sm" value="{{ $circuit->tube_2 ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Mark 2</label>
                                    <input type="text" name="mark_2" class="form-control form-control-sm" value="{{ $circuit->mark_2 ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Remark 2</label>
                                    <textarea name="remark_2" class="form-control form-control-sm" rows="2">{{ $circuit->remark_2 ?? '' }}</textarea>
                                </div>

                                <!-- T Fields -->
                                <h5 class="mt-3">T Fields</h5>
                                <div class="mb-3">
                                    <label>TA</label>
                                    <input type="text" name="ta" class="form-control form-control-sm" value="{{ $circuit->ta ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>TB</label>
                                    <input type="text" name="tb" class="form-control form-control-sm" value="{{ $circuit->tb ?? '' }}">
                                </div>

                                @for($i = 1; $i <= 6; $i++)
                                <div class="mb-3">
                                    <label>T{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</label>
                                    <input type="text" name="t{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" class="form-control form-control-sm" value="{{ $circuit->{'t' . str_pad($i, 2, '0', STR_PAD_LEFT)} ?? '' }}">
                                </div>
                                @endfor

                                <!-- Image Upload -->
                                <div class="mb-3">
                                    <label>Image</label>
                                    <input type="file" name="image" id="imageInput" class="form-control form-control-sm" accept="image/*">
                                    @if(isset($circuit) && $circuit->image_path)
                                        <div class="mt-2">
                                            <img id="imagePreview" src="{{ asset($circuit->image_path) }}" alt="Circuit Image" class="img-thumbnail" style="max-width: 300px;">
                                        </div>
                                    @else
                                        <div class="mt-2" id="imagePreviewContainer" style="display: none;">
                                            <img id="imagePreview" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px;">
                                        </div>
                                    @endif
                                </div>

                                <!-- Assy List -->
                                @if(isset($circuit))
                                <div class="mb-3">
                                    <label class="d-block">Assy List</label>
                                    <div class="card">
                                        <div class="card-body">
                                            @if($circuit->assemblies && $circuit->assemblies->count() > 0)
                                                <div style="max-height: 200px; overflow-y: auto;">
                                                    {{ $circuit->assemblies->pluck('assy')->implode(', ') }}
                                                </div>
                                            @else
                                                <p class="text-muted mb-0">No assemblies found</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="window.location='{{ route('master-data.master-circuit.index') }}'">
                            <i class="fa-solid fa-xmark"></i> Cancel
                        </button>
                        @if(isset($circuit))
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-floppy-disk"></i> Submit
                            </button>
                        @else
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fa-solid fa-floppy-disk"></i> Submit
                            </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Image preview
            $('#imageInput').change(function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#imagePreview').attr('src', e.target.result);
                        $('#imagePreviewContainer').show();
                    }
                    reader.readAsDataURL(file);
                }
            });

            // Form submission
            $('#circuitForm').submit(function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const url = @if(isset($circuit))
                    "{{ route('master-data.master-circuit.update', $circuit->id) }}"
                @else
                    "{{ route('master-data.master-circuit.store') }}"
                @endif;

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = "{{ route('master-data.master-circuit.index') }}";
                        });
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage
                        });
                    }
                });
            });
        });
    </script>
@endsection
