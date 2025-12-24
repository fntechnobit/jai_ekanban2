@extends('layout')

@section('title', 'eKanban Shikake - Print Per Machine')

@section('content')
    <x-page-header menu-code="ekanban_shikake_print_machine" />

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-wrench"></i> eKanban Shikake - Print Per Machine
                    </h3>
                    <div class="card-tools">
                        <button class="btn btn-sm btn-success mr-1" id="btn-connect">
                            <i class="fas fa-plug"></i> Connect
                        </button>
                        <button class="btn btn-sm btn-warning mr-2" id="btn-disconnect">
                            <i class="fas fa-unlink"></i> Disconnect
                        </button>
                        <span id="qz-status" class="mr-2">QZ: Idle</span>
                        <button type="button" class="btn btn-info btn-sm" id="btn-refresh">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <select class="btn btn-sm" id="printer-select" style="max-width: 200px;" disabled>
                            <option>- Choose Printer -</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form class="form-horizontal">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group row">
                                    <label for="filter_area" class="col-sm-3 col-form-label">Area:</label>
                                    <div class="col-sm-9">
                                        <select class="form-control select2" id="filter_area">
                                            <option value="">- All Area -</option>
                                            @foreach($areas as $area)
                                                <option value="{{ $area->id }}">{{ $area->area }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group row">
                                    <label for="filter_conveyor" class="col-sm-3 col-form-label">Conveyor: <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <select class="form-control select2" id="filter_conveyor" required>
                                            <option value="">- Choose Conveyor -</option>
                                            @foreach($conveyors as $conveyor)
                                                <option value="{{ $conveyor->id }}">{{ $conveyor->conveyor }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group row">
                                    <label for="filter_machine" class="col-sm-3 col-form-label">Machine: <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <select class="form-control select2" id="filter_machine" required>
                                            <option value="">- Choose Machine -</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group row">
                                    <label for="filter_dates" class="col-sm-3 col-form-label">Dates:</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="filter_dates" readonly placeholder="Select date range">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group row">
                                    <label for="filter_shift" class="col-sm-3 col-form-label">Shift:</label>
                                    <div class="col-sm-9">
                                        <select class="form-control select2" id="filter_shift">
                                            <option value="">- All Shift -</option>
                                            <option value="1">Shift 1</option>
                                            <option value="2">Shift 2</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label">&nbsp;</label>
                                    <div class="col-sm-9">
                                        <button type="button" class="btn btn-info" id="btn-filter">
                                            <i class="fas fa-search"></i> Filter
                                        </button>
                                        <button type="button" class="btn btn-secondary ml-2" id="btn-reset">
                                            <i class="fas fa-redo"></i> Reset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table id="shikake-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">Num.</th>
                                    <th>Conveyor</th>
                                    <th>Machine</th>
                                    <th>Dates</th>
                                    <th>Shift</th>
                                    <th>Assy</th>
                                    <th>Listing</th>
                                    <th>Pallet</th>
                                    <th>W/D</th>
                                    <th width="8%">#</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
    <!-- QZ Tray -->
    <script src="https://unpkg.com/qz-tray@2.2.3/qz-tray.js"></script>
    <script src="{{ asset('js/qz-print.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <script>
        var qz = window.qz;
        var table;
        
        $(function () {
            // Initialize Select2
            $('.select2').select2({
                allowClear: true
            });

            // Initialize date range picker
            var startDate = moment();
            var endDate = moment();

            $('#filter_dates').daterangepicker({
                startDate: startDate,
                endDate: endDate,
                locale: {
                    format: 'DD-MM-YYYY'
                }
            });

            // DataTable - Don't load data initially
            table = $('#shikake-table').DataTable({
                processing: true,
                serverSide: true,
                deferLoading: 0, // Don't load data on initialization
                ajax: {
                    url: "{{ route('schedule.ekanban-shikake.print-machine') }}",
                    data: function(d) {
                        var dates = $('#filter_dates').data('daterangepicker');
                        d.area_id = $('#filter_area').val();
                        d.conveyor_id = $('#filter_conveyor').val();
                        d.machine = $('#filter_machine').val();
                        d.start_date = dates.startDate.format('YYYY-MM-DD');
                        d.end_date = dates.endDate.format('YYYY-MM-DD');
                        d.shift = $('#filter_shift').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '5%' },
                    { data: 'conveyor', name: 'conveyor', width: '12%' },
                    { data: 'machine', name: 'machine', width: '12%' },
                    { data: 'dates', name: 'dates', width: '10%' },
                    { data: 'shift', name: 'shift', width: '8%' },
                    { data: 'assy', name: 'assy', width: '15%' },
                    { data: 'listing', name: 'listing', width: '8%' },
                    { data: 'pallet', name: 'pallet', width: '8%' },
                    { data: 'wd', name: 'wd', width: '10%' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '8%' }
                ],
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[3, 'asc']]
            });

            // Filter buttons
            $('#btn-filter').click(function() {
                // Validate required filters
                var machine = $('#filter_machine').val();
                var conveyor = $('#filter_conveyor').val();
                
                if (!machine || !conveyor) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Required Fields',
                        text: 'Please select both Machine and Conveyor before filtering'
                    });
                    return;
                }
                
                table.ajax.reload();
            });

            $('#btn-reset').click(function() {
                $('.select2').val('').trigger('change');
                $('#filter_machine').empty().append('<option value="">- Choose Machine -</option>');
                $('#filter_dates').data('daterangepicker').setStartDate(moment());
                $('#filter_dates').data('daterangepicker').setEndDate(moment());
                table.ajax.reload();
            });

            // Dynamic machine loading based on conveyor selection
            $('#filter_conveyor').change(function() {
                var conveyorId = $(this).val();
                var machineSelect = $('#filter_machine');
                
                machineSelect.empty().append('<option value="">- Choose Machine -</option>');
                
                if (conveyorId) {
                    $.ajax({
                        url: "{{ route('schedule.ekanban-shikake.machines-by-conveyor') }}",
                        type: 'GET',
                        data: { conveyor_id: conveyorId },
                        success: function(machines) {
                            $.each(machines, function(index, machine) {
                                machineSelect.append('<option value="' + machine.machine + '">' + machine.name + '</option>');
                            });
                        },
                        error: function() {
                            console.error('Failed to load machines');
                        }
                    });
                }
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

            // Print button handler
            $(document).on('click', '.btn-print', function() {
                var ids = $(this).data('ids');
                printShikake(ids);
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
        const THRESHOLD = 145;
        const BLANK_AFTER_PAGE_DOTS = 250;
        const CUT_OFFSET_DOTS = 8;

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
                // Fetch HTML payload
                const response = await fetch("{{ route('schedule.ekanban-shikake.print') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ ids: ids })
                });
                
                const data = await response.json();
                
                if (!data.ok || !data.html) {
                    Swal.fire('Error!', 'Failed to get print data', 'error');
                    return;
                }

                // Parse HTML and install styles
                const parser = new DOMParser();
                const doc = parser.parseFromString(data.html, 'text/html');
                
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
                    holder.style.visibility = 'hidden';
                    holder.style.height = '0';
                    holder.style.overflow = 'hidden';
                    document.body.appendChild(holder);
                }
                holder.innerHTML = '';
                holder.appendChild(stack);

                await printStackNow('#print_stack_ajax', selectedPrinter);
                
                Swal.fire('Success!', 'Print completed successfully!', 'success');
                if (typeof table !== 'undefined') {
                    table.ajax.reload();
                }

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
            const prevW = ticket.style.width;
            const prevB = ticket.style.boxSizing;
            ticket.style.boxSizing = 'border-box';
            ticket.style.width = BASE_DOTS + 'px';
            
            const restore = makeVisibleForCapture(ticket);
            const canvas = await html2canvas(ticket, { scale: 1, backgroundColor: '#fff', useCORS: true });
            restore();
            
            ticket.style.width = prevW;
            ticket.style.boxSizing = prevB;

            const srcW = canvas.width;
            const srcH = canvas.height;
            let dstW = Math.min(BASE_DOTS, srcW);
            dstW -= (dstW % 8);
            const dstH = Math.floor(srcH * (dstW / srcW));
            
            if (dstW !== srcW) {
                const out = document.createElement('canvas');
                out.width = dstW;
                out.height = dstH;
                const ctx = out.getContext('2d');
                ctx.imageSmoothingEnabled = false;
                ctx.fillStyle = '#fff';
                ctx.fillRect(0, 0, dstW, dstH);
                ctx.drawImage(canvas, 0, 0, dstW, dstH);
                return out;
            }
            return canvas;
        }

        function makeVisibleForCapture(node) {
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
            node.style.height = 'auto';
            return () => {
                node.style.visibility = prev.v;
                node.style.position = prev.p;
                node.style.left = prev.l;
                node.style.top = prev.t;
                node.style.height = prev.h;
            };
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