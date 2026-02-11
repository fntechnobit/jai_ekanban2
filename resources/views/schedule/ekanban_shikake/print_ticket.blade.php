<style>
  /* PRINT AREA — 80mm ≈ 576 dots */
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
    font-size: 22px;
    font-weight: bold;
    border: 2px solid #000;
    padding: 8px;
    margin-bottom: 5px;
  }

  .info-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
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
    font-size: 11px;
  }

  .detail-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
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

  .label-col { width: 60px; }
  .value-col { width: 100px; }
  .middle-label-col { width: 50px; }
  .right-label-col { width: 60px; }

  .section-header {
    background: #000;
    color: #fff;
    font-weight: bold;
    text-align: center;
    padding: 5px;
    font-size: 14px;
  }

  /* Legacy styles for backward compatibility */
  .ptbl {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
  }

  .ptbl th,
  .ptbl td {
    border: 1px solid #000;
    padding: 5px;
    font-size: 20px;
    font-weight: 600;
    vertical-align: top;
  }

  .ptbl thead th {
    text-align: center;
    font-weight: 800;
    font-size: 35px;
  }

  .list-flex-2col {
    display: flex;
    flex-wrap: wrap;
    list-style: none;
    padding-left: 0;
    margin: 0;
  }

  .list-flex-2col li {
    width: 50%;
    box-sizing: border-box;
    padding-right: 4px;
    margin: 0;
    line-height: 1.35;
    font-size: 30px;
  }
</style>

<div id="print_stack_ajax">
  @foreach($shikakes as $shikake)
    @php
      $processData = $shikake->details ?? null;
      $process = $shikake->process ?? null;
    @endphp

    @switch($process)
      @case('TWIST')
        @include('schedule.ekanban_shikake.print_ticket_twist', ['shikake' => $shikake, 'processData' => $processData])
        @break

      @case('BONDER')
        @include('schedule.ekanban_shikake.print_ticket_bonder', ['shikake' => $shikake, 'processData' => $processData])
        @break

      @case('JOINT')
        @include('schedule.ekanban_shikake.print_ticket_joint', ['shikake' => $shikake, 'processData' => $processData])
        @break

      @case('SHIELD')
        @include('schedule.ekanban_shikake.print_ticket_shield', ['shikake' => $shikake, 'processData' => $processData])
        @break

      @case('DBL CRIMP')
        @include('schedule.ekanban_shikake.print_ticket_dbl_crimp', ['shikake' => $shikake, 'processData' => $processData])
        @break

      @default
        {{-- Fallback to generic template for unknown process types --}}
        <div class="ticket-wrap ticket">
          <table class="ptbl">
            <thead>
              <tr>
                <th colspan="3" style="text-align:center">
                  {{ $shikake->shikake_name ?? $shikake->family ?? 'N/A' }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  Conveyor: <b>{{ $shikake->conveyor ?? 'N/A' }}</b>
                </td>
                <td>
                  Assy: <b>{{ $shikake->assy ?? 'N/A' }}</b><br>
                  Date: <b>{{ isset($shikake->schedule) ? date('d M Y', strtotime($shikake->schedule)) : 'N/A' }}</b>
                </td>
                <td>
                  Shift: <b>{{ $shikake->shift ?? 'N/A' }}</b><br>
                  Qty: <b>{{ $shikake->qty ?? 0 }}</b>
                </td>
              </tr>
              @if(isset($shikake->pallet_count))
              <tr>
                <td colspan="3" style="text-align:center;font-size:25px">
                  <b>Pallet: {{ $shikake->pallet_count }}</b>
                </td>
              </tr>
              @endif
              @if(isset($shikake->machine))
              <tr>
                <td colspan="3" style="text-align:center;font-size:25px">
                  <b>Machine: {{ $shikake->machine }}</b>
                </td>
              </tr>
              @endif
            </tbody>
          </table>
        </div>
    @endswitch
  @endforeach
</div>
