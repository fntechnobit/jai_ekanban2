@extends('layouts.master')

@section('title', 'Shikake - Print Per Machine')

@section('breadcrumb')
    <x-page-header menu-code="ekanban_shikake" />
@endsection

@section('content')
    <style>
        /* Match select2 height with button and datepicker - Smaller size like assy schedule */
        .card-body .select2-container--bootstrap-5 .select2-selection--single,
        .card-body .select2-container--bootstrap-5 .select2-selection {
            height: 31px !important;
            min-height: 31px !important;
            padding: 0.25rem 0.5rem !important;
        }
        .card-body .select2-container--bootstrap-5 .select2-selection__rendered {
            line-height: 1.5 !important;
            padding-left: 0 !important;
            padding-top: 0 !important;
        }
        .card-body .select2-container--bootstrap-5 .select2-selection__arrow {
            height: 29px !important;
        }
        /* Datepicker text align right */
        #filter_date {
            text-align: right;
        }
    </style>
    <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fa-solid fa-table"></i> Shikake
                    </h3>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-success mr-1" id="btn-connect">
                            <i class="fa-solid fa-plug"></i> Connect
                        </button>
                        <button class="btn btn-sm btn-warning mr-2" id="btn-disconnect">
                            <i class="fa-solid fa-unlink"></i> Disconnect
                        </button>
                        <span id="qz-status" class="mr-2">QZ: Idle</span>
                        <button type="button" class="btn btn-info btn-sm" id="btn-refresh">
                            <i class="fa-solid fa-arrows-rotate"></i> Refresh
                        </button>
                        <select class="btn btn-sm" id="printer-select" style="max-width: 200px;" disabled>
                            <option>- Choose Printer -</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters - 4 columns x 2 rows -->
                    <form class="mb-3">
                        <div class="row g-2 mb-2">
                            <div class="col-md-3">
                                <select class="form-select form-select-sm select2" id="filter_area">
                                    <option value="">- All Area -</option>
                                    @foreach($areas as $area)
                                        <option value="{{ $area->id }}">{{ $area->area }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select form-select-sm select2" id="filter_machine" required>
                                    <option value="">- Choose Machine -</option>
                                    @foreach($machines as $machine)
                                        <option value="{{ $machine->machine }}">{{ $machine->machine }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select form-select-sm select2" id="filter_process">
                                    <option value="">- All Process -</option>
                                    @foreach($processTypes as $processType)
                                        <option value="{{ $processType->value }}">{{ $processType->value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select form-select-sm select2" id="filter_print_status">
                                    <option value="all">- All Print Status -</option>
                                    <option value="not_printed" selected>Waiting Print</option>
                                    <option value="printed">Printed</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <input type="text" class="form-control form-control-sm" id="filter_date" readonly placeholder="Select date">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select form-select-sm select2" id="filter_shift">
                                    <option value="">- All Shift -</option>
                                    <option value="1">Shift 1</option>
                                    <option value="2">Shift 2</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select form-select-sm select2" id="filter_cutoff">
                                    <option value="">- All Cut Off -</option>
                                    <option value="1">Cut Off 1</option>
                                    <option value="2">Cut Off 2</option>
                                    <option value="3">Cut Off 3</option>
                                    <option value="4">Cut Off 4</option>
                                    <option value="5">Cut Off 5</option>
                                </select>
                            </div>
                            <div class="col-md-3 text-end">
                                <button type="button" class="btn btn-secondary" id="btn-reset" title="Reset Filters" style="padding: 0.25rem 0.5rem; font-size: 0.875rem; height: 31px;">
                                    <i class="fa-solid fa-arrow-rotate-right"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table id="shikake-table" class="table table-bordered table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Code</th>
                                    <th>CV</th>
                                    <th>Family</th>
                                    <th>Qty</th>
                                    <th>Issue</th>
                                    <th>Seq</th>
                                    <th>Kanban</th>
                                    <th>CO</th>
                                    <th>#</th>
                                    <th width="12%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewModalLabel">Print Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body" id="previewContent" style="max-height: 70vh; overflow: auto;">
                    <div class="text-center">
                        <i class="fa-solid fa-spinner ti-spin" style="font-size: 3rem;"></i>
                        <p>Loading preview...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <!-- QZ Tray -->
    <script src="{{ asset('js/qz-tray.js') }}"></script>
    <script src="{{ asset('js/qz-print.js') }}"></script>
    <script src="{{ asset('js/crypto-js.min.js') }}"></script>
    <script src="{{ asset('js/html2canvas.min.js') }}"></script>
    
    <script>
        var qz = window.qz;
        var table;
        
        $(function () {
            // Initialize Select2
            $('.select2').select2({
                allowClear: true,
                theme: 'bootstrap-5'
            });

            // Force height after initialization
            setTimeout(function() {
                $('.select2-container--bootstrap-5 .select2-selection').css({
                    'height': '38px',
                    'min-height': '38px'
                });
            }, 100);

            // Initialize single date picker
            var currentDate = moment();

            $('#filter_date').daterangepicker({
                singleDatePicker: true,
                autoApply: true,
                startDate: currentDate,
                locale: {
                    format: 'DD-MM-YYYY'
                }
            });

            // DataTable - Don't load data initially
            table = $('#shikake-table').DataTable({
                processing: true,
                serverSide: true,
                deferLoading: 0, // Don't load data on initialization
                scrollX: true,
                scrollCollapse: true,
                fixedColumns: {
                    leftColumns: 3,  // Freeze: No, Process/Code, CV
                    rightColumns: 1   // Freeze: Actions
                },
                ajax: {
                    url: "{{ route('schedule.ekanban-shikake.print-machine') }}",
                    data: function(d) {
                        var date = $('#filter_date').data('daterangepicker');
                        d.area_id = $('#filter_area').val();
                        d.cutoff = $('#filter_cutoff').val();
                        d.machine = $('#filter_machine').val();
                        d.process = $('#filter_process').val();
                        d.date = date.startDate.format('YYYY-MM-DD');
                        d.shift = $('#filter_shift').val();
                        d.print_status = $('#filter_print_status').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '4%' },
                    { 
                        data: 'process', 
                        name: 'process', 
                        width: '16%',
                        render: function(data, type, row) {
                            var processMap = {
                                'TWIST': { label: 'TWS', class: 'bg-primary' },
                                'BONDER': { label: 'BDR', class: 'bg-success' },
                                'JOINT': { label: 'JNT', class: 'bg-info' },
                                'SHIELD': { label: 'SHL', class: 'bg-warning' },
                                'DBL CRIMP': { label: 'DBC', class: 'bg-secondary' }
                            };
                            var p = processMap[data] || { label: data, class: 'bg-dark' };
                            return '<span class="badge ' + p.class + '">' + p.label + '</span> <span class="text-muted">' + (row.identifier || '') + '</span>';
                        }
                    },
                    { data: 'conveyor', name: 'conveyor', width: '7%' },
                    { data: 'family', name: 'family', width: '8%' },
                    { data: 'qty', name: 'qty', width: '4%', className: 'text-end' },
                    { 
                        data: 'issue_count', 
                        name: 'issue_count', 
                        width: '5%',
                        className: 'text-end',
                        orderable: false,
                        render: function(data, type, row) {
                            if (data && data > 0) {
                                return '<span class="badge bg-secondary fw-bold">' + data + '</span>';
                            } else {
                                return '<span class="badge bg-light text-dark border">0</span>';
                            }
                        }
                    },
                    { 
                        data: 'sequence', 
                        name: 'sequence', 
                        width: '4%',
                        className: 'text-center'
                    },
                    { 
                        data: 'barcodes', 
                        name: 'barcodes', 
                        width: '14%',
                        orderable: false,
                        render: function(data, type, row) {
                            if (data && data !== '-') {
                                var barcodes = data.split(', ');
                                return barcodes.map(function(b) {
                                    return '<code>' + b + '</code>';
                                }).join('<br>');
                            }
                            return '-';
                        }
                    },
                    { 
                        data: 'shift', 
                        name: 'shift', 
                        width: '9%', 
                        className: 'text-center',
                        render: function(data, type, row) {
                            var colorClass = (row.shift == 1) ? 'bg-primary' : 'bg-danger';
                            return '<span class="badge ' + colorClass + '">' + (row.shift || '-') + '/' + (row.cutoff || '-') + '</span>';
                        }
                    },
                    { 
                        data: 'print_status', 
                        name: 'print_status',
                        width: '10%',
                        orderable: false,
                        render: function(data, type, row) {
                            if (row.is_printed) {
                                var count = row.print_count > 0 ? ' <small>(' + row.print_count + ')</small>' : '';
                                var badge = '<span class="badge bg-success">Printed</span>' + count;
                                if (row.last_printed_at) {
                                    badge += '<br><small>' + row.last_printed_at + '</small>';
                                }
                                return badge;
                            } else {
                                return '<span class="badge bg-warning">Waiting</span>';
                            }
                        }
                    },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '12%' }
                ],
                ordering: false,
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]
            });

            // Auto-reload on all filter changes
            $('#filter_area, #filter_machine, #filter_process, #filter_shift, #filter_cutoff, #filter_print_status').on('change', function() {
                var machine = $('#filter_machine').val();
                if (machine) {
                    table.ajax.reload();
                }
            });

            // Auto-reload when date changes
            $('#filter_date').on('apply.daterangepicker', function() {
                var machine = $('#filter_machine').val();
                if (machine) {
                    table.ajax.reload();
                }
            });

            $('#btn-reset').click(function() {
                // Reset all filters
                $('#filter_area').val('').trigger('change');
                $('#filter_process').val('').trigger('change');
                $('#filter_shift').val('').trigger('change');
                $('#filter_cutoff').val('').trigger('change');
                $('#filter_print_status').val('not_printed').trigger('change');
                $('#filter_machine').val('').trigger('change');
                $('#filter_date').data('daterangepicker').setStartDate(moment());
                // Clear table
                table.clear().draw();
            });

            $('#btn-refresh').click(function() {
                if (qz.websocket.isActive()) {
                    qz.printers.find().then(function(printers) {
                        var $select = $('#printer-select');
                        $select.empty();
                        $select.append('<option>- Choose Printer -</option>');
                        printers.forEach(function(printer) {
                            $select.append('<option value="' + printer + '">' + printer + '</option>');
                        });
                        Swal.fire({
                            icon: 'success',
                            title: 'Printer List Refreshed',
                            text: 'Found ' + printers.length + ' printer(s)',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }).catch(function(err) {
                        Swal.fire('Error', 'Failed to refresh printer list: ' + err, 'error');
                    });
                } else {
                    Swal.fire('Warning', 'Please connect to QZ Tray first', 'warning');
                }
            });

            // Preview button handler (like Circuit)
            $(document).on('click', '.btn-preview', function() {
                var groupId = $(this).data('group-id');
                
                // Show modal with loading indicator
                $('#previewModal').modal('show');
                $('#previewContent').html('<div class="text-center"><i class="fa-solid fa-spinner ti-spin" style="font-size: 3rem;"></i><p>Loading preview...</p></div>');
                
                // Fetch preview HTML
                $.ajax({
                    url: "{{ route('schedule.ekanban-shikake.print-preview') }}",
                    type: 'GET',
                    data: { ids: groupId },
                    success: function(response) {
                        $('#previewContent').html(response);
                    },
                    error: function(xhr, status, error) {
                        $('#previewContent').html('<div class="alert alert-danger">Failed to load preview: ' + error + '</div>');
                    }
                });
            });

            // Print button handler
            $(document).on('click', '.btn-print', function() {
                var groupId = $(this).data('group-id');
                printShikake(groupId);
            });
        });

        // QZ Tray button handlers
        $('#btn-connect').click(function() {
            console.log('Connect button clicked');
            QZPrint.connect().then(function() {
                console.log('Successfully connected to QZ Tray');
            }).catch(function(err) {
                console.error('Connection failed:', err);
                Swal.fire('Error!', 'Failed to connect to QZ Tray: ' + err.toString(), 'error');
            });
        });

        $('#btn-disconnect').click(function() {
            console.log('Disconnect button clicked');
            QZPrint.disconnect();
        });

        // Constants from working code
        const TARGET_DOTS = 576;
        const RASTER_SCALE_MODE = 0x00;
        const SCALE_W = (RASTER_SCALE_MODE & 0x01) ? 2 : 1;
        const SCALE_H = (RASTER_SCALE_MODE & 0x02) ? 2 : 1;
        const BASE_DOTS = Math.floor((TARGET_DOTS / SCALE_W) / 8) * 8;
        const SLICE_ROWS = 256;
        const THRESHOLD = 190;
        const BLANK_AFTER_PAGE_DOTS = 20;
        const CUT_OFFSET_DOTS = 184; // ~23mm feed to pass cutter blade position

        async function printShikake(ids) {
            if (!QZPrint.isConnected) {
                Swal.fire('Error!', 'QZ Tray is not connected. Please connect first.', 'error');
                return;
            }

            var selectedPrinter = $('#printer-select').val();
            if (!selectedPrinter) {
                Swal.fire('Error!', 'Please select a printer first.', 'warning');
                return;
            }

            try {
                // Step 1: Fetch HTML payload via GET with print mode (no side effects)
                const previewResponse = await fetch("{{ route('schedule.ekanban-shikake.print-preview') }}?ids=" + encodeURIComponent(ids) + "&mode=print");
                
                if (!previewResponse.ok) {
                    Swal.fire('Error!', 'Failed to get print data (HTTP ' + previewResponse.status + ')', 'error');
                    return;
                }

                const html = await previewResponse.text();

                if (!html || html.trim() === '') {
                    Swal.fire('Error!', 'No print data returned', 'error');
                    return;
                }

                // Parse HTML and install styles
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                doc.querySelectorAll('style').forEach(styleEl => {
                    const css = (styleEl.textContent || '').trim();
                    if (!css) return;
                    
                    // Create simple hash signature without btoa
                    let sig = 0;
                    for (let i = 0; i < css.length; i++) {
                        sig = ((sig << 5) - sig) + css.charCodeAt(i);
                        sig = sig & sig;
                    }
                    sig = Math.abs(sig).toString(16);
                    
                    if (document.head.querySelector('style[data-print-style="' + sig + '"]')) return;
                    
                    const s = document.createElement('style');
                    s.type = 'text/css';
                    s.setAttribute('data-print-style', sig);
                    s.textContent = css;
                    document.head.appendChild(s);
                });

                const stack = doc.querySelector('#print_stack_ajax');
                if (!stack) {
                    Swal.fire('Error!', 'Invalid print payload', 'error');
                    return;
                }

                // Inject into page
                let holder = document.getElementById('print_area_ajax_holder');
                if (!holder) {
                    holder = document.createElement('div');
                    holder.id = 'print_area_ajax_holder';
                    holder.style.position = 'fixed';
                    holder.style.left = '-9999px';
                    holder.style.top = '0';
                    holder.style.visibility = 'hidden';
                    holder.style.overflow = 'visible';
                    document.body.appendChild(holder);
                }
                holder.innerHTML = '';
                holder.appendChild(stack);

                // Wait for all images inside tickets to load before capturing
                const imgs = stack.querySelectorAll('img');
                if (imgs.length > 0) {
                    await Promise.all(Array.from(imgs).map(img => {
                        if (img.complete) return Promise.resolve();
                        return new Promise((resolve) => {
                            img.onload = resolve;
                            img.onerror = resolve;
                        });
                    }));
                }

                // Allow browser to complete layout before capture
                await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));

                // Step 2: Print via QZ Tray
                await printStackNow('#print_stack_ajax', selectedPrinter);

                // Step 3: Mark as printed ONLY after successful QZ print
                try {
                    const markResponse = await fetch("{{ route('schedule.ekanban-shikake.print') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ ids: ids, mark_only: true })
                    });
                    const markData = await markResponse.json();
                    if (!markData.ok) {
                        console.warn('Failed to update print status:', markData);
                    }
                } catch (markErr) {
                    console.error('Failed to update print status:', markErr);
                }

                // Step 4: Reload table to show updated status
                if (typeof table !== 'undefined') {
                    table.ajax.reload(null, false);
                }

                Swal.fire('Success!', 'Print completed successfully!', 'success');

            } catch (err) {
                console.error('Print error:', err);
                Swal.fire('Error!', 'Print failed: ' + err.toString(), 'error');
            }
        }

        async function printStackNow(stackRootSel, printer) {
            const tickets = Array.from(document.querySelectorAll(stackRootSel + ' .ticket'));
            if (!tickets.length) {
                throw new Error('No tickets found');
            }

            const jobs = [{ type: 'raw', format: 'command', flavor: 'hex', data: '1B40' }]; // ESC @ init
            
            for (const t of tickets) {
                const cvs = await renderTicketToCanvas(t);
                const { slices, bpr } = canvasToEscposSlices(cvs);
                
                for (const hex of slices) {
                    jobs.push({ type: 'raw', format: 'command', flavor: 'hex', data: hex });
                }
                
                // Blank after page
                if (BLANK_AFTER_PAGE_DOTS > 0) {
                    jobs.push({ type: 'raw', format: 'command', flavor: 'hex', data: makeBlankRasterHex(bpr, BLANK_AFTER_PAGE_DOTS) });
                }
                
                // Feed before cut
                if (CUT_OFFSET_DOTS > 0) {
                    jobs.push({ type: 'raw', format: 'command', flavor: 'hex', data: feedDotsHex(CUT_OFFSET_DOTS) });
                }
                
                // Cut
                jobs.push({ type: 'raw', format: 'command', flavor: 'hex', data: '1D5600' });
            }

            const cfg = qz.configs.create(printer);
            await qz.print(cfg, jobs);
        }

        async function renderTicketToCanvas(ticket) {
            const isLandscape = ticket.dataset.orientation === 'landscape';

            // Save original inline styles
            const saved = {
                w: ticket.style.width,
                h: ticket.style.height,
                b: ticket.style.boxSizing,
                mw: ticket.style.maxWidth,
                ov: ticket.style.overflow
            };

            ticket.style.boxSizing = 'border-box';

            if (isLandscape) {
                // Landscape: wrapper already at 576px via CSS, ensure it's set
                // After rotation: 576px height -> 576 dots width = 1:1 no scaling
                ticket.style.height = BASE_DOTS + 'px';
                ticket.style.maxWidth = 'none';
                ticket.style.overflow = 'visible';
            } else {
                ticket.style.width = BASE_DOTS + 'px';
            }

            const restore = makeVisibleForCapture(ticket, isLandscape);
            const canvas = await html2canvas(ticket, { scale: 1, backgroundColor: '#fff', useCORS: true });
            restore();

            // Restore all saved inline styles
            ticket.style.width = saved.w;
            ticket.style.height = saved.h;
            ticket.style.boxSizing = saved.b;
            ticket.style.maxWidth = saved.mw;
            ticket.style.overflow = saved.ov;

            let finalCanvas, dstW, dstH, srcW, srcH;

            if (isLandscape) {
                // For landscape: rotate 90° CW so height becomes print-head width
                finalCanvas = rotateCanvas90CW(canvas);
                
                // After rotation: original height -> new width, original width -> new height
                // Scale so that new width (original height) = BASE_DOTS (paper width)
                srcW = finalCanvas.width;  // This was original height
                srcH = finalCanvas.height; // This was original width (can be long)
                
                dstW = BASE_DOTS;
                dstW -= (dstW % 8);
                const scale = dstW / srcW;
                dstH = Math.floor(srcH * scale);
            } else {
                finalCanvas = canvas;
                srcW = finalCanvas.width;
                srcH = finalCanvas.height;
                dstW = Math.min(BASE_DOTS, srcW);
                dstW -= (dstW % 8);
                dstH = Math.floor(srcH * (dstW / srcW));
            }

            if (dstW !== srcW || dstH !== srcH) {
                const out = document.createElement('canvas');
                out.width = dstW;
                out.height = dstH;
                const ctx = out.getContext('2d');
                ctx.imageSmoothingEnabled = false;
                ctx.fillStyle = '#fff';
                ctx.fillRect(0, 0, dstW, dstH);
                ctx.drawImage(finalCanvas, 0, 0, dstW, dstH);
                return out;
            }
            return finalCanvas;
        }

        function makeVisibleForCapture(node, isLandscape) {
            const prev = {
                v: node.style.visibility,
                p: node.style.position,
                l: node.style.left,
                t: node.style.top,
                h: node.style.height
            };
            node.style.visibility = 'visible';
            node.style.position = 'absolute';
            node.style.left = '-10000px';
            node.style.top = '0';
            if (!isLandscape) {
                node.style.height = 'auto';
            }
            return () => {
                node.style.visibility = prev.v;
                node.style.position = prev.p;
                node.style.left = prev.l;
                node.style.top = prev.t;
                node.style.height = prev.h;
            };
        }

        function rotateCanvas90CW(canvas) {
            const w = canvas.width;
            const h = canvas.height;
            const rotated = document.createElement('canvas');
            rotated.width = h;
            rotated.height = w;
            const ctx = rotated.getContext('2d');
            ctx.translate(h, 0);
            ctx.rotate(Math.PI / 2);
            ctx.drawImage(canvas, 0, 0);
            return rotated;
        }

        function canvasToEscposSlices(canvas) {
            const w = canvas.width;
            const h = canvas.height;
            const ctx = canvas.getContext('2d', { willReadFrequently: true });
            const img = ctx.getImageData(0, 0, w, h).data;
            const bpr = Math.ceil(w / 8);
            const out = [];
            const m = RASTER_SCALE_MODE & 0xFF;

            for (let y0 = 0; y0 < h; y0 += SLICE_ROWS) {
                const sH = Math.min(SLICE_ROWS, h - y0);
                const band = new Uint8Array(bpr * sH);
                
                for (let y = 0; y < sH; y++) {
                    for (let xb = 0; xb < bpr; xb++) {
                        let b = 0;
                        for (let bit = 0; bit < 8; bit++) {
                            const x = xb * 8 + bit;
                            if (x >= w) break;
                            const idx = ((y0 + y) * w + x) * 4;
                            const r = img[idx];
                            const g = img[idx + 1];
                            const bl = img[idx + 2];
                            const lum = 0.299 * r + 0.587 * g + 0.114 * bl;
                            if (lum < THRESHOLD) b |= (0x80 >> bit);
                        }
                        band[y * bpr + xb] = b;
                    }
                }
                
                const xL = bpr & 0xFF;
                const xH = (bpr >> 8) & 0xFF;
                const yL = sH & 0xFF;
                const yH = (sH >> 8) & 0xFF;
                const header = new Uint8Array([0x1D, 0x76, 0x30, m, xL, xH, yL, yH]);
                const pkt = new Uint8Array(header.length + band.length);
                pkt.set(header, 0);
                pkt.set(band, header.length);
                out.push(u8ToHex(pkt));
            }
            
            return { slices: out, bpr: bpr };
        }

        function makeBlankRasterHex(bpr, dots) {
            const rows = Math.max(1, Math.ceil(dots / ((RASTER_SCALE_MODE & 0x02) ? 2 : 1)));
            const band = new Uint8Array(bpr * rows);
            const m = RASTER_SCALE_MODE & 0xFF;
            const xL = bpr & 0xFF;
            const xH = (bpr >> 8) & 0xFF;
            const yL = rows & 0xFF;
            const yH = (rows >> 8) & 0xFF;
            const header = new Uint8Array([0x1D, 0x76, 0x30, m, xL, xH, yL, yH]);
            const pkt = new Uint8Array(header.length + band.length);
            pkt.set(header, 0);
            pkt.set(band, header.length);
            return u8ToHex(pkt);
        }

        function feedDotsHex(n) {
            n = Math.max(0, Math.min(255, n | 0));
            const arr = new Uint8Array([0x1B, 0x4A, n]);
            let s = '';
            for (let i = 0; i < arr.length; i++) {
                s += arr[i].toString(16).padStart(2, '0').toUpperCase();
            }
            return s;
        }

        function u8ToHex(u) {
            let s = '';
            for (let i = 0; i < u.length; i++) {
                s += u[i].toString(16).padStart(2, '0').toUpperCase();
            }
            return s;
        }

        // Ready
        $(document).ready(function() {
            console.log('Page loaded - QZ Tray ready for manual connection');
        });
    </script>
@endsection