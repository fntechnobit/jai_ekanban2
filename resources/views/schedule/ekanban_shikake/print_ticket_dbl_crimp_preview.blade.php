{{-- DBL CRIMP Process Print Template - PREVIEW VERSION --}}
<style>
/* DBL CRIMP Preview Template - Wrapper for Image + Ticket */
.dbl-crimp-preview-wrapper {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 0;
    margin: 15px auto;
    justify-content: center;
    align-items: flex-start;
}

.dbl-crimp-preview-wrapper .shikake-image-section {
    flex-shrink: 0;
}

.dbl-crimp-preview-wrapper .shikake-image-section img {
    height: 300px;
    width: auto;
    object-fit: contain;
    border: 1px solid #ccc;
    border-radius: 4px;
}

/* DBL CRIMP Template Styles - 80mm ≈ 576 dots */
.ticket-dbl-crimp-preview {
    width: 576px;
    min-width: 576px;
    flex-shrink: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
    background: white;
    padding: 8px;
    border: 1px solid #ddd;
}

.ticket-dbl-crimp-preview .header {
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    border: 2px solid #000;
    padding: 6px;
    margin-bottom: 4px;
    background: #e2e3e5;
}

.ticket-dbl-crimp-preview .info-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    margin-bottom: 4px;
}

.ticket-dbl-crimp-preview .info-table th,
.ticket-dbl-crimp-preview .info-table td {
    border: 1px solid #000;
    padding: 4px;
    text-align: center;
}

.ticket-dbl-crimp-preview .info-table th {
    background: #d6d8db;
    font-weight: bold;
    font-size: 11px;
}

.ticket-dbl-crimp-preview .detail-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    margin-bottom: 4px;
}

.ticket-dbl-crimp-preview .detail-table td {
    border: 1px solid #000;
    padding: 3px 5px;
}

.ticket-dbl-crimp-preview .detail-table .label {
    font-weight: 600;
    background: #f0f0f0;
    text-align: left;
    width: 25%;
}

.ticket-dbl-crimp-preview .detail-table .value {
    text-align: left;
}

.ticket-dbl-crimp-preview .barcode-section {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
}

.ticket-dbl-crimp-preview .barcode-section td {
    border: 1px solid #000;
    padding: 5px;
}

.ticket-dbl-crimp-preview .barcode-header {
    text-align: center;
    font-weight: bold;
    background: #d6d8db;
}

.ticket-dbl-crimp-preview .summary-text {
    font-size: 14px;
    line-height: 1.6;
}

.ticket-dbl-crimp-preview .cct-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
    margin-bottom: 4px;
}

.ticket-dbl-crimp-preview .cct-table th,
.ticket-dbl-crimp-preview .cct-table td {
    border: 1px solid #000;
    padding: 3px;
    text-align: center;
}

.ticket-dbl-crimp-preview .cct-table th {
    background: #d6d8db;
    font-weight: bold;
}
</style>

{{-- Wrapper with flexbox for image + ticket --}}
<div class="dbl-crimp-preview-wrapper">
    {{-- Image section (LEFT) - only if image exists --}}
    @if(!empty($shikake->image_path))
    <div class="shikake-image-section">
        <img src="{{ asset($shikake->image_path) }}" alt="Shikake Image">
    </div>
    @endif
    
    {{-- Ticket section (RIGHT) --}}
    <div class="ticket-dbl-crimp-preview">
    <div class="header">
        E-KANBAN SHIKAKE - DBL CRIMP
    </div>

    <table class="info-table">
        <thead>
            <tr>
                <th style="width: 30%;">DRAWING NO</th>
                <th style="width: 25%;">ADDRESS</th>
                <th style="width: 25%;">BARCODE MESIN</th>
                <th style="width: 20%;">TO MACHINE</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong style="font-size: 14px;">{{ $processData->drawing_no ?? '' }}</strong></td>
                <td><strong style="font-size: 14px;">{{ $processData->address ?? '' }}</strong></td>
                <td><strong style="font-size: 12px;">{{ $processData->barcode_mesin ?? '' }}</strong></td>
                <td><strong>{{ $processData->to_machine ?? '' }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- CCT & Address Section -->
    <table class="cct-table">
        <thead>
            <tr>
                <th>CCT No 1</th>
                <th>Address 1</th>
                <th>CCT No 2</th>
                <th>Address 2</th>
                <th>CCT No 3</th>
                <th>Address 3</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $processData->cct_no_1 ?? '' }}</td>
                <td>{{ $processData->address_1 ?? '' }}</td>
                <td>{{ $processData->cct_no_2 ?? '' }}</td>
                <td>{{ $processData->address_2 ?? '' }}</td>
                <td>{{ $processData->cct_no_3 ?? '' }}</td>
                <td>{{ $processData->address_3 ?? '' }}</td>
            </tr>
        </tbody>
    </table>
    <table class="cct-table">
        <thead>
            <tr>
                <th>CCT No 4</th>
                <th>Address 4</th>
                <th>CCT No 5</th>
                <th>Address 5</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $processData->cct_no_4 ?? '' }}</td>
                <td>{{ $processData->address_4 ?? '' }}</td>
                <td>{{ $processData->cct_no_5 ?? '' }}</td>
                <td>{{ $processData->address_5 ?? '' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Info Section -->
    <table class="detail-table">
        <tr>
            <td class="label">Conveyor</td>
            <td class="value" style="width: 25%;">{{ $shikake->conveyor ?? '' }}</td>
            <td class="label">Family</td>
            <td class="value" style="width: 25%;">{{ $shikake->family ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Machine</td>
            <td class="value">{{ $shikake->machine ?? '' }}</td>
            <td class="label">Issue</td>
            <td class="value">{{ $shikake->issue ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Qty</td>
            <td class="value">{{ $shikake->qty ?? '' }}</td>
            <td class="label">Sequence</td>
            <td class="value">{{ $shikake->sequence ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Released Date</td>
            <td class="value">{{ $shikake->released_date ? \Carbon\Carbon::parse($shikake->released_date)->format('d M Y') : '' }}</td>
            <td class="label" colspan="1">Released Note</td>
            <td class="value" colspan="1">{{ $shikake->released_note ?? '' }}</td>
        </tr>
    </table>

    <!-- BARCODE SECTION -->
    <table class="barcode-section">
        <tr>
            <td class="barcode-header" style="width: 50%;">BARCODE KANBAN</td>
            <td class="barcode-header" style="width: 50%;">SUMMARY</td>
        </tr>
        <tr>
            <td style="text-align: center; vertical-align: middle; padding: 12px;">
                @if(isset($shikake->qr_code_path))
                    <img src="{{ $shikake->qr_code_path }}" style="width: 120px; height: 120px;" alt="QR Code">
                    <div style="font-size: 11px; margin-top: 4px; font-weight: bold;">{{ $shikake->barcode_kanban ?? "" }}</div>
                @else
                    <div style="width: 100px; height: 100px; margin: 8px auto; border: 1px solid #000;"></div>
                @endif
            </td>
            <td style="text-align: left; vertical-align: middle; padding: 12px;">
                <div class="summary-text">
                    <div><strong>Drawing No:</strong> {{ $processData->drawing_no ?? '-' }}</div>
                    <div><strong>Address:</strong> {{ $processData->address ?? '-' }}</div>
                    <div><strong>Barcode Mesin:</strong> {{ $processData->barcode_mesin ?? '-' }}</div>
                    <div><strong>To Machine:</strong> {{ $processData->to_machine ?? '-' }}</div>
                    <div><strong>Conveyor:</strong> {{ $shikake->conveyor ?? '-' }}</div>
                    <div><strong>Family:</strong> {{ $shikake->family ?? '-' }}</div>
                    <div><strong>Qty:</strong> {{ $shikake->qty ?? 0 }}</div>
                </div>
            </td>
        </tr>
    </table>
    </div>
</div>
{{-- End of dbl-crimp-preview-wrapper --}}
