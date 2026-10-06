{{-- DBL CRIMP - template tiket (landscape, tinggi 576 dot = kertas thermal 80 mm).
     Dipakai untuk print (raster QZ) dan preview. Susunan sama dengan JOINT, lihat partial. --}}
@include('schedule.ekanban_shikake.partials.ticket_crimp_joint', [
    'p' => 'dbl-crimp',
    'title' => 'DBL CRIMP',
    'noLabel' => 'D.CRIMPING NO',
    'code' => $processData->drawing_no ?? '',
    'addrPrefix' => 'address_',
])
