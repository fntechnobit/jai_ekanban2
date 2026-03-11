{{-- TWIST Process Print Template - PRINT VERSION (landscape, 80mm height) --}}
<style>
/* TWIST Print Template - Landscape layout for 80mm thermal printer */
/* Native 576px height = 80mm paper width at 203dpi (1:1 no scaling) */
/* Ticket width: 120mm = 987px at 203dpi */
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
    margin: 0;
    padding: 0;
    justify-content: flex-start;
    align-items: stretch;
    background: white;
    page-break-after: always;
    height: 576px;
}

.twist-print-wrapper .shikake-image-section {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    background: white;
    height: 100%;
    margin: 0;
    padding: 0;
}

.twist-print-wrapper .shikake-image-section img {
    height: 100%;
    width: auto;
    object-fit: contain;
    margin: 0;
    padding: 0;
    display: block;
    /* Enhance drawing visibility for thermal printing */
    filter: contrast(2.0) brightness(0.95);
    -webkit-filter: contrast(2.0) brightness(0.95);
}

.twist-kanban-container {
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

.twist-kanban-container table {
    width: 100%;
    height: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.twist-kanban-container th,
.twist-kanban-container td {
    border: 1px solid #000;
    padding: 2px 4px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.2;
    font-size: 18px;
    box-sizing: border-box;
}

.twist-kanban-container thead th {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 28px;
    padding: 4px;
    border: 2px solid #000;
}

.twist-kanban-container .twist-section-label {
    background-color: transparent;
    color: #000;
    font-weight: bold;
    font-size: 18px;
    width: 5.5mm;
    padding: 4px;
}

.twist-kanban-container .twist-section-label.black-bg {
    background-color: #000;
    color: white;
}

.twist-kanban-container .twist-label-cell {
    background-color: transparent;
    font-weight: 500;
    font-size: 14px;
}

.twist-kanban-container .twist-value-cell {
    font-weight: bold;
    font-size: 18px;
}

.twist-kanban-container .text-left {
    text-align: left !important;
    padding-left: 4px;
}

.twist-kanban-container .twist-qrcode-cell {
    padding: 2px;
    vertical-align: middle;
    text-align: center;
}

.twist-kanban-container .twist-qrcode-cell img {
    max-width: 180px;
    max-height: 180px;
}

.twist-kanban-container .twist-barcode-cell {
    padding: 2px;
    vertical-align: middle;
    text-align: center;
}

.twist-kanban-container .twist-barcode-cell img {
    width: 80%;
    max-height: 10mm;
    height: auto;
    object-fit: contain;
    display: block;
    margin: 0 auto;
}

.twist-kanban-container .twist-qr-label {
    font-size: 12px;
    font-weight: bold;
    margin-bottom: 4px;
}

.twist-kanban-container .twist-qr-img {
    width: 180px;
    height: 180px;
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
    
    .twist-print-wrapper .shikake-image-section {
        display: flex;
        flex-shrink: 0;
        align-items: center;
    }
    
    .twist-print-wrapper .shikake-image-section img {
        height: 70mm;
        width: auto;
        object-fit: contain;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .twist-kanban-container {
        width: auto;
        max-width: none;
        height: auto;
        border: none;
        margin: 0;
        padding: 0;
    }
    
    .twist-kanban-container table {
        width: 100%;
        page-break-inside: avoid;
    }
    
    .twist-kanban-container th,
    .twist-kanban-container td {
        border: 1px solid #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .twist-kanban-container thead th {
        font-size: 28px;
    }
    
    .twist-kanban-container .twist-label-cell {
        font-size: 14px;
    }
    
    .twist-kanban-container .twist-value-cell {
        font-size: 18px;
    }
    
    .twist-kanban-container .twist-section-label {
        font-size: 18px;
    }
    
    .twist-kanban-container .twist-section-label.black-bg {
        background-color: #000 !important;
        color: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .twist-kanban-container .twist-qr-img {
        width: 180px;
        height: 180px;
    }
    
    .twist-kanban-container .twist-barcode-cell img {
        width: 80%;
        max-height: 10mm;
        height: auto;
        object-fit: contain;
        display: block;
        margin: 0 auto;
    }
    
    @page {
        size: landscape;
        margin: 1mm;
    }
}
</style>

{{-- Wrapper with flexbox for image + ticket --}}
<div class="ticket twist-print-wrapper" data-orientation="landscape">
    {{-- Image section (LEFT) - only if image exists --}}
    @if(!empty($shikake->image_path))
    <div class="shikake-image-section">
        <img src="{{ asset($shikake->image_path) }}" alt="Shikake Image">
    </div>
    @endif
    
    {{-- Ticket section (RIGHT) - landscape layout for 80mm thermal printer --}}
    <div class="twist-kanban-container">
    <table>
        <colgroup>
            <col style="width: 5.5mm">
            <col style="width: 12.3mm">
            <col style="width: 10.3mm">
            <col style="width: 12.3mm">
            <col style="width: 12.3mm">
            <col style="width: 12.3mm">
            <col style="width: 12.3mm">
            <col style="width: 12.3mm">
            <col style="width: 23.5mm">
        </colgroup>
        <thead>
            <tr>
                <th colspan="9">EKANBAN CUTTING TWIST - {{ $shikake->carline ?? '' }}</th>
            </tr>
        </thead>
        <tbody>
            <!-- Row 1: Labels -->
            <tr class="twist-label-cell">
                <td colspan="2">CCT CODE</td>
                <td>CCT NO</td>
                <td colspan="2">MACHINE</td>
                <td>SEQ</td>
                <td colspan="3" rowspan="2" class="twist-barcode-cell">
                    @if(isset($processData->barcode_navigasi_path))
                        <img src="{{ $processData->barcode_navigasi_path }}" alt="Barcode">
                    @else
                        <span style="font-size:7px;">{{ $processData->barcode_navigasi ?? '-' }}</span>
                    @endif
                </td>
            </tr>
            <!-- Row 2: Values -->
            <tr class="twist-value-cell">
                <td colspan="2">{{ $processData->cct_code ?? '-' }}</td>
                <td>{{ $processData->cct_no ?? '-' }}</td>
                <td colspan="2">{{ $shikake->machine ?? '-' }}</td>
                <td>{{ $shikake->sequence ?? '-' }}</td>
            </tr>
            
            <!-- Row 3: Customer Info Labels -->
            <tr class="twist-label-cell">
                <td colspan="2">CUST NO</td>
                <td>KIND</td>
                <td>SIZE</td>
                <td>COLOR</td>
                <td>C/L</td>
                <td>QTY</td>
                <td colspan="2">ISSUE</td>
            </tr>
            <!-- Row 4: Customer Info Values -->
            <tr class="twist-value-cell">
                <td colspan="2">{{ $processData->cust_no ?? '-' }}</td>
                <td>{{ $processData->kind ?? '-' }}</td>
                <td>{{ $processData->size ?? '-' }}</td>
                <td>{{ $processData->color ?? '-' }}</td>
                <td>{{ $processData->cl ?? '-' }}</td>
                <td>{{ $shikake->qty ?? '-' }}</td>
                <td colspan="2">{{ $shikake->issue ?? '-' }}</td>
            </tr>
            
            <!-- Section A - Row 1 -->
            <tr>
                <td rowspan="3" class="twist-section-label">A</td>
                <td class="twist-label-cell text-left">TERMINAL</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $processData->terminal_a ?? '-' }}</td>
                <td class="twist-label-cell text-left">NOTE</td>
                <td class="twist-value-cell text-left">{{ $processData->note_a ?? '-' }}</td>
                <td colspan="2" class="twist-label-cell">MACH. TWIST</td>
                <td class="twist-label-cell">SEQ</td>
            </tr>
            <!-- Section A - Row 2 -->
            <tr>
                <td class="twist-label-cell text-left">ACC 1</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $processData->acc_1_a ?? '-' }}</td>
                <td class="twist-label-cell text-left">STRIP</td>
                <td class="twist-value-cell text-left">{{ $processData->strip_a ?? '-' }}</td>
                <td colspan="2" class="twist-value-cell">{{ $processData->machine_twist ?? '-' }}</td>
                <td class="twist-value-cell">{{ $processData->sequence_2 ?? '-' }}</td>
            </tr>
            <!-- Section A - Row 3 -->
            <tr>
                <td class="twist-label-cell text-left">TUBE</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $processData->tube_a ?? '-' }}</td>
                <td class="twist-label-cell text-left">MARK</td>
                <td class="twist-value-cell text-left">{{ $processData->mark_a ?? '-' }}</td>
                <td colspan="3" rowspan="2" class="twist-barcode-cell">
                    @if(isset($processData->barcode_twist_path))
                        <img src="{{ $processData->barcode_twist_path }}" alt="Barcode Twist">
                    @elseif(!empty($processData->barcode_process))
                        <span style="font-size:7px;">{{ $processData->barcode_process }}</span>
                    @endif
                </td>
            </tr>
            
            <!-- Section B - Row 1 -->
            <tr>
                <td rowspan="3" class="twist-section-label black-bg">B</td>
                <td class="twist-label-cell text-left">TERMINAL</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $processData->terminal_b ?? '-' }}</td>
                <td class="twist-label-cell text-left">NOTE</td>
                <td class="twist-value-cell text-left">{{ $processData->note_b ?? '-' }}</td>
            </tr>
            <!-- Section B - Row 2 -->
            <tr>
                <td class="twist-label-cell text-left">ACC 1</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $processData->acc_1_ab ?? '-' }}</td>
                <td class="twist-label-cell text-left">STRIP</td>
                <td class="twist-value-cell text-left">{{ $processData->strip_b ?? '-' }}</td>
                <td colspan="3" class="twist-label-cell">TO STORE</td>
            </tr>
            <!-- Section B - Row 3 -->
            <tr>
                <td class="twist-label-cell text-left">TUBE</td>
                <td colspan="2" class="twist-value-cell text-left">{{ $processData->tube_b ?? '-' }}</td>
                <td class="twist-label-cell text-left">MARK</td>
                <td class="twist-value-cell text-left">{{ $processData->mark_b ?? '-' }}</td>
                <td colspan="3" class="twist-value-cell">{{ $processData->to_store ?? '-' }}</td>
            </tr>
            
            <!-- Bottom Section - Row 1 -->
            <tr>
                <td colspan="3" rowspan="4" class="twist-qrcode-cell">
                    <div class="twist-qr-label">QRCODE KANBAN</div>
                    @if(isset($shikake->qr_code_path))
                        <img src="{{ $shikake->qr_code_path }}" alt="QR Kanban" class="twist-qr-img">
                    @else
                        <div style="width:13mm;height:13mm;border:1px solid #000;margin:0 auto;font-size:6px;display:flex;align-items:center;justify-content:center;">QR</div>
                    @endif
                </td>
                <td class="twist-label-cell text-left">CV NO</td>
                <td colspan="3" class="twist-value-cell text-left">{{ $shikake->conveyor ?? '-' }}</td>
                <td colspan="2" rowspan="4" class="twist-qrcode-cell">
                    @if(isset($processData->qr_qrcode_drawing_path))
                        <div class="twist-qr-label">QRCODE DRAWING</div>
                        <img src="{{ $processData->qr_qrcode_drawing_path }}" alt="QR Drawing" class="twist-qr-img">
                    @endif
                </td>
            </tr>
            <!-- Bottom Section - Row 2 -->
            <tr>
                <td class="twist-label-cell text-left">FAMILY</td>
                <td colspan="3" class="twist-value-cell text-left">{{ $shikake->family ?? '-' }}</td>
            </tr>
            <!-- Bottom Section - Row 3 -->
            <tr>
                <td class="twist-label-cell text-left">DATE</td>
                <td colspan="3" class="twist-value-cell text-left">
                    {{ $shikake->release_date ? \Carbon\Carbon::parse($shikake->release_date)->format('d-M-y') : '-' }}
                </td>
            </tr>
            <!-- Bottom Section - Row 4 -->
            <tr>
                <td class="twist-label-cell text-left">NOTE</td>
                <td colspan="3" class="twist-value-cell text-left">{{ $shikake->released_note ?? '-' }}</td>
            </tr>
        </tbody>
    </table>
    </div>
</div>
{{-- End of twist-print-wrapper --}}