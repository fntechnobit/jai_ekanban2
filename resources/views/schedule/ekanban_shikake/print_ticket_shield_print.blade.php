{{-- SHIELD WIRE - template tiket (landscape, tinggi 576 dot = kertas thermal 80 mm).
     Dipakai untuk print (raster QZ) dan preview, supaya preview selalu sama dengan hasil cetak.
     5 kolom x 14 baris @ 37 px: QR Drawing (kolom 2) dan QR Kanban (kolom 5) masing-masing
     setinggi 5 baris, dipisah kolom MACHINE/SEQ/BLADE supaya scanner tidak bingung. --}}
@include('schedule.ekanban_shikake.partials.ticket_style', ['p' => 'shield'])
<style>
/* QR 170 px (tiket lama 140 px): label 1 baris + gambar 5 baris x 37 px */
.ticket-shield-print .qr-img,
.ticket-shield-print .qrcode-placeholder {
    width: 170px;
    height: 170px;
}

.ticket-shield-print .qr-text {
    margin-top: 1px;
}
</style>
@php
    $releaseDate = $shikake->release_date
        ? \Carbon\Carbon::parse($shikake->release_date)->locale('id')->translatedFormat('d-M-y')
        : '';
@endphp

<div class="ticket shield-print-wrapper" data-orientation="landscape">
    @if(!empty($shikake->image_path))
    <div class="shikake-image-section">
        <img src="{{ asset($shikake->image_path) }}" alt="Shikake Image">
    </div>
    @endif

    <div class="ticket-shield-print">
        <table>
            <colgroup>
                <col style="width: 20mm">
                <col style="width: 20mm">
                <col style="width: 16mm">
                <col style="width: 16mm">
                <col style="width: 20mm">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="5">E-KANBAN SHIELD WIRE {{ $shikake->carline ?? '' }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="2" class="label-cell">SHIELD WIRE NO</td>
                    <td colspan="3" class="label-cell">ADDRESS S/A</td>
                </tr>
                <tr>
                    <td colspan="2" class="value-cell">{{ $processData->shield_no ?? '' }}</td>
                    <td colspan="3" class="value-cell">{{ $processData->address ?? '' }}</td>
                </tr>

                <tr>
                    <td class="label-cell">CCT NO 1</td>
                    <td class="label-cell">QR DRWG</td>
                    <td class="label-cell">QTY</td>
                    <td class="label-cell">ISSUE</td>
                    <td class="label-cell">QR KANBAN</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_1 ?? '' }}</td>
                    <td rowspan="5" class="qrcode-cell">
                        @if(isset($processData->qrcode_drawing_path))
                            <img src="{{ $processData->qrcode_drawing_path }}" alt="QR Drawing" class="qr-img">
                        @else
                            <div class="qrcode-placeholder">QR</div>
                        @endif
                        @if(!empty($processData->qrcode_drawing))
                            <div class="qr-text">{{ $processData->qrcode_drawing }}</div>
                        @endif
                    </td>
                    <td class="value-cell">{{ $shikake->qty ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->issue ?? '' }}</td>
                    <td rowspan="5" class="qrcode-cell">
                        @if(isset($shikake->qr_code_path))
                            <img src="{{ $shikake->qr_code_path }}" alt="QR Code" class="qr-img">
                        @else
                            <div class="qrcode-placeholder">QR</div>
                        @endif
                        @if(!empty($shikake->barcode_kanban))
                            <div class="qr-text">{{ $shikake->barcode_kanban }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->cct_no_2 ?? '' }}</td>
                    <td colspan="2" class="label-cell">MACHINE</td>
                </tr>
                <tr>
                    <td class="label-cell">ADDRESS 1</td>
                    <td colspan="2" class="value-cell">{{ $shikake->machine ?? '' }}</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->address_no_1_1 ?? '' }}</td>
                    <td class="label-cell">SEQ</td>
                    <td class="label-cell">BLADE</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->address_no_1_2 ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->sequence ?? '' }}</td>
                    <td class="value-cell">{{ $processData->blade ?? '' }}</td>
                </tr>

                <tr>
                    <td class="label-cell">TO 1</td>
                    <td class="label-cell">TO 2</td>
                    <td class="label-cell">TO 3</td>
                    <td class="label-cell">TO 4</td>
                    <td class="label-cell">TO 5</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->to_1 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->to_2 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->to_3 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->to_4 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->to_5 ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">TO 6</td>
                    <td class="label-cell">TO 7</td>
                    <td class="label-cell">TO 8</td>
                    <td class="label-cell">TO 9</td>
                    <td class="label-cell">DATE</td>
                </tr>
                <tr>
                    <td class="value-cell">{{ $processData->to_6 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->to_7 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->to_8 ?? '' }}</td>
                    <td class="value-cell">{{ $processData->to_9 ?? '' }}</td>
                    <td class="value-cell">{{ $releaseDate }}</td>
                </tr>

                <tr>
                    <td colspan="2" class="label-cell">ADD.STORE</td>
                    <td class="label-cell">CV</td>
                    <td class="label-cell">FAMILY</td>
                    <td class="label-cell">NOTE</td>
                </tr>
                <tr>
                    <td colspan="2" class="store-cell">{{ $processData->address_store ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->conveyor ?? '' }}</td>
                    <td class="value-cell">{{ $shikake->family ?? '' }}</td>
                    <td class="one-cell">{{ $shikake->released_note ?? '' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Punch strip (RIGHT) - jarak 5mm + lingkaran hitam, sama seperti cutting/twist --}}
    <div class="shield-punch-strip">
        <div class="punch-circle"></div>
    </div>
</div>
