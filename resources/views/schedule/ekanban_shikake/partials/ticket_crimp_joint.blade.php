{{--
    Isi tiket DBL CRIMP dan JOINT (susunannya sama di lampiran revisi).
    Parameter:
      $p          prefix kelas: dbl-crimp | joint
      $title      judul proses: DBL CRIMP | JOINT
      $noLabel    label nomor: D.CRIMPING NO | JOINT NO
      $code       nomor shikake (drawing_no / bonder_no)
      $addrPrefix kolom alamat per CCT: address_ (dbl crimp) | bonder_no_ (joint)
    4 kolom x 14 baris @ 37 px. QR Drawing (kolom 3, 4 baris) di atas dan QR Kanban
    (kolom 2, 5 baris, menempel di dasar sel) dipisah 3 baris address store/machine.
--}}
@include('schedule.ekanban_shikake.partials.ticket_style', ['p' => $p])
<style>
/* QR Drawing 145 px (tiket lama 130 px) memenuhi tinggi 4 baris; kodenya di kolom kanan */
.ticket-{{ $p }}-print .qr-drawing,
.ticket-{{ $p }}-print .qr-drawing-placeholder {
    width: 145px;
    height: 145px;
}

/* QR Kanban 145 px (tiket lama 130 px) di dasar selnya supaya menjauh dari QR Drawing */
.ticket-{{ $p }}-print .qr-kanban,
.ticket-{{ $p }}-print .qr-kanban-placeholder {
    width: 145px;
    height: 145px;
}

.ticket-{{ $p }}-print .qrcode-cell.qr-bottom {
    vertical-align: bottom;
}

.ticket-{{ $p }}-print .qr-side-label {
    white-space: normal;
    line-height: 1.4;
}

.ticket-{{ $p }}-print .qr-side-label b {
    font-size: 13px;
    white-space: nowrap;
}
</style>
@php
    $releaseDate = $shikake->release_date
        ? \Carbon\Carbon::parse($shikake->release_date)->locale('id')->translatedFormat('d-M-y')
        : '';
@endphp

<div class="ticket {{ $p }}-print-wrapper" data-orientation="landscape">
    @if(!empty($shikake->image_path))
    <div class="shikake-image-section">
        <img src="{{ asset($shikake->image_path) }}" alt="Shikake Image">
    </div>
    @endif

    <div class="ticket-{{ $p }}-print">
        <table>
            <colgroup>
                <col style="width: 16mm">
                <col style="width: 31mm">
                <col style="width: 16mm">
                <col style="width: 19mm">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="4">E-KANBAN {{ $title }} {{ $shikake->carline ?? '' }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="2" class="label-cell">{{ $noLabel }}</td>
                    <td colspan="2" class="label-cell">ADDRESS S/A</td>
                </tr>
                <tr>
                    <td colspan="2" class="value-cell">{{ $code }}</td>
                    <td colspan="2" class="value-cell">{{ $processData->address ?? '' }}</td>
                </tr>

                <tr>
                    <td class="label-cell">CCT-NO</td>
                    <td class="label-cell">ADDRESS</td>
                    <td rowspan="4" class="qrcode-cell">
                        @if(isset($processData->qrcode_drawing_path))
                            <img src="{{ $processData->qrcode_drawing_path }}" alt="QR Drawing" class="qr-img qr-drawing">
                        @else
                            <div class="qrcode-placeholder qr-drawing-placeholder">QR</div>
                        @endif
                    </td>
                    <td rowspan="4" class="label-cell qr-side-label">QR<br>DRAWING<br><b>{{ $processData->qrcode_drawing ?? '' }}</b></td>
                </tr>
                @for($i = 1; $i <= 5; $i++)
                <tr>
                    <td class="value-cell">{{ $processData->{'cct_no_' . $i} ?? '' }}</td>
                    <td class="value-cell">{{ $processData->{$addrPrefix . $i} ?? '' }}</td>
                    @if($i === 4)
                    <td colspan="2" class="label-cell">ADDRESS STORE</td>
                    @elseif($i === 5)
                    <td colspan="2" class="store-cell">{{ $processData->address_store ?? '' }}</td>
                    @endif
                </tr>
                @endfor

                <tr>
                    <td class="one-cell">{{ $shikake->family ?? '' }}</td>
                    <td class="label-cell">QR KANBAN</td>
                    <td colspan="2" class="label-cell">MACHINE</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $shikake->conveyor ? 'CV' . $shikake->conveyor : '' }}</td>
                    <td rowspan="5" class="qrcode-cell qr-bottom">
                        @if(isset($shikake->qr_code_path))
                            <img src="{{ $shikake->qr_code_path }}" alt="QR Code" class="qr-img qr-kanban">
                        @else
                            <div class="qrcode-placeholder qr-kanban-placeholder">QR</div>
                        @endif
                        @if(!empty($shikake->barcode_kanban))
                            <div class="qr-text">{{ $shikake->barcode_kanban }}</div>
                        @endif
                    </td>
                    <td colspan="2" class="value-cell">{{ $shikake->machine ?? '' }}</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $releaseDate }}</td>
                    <td class="label-cell">SEQ</td>
                    <td class="label-cell">TO MACH</td>
                </tr>
                <tr>
                    <td rowspan="3" class="stack-cell">{{ $shikake->released_note ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->sequence ?? '' }}</td>
                    <td class="value-cell">{{ $processData->to_machine ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">QTY</td>
                    <td class="label-cell">ISSUE</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $shikake->qty ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->issue ?? '' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Punch strip (RIGHT) - jarak 5mm + lingkaran hitam, sama seperti cutting/twist --}}
    <div class="{{ $p }}-punch-strip">
        <div class="punch-circle"></div>
    </div>
</div>
