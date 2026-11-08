{{-- CIRCUIT CUTTING_ANTENA_FEEDER Print Template. 1px = 1 dot @203dpi, landscape, tinggi 576px = 80mm.
     8 kolom x 14 baris x 40 px. Satu-satunya kode di tiket ini adalah QR kanban;
     BARCODE IKITSUKAN dari format lama sengaja dihapus, ruangnya dipakai KODE & PART NO.
     Kiri: spesifikasi kabel, terminal/strip/accessories A & B, KODE, PART NO. & NO ASSY.
     Kanan: mesin, seq, C/L, qty, issue | QR kanban + CV, tanggal | TO (to_store), family, note. --}}
@include('schedule.ekanban_circuit.partials.ticket_special_style')

@foreach($circuits as $circuit)
<div class="ticket special-print-wrapper" data-orientation="landscape">
    <div class="ticket-special-print">
        <table class="rows-14-40">
            <colgroup>
                <col style="width: 130px">  {{-- Col 1: CCT CODE / TERMINAL / KODE --}}
                <col style="width: 110px">  {{-- Col 2: CUST NO --}}
                <col style="width: 110px">  {{-- Col 3: KIND / STRIP --}}
                <col style="width: 90px">   {{-- Col 4: SIZE / ACCESSORIES --}}
                <col style="width: 110px">  {{-- Col 5: COLOUR / ACCESSORIES / NO ASSY --}}
                <col style="width: 100px">  {{-- Col 6: C/L / QR / TO --}}
                <col style="width: 90px">   {{-- Col 7: QTY / QR --}}
                <col style="width: 150px">  {{-- Col 8: ISSUE NO / SEQUENCE / CV / DATE --}}
            </colgroup>
            <tbody>
                {{-- r1-r2: judul | MACH NO, SEQUENCE --}}
                <tr>
                    <td colspan="5" rowspan="2" class="sp-title" style="white-space: normal;">INSTRUCTION KANBAN<br>ANTENA FEEDER</td>
                    <td colspan="2" class="sp-label">MACH NO.</td>
                    <td class="sp-label">SEQUENCE</td>
                </tr>
                <tr>
                    <td colspan="2" class="sp-big" style="font-size: 26px;">{{ $circuit->machine ?? '-' }}</td>
                    <td class="sp-big" style="font-size: 26px;">{{ $circuit->sequence ?? '-' }}</td>
                </tr>

                {{-- r3-r4: spesifikasi kabel | C/L, QTY, ISSUE NO --}}
                <tr>
                    <td class="sp-label">CCT CODE</td>
                    <td class="sp-label">CUST NO.</td>
                    <td class="sp-label">KIND</td>
                    <td class="sp-label">SIZE</td>
                    <td class="sp-label">COLOUR</td>
                    <td class="sp-label">C/L</td>
                    <td class="sp-label">QTY</td>
                    <td class="sp-label">ISSUE NO.</td>
                </tr>
                <tr>
                    <td class="sp-value">{{ $circuit->cct_code ?? '-' }}</td>
                    <td class="sp-value">{{ $circuit->cust_no ?? '-' }}</td>
                    <td class="sp-value">{{ $circuit->kind ?? '-' }}</td>
                    <td class="sp-value">{{ $circuit->size ?? '-' }}</td>
                    <td class="sp-value">{{ $circuit->col ?? '-' }}</td>
                    <td class="sp-value">{{ $circuit->cl ?? '-' }}</td>
                    <td class="sp-value">{{ $circuit->qty ?? '-' }}</td>
                    <td class="sp-value">{{ $circuit->issue ?? '-' }}</td>
                </tr>

                {{-- r5-r8: terminal/strip/accessories A & B | QR kanban (r5-r8) | CV NO, ISSUE DATE --}}
                <tr>
                    <td colspan="2" class="sp-label">TERMINAL A</td>
                    <td class="sp-label">STRIP A</td>
                    <td colspan="2" class="sp-label">ACCESSORIES A</td>
                    <td colspan="2" rowspan="4" class="sp-qr-cell">
                        <div class="sp-qr-label">QRCODE KANBAN</div>
                        @if(isset($circuit->qr_code_path))
                            <img src="{{ $circuit->qr_code_path }}" alt="QR Kanban" class="sp-qr-img">
                        @endif
                        <div class="sp-qr-text">{{ $circuit->barcode_kanban ?? '' }}</div>
                    </td>
                    <td class="sp-label">CV NO.</td>
                </tr>
                <tr>
                    <td colspan="2" class="sp-value{{ !empty($circuit->gold_1) ? ' sp-inv' : '' }}">{{ $circuit->terminal_1 ?? '' }}</td>
                    <td class="sp-value">{{ $circuit->strip_1 ?? '' }}</td>
                    <td colspan="2" class="sp-value">{{ $circuit->acc_1 ?? '' }}</td>
                    <td class="sp-value">{{ $circuit->conveyor ?? '-' }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="sp-label">TERMINAL B</td>
                    <td class="sp-label">STRIP B</td>
                    <td colspan="2" class="sp-label">ACCESSORIES B</td>
                    <td class="sp-label">ISSUE DATE</td>
                </tr>
                <tr>
                    <td colspan="2" class="sp-value{{ !empty($circuit->gold_2) ? ' sp-inv' : '' }}">{{ $circuit->terminal_2 ?? '' }}</td>
                    <td class="sp-value">{{ $circuit->strip_2 ?? '' }}</td>
                    <td colspan="2" class="sp-value">{{ $circuit->acc_2 ?? '' }}</td>
                    <td class="sp-value">{{ strtoupper(\Carbon\Carbon::now()->format("d M'y")) }}</td>
                </tr>

                {{-- r9-r14: KODE (r9-r10), PART NO & NO ASSY (r11-r14) | TO (r9-r12), FAMILY, NOTE --}}
                <tr>
                    <td rowspan="2" class="sp-label">KODE</td>
                    <td colspan="4" rowspan="2" class="sp-big" style="font-size: 60px;">{{ $circuit->part_code ?? '' }}</td>
                    <td colspan="3" class="sp-label">TO</td>
                </tr>
                <tr>
                    <td colspan="3" rowspan="3" class="sp-big" style="font-size: 28px; white-space: normal;">{{ $circuit->to_store ?? '' }}</td>
                </tr>
                <tr>
                    <td colspan="4" class="sp-label">PART NO.</td>
                    <td class="sp-label">NO ASSY</td>
                </tr>
                <tr>
                    <td colspan="4" rowspan="3" class="sp-big" style="font-size: 64px;">{{ $circuit->part_no ?? '' }}</td>
                    <td rowspan="3" class="sp-big" style="font-size: 40px;">{{ $circuit->no_assy ?? '' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">FAMILY</td>
                    <td colspan="2" class="sp-value text-left" style="font-size: 18px;">{{ $circuit->family ?? '' }}</td>
                </tr>
                <tr>
                    <td class="sp-label text-left">NOTE</td>
                    <td colspan="2" class="text-left"><span class="sp-note">{{ $circuit->released_note ?? '' }}</span></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="special-punch-strip">
        <div class="punch-circle"></div>
    </div>
</div>
@endforeach
