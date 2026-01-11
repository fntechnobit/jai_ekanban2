{{-- JOINT Process Print Template - Standalone --}}
<style>
/* JOINT Template Styles - 80mm ≈ 576 dots */
.ticket-joint {
    width: 576px;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
    page-break-after: always;
    background: white;
    padding: 8px;
}

.ticket-joint .header {
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    border: 2px solid #000;
    padding: 6px;
    margin-bottom: 4px;
    background: #d1ecf1;
}

.ticket-joint .info-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    margin-bottom: 4px;
}

.ticket-joint .info-table th,
.ticket-joint .info-table td {
    border: 1px solid #000;
    padding: 3px;
    text-align: center;
}

.ticket-joint .info-table th {
    background: #bee5eb;
    font-weight: bold;
    font-size: 10px;
}

.ticket-joint .detail-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
    margin-bottom: 3px;
}

.ticket-joint .detail-table td {
    border: 1px solid #000;
    padding: 2px 4px;
}

.ticket-joint .detail-table .label {
    font-weight: 600;
    background: #f0f0f0;
    text-align: center;
}

.ticket-joint .detail-table .value {
    text-align: center;
}

.ticket-joint .section-header {
    background: #000;
    color: #fff;
    font-weight: bold;
    text-align: center;
    padding: 3px;
    font-size: 11px;
}

.ticket-joint .barcode-section {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
}

.ticket-joint .barcode-section td {
    border: 1px solid #000;
    padding: 3px;
}

.ticket-joint .barcode-header {
    text-align: center;
    font-weight: bold;
    background: #bee5eb;
}
</style>

<div class="ticket-joint">
    <div class="header">
        E-KANBAN SHIKAKE - JOINT
    </div>

    <table class="info-table">
        <thead>
            <tr>
                <th>BONDER NO</th>
                <th>ADDRESS</th>
                <th>ADDRESS STORE</th>
                <th>TO MACHINE</th>
                <th>MACHINE</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ $processData->bonder_no ?? '' }}</strong></td>
                <td>{{ $processData->address ?? '' }}</td>
                <td>{{ $processData->address_store ?? '' }}</td>
                <td>{{ $processData->to_machine ?? '' }}</td>
                <td>{{ $shikake->machine ?? '' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- CCT/Bonder Pairs Section -->
    <table class="detail-table">
        <tr>
            <td colspan="4" class="section-header">CCT / BONDER PAIRS</td>
        </tr>
        <tr>
            <td class="label" style="width: 25%;">CCT No 1</td>
            <td class="value" style="width: 25%;">{{ $processData->cct_no_1 ?? '' }}</td>
            <td class="label" style="width: 25%;">Bonder No 1</td>
            <td class="value" style="width: 25%;">{{ $processData->bonder_no_1 ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">CCT No 2</td>
            <td class="value">{{ $processData->cct_no_2 ?? '' }}</td>
            <td class="label">Bonder No 2</td>
            <td class="value">{{ $processData->bonder_no_2 ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">CCT No 3</td>
            <td class="value">{{ $processData->cct_no_3 ?? '' }}</td>
            <td class="label">Bonder No 3</td>
            <td class="value">{{ $processData->bonder_no_3 ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">CCT No 4</td>
            <td class="value">{{ $processData->cct_no_4 ?? '' }}</td>
            <td class="label">Bonder No 4</td>
            <td class="value">{{ $processData->bonder_no_4 ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">CCT No 5</td>
            <td class="value">{{ $processData->cct_no_5 ?? '' }}</td>
            <td class="label">Bonder No 5</td>
            <td class="value">{{ $processData->bonder_no_5 ?? '' }}</td>
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
            <td class="barcode-header" style="width: 50%;">BARCODE PROCESS</td>
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
                @if(isset($processData->barcode_process_path))
                    <img src="{{ $processData->barcode_process_path }}" style="max-width: 140px; height: auto;" alt="Barcode">
                    <div style="font-size: 9px; margin-top: 2px;">{{ $processData->barcode_process ?? "" }}</div>
                @else
                    <div style="width: 120px; height: 60px; margin: 3px auto; border: 1px solid #000;"></div>
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center; font-size: 10px;">
                {{ $processData->released_date ? 'Released: ' . \Carbon\Carbon::parse($processData->released_date)->format('d M Y') : '' }}
                @if($shikake->released_note)
                    | {{ $shikake->released_note }}
                @endif
            </td>
        </tr>
    </table>
</div>
