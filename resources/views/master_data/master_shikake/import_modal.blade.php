<!-- Import Modal -->
<div class="modal fade" id="importShikakeModal" tabindex="-1" role="dialog" aria-labelledby="importShikakeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importShikakeModalLabel">Import Data shikake</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="importShikakeForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group row">
                        <label for="import_area_id" class="col-sm-3 col-form-label">Area :</label>
                        <div class="col-sm-9">
                            <select class="form-control select2" id="import_area_id" name="area_id" style="width: 100%;">
                                <option value="">- All Area -</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}">{{ $area->area }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-danger import_area_id_error"></small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="import_conveyor_id" class="col-sm-3 col-form-label">Conveyor :</label>
                        <div class="col-sm-9">
                            <select class="form-control select2" id="import_conveyor_id" name="conveyor_id" style="width: 100%;">
                                <option value="">- Choose Conveyor -</option>
                                @foreach($conveyors as $conveyor)
                                    <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-danger import_conveyor_id_error"></small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="import_file" class="col-sm-3 col-form-label">File Import :</label>
                        <div class="col-sm-9">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="import_file" name="file" accept=".xlsx,.xls">
                                <label class="custom-file-label" for="import_file">Browse File</label>
                            </div>
                            <small class="form-text text-danger import_file_error"></small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="rows_start" class="col-sm-3 col-form-label">Rows Start :</label>
                        <div class="col-sm-4">
                            <input type="number" class="form-control" id="rows_start" name="rows_start" value="2" min="1">
                            <small class="form-text text-danger rows_start_error"></small>
                        </div>
                        <div class="col-sm-5">
                            <small class="form-text text-danger" style="font-style: italic;">*Rows number on excel files</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="btn-download-template">
                        <i class="fas fa-file-excel"></i> Excel Template
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize Select2 for import modal
        $('#import_area_id, #import_conveyor_id').select2({
            theme: 'bootstrap4',
            dropdownParent: $('#importShikakeModal'),
            allowClear: true
        });

        // Update file input label
        $('#import_file').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName || 'Browse File');
        });

        // Download Template
        $('#btn-download-template').click(function() {
            window.location.href = "{{ route('master-data.master-shikake.download-template') }}";
        });

        // Submit Import Form
        $('#importShikakeForm').submit(function(e) {
            e.preventDefault();
            $('.error-text, .text-danger').text('');

            var formData = new FormData(this);

            $.ajax({
                url: "{{ route('master-data.master-shikake.import') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#importShikakeModal').modal('hide');
                    $('#importShikakeForm')[0].reset();
                    $('.custom-file-label').html('Browse File');
                    $('#master-shikake-table').DataTable().ajax.reload();
                    Swal.fire('Success!', response.message, 'success');
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            var errorKey = key.replace('.', '_');
                            $('.import_' + errorKey + '_error').text(value[0]);
                        });
                    } else {
                        Swal.fire('Error!', xhr.responseJSON.message || 'Something went wrong', 'error');
                    }
                }
            });
        });
    });
</script>
