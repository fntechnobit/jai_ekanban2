{{-- Standalone E-KANBAN CUTTING TWIST Template --}}
{{-- Container: 120mm x 70mm - Portrait preview, Landscape print --}}
<style>
    /* ========================================
       TWIST Template - Screen Styles (Portrait Preview)
       ======================================== */
    .twist-kanban-container {
        width: 120mm;
        max-width: 120mm;
        height: 70mm;
        background: white;
        margin: 10px auto;
        padding: 0;
        border: 1px solid #ddd;
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
        padding: 1px 2px;
        text-align: center;
        vertical-align: middle;
        line-height: 1.1;
        font-size: 9px;
        height: 4.5mm;
        box-sizing: border-box;
    }
    
    .twist-kanban-container thead th {
        background-color: transparent;
        color: #000;
        font-weight: bold;
        font-size: 11px;
        padding: 4px;
        border: 1px solid #000;
        height: 4.5mm;
    }
    
    .twist-kanban-container .twist-section-label {
        background-color: transparent;
        color: #000;
        font-weight: bold;
        font-size: 10px;
        width: 5.5mm;
        padding: 2px;
    }
    
    .twist-kanban-container .twist-section-label.black-bg {
        background-color: #000;
        color: white;
    }
    
    .twist-kanban-container .twist-label-cell {
        background-color: transparent;
        font-weight: 500;
        font-size: 8px;
    }
    
    .twist-kanban-container .twist-value-cell {
        font-weight: bold;
        font-size: 9px;
    }
    
    .twist-kanban-container .text-left {
        text-align: left !important;
        padding-left: 2px;
    }
    
    .twist-kanban-container .twist-qrcode-cell {
        padding: 2px;
        vertical-align: middle;
        text-align: center;
    }
    
    .twist-kanban-container .twist-qrcode-cell img {
        max-width: 100%;
        height: auto;
    }
    
    .twist-kanban-container .twist-barcode-cell {
        padding: 1px;
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
        font-size: 6px;
        font-weight: bold;
        margin-bottom: 1px;
    }
    
    .twist-kanban-container .twist-qr-img {
        width: 13mm;
        height: 13mm;
    }
    
    /* ========================================
       TWIST Template - Print Styles (Landscape)
       ======================================== */
    @media print {
        @page {
            size: landscape;
            margin: 1mm;
        }
        
        .twist-kanban-container {
            width: 100%;
            max-width: none;
            height: auto;
            margin: 0;
            padding: 0;
            border: none;
            page-break-after: always;
            page-break-inside: avoid;
        }
        
        .twist-kanban-container table {
            width: 100%;
            page-break-inside: avoid;
        }
        
        .twist-kanban-container th,
        .twist-kanban-container td {
            border: 1px solid #000 !important;
            font-size: 9px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .twist-kanban-container thead th {
            font-size: 11px;
        }
        
        .twist-kanban-container .twist-label-cell {
            font-size: 8px;
        }
        
        .twist-kanban-container .twist-value-cell {
            font-size: 9px;
        }
        
        .twist-kanban-container .twist-section-label {
            font-size: 10px;
        }
        
        .twist-kanban-container .twist-section-label.black-bg {
            background-color: #000 !important;
            color: white !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .twist-kanban-container .twist-qr-img {
            width: 15mm;
            height: 15mm;
        }
        
        .twist-kanban-container .twist-barcode-cell img {
            width: 80%;
            max-height: 10mm;
            height: auto;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
    }
</style>

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
                    {{ $shikake->released_date ? \Carbon\Carbon::parse($shikake->released_date)->format('d-M-y') : '-' }}
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
