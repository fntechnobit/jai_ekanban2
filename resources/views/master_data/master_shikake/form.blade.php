@extends('layouts.master')

@section('title', isset($shikake) ? 'Detail Shikake' : 'Create Shikake')

@section('breadcrumb')
    <x-page-header menu-code="master_shikake" />
@endsection

@section('content')
    <div class="container-fluid">
            <form id="shikakeForm" enctype="multipart/form-data">
                @csrf
                @if(isset($shikake))
                    @method('PUT')
                @endif
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ isset($shikake) ? 'Detail shikake' : 'Create New Shikake' }}</h3>
                        <div class="card-tools">
                            <a href="{{ route('master-data.master-shikake.index') }}" class="btn btn-secondary btn-sm">
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
                                    <input type="text" class="form-control form-control-sm" value="{{ $shikake->getRelation('conveyor') ? $shikake->getRelation('conveyor')->conveyor : $shikake->conveyor }}" readonly>
                                </div>

                                <div class="mb-3">
                                    <label>CCT Code</label>
                                    <input type="text" name="cct_code" class="form-control form-control-sm" value="{{ $shikake->shikake_no ?? '' }}" readonly>
                                </div>

                                <div class="mb-3">
                                    <label>Barcode</label>
                                    <input type="text" name="barcode_kanban" class="form-control form-control-sm" value="{{ $shikake->barcode_kanban ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Family</label>
                                    <input type="text" name="family" class="form-control form-control-sm" value="{{ $shikake->family ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Process</label>
                                    <input type="text" name="process" class="form-control form-control-sm" value="{{ $shikake->process ?? '' }}" readonly>
                                </div>

                                <div class="mb-3">
                                    <label>Barcode Process</label>
                                    <input type="text" name="barcode_proses" class="form-control form-control-sm" value="{{ $shikake->barcode_proses ?? '' }}" readonly>
                                </div>

                                <div class="mb-3">
                                    <label>QTY</label>
                                    <input type="number" name="qty" class="form-control form-control-sm" value="{{ $shikake->qty ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Issue</label>
                                    <input type="text" name="issue" class="form-control form-control-sm" value="{{ $shikake->issue ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Machine</label>
                                    <input type="text" name="machine" class="form-control form-control-sm" value="{{ $shikake->machine ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Sequence</label>
                                    <input type="number" name="sequence" class="form-control form-control-sm" value="{{ $shikake->sequence ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Released Date</label>
                                    <input type="date" name="released_date" class="form-control form-control-sm" value="{{ $shikake->released_date ? $shikake->released_date->format('Y-m-d') : '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Released Note</label>
                                    <textarea name="released_note" class="form-control form-control-sm" rows="3">{{ $shikake->released_note ?? '' }}</textarea>
                                </div>

                                <div class="mb-3">
                                    <label>Store</label>
                                    <input type="text" name="store" class="form-control form-control-sm" value="{{ $shikake->store ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Barcode Mesin</label>
                                    <input type="text" name="barcode_mesin" class="form-control form-control-sm" value="{{ $shikake->barcode_mesin ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Address</label>
                                    <input type="text" name="address" class="form-control form-control-sm" value="{{ $shikake->address ?? '' }}">
                                </div>

                                <!-- CCT Fields -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label>CCT A</label>
                                            <input type="text" name="cct_a" class="form-control form-control-sm" value="{{ $shikake->cct_a ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label>Address A</label>
                                            <input type="text" name="address_a" class="form-control form-control-sm" value="{{ $shikake->address_a ?? '' }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label>CCT B</label>
                                            <input type="text" name="cct_b" class="form-control form-control-sm" value="{{ $shikake->cct_b ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label>Address B</label>
                                            <input type="text" name="address_b" class="form-control form-control-sm" value="{{ $shikake->address_b ?? '' }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label>CCT C</label>
                                            <input type="text" name="cct_c" class="form-control form-control-sm" value="{{ $shikake->cct_c ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label>Address C</label>
                                            <input type="text" name="address_c" class="form-control form-control-sm" value="{{ $shikake->address_c ?? '' }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label>CCT 4</label>
                                            <input type="text" name="cct_4" class="form-control form-control-sm" value="{{ $shikake->cct_4 ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label>Address 4</label>
                                            <input type="text" name="address_4" class="form-control form-control-sm" value="{{ $shikake->address_4 ?? '' }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label>CCT 5</label>
                                            <input type="text" name="cct_5" class="form-control form-control-sm" value="{{ $shikake->cct_5 ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label>Address 5</label>
                                            <input type="text" name="address_5" class="form-control form-control-sm" value="{{ $shikake->address_5 ?? '' }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label>CCT 6</label>
                                            <input type="text" name="cct_6" class="form-control form-control-sm" value="{{ $shikake->cct_6 ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label>Address 6</label>
                                            <input type="text" name="address_6" class="form-control form-control-sm" value="{{ $shikake->address_6 ?? '' }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label>CCT 7</label>
                                            <input type="text" name="cct_7" class="form-control form-control-sm" value="{{ $shikake->cct_7 ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label>Address 7</label>
                                            <input type="text" name="address_7" class="form-control form-control-sm" value="{{ $shikake->address_7 ?? '' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Barcode Navigasi</label>
                                    <input type="text" name="barcode_navigasi" class="form-control form-control-sm" value="{{ $shikake->barcode_navigasi ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Dies</label>
                                    <input type="text" name="dies" class="form-control form-control-sm" value="{{ $shikake->dies ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Jumlah Kombinasi</label>
                                    <input type="number" name="jumlah_kombinasi" class="form-control form-control-sm" value="{{ $shikake->jumlah_kombinasi ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label>Blade</label>
                                    <input type="text" name="blade" class="form-control form-control-sm" value="{{ $shikake->blade ?? '' }}">
                                </div>

                                <!-- T Fields -->
                                @for($i = 1; $i <= 9; $i++)
                                <div class="mb-3">
                                    <label>T{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</label>
                                    <input type="text" name="t{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" class="form-control form-control-sm" value="{{ $shikake->{'t' . str_pad($i, 2, '0', STR_PAD_LEFT)} ?? '' }}">
                                </div>
                                @endfor

                                <div class="mb-3">
                                    <label>Joint</label>
                                    <input type="text" name="joint" class="form-control form-control-sm" value="{{ $shikake->joint ?? '' }}">
                                </div>

                                <!-- Image Upload -->
                                <div class="mb-3">
                                    <label>Image</label>
                                    <input type="file" name="image" id="imageInput" class="form-control form-control-sm" accept="image/*">
                                    @if(isset($shikake) && $shikake->image_path)
                                        <div class="mt-2">
                                            <img id="imagePreview" src="{{ asset($shikake->image_path) }}" alt="Shikake Image" class="img-thumbnail" style="max-width: 300px;">
                                        </div>
                                    @else
                                        <div class="mt-2" id="imagePreviewContainer" style="display: none;">
                                            <img id="imagePreview" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px;">
                                        </div>
                                    @endif
                                </div>

                                <!-- Assy List -->
                                @if(isset($shikake))
                                <div class="mb-3">
                                    <label class="d-block">Assy List</label>
                                    <div class="card">
                                        <div class="card-body">
                                            @if($shikake->assemblies && $shikake->assemblies->count() > 0)
                                                <div style="max-height: 200px; overflow-y: auto;">
                                                    {{ $shikake->assemblies->pluck('assy')->implode(', ') }}
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
                        <button type="button" class="btn btn-secondary btn-sm" onclick="window.location='{{ route('master-data.master-shikake.index') }}'">
                            <i class="fa-solid fa-xmark"></i> Cancel
                        </button>
                        @if(isset($shikake))
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-floppy-disk"></i> Update
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
            $('#shikakeForm').submit(function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const url = @if(isset($shikake))
                    "{{ route('master-data.master-shikake.update', $shikake->id) }}"
                @else
                    "{{ route('master-data.master-shikake.store') }}"
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
                            window.location.href = "{{ route('master-data.master-shikake.index') }}";
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
