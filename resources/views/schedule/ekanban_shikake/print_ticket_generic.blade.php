{{-- Generic Shikake Process Print Template (Fallback) --}}
<style>
/* PRINT AREA — 80mm ≈ 576 dots */
.ticket-wrap-generic {
    width: 576px;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

.ticket-generic {
    page-break-after: always;
    background: white;
    padding: 8px;
}

.ticket-generic .header {
    text-align: center;
    font-size: 22px;
    font-weight: bold;
    border: 2px solid #000;
    padding: 8px;
    margin-bottom: 5px;
}

.ticket-generic .ptbl {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.ticket-generic .ptbl th,
.ticket-generic .ptbl td {
    border: 1px solid #000;
    padding: 5px;
    font-size: 16px;
    font-weight: 600;
    vertical-align: top;
}

.ticket-generic .ptbl thead th {
    text-align: center;
    font-weight: 800;
    font-size: 24px;
    background: #f0f0f0;
}

.ticket-generic .info-row {
    font-size: 14px;
}

.ticket-generic .label {
    font-weight: bold;
    background: #f0f0f0;
}
</style>

<div class="ticket ticket-wrap-generic ticket-generic">
    <div class="header">
        E-KANBAN SHIKAKE - {{ $shikake->process ?? 'UNKNOWN' }}
    </div>

    <table class="ptbl">
        <thead>
            <tr>
                <th colspan="3">{{ $shikake->family ?? 'N/A' }}</th>
            </tr>
        </thead>
        <tbody>
            <tr class="info-row">
                <td class="label">Conveyor</td>
                <td colspan="2">{{ $shikake->conveyor ?? 'N/A' }}</td>
            </tr>
            <tr class="info-row">
                <td class="label">Assy</td>
                <td>{{ $shikake->assy ?? 'N/A' }}</td>
                <td>{{ isset($shikake->schedule) ? date('d M Y', strtotime($shikake->schedule)) : 'N/A' }}</td>
            </tr>
            <tr class="info-row">
                <td class="label">Shift</td>
                <td>{{ $shikake->shift ?? 'N/A' }}</td>
                <td>Qty: {{ $shikake->qty ?? 0 }}</td>
            </tr>
            @if(isset($shikake->pallet_count))
            <tr>
                <td colspan="3" style="text-align:center; font-size: 18px;">
                    <strong>Pallet: {{ $shikake->pallet_count }}</strong>
                </td>
            </tr>
            @endif
            @if(isset($shikake->machine))
            <tr>
                <td colspan="3" style="text-align:center; font-size: 18px;">
                    <strong>Machine: {{ $shikake->machine }}</strong>
                </td>
            </tr>
            @endif
            @if(isset($shikake->barcode_kanban))
            <tr>
                <td colspan="3" style="text-align:center;">
                    <strong>Barcode:</strong> {{ $shikake->barcode_kanban }}
                </td>
            </tr>
            @endif
        </tbody>
    </table>
</div>
