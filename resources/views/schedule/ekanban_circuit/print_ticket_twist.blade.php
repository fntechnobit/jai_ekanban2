{{-- CIRCUIT CUTTING_TWIST Print Template - adapted from shikake twist template --}}
{{-- Layout: 987px × 576px (120mm × 80mm at 203dpi), landscape --}}
<style>
/* CUTTING TWIST Print Template - Landscape layout for 80mm thermal printer */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.twist-print-wrapper {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 0;
    margin: 10px auto;
    justify-content: flex-start;
    align-items: stretch;
    background: white;
    page-break-after: always;
    height: 576px;
}

.twist-print-wrapper .twist-image-section {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    height: 100%;
}

.twist-print-wrapper .twist-image-section img {
    height: 100%;
    width: auto;
    object-fit: contain;
}

.ticket-twist-print {
    width: 987px;
    min-width: 987px;
    height: 100%;
    flex-shrink: 0;
    background: white;
    margin: 0;
    padding: 0;
    border: 2px solid #000;
    overflow: hidden;
    font-family: Arial, sans-serif;
}

.ticket-twist-print table {
    width: 100%;
    height: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.ticket-twist-print th,
.ticket-twist-print td {
    border: 1px solid #000;
    padding: 2px 3px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.1;
    font-size: 13px;
    box-sizing: border-box;
}

.ticket-twist-print thead th {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 20px;
    padding: 3px;
    border: 2px solid #000;
}

.twist-label-cell {
    background-color: #f0f0f0;
    font-weight: 500;
    font-size: 10px;
}

.twist-value-cell {
    font-weight: bold;
    font-size: 14px;
}

.twist-section-label {
    font-weight: bold;
    font-size: 16px;
    width: 22px;
    background-color: #fff;
    color: #000;
}

.twist-section-label.black-bg {
    background-color: #000;
    color: #fff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.twist-barcode-cell {
    padding: 2px;
    vertical-align: middle;
    text-align: center;
}

.twist-barcode-cell img {
    max-width: 140px;
    height: 45px;
    display: block;
    margin: 0 auto;
}

.twist-qrcode-cell {
    padding: 2px;
    vertical-align: middle;
    text-align: center;
}

.twist-qr-label {
    font-size: 8px;
    font-weight: bold;
    margin-bottom: 1px;
}

.twist-qr-img {
    width: 13mm;
    height: 13mm;
    display: block;
    margin: 0 auto;
}

.twist-navigasi-cell {
    padding: 2px;
    vertical-align: middle;
    text-align: center;
}

.twist-navigasi-cell img {
    max-width: 140px;
    height: 45px;
    display: block;
    margin: 0 auto;
}

.text-left {
    text-align: left !important;
}

/* Thermal Printer Optimization */
@media print {
    body {
        margin: 0;
        padding: 0;
        background: white;
    }

    .twist-print-wrapper {
        flex-direction: row;
        gap: 0;
        margin: 0;
        justify-content: flex-start;
        page-break-after: always;
        page-break-inside: avoid;
    }

    .twist-print-wrapper .twist-image-section {
        display: flex;
        flex-shrink: 0;
        align-items: center;
    }

    .twist-print-wrapper .twist-image-section img {
        height: 70mm;
        width: auto;
        object-fit: contain;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .ticket-twist-print {
        width: auto;
        max-width: none;
        height: auto;
        border: none;
        margin: 0;
        padding: 0;
    }

    .ticket-twist-print table {
        page-break-inside: avoid;
        width: 100%;
    }

    .ticket-twist-print th,
    .ticket-twist-print td {
        border: 1px solid #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .twist-section-label.black-bg {
        background-color: #000 !important;
        color: #fff !important;
    }

    .twist-label-cell {
        background-color: #f0f0f0 !important;
    }

    @page {
        size: landscape;
        margin: 1mm;
    }
}
</style>

@foreach($circuits as $circuit)
{{-- Wrapper with flexbox for image + ticket --}}
<div class="ticket twist-print-wrapper" data-orientation="landscape">
    {{-- Image section (LEFT) - only if image exists --}}
    @if(!empty($circuit->image_path))
    <div class="twist-image-section">
        <img src="{{ asset($circuit->image_path) }}" alt="Circuit Image">
    </div>
    @endif

    {{-- Ticket section (RIGHT) --}}
    <div class="ticket-twist-print">
    <table>
        <colgroup>
            <col style="width: 3%">   {{-- Section label --}}
            <col style="width: 8%">   {{-- Label col --}}
            <col style="width: 10%">  {{-- Value col --}}
            <col style="width: 10%">  {{-- Value col --}}
            <col style="width: 8%">   {{-- Label col --}}
            <col style="width: 10%">  {{-- Value col --}}
            <col style="width: 13%">  {{-- Mach twist / Barcode --}}
            <col style="width: 13%">  {{-- Mach twist / Barcode --}}
            <col style="width: 10%">  {{-- SEQ / Barcode --}}
        </colgroup>
        <thead>
            <tr>
                <th colspan="9">EKANBAN CUTTING TWIST - {{ $circuit->carline ?? '' }}</th>
            </tr>
        </thead>
        <tbody>
            <!-- Row 1: Barcode Navigasi + Info Headers -->
            <tr>
                <td colspan="3" rowspan="2" class="twist-navigasi-cell">
                    @if(isset($circuit->barcode_navigasi_path))
                        <img src="{{ $circuit->barcode_navigasi_path }}" alt="Barcode Navigasi">
                    @else
                        <span style="font-size:7px;">{{ $circuit->barcode_navigasi ?? '-' }}</span>
                    @endif
                </td>
                <td class="twist-label-cell">CCT CODE</td>
                <td colspan="2" class="twist-value-cell">{{ $circuit->cct_code ?? '-' }}</td>
                <td class="twist-label-cell">MACHINE</td>
                <td class="twist-value-cell">{{ $circuit->machine ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->sequence ?? '-' }}</td>
            </tr>
            <!-- Row 2: Continued -->
            <tr>
                <td class="twist-label-cell">CCT NO</td>
                <td colspan="2" class="twist-value-cell">{{ $circuit->cct_no ?? '-' }}</td>
                <td class="twist-label-cell">SEQ</td>
                <td colspan="2" class="twist-value-cell">{{ $circuit->sequence ?? '-' }}</td>
            </tr>
            <!-- Row 3: Detail info -->
            <tr>
                <td colspan="2" class="twist-label-cell">CUST NO</td>
                <td>{{ $circuit->cust_no ?? '-' }}</td>
                <td>{{ $circuit->kind ?? '-' }}</td>
                <td>{{ $circuit->size ?? '-' }}</td>
                <td>{{ $circuit->col ?? '-' }}</td>
                <td>{{ $circuit->cl ?? '-' }}</td>
                <td>{{ $circuit->qty ?? '-' }}</td>
                <td colspan="1">{{ $circuit->issue ?? '-' }}</td>
            </tr>

            <!-- Section A - Row 1 -->
            <tr>
                <td rowspan="3" class="twist-section-label">A</td>
                <td class="twist-label-cell text-left">TERMINAL</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->terminal_1 ?? '-' }}</td>
                <td class="twist-label-cell text-left">NOTE</td>
                <td class="twist-value-cell text-left">{{ $circuit->note_1 ?? '-' }}</td>
                <td colspan="2" class="twist-label-cell">MACH. TWIST</td>
                <td class="twist-label-cell">SEQ</td>
            </tr>
            <!-- Section A - Row 2 -->
            <tr>
                <td class="twist-label-cell text-left">ACC 1</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->acc_1a ?? '-' }}</td>
                <td class="twist-label-cell text-left">STRIP</td>
                <td class="twist-value-cell text-left">{{ $circuit->strip_1 ?? '-' }}</td>
                <td colspan="2" class="twist-value-cell">{{ $circuit->machine_twist ?? '-' }}</td>
                <td class="twist-value-cell">{{ $circuit->sequence_2 ?? '-' }}</td>
            </tr>
            <!-- Section A - Row 3 -->
            <tr>
                <td class="twist-label-cell text-left">TUBE</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->tube_1 ?? '-' }}</td>
                <td class="twist-label-cell text-left">MARK</td>
                <td class="twist-value-cell text-left">{{ $circuit->mark_1 ?? '-' }}</td>
                <td colspan="3" rowspan="2" class="twist-barcode-cell">
                    @if(isset($circuit->barcode_process_path))
                        <img src="{{ $circuit->barcode_process_path }}" alt="Barcode Process">
                    @else
                        <span style="font-size:7px;">{{ $circuit->barcode_process ?? '-' }}</span>
                    @endif
                </td>
            </tr>

            <!-- Section B - Row 1 -->
            <tr>
                <td rowspan="3" class="twist-section-label black-bg">B</td>
                <td class="twist-label-cell text-left">TERMINAL</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->terminal_2 ?? '-' }}</td>
                <td class="twist-label-cell text-left">NOTE</td>
                <td class="twist-value-cell text-left">{{ $circuit->note_2 ?? '-' }}</td>
            </tr>
            <!-- Section B - Row 2 -->
            <tr>
                <td class="twist-label-cell text-left">ACC 1</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->acc_2a ?? '-' }}</td>
                <td class="twist-label-cell text-left">STRIP</td>
                <td class="twist-value-cell text-left">{{ $circuit->strip_2 ?? '-' }}</td>
                <td colspan="3" class="twist-label-cell">TO STORE</td>
            </tr>
            <!-- Section B - Row 3 -->
            <tr>
                <td class="twist-label-cell text-left">TUBE</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $circuit->tube_2 ?? '-' }}</td>
                <td class="twist-label-cell text-left">MARK</td>
                <td class="twist-value-cell text-left">{{ $circuit->mark_2 ?? '-' }}</td>
                <td colspan="3" class="twist-value-cell">{{ $circuit->to_store ?? '-' }}</td>
            </tr>

            <!-- Bottom Section - Row 1 -->
            <tr>
                <td colspan="3" rowspan="4" class="twist-qrcode-cell">
                    <div class="twist-qr-label">QRCODE KANBAN</div>
                    @if(isset($circuit->qr_code_path))
                        <img src="{{ $circuit->qr_code_path }}" alt="QR Kanban" class="twist-qr-img">
                    @else
                        <div style="width:13mm;height:13mm;border:1px solid #000;margin:0 auto;font-size:6px;display:flex;align-items:center;justify-content:center;">QR</div>
                    @endif
                </td>
                <td class="twist-label-cell text-left">CV NO</td>
                <td colspan="3" class="twist-value-cell text-left">{{ $circuit->conveyor ?? '-' }}</td>
                <td colspan="2" rowspan="4" class="twist-qrcode-cell">
                    @if(!empty($circuit->barcode_shikake))
                        <div class="twist-qr-label">QRCODE SHIKAKE</div>
                        @if(isset($circuit->barcode_shikake_path))
                            <img src="{{ $circuit->barcode_shikake_path }}" alt="QR Shikake" class="twist-qr-img">
                        @endif
                    @endif
                </td>
            </tr>
            <!-- Bottom Section - Row 2 -->
            <tr>
                <td class="twist-label-cell text-left">FAMILY</td>
                <td colspan="3" class="twist-value-cell text-left">{{ $circuit->family ?? '-' }}</td>
            </tr>
            <!-- Bottom Section - Row 3 -->
            <tr>
                <td class="twist-label-cell text-left">DATE</td>
                <td colspan="3" class="twist-value-cell text-left">
                    {{ $circuit->release_date ? \Carbon\Carbon::parse($circuit->release_date)->format('d-M-y') : '-' }}
                </td>
            </tr>
            <!-- Bottom Section - Row 4 -->
            <tr>
                <td class="twist-label-cell text-left">NOTE</td>
                <td colspan="3" class="twist-value-cell text-left">{{ $circuit->released_note ?? '-' }}</td>
            </tr>
        </tbody>
    </table>
    </div>
</div>
{{-- End of twist-print-wrapper --}}
@endforeach
