{{-- SHIELD Process Print Template - Standalone --}}
<style>
/* SHIELD Template Styles - 80mm ≈ 576 dots */
.ticket-shield {
    width: 576px;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
    page-break-after: always;
    background: white;
    padding: 8px;
}

.ticket-shield .header {
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    border: 2px solid #000;
    padding: 6px;
    margin-bottom: 4px;
    background: #f8d7da;
}

.ticket-shield .info-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    margin-bottom: 4px;
}

.ticket-shield .info-table th,
.ticket-shield .info-table td {
    border: 1px solid #000;
    padding: 3px;
    text-align: center;
}

.ticket-shield .info-table th {
    background: #f5c6cb;
    font-weight: bold;
    font-size: 10px;
}

.ticket-shield .detail-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
    margin-bottom: 3px;
}

.ticket-shield .detail-table td {
    border: 1px solid #000;
    padding: 2px 4px;
}

.ticket-shield .detail-table .label {
    font-weight: 600;
    background: #f0f0f0;
    text-align: center;
}

.ticket-shield .detail-table .value {
    text-align: center;
}

.ticket-shield .section-header {
    background: #000;
    color: #fff;
    font-weight: bold;
    text-align: center;
    padding: 3px;
    font-size: 11px;
}

.ticket-shield .barcode-section {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
}

.ticket-shield .barcode-section td {
    border: 1px solid #000;
    padding: 3px;
}

.ticket-shield .barcode-header {
    text-align: center;
    font-weight: bold;
    background: #f5c6cb;
}
</style>

<div class="ticket-shield">
    <div class="header">
        E-KANBAN SHIKAKE - SHIELD
    </div>

    <table class="info-table">
        <thead>
            <tr>
                <th>SHIELD NO</th>
                <th>ADDRESS</th>
                <th>BLADE</th>
                <th>MACHINE</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ $processData->shield_no ?? '' }}</strong></td>
                <td>{{ $processData->address ?? '' }}</td>
                <td>{{ $processData->blade ?? '' }}</td>
                <td>{{ $shikake->machine ?? '' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- CCT/Bonder Pairs Section -->
    <table class="detail-table">
        <tr>
            <td class="label" style="width: 20%;">CCT No 1</td>
            <td class="value" style="width: 30%;">{{ $processData->cct_no_1 ?? '' }}</td>
            <td class="label" style="width: 20%;">Bonder No 1</td>
            <td class="value" style="width: 30%;">{{ $processData->bonder_no_1 ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">CCT No 2</td>
            <td class="value">{{ $processData->cct_no_2 ?? '' }}</td>
            <td class="label">Bonder No 2</td>
            <td class="value">{{ $processData->bonder_no_2 ?? '' }}</td>
        </tr>
    </table>

    <!-- TO Section -->
    <table class="detail-table" style="margin-top: 3px;">
        <tr>
            <td colspan="9" class="section-header">TO DESTINATIONS</td>
        </tr>
        <tr>
            <td class="label" style="width: 11%;">To 1</td>
            <td class="label" style="width: 11%;">To 2</td>
            <td class="label" style="width: 11%;">To 3</td>
            <td class="label" style="width: 11%;">To 4</td>
            <td class="label" style="width: 12%;">To 5</td>
            <td class="label" style="width: 11%;">To 6</td>
            <td class="label" style="width: 11%;">To 7</td>
            <td class="label" style="width: 11%;">To 8</td>
            <td class="label" style="width: 11%;">To 9</td>
        </tr>
        <tr>
            <td class="value">{{ $processData->to_1 ?? '' }}</td>
            <td class="value">{{ $processData->to_2 ?? '' }}</td>
            <td class="value">{{ $processData->to_3 ?? '' }}</td>
            <td class="value">{{ $processData->to_4 ?? '' }}</td>
            <td class="value">{{ $processData->to_5 ?? '' }}</td>
            <td class="value">{{ $processData->to_6 ?? '' }}</td>
            <td class="value">{{ $processData->to_7 ?? '' }}</td>
            <td class="value">{{ $processData->to_8 ?? '' }}</td>
            <td class="value">{{ $processData->to_9 ?? '' }}</td>
        </tr>
    </table>

    <!-- Info Row -->
    <table class="detail-table" style="margin-top: 3px;">
        <tr>
            <td class="label" style="width: 15%;">Conveyor</td>
            <td class="value" style="width: 20%;">{{ $shikake->conveyor ?? '' }}</td>
            <td class="label" style="width: 15%;">Family</td>
            <td class="value" style="width: 15%;">{{ $shikake->family ?? '' }}</td>
            <td class="label" style="width: 10%;">Qty</td>
            <td class="value" style="width: 10%;">{{ $shikake->qty ?? '' }}</td>
            <td class="label" style="width: 10%;">Issue</td>
            <td class="value" style="width: 5%;">{{ $shikake->issue ?? '' }}</td>
        </tr>
    </table>

    <!-- BARCODE SECTION -->
    <table class="barcode-section" style="margin-top: 3px;">
        <tr>
            <td class="barcode-header" style="width: 50%;">BARCODE KANBAN</td>
            <td class="barcode-header" style="width: 50%;">INFO</td>
        </tr>
        <tr>
            <td style="text-align: center; vertical-align: middle; padding: 8px;">
                @if(isset($shikake->qr_code_path))
                    <img src="{{ $shikake->qr_code_path }}" style="width: 100px; height: 100px;" alt="QR Code">
                    <div style="font-size: 9px; margin-top: 2px;">{{ $shikake->barcode_kanban ?? "" }}</div>
                @else
                    <div style="width: 80px; height: 80px; margin: 3px auto; border: 1px solid #000;"></div>
                @endif
            </td>
            <td style="text-align: center; vertical-align: middle; padding: 8px;">
                <div style="font-size: 12px; line-height: 1.5;">
                    <strong>Shield No:</strong> {{ $processData->shield_no ?? '' }}<br>
                    <strong>Blade:</strong> {{ $processData->blade ?? '' }}<br>
                    <strong>Machine:</strong> {{ $shikake->machine ?? '' }}
                </div>
                @if($shikake->released_date)
                    <div style="margin-top: 4px; font-size: 10px;">Released: {{ \Carbon\Carbon::parse($shikake->released_date)->format('d M Y') }}</div>
                @endif
                @if($shikake->released_note)
                    <div style="margin-top: 4px; font-size: 10px;">{{ $shikake->released_note }}</div>
                @endif
            </td>
        </tr>
    </table>
</div>
