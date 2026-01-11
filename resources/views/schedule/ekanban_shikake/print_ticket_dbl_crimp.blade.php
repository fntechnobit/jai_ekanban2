{{-- DBL CRIMP Process Print Template - Standalone --}}
<style>
/* DBL CRIMP Template Styles - 80mm ≈ 576 dots */
.ticket-dbl-crimp {
    width: 576px;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
    page-break-after: always;
    background: white;
    padding: 8px;
}

.ticket-dbl-crimp .header {
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    border: 2px solid #000;
    padding: 6px;
    margin-bottom: 4px;
    background: #e2e3e5;
}

.ticket-dbl-crimp .info-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    margin-bottom: 4px;
}

.ticket-dbl-crimp .info-table th,
.ticket-dbl-crimp .info-table td {
    border: 1px solid #000;
    padding: 4px;
    text-align: center;
}

.ticket-dbl-crimp .info-table th {
    background: #d6d8db;
    font-weight: bold;
    font-size: 11px;
}

.ticket-dbl-crimp .detail-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    margin-bottom: 4px;
}

.ticket-dbl-crimp .detail-table td {
    border: 1px solid #000;
    padding: 3px 5px;
}

.ticket-dbl-crimp .detail-table .label {
    font-weight: 600;
    background: #f0f0f0;
    text-align: left;
    width: 25%;
}

.ticket-dbl-crimp .detail-table .value {
    text-align: left;
}

.ticket-dbl-crimp .barcode-section {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
}

.ticket-dbl-crimp .barcode-section td {
    border: 1px solid #000;
    padding: 5px;
}

.ticket-dbl-crimp .barcode-header {
    text-align: center;
    font-weight: bold;
    background: #d6d8db;
}

.ticket-dbl-crimp .summary-text {
    font-size: 14px;
    line-height: 1.6;
}
</style>

<div class="ticket-dbl-crimp">
    <div class="header">
        E-KANBAN SHIKAKE - DBL CRIMP
    </div>

    <table class="info-table">
        <thead>
            <tr>
                <th style="width: 40%;">SHIELD NO</th>
                <th style="width: 30%;">DBL CRIMP</th>
                <th style="width: 30%;">MACHINE</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong style="font-size: 16px;">{{ $processData->shield_no ?? '' }}</strong></td>
                <td><strong style="font-size: 16px;">{{ $processData->dbl_crimp ?? '' }}</strong></td>
                <td><strong>{{ $shikake->machine ?? '' }}</strong></td>
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
            <td class="label">Qty</td>
            <td class="value">{{ $shikake->qty ?? '' }}</td>
            <td class="label">Issue</td>
            <td class="value">{{ $shikake->issue ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Sequence</td>
            <td class="value">{{ $shikake->sequence ?? '' }}</td>
            <td class="label">Released Note</td>
            <td class="value">{{ $shikake->released_note ?? '' }}</td>
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
                    <div><strong>Shield No:</strong> {{ $processData->shield_no ?? '-' }}</div>
                    <div><strong>DBL Crimp:</strong> {{ $processData->dbl_crimp ?? '-' }}</div>
                    <div><strong>Machine:</strong> {{ $shikake->machine ?? '-' }}</div>
                    <div><strong>Conveyor:</strong> {{ $shikake->conveyor ?? '-' }}</div>
                    <div><strong>Family:</strong> {{ $shikake->family ?? '-' }}</div>
                    <div><strong>Qty:</strong> {{ $shikake->qty ?? 0 }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>
