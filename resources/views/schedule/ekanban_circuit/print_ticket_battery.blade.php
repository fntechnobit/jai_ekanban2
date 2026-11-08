{{-- CIRCUIT CUTTING_BATTERY_CABLE Print Template. 1px = 1 dot @203dpi, landscape, tinggi 576px = 80mm.
     Format asli user berbentuk portrait; ditata ulang ke landscape seperti tiket cutting lain
     supaya tinggi tiket tetap 80 mm (lebar kepala cetak) dan strip lubang di ujungnya sama.
     7 kolom, header 52 px + 14 baris x 37 px.
     Kiri: QR mesin | MACHINE | QR drawing. Tengah: battery cable no, qty, CCT code, address,
     issue, CV, seq, tanggal, qty DC, family, remark. Kanan: TO 1-9 | QR kanban.
     Ketiga QR dipisah blok teks supaya scanner tidak menangkap QR sebelahnya. --}}
@include('schedule.ekanban_circuit.partials.ticket_special_style')
<style>
.ticket-special-print .sp-qr-img.qr-lg {
    width: 140px;
    height: 140px;
}
</style>

@foreach($circuits as $circuit)
<div class="ticket special-print-wrapper" data-orientation="landscape">
    <div class="ticket-special-print">
        <table class="rows-14-37">
            <colgroup>
                <col style="width: 160px">  {{-- Col 1: QR MESIN / MACHINE / QR DRAWING --}}
                <col style="width: 130px">  {{-- Col 2: CCT CODE / CV NO / FAMILY / REMARK --}}
                <col style="width: 130px">  {{-- Col 3: ADDRESS / SEQUENCE --}}
                <col style="width: 130px">  {{-- Col 4: ADDRESS / ISSUE DATE --}}
                <col style="width: 120px">  {{-- Col 5: QTY / ISSUE NO / QTY DC --}}
                <col style="width: 70px">   {{-- Col 6: label TO n --}}
                <col style="width: 180px">  {{-- Col 7: tujuan TO n --}}
            </colgroup>
            <thead>
                <tr>
                    <th colspan="7">E-KANBAN BATTERY CABLE</th>
                </tr>
            </thead>
            <tbody>
                {{-- r1-r4: QR mesin | BATTERY CABLE NO & QTY | TO 1-4 --}}
                <tr>
                    <td rowspan="5" class="sp-qr-cell">
                        <div class="sp-qr-label">QR MESIN</div>
                        @if(isset($circuit->qr_mesin_path))
                            <img src="{{ $circuit->qr_mesin_path }}" alt="QR Mesin" class="sp-qr-img qr-lg">
                        @endif
                        <div class="sp-qr-text">{{ $circuit->qrcode_mesin ?? '' }}</div>
                    </td>
                    <td colspan="3" class="sp-label">BATTERY CABLE NO.</td>
                    <td class="sp-label">QTY</td>
                    <td class="sp-label">TO 1</td>
                    <td class="sp-value text-left">{{ $circuit->t01 ?: '-' }}</td>
                </tr>
                <tr>
                    <td colspan="3" rowspan="3" class="sp-big" style="font-size: 52px;">{{ $circuit->cct_no ?? '-' }}</td>
                    <td rowspan="3" class="sp-big" style="font-size: 52px;">{{ $circuit->qty ?? '-' }}</td>
                    <td class="sp-label">TO 2</td>
                    <td class="sp-value text-left">{{ $circuit->t02 ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="sp-label">TO 3</td>
                    <td class="sp-value text-left">{{ $circuit->t03 ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="sp-label">TO 4</td>
                    <td class="sp-value text-left">{{ $circuit->t04 ?: '-' }}</td>
                </tr>

                {{-- r5-r7: CCT CODE, ADDRESS, ISSUE NO | MACHINE (r6-r8) | TO 5-7 --}}
                <tr>
                    <td class="sp-label">CCT CODE</td>
                    <td colspan="2" class="sp-label">ADDRESS</td>
                    <td class="sp-label">ISSUE NO.</td>
                    <td class="sp-label">TO 5</td>
                    <td class="sp-value text-left">{{ $circuit->t05 ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="sp-label">MACHINE</td>
                    <td rowspan="2" class="sp-big" style="font-size: 32px;">{{ $circuit->cct_code ?? '-' }}</td>
                    <td colspan="2" rowspan="2" class="sp-big" style="font-size: 28px;">{{ $circuit->address ?? '' }}</td>
                    <td rowspan="2" class="sp-big" style="font-size: 28px;">{{ $circuit->issue ?? '-' }}</td>
                    <td class="sp-label">TO 6</td>
                    <td class="sp-value text-left">{{ $circuit->t06 ?: '-' }}</td>
                </tr>
                <tr>
                    <td rowspan="2" class="sp-big" style="font-size: 24px;">{{ $circuit->machine ?? '-' }}</td>
                    <td class="sp-label">TO 7</td>
                    <td class="sp-value text-left">{{ $circuit->t07 ?: '-' }}</td>
                </tr>

                {{-- r8-r10: CV NO, SEQUENCE, ISSUE DATE, QTY DC | QR drawing (r9-r14) | TO 8-9, QR kanban (r10-r14) --}}
                <tr>
                    <td class="sp-label">CV NO.</td>
                    <td class="sp-label">SEQUENCE</td>
                    <td class="sp-label">ISSUE DATE</td>
                    <td class="sp-label">QTY DC</td>
                    <td class="sp-label">TO 8</td>
                    <td class="sp-value text-left">{{ $circuit->t08 ?: '-' }}</td>
                </tr>
                <tr>
                    <td rowspan="6" class="sp-qr-cell">
                        <div class="sp-qr-label">QR DRAWING</div>
                        @if(isset($circuit->qr_drawing_path))
                            <img src="{{ $circuit->qr_drawing_path }}" alt="QR Drawing" class="sp-qr-img qr-lg">
                        @endif
                        <div class="sp-qr-text">{{ $circuit->qrcode_drawing ?? '' }}</div>
                    </td>
                    <td rowspan="2" class="sp-value">{{ $circuit->conveyor ?? '-' }}</td>
                    <td rowspan="2" class="sp-value">{{ $circuit->sequence ?? '-' }}</td>
                    <td rowspan="2" class="sp-value">{{ strtoupper(\Carbon\Carbon::now()->format("d M'y")) }}</td>
                    <td rowspan="2" class="sp-value">{{ $circuit->qty_dc ?: '-' }}</td>
                    <td class="sp-label">TO 9</td>
                    <td class="sp-value text-left">{{ $circuit->t09 ?: '-' }}</td>
                </tr>
                <tr>
                    <td colspan="2" rowspan="5" class="sp-qr-cell">
                        <div class="sp-qr-label">QR KANBAN</div>
                        @if(isset($circuit->qr_code_path))
                            <img src="{{ $circuit->qr_code_path }}" alt="QR Kanban" class="sp-qr-img qr-lg">
                        @endif
                        <div class="sp-qr-text">{{ $circuit->barcode_kanban ?? '' }}</div>
                    </td>
                </tr>

                {{-- r11-r14: FAMILY, REMARK --}}
                <tr>
                    <td class="sp-label text-left">FAMILY</td>
                    <td colspan="3" class="sp-value text-left">{{ $circuit->family ?? '' }}</td>
                </tr>
                <tr>
                    <td rowspan="3" class="sp-label text-left" style="vertical-align: top;">REMARK</td>
                    <td colspan="3" rowspan="3" class="text-left" style="vertical-align: top;"><span class="sp-note" style="font-size: 18px;">{{ $circuit->released_note ?? '' }}</span></td>
                </tr>
                <tr></tr>
                <tr></tr>
            </tbody>
        </table>
    </div>
    <div class="special-punch-strip">
        <div class="punch-circle"></div>
    </div>
</div>
@endforeach
