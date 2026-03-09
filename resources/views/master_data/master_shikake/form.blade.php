@extends('layouts.master')

@section('title', isset($shikake) ? 'Edit Shikake' : 'Create Shikake')

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
                    <h3 class="card-title">{{ isset($shikake) ? 'Edit Shikake' : 'Create New Shikake' }}</h3>
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
                            <div class="mb-2">
                                <label class="form-label small mb-0">Conveyor <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm bg-light" value="{{ $shikake->getRelation('conveyor') ? $shikake->getRelation('conveyor')->conveyor : $shikake->conveyor }}" readonly>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-0">Carline <span class="text-danger">*</span></label>
                                <input type="text" name="carline" class="form-control form-control-sm" value="{{ $shikake->carline ?? '' }}" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-0">Shikake No</label>
                                <input type="text" class="form-control form-control-sm bg-light" value="{{ $shikake->shikake_no ?? '' }}" readonly>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-0">Process</label>
                                <select name="process" class="form-select form-select-sm">
                                    <option value="">- Choose Process -</option>
                                    @foreach($processTypes as $processType)
                                        <option value="{{ $processType->value }}" {{ isset($shikake) && $shikake->process == $processType->value ? 'selected' : '' }}>
                                            {{ $processType->value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-0">Family</label>
                                <input type="text" name="family" class="form-control form-control-sm" value="{{ $shikake->family ?? '' }}">
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">QTY</label>
                                        <input type="number" name="qty" class="form-control form-control-sm" value="{{ $shikake->qty ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Machine</label>
                                        <input type="text" name="machine" class="form-control form-control-sm" value="{{ $shikake->machine ?? '' }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Sequence</label>
                                        <input type="number" name="sequence" class="form-control form-control-sm" value="{{ $shikake->sequence ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Store</label>
                                        <input type="text" name="store" class="form-control form-control-sm" value="{{ $shikake->store ?? '' }}">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-0">Released Note</label>
                                <textarea name="released_note" class="form-control form-control-sm" rows="2">{{ $shikake->released_note ?? '' }}</textarea>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Barcode Mesin</label>
                                        <input type="text" name="barcode_mesin" class="form-control form-control-sm" value="{{ $shikake->barcode_mesin ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Address</label>
                                        <input type="text" name="address" class="form-control form-control-sm" value="{{ $shikake->address ?? '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label small mb-0">Barcode Process</label>
                                <input type="text" name="barcode_proses" class="form-control form-control-sm bg-light" value="{{ $shikake->barcode_proses ?? '' }}" readonly>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-0">Barcode Navigasi</label>
                                <input type="text" name="barcode_navigasi" class="form-control form-control-sm" value="{{ $shikake->barcode_navigasi ?? '' }}">
                            </div>
                            <div class="row g-2">
                                <div class="col-4">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Dies</label>
                                        <input type="text" name="dies" class="form-control form-control-sm" value="{{ $shikake->dies ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Jml. Kombinasi</label>
                                        <input type="number" name="jumlah_kombinasi" class="form-control form-control-sm" value="{{ $shikake->jumlah_kombinasi ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Joint</label>
                                        <input type="text" name="joint" class="form-control form-control-sm" value="{{ $shikake->joint ?? '' }}">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-0">Blade</label>
                                <input type="text" name="blade" class="form-control form-control-sm" value="{{ $shikake->blade ?? '' }}">
                            </div>

                            <!-- Drawing Upload -->
                            <div class="border rounded p-2 mt-2 mb-2">
                                <label class="form-label small mb-1 fw-semibold text-primary">Drawing / Image</label>
                                <input type="file" name="image" id="imageInput" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                <div class="form-text text-muted">Format: JPG, PNG, WEBP. Maks: 5MB</div>
                                @if(isset($shikake) && $shikake->image_path)
                                    <div class="text-center mt-2" id="imagePreviewContainer">
                                        <img id="imagePreview" src="{{ asset($shikake->image_path) }}" alt="Drawing" class="img-fluid rounded border" style="max-height: 180px; cursor:pointer;" onclick="window.open(this.src,'_blank')">
                                    </div>
                                @else
                                    <div class="text-center mt-2" id="imagePreviewContainer" style="display: none;">
                                        <img id="imagePreview" src="" alt="Preview" class="img-fluid rounded border" style="max-height: 180px;">
                                    </div>
                                @endif
                            </div>

                            <!-- Assy List -->
                            @if(isset($shikake))
                            <div class="border rounded p-2 mb-2">
                                <label class="form-label small mb-1 fw-semibold text-primary d-block">Assy List</label>
                                <div style="max-height: 100px; overflow-y: auto;">
                                    @if($shikake->assemblies && $shikake->assemblies->count() > 0)
                                        <span class="small">{{ $shikake->assemblies->pluck('assy')->implode(', ') }}</span>
                                    @else
                                        <p class="text-muted mb-0 small">No assemblies found</p>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- CCT & Address Pairs -->
                    <div class="row g-2 mt-1">
                        <div class="col-12">
                            <div class="border rounded p-2">
                                <h6 class="fw-semibold small mb-2 text-primary">CCT & Address Pairs</h6>
                                <div class="row g-2">
                                    @foreach([['A', 'cct_a', 'address_a'], ['B', 'cct_b', 'address_b'], ['C', 'cct_c', 'address_c'], ['4', 'cct_4', 'address_4'], ['5', 'cct_5', 'address_5'], ['6', 'cct_6', 'address_6'], ['7', 'cct_7', 'address_7']] as $pair)
                                    <div class="col-md-3">
                                        <div class="row g-1">
                                            <div class="col-6">
                                                <label class="form-label small mb-0">CCT {{ $pair[0] }}</label>
                                                <input type="text" name="{{ $pair[1] }}" class="form-control form-control-sm" value="{{ $shikake->{$pair[1]} ?? '' }}">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small mb-0">Addr {{ $pair[0] }}</label>
                                                <input type="text" name="{{ $pair[2] }}" class="form-control form-control-sm" value="{{ $shikake->{$pair[2]} ?? '' }}">
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- T Fields -->
                    <div class="row g-2 mt-1">
                        <div class="col-12">
                            <div class="border rounded p-2">
                                <h6 class="fw-semibold small mb-2 text-primary">T Fields</h6>
                                <div class="row g-2">
                                    @for($i = 1; $i <= 9; $i++)
                                    <div class="col">
                                        <label class="form-label small mb-0">T{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</label>
                                        <input type="text" name="t{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" class="form-control form-control-sm" value="{{ $shikake->{'t' . str_pad($i, 2, '0', STR_PAD_LEFT)} ?? '' }}">
                                    </div>
                                    @endfor
                                </div>
                            </div>
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
