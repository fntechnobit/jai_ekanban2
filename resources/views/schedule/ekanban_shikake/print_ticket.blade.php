<style>
  /* PRINT AREA — 80mm ≈ 576 dots */
  .ticket-wrap {
    width: 576px;
    box-sizing: border-box;
    margin-top: -50px;
  }

  .ticket {
    page-break-after: always;
  }

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

  /* list 2 kolom tanpa bullet, font besar */
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
  <div class="ticket-wrap ticket">
    <table class="ptbl">
      <thead>
        <tr>
          <th colspan="3" style="text-align:center">
            {{ $shikake->shikake_name ?? 'N/A' }}
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
  @endforeach
</div>
