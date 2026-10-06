{{-- JOINT - template tiket (landscape, tinggi 576 dot = kertas thermal 80 mm).
     Dipakai untuk print (raster QZ) dan preview. Susunan sama dengan DBL CRIMP, lihat partial.
     Kolom ADDRESS per CCT pada joint disimpan di bonder_no_1..5. --}}
@include('schedule.ekanban_shikake.partials.ticket_crimp_joint', [
    'p' => 'joint',
    'title' => 'JOINT',
    'noLabel' => 'JOINT NO',
    'code' => $processData->bonder_no ?? '',
    'addrPrefix' => 'bonder_no_',
])
