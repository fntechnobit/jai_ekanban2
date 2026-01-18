{{-- Standalone E-KANBAN CUTTING TWIST Template - PREVIEW VERSION --}}
{{-- Container: 576px width - For screen display in preview modal --}}
<style>
    /* ========================================
       TWIST Preview Template - Wrapper for Image + Ticket
       ======================================== */
    .twist-preview-wrapper {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        gap: 0;
        margin: 15px auto;
        justify-content: center;
        align-items: flex-start;
    }
    
    .twist-preview-wrapper .shikake-image-section {
        flex-shrink: 0;
    }
    
    .twist-preview-wrapper .shikake-image-section img {
        height: 420px;
        width: auto;
        object-fit: contain;
        border: 1px solid #ccc;
    }
    
    /* ========================================
       TWIST Preview Template - Screen Styles (576px)
       ======================================== */
    .twist-preview-container {
        width: 576px;
        min-width: 576px;
        flex-shrink: 0;
        background: white;
        padding: 0;
        border: 1px solid #ddd;
        overflow: hidden;
        font-family: Arial, sans-serif;
    }
    
    .twist-preview-container table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    
    .twist-preview-container th,
    .twist-preview-container td {
        border: 1px solid #000;
        padding: 4px 6px;
        text-align: center;
        vertical-align: middle;
        line-height: 1.2;
        font-size: 14px;
    }
    
    .twist-preview-container thead th {
        background-color: #f0f0f0;
        color: #000;
        font-weight: bold;
        font-size: 18px;
        padding: 10px;
        border: 2px solid #000;
    }
    
    .twist-preview-container .twist-section-label {
        background-color: transparent;
        color: #000;
        font-weight: bold;
        font-size: 16px;
        width: 30px;
        padding: 4px;
    }
    
    .twist-preview-container .twist-section-label.black-bg {
        background-color: #000;
        color: white;
    }
    
    .twist-preview-container .twist-label-cell {
        background-color: #f5f5f5;
        font-weight: 600;
        font-size: 12px;
    }
    
    .twist-preview-container .twist-value-cell {
        font-weight: bold;
        font-size: 14px;
    }
    
    .twist-preview-container .text-left {
        text-align: left !important;
        padding-left: 6px;
    }
    
    .twist-preview-container .twist-qrcode-cell {
        padding: 8px;
        vertical-align: middle;
        text-align: center;
    }
    
    .twist-preview-container .twist-qrcode-cell img {
        max-width: 100%;
        height: auto;
    }
    
    .twist-preview-container .twist-barcode-cell {
        padding: 4px;
        vertical-align: middle;
        text-align: center;
    }
    
    .twist-preview-container .twist-barcode-cell img {
        max-width: 90%;
        width: auto;
        height: 40px;
    }
    
    .twist-preview-container .twist-qr-label {
        font-size: 10px;
        font-weight: bold;
        margin-bottom: 4px;
    }
    
    .twist-preview-container .twist-qr-img {
        width: 100px;
        height: 100px;
    }
</style>

{{-- Wrapper with flexbox for image + ticket --}}
<div class="twist-preview-wrapper">
    {{-- Image section (LEFT) - only if image exists --}}
    @if(!empty($shikake->image_path))
    <div class="shikake-image-section">
        <img src="{{ asset($shikake->image_path) }}" alt="Shikake Image">
    </div>
    @endif
    
    {{-- Ticket section (RIGHT) --}}
    <div class="twist-preview-container">
    <table>
        <colgroup>
            <col style="width: 5%">   {{-- SEQ --}}
            <col style="width: 12%">  {{-- SA --}}
            <col style="width: 10%">  {{-- DATE --}}
            <col style="width: 12%">  {{-- SHIKAKE --}}
            <col style="width: 12%">  {{-- CIRCUIT --}}
            <col style="width: 12%">  {{-- COLOR --}}
            <col style="width: 12%">  {{-- SIZE --}}
            <col style="width: 12%">  {{-- LENGTH --}}
            <col style="width: 13%">  {{-- QR --}}
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
                        <span style="font-size:10px;">{{ $processData->barcode_navigasi ?? '-' }}</span>
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
                    @if(isset($processData->barcode_process_path))
                        <img src="{{ $processData->barcode_process_path }}" alt="Barcode">
                    @else
                        <span style="font-size:10px;">{{ $processData->barcode_process ?? '-' }}</span>
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
                        <div style="width:100px;height:100px;border:1px solid #000;margin:0 auto;font-size:10px;display:flex;align-items:center;justify-content:center;">QR</div>
                    @endif
                </td>
                <td class="twist-label-cell text-left">CV NO</td>
                <td colspan="3" class="twist-value-cell text-left">{{ $shikake->conveyor ?? '-' }}</td>
                <td colspan="2" rowspan="4" class="twist-qrcode-cell">
                    @if(!empty($processData->barcode_shikake))
                        <div class="twist-qr-label">QRCODE SHIKAKE</div>
                        @if(isset($processData->barcode_shikake_path))
                            <img src="{{ $processData->barcode_shikake_path }}" alt="QR Shikake" class="twist-qr-img">
                        @endif
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
</div>
{{-- End of twist-preview-wrapper --}}
