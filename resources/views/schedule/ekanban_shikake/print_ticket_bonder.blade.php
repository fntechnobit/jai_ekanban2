{{-- BONDER Process Print Template - Standalone --}}
<style>
/* BONDER Template Styles - 80mm ≈ 576 dots */
.ticket-bonder {
    width: 576px;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
    page-break-after: always;
    background: white;
    padding: 8px;
}

.ticket-bonder .header {
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    border: 2px solid #000;
    padding: 6px;
    margin-bottom: 4px;
    background: #fff3cd;
}

.ticket-bonder .info-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    margin-bottom: 4px;
}

.ticket-bonder .info-table th,
.ticket-bonder .info-table td {
    border: 1px solid #000;
    padding: 3px;
    text-align: center;
}

.ticket-bonder .info-table th {
    background: #ffeeba;
    font-weight: bold;
    font-size: 10px;
}

.ticket-bonder .detail-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
    margin-bottom: 3px;
}

.ticket-bonder .detail-table td {
    border: 1px solid #000;
    padding: 2px 4px;
}

.ticket-bonder .detail-table .label {
    font-weight: 600;
    background: #f0f0f0;
    text-align: center;
}

.ticket-bonder .detail-table .value {
    text-align: center;
}

.ticket-bonder .section-a {
    background: #000;
    color: #fff;
    font-weight: bold;
    text-align: center;
    padding: 3px;
    font-size: 11px;
}

.ticket-bonder .section-b {
    background: #555;
    color: #fff;
    font-weight: bold;
    text-align: center;
    padding: 3px;
    font-size: 11px;
}

.ticket-bonder .barcode-section {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
}

.ticket-bonder .barcode-section td {
    border: 1px solid #000;
    padding: 3px;
}

.ticket-bonder .barcode-header {
    text-align: center;
    font-weight: bold;
    background: #ffeeba;
}
</style>

<div class="ticket-bonder">
    <div class="header">
        E-KANBAN SHIKAKE - BONDER
    </div>

    <table class="info-table">
        <thead>
            <tr>
                <th>BONDER NO</th>
                <th>ADDRESS</th>
                <th>DIES</th>
                <th>TO MACHINE</th>
                <th>MACHINE</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ $processData->bonder_no ?? '' }}</strong></td>
                <td>{{ $processData->address ?? '' }}</td>
                <td>{{ $processData->dies ?? '' }}</td>
                <td>{{ $processData->to_machine ?? '' }}</td>
                <td>{{ $shikake->machine ?? '' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Section A - Side A CCT/Bonder Pairs -->
    <table class="detail-table">
        <tr>
            <td colspan="8" class="section-a">SIDE A</td>
        </tr>
        <tr>
            <td class="label" style="width: 12%;">CCT No 1</td>
            <td class="value" style="width: 13%;">{{ $processData->cct_no_a_1 ?? '' }}</td>
            <td class="label" style="width: 12%;">Bonder 1</td>
            <td class="value" style="width: 13%;">{{ $processData->bonder_no_a_1 ?? '' }}</td>
            <td class="label" style="width: 12%;">CCT No 5</td>
            <td class="value" style="width: 13%;">{{ $processData->cct_no_a_5 ?? '' }}</td>
            <td class="label" style="width: 12%;">Bonder 5</td>
            <td class="value" style="width: 13%;">{{ $processData->bonder_no_a_5 ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">CCT No 2</td>
            <td class="value">{{ $processData->cct_no_a_2 ?? '' }}</td>
            <td class="label">Bonder 2</td>
            <td class="value">{{ $processData->bonder_no_a_2 ?? '' }}</td>
            <td class="label">CCT No 6</td>
            <td class="value">{{ $processData->cct_no_a_6 ?? '' }}</td>
            <td class="label">Bonder 6</td>
            <td class="value">{{ $processData->bonder_no_a_6 ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">CCT No 3</td>
            <td class="value">{{ $processData->cct_no_a_3 ?? '' }}</td>
            <td class="label">Bonder 3</td>
            <td class="value">{{ $processData->bonder_no_a_3 ?? '' }}</td>
            <td class="label">CCT No 7</td>
            <td class="value">{{ $processData->cct_no_a_7 ?? '' }}</td>
            <td class="label">Bonder 7</td>
            <td class="value">{{ $processData->bonder_no_a_7 ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">CCT No 4</td>
            <td class="value">{{ $processData->cct_no_a_4 ?? '' }}</td>
            <td class="label">Bonder 4</td>
            <td class="value">{{ $processData->bonder_no_a_4 ?? '' }}</td>
            <td colspan="4"></td>
        </tr>
    </table>

    <!-- Section B - Side B CCT/Bonder Pairs -->
    <table class="detail-table" style="margin-top: 3px;">
        <tr>
            <td colspan="8" class="section-b">SIDE B</td>
        </tr>
        <tr>
            <td class="label" style="width: 12%;">CCT No 1</td>
            <td class="value" style="width: 13%;">{{ $processData->cct_no_b_1 ?? '' }}</td>
            <td class="label" style="width: 12%;">Bonder 1</td>
            <td class="value" style="width: 13%;">{{ $processData->bonder_no_b_1 ?? '' }}</td>
            <td class="label" style="width: 12%;">CCT No 5</td>
            <td class="value" style="width: 13%;">{{ $processData->cct_no_b_5 ?? '' }}</td>
            <td class="label" style="width: 12%;">Bonder 5</td>
            <td class="value" style="width: 13%;">{{ $processData->bonder_no_b_5 ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">CCT No 2</td>
            <td class="value">{{ $processData->cct_no_b_2 ?? '' }}</td>
            <td class="label">Bonder 2</td>
            <td class="value">{{ $processData->bonder_no_b_2 ?? '' }}</td>
            <td class="label">CCT No 6</td>
            <td class="value">{{ $processData->cct_no_b_6 ?? '' }}</td>
            <td class="label">Bonder 6</td>
            <td class="value">{{ $processData->bonder_no_b_6 ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">CCT No 3</td>
            <td class="value">{{ $processData->cct_no_b_3 ?? '' }}</td>
            <td class="label">Bonder 3</td>
            <td class="value">{{ $processData->bonder_no_b_3 ?? '' }}</td>
            <td class="label">CCT No 7</td>
            <td class="value">{{ $processData->cct_no_b_7 ?? '' }}</td>
            <td class="label">Bonder 7</td>
            <td class="value">{{ $processData->bonder_no_b_7 ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">CCT No 4</td>
            <td class="value">{{ $processData->cct_no_b_4 ?? '' }}</td>
            <td class="label">Bonder 4</td>
            <td class="value">{{ $processData->bonder_no_b_4 ?? '' }}</td>
            <td colspan="4"></td>
        </tr>
    </table>

    <!-- Info Row -->
    <table class="detail-table" style="margin-top: 3px;">
        <tr>
            <td class="label" style="width: 15%;">Conveyor</td>
            <td class="value" style="width: 20%;">{{ $shikake->conveyor ?? '' }}</td>
            <td class="label" style="width: 15%;">Qty</td>
            <td class="value" style="width: 15%;">{{ $shikake->qty ?? '' }}</td>
            <td class="label" style="width: 15%;">Issue</td>
            <td class="value" style="width: 20%;">{{ $shikake->issue ?? '' }}</td>
        </tr>
    </table>

    <!-- BARCODE SECTION -->
    <table class="barcode-section" style="margin-top: 3px;">
        <tr>
            <td class="barcode-header" style="width: 35%;">QRCODE KANBAN</td>
            <td style="text-align: left; width: 30%;">{{ $shikake->family ?? '' }}</td>
            <td class="barcode-header" style="width: 35%;">BARCODE NAVIGASI</td>
        </tr>
        <tr>
            <td rowspan="3" style="text-align: center; vertical-align: middle; padding: 5px;">
                @if(isset($shikake->qr_code_path))
                    <img src="{{ $shikake->qr_code_path }}" style="width: 80px; height: 80px;" alt="QR Code">
                    <div style="font-size: 9px; margin-top: 2px;">{{ $shikake->barcode_kanban ?? "" }}</div>
                @else
                    <div style="width: 70px; height: 70px; margin: 3px auto; border: 1px solid #000;"></div>
                @endif
            </td>
            <td style="text-align: left; font-size: 10px;">{{ $shikake->conveyor ? 'CV ' . $shikake->conveyor : '' }}</td>
            <td rowspan="3" style="text-align: center; vertical-align: middle; padding: 5px;">
                @if(isset($processData->barcode_navigasi_path))
                    <img src="{{ $processData->barcode_navigasi_path }}" style="max-width: 120px; height: auto;" alt="Barcode">
                    <div style="font-size: 9px; margin-top: 2px;">{{ $processData->barcode_navigasi ?? "" }}</div>
                @else
                    <div style="width: 100px; height: 50px; margin: 3px auto; border: 1px solid #000;"></div>
                @endif
            </td>
        </tr>
        <tr>
            <td style="text-align: left; font-size: 10px;">{{ $shikake->released_date ? \Carbon\Carbon::parse($shikake->released_date)->format('d M Y') : '' }}</td>
        </tr>
        <tr>
            <td style="text-align: left; font-size: 10px;">{{ $shikake->released_note ?? '' }}</td>
        </tr>
    </table>
</div>
