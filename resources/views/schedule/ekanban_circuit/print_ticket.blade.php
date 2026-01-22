<style>
  /* PRINT AREA — Portrait format */
  .ticket-wrap {
    width: 576px;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
  }

  .ticket {
    page-break-after: always;
    background: white;
    padding: 8px;
  }

  .ticket-header {
    text-align: center;
    font-size: 24px;
    font-weight: bold;
    border: 2px solid #000;
    padding: 8px;
    margin-bottom: 5px;
  }

  .info-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
    margin-bottom: 5px;
  }

  .info-table th,
  .info-table td {
    border: 1px solid #000;
    padding: 4px;
    text-align: center;
  }

  .info-table th {
    background: #f0f0f0;
    font-weight: bold;
    font-size: 12px;
  }

  .detail-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    margin-bottom: 3px;
  }

  .detail-table td {
    border: 1px solid #000;
    padding: 3px 5px;
  }

  .detail-table .label {
    font-weight: 600;
    background: #f0f0f0;
    text-align: left;
  }

  .detail-table .value {
    text-align: left;
  }

  /* Width classes for columns */
  .label-col {
    width: 60px;
  }

  .value-col {
    width: 100px;
  }

  .middle-label-col {
    width: 50px;
  }

  .right-label-col {
    width: 60px;
  }

  .section-header {
    background: #000;
    color: #fff;
    font-weight: bold;
    text-align: center;
    padding: 5px;
    font-size: 18px;
  }

  .terminal-section {
    border: 2px solid #000;
    margin-bottom: 5px;
  }

  .terminal-header {
    background: #e0e0e0;
    font-weight: bold;
    padding: 4px;
    font-size: 14px;
    text-align: center;
  }

  .accessories-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
  }

  .accessories-table td {
    border: 1px solid #000;
    padding: 3px;
  }

  .accessories-label {
    width: 35%;
    font-weight: 600;
  }

  .accessories-value {
    width: 65%;
  }

  .barcode-section {
    text-align: center;
    border: 2px solid #000;
    padding: 10px;
    margin-top: 5px;
  }

  .barcode-text {
    font-family: 'Libre Barcode 128 Text', cursive;
    font-size: 48px;
    margin: 5px 0;
  }

  .footer-info {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    margin-top: 5px;
    padding: 5px;
    border: 1px solid #000;
  }

  .old-note,
  .new-note {
    background: #ffeb3b;
    color: #000;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 3px;
  }

  .gold-text {
    color: #d4af37;
    font-weight: bold;
  }
</style>

<div id="print_stack_ajax">
  @foreach($circuits as $circuit)
  <div class="ticket-wrap ticket">
    <div class="ticket-header">
      E-KANBAN CUTTING - {{ $circuit->carline ?? '' }}
    </div>

    <table class="info-table">
      <thead>
        <tr>
          <th>CCT NO</th>
          <th>CUST NO</th>
          <th>Kind</th>
          <th>Size</th>
          <th>Colour</th>
          <th>C/L</th>
          <th>SEQ</th>
          <th>MACHINE</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>{{ $circuit->cct_no ?? '' }}</strong></td>
          <td><strong>{{ $circuit->cust_no ?? '' }}</strong></td>
          <td>{{ $circuit->kind ?? '' }}</td>
          <td>{{ $circuit->size ?? '' }}</td>
          <td>{{ $circuit->col ?? '' }}</td>
          <td>{{ $circuit->cl ?? '' }}</td>
          <td>{{ $circuit->sequence ?? '' }}</td>
          <td>{{ $circuit->machine ?? '' }}</td>
        </tr>
      </tbody>
    </table>

    <!-- Section A and B Combined -->
    <table class="detail-table">
      <tr>
        <td rowspan="4" class="section-header" style="width: 30px; background: #fff; color: #000;">A</td>
        <td class="label label-col">Terminal</td>
        <td class="value value-col">{{ $circuit->terminal_1 ?? '' }}</td>
        <td class="label middle-label-col">{{ $circuit->gold_1 ?? '' }}</td>
        <td class="label middle-label-col" style="text-align: center; font-weight: bold;">{{ $circuit->note_1 ?? '' }}</td>
        <td class="label right-label-col">To 1</td>
        <td class="value value-col">{{ $circuit->t01 ?? '' }}</td>
      </tr>
      <tr>
        <td class="label label-col">Acc 1</td>
        <td class="value value-col">{{ $circuit->acc_1 ?? '' }}</td>
        <td class="label middle-label-col">Note</td>
        <td class="value middle-label-col">{{ $circuit->ta ?? '' }}</td>
        <td class="label right-label-col">To 2</td>
        <td class="value value-col">{{ $circuit->t02 ?? '' }}</td>
      </tr>
      <tr>
        <td class="label label-col">Acc 2</td>
        <td class="value value-col">{{ $circuit->acc_1a ?? '' }}</td>
        <td class="label middle-label-col">Strip</td>
        <td class="value middle-label-col">{{ $circuit->strip_1 ?? '' }}</td>
        <td class="label right-label-col">To 3</td>
        <td class="value value-col">{{ $circuit->t03 ?? '' }}</td>
      </tr>
      <tr>
        <td class="label label-col">Tube</td>
        <td class="value value-col">{{ $circuit->tube_1 ?? '' }}</td>
        <td class="label middle-label-col">Mark</td>
        <td class="value middle-label-col">{{ $circuit->mark_1 ?? '' }}</td>
        <td class="label right-label-col">To</td>
        <td class="value value-col">{{ $circuit->address ?? '' }}</td>
      </tr>
      
      <tr>
        <td rowspan="4" class="section-header" style="width: 30px; background: #000; color: #fff;">B</td>
        <td class="label label-col">Terminal</td>
        <td class="value value-col">{{ $circuit->terminal_2 ?? '' }}</td>
        <td class="label middle-label-col">{{ $circuit->gold_2 ?? '' }}</td>
        <td class="label middle-label-col" style="text-align: center; font-weight: bold;">{{ $circuit->note_2 ?? '' }}</td>
        <td class="label right-label-col">Store</td>
        <td class="value value-col"></td>
      </tr>
      <tr>
        <td class="label label-col">Acc 1</td>
        <td class="value value-col">{{ $circuit->acc_2 ?? '' }}</td>
        <td class="label middle-label-col">Note</td>
        <td class="value middle-label-col">{{ $circuit->tb ?? '' }}</td>
        <td class="label right-label-col">Cct Code</td>
        <td class="value value-col">{{ $circuit->cct_code ?? '' }}</td>
      </tr>
      <tr>
        <td class="label label-col">Acc 2</td>
        <td class="value value-col">{{ $circuit->acc_2a ?? '' }}</td>
        <td class="label middle-label-col">Strip</td>
        <td class="value middle-label-col">{{ $circuit->strip_2 ?? '' }}</td>
        <td class="label right-label-col">Qty</td>
        <td class="value value-col">{{ $circuit->qty ?? '' }}</td>
      </tr>
      <tr>
        <td class="label label-col">Tube</td>
        <td class="value value-col">{{ $circuit->tube_2 ?? '' }}</td>
        <td class="label middle-label-col">Mark</td>
        <td class="value middle-label-col">{{ $circuit->mark_2 ?? '' }}</td>
        <td class="label right-label-col">Issue</td>
        <td class="value value-col">{{ $circuit->issue ?? '' }}</td>
      </tr>
    </table>

    <!-- ACCESSORIES KANBAN -->
    <table class="detail-table">
      <tr>
        <td style="text-align: center; font-weight: bold; background: #f0f0f0;">BARCODE KANBAN</td>
        <td style="text-align: left;">{{ $circuit->family ?? '' }}</td>
        <td style="text-align: center; font-weight: bold; background: #f0f0f0;">BARCODE MESIN</td>
      </tr>
      <tr>
        <td rowspan="3" style="text-align: center; vertical-align: middle; padding: 5px;">
          @if(isset($circuit->qr_code_path))
            <img src="{{ $circuit->qr_code_path }}" style="width: 100px; height: 100px;" alt="QR Code">
            <div style="font-size: 10px; margin-top: 3px;">{{ $circuit->barcode_kanban ?? "" }}</div>
          @else
            <div style="width: 80px; height: 80px; margin: 5px auto; border: 1px solid #000;"></div>
          @endif
        </td>
        <td style="text-align: left;">{{ $circuit->conveyor ? 'CV ' . $circuit->conveyor : '' }}</td>
        <td rowspan="3" style="text-align: center; vertical-align: middle; padding: 5px;">
          @if(isset($circuit->barcode_path))
            <img src="{{ $circuit->barcode_path }}" style="max-width: 140px; height: auto;" alt="Barcode">
            <div style="font-size: 10px; margin-top: 3px;">{{ $circuit->barcode_mesin ?? "" }}</div>
          @else
            <div style="width: 120px; height: 60px; margin: 5px auto; border: 1px solid #000;"></div>
          @endif
        </td>
      </tr>
      <tr>
        <td style="text-align: left;">{{ $circuit->release_date ? \Carbon\Carbon::parse($circuit->release_date)->format('d M Y') : '' }}</td>
      </tr>
      <tr>
        <td style="text-align: left;">{{ $circuit->released_note ?? '' }}</td>
      </tr>
    </table>
  </div>
  @endforeach
</div>
