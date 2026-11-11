{{-- Tanda bintang (master circuit note_1 / note_2) ditulis setelah data terminal, di sel yang
     sama, mis. "711-727-03 *". Warna mengikuti sel terminal (putih di atas blok hitam untuk
     terminal Gold). Glyph "*" Arial kecil dan menggantung di atas, jadi dicetak dengan huruf
     besar ($size) lalu diturunkan sampai sejajar tengah teks terminal ($text = ukuran huruf
     terminal). line-height 0 supaya bintang besar tidak menambah tinggi baris tiket.
     Isi selain "*" dicetak sebagai teks tebal biasa. --}}
@php
    $starValue = trim((string) ($star ?? ''));
    $starSize = $size ?? 84;
    $starShift = -round(0.55 * $starSize - 0.36 * ($text ?? 22));
@endphp
@if($starValue === '*')<span class="term-star" style="display:inline-block;font-size:{{ $starSize }}px;font-weight:bold;line-height:0;vertical-align:{{ $starShift }}px;margin-left:8px;">*</span>@elseif($starValue !== '')<span class="term-star" style="font-weight:bold;margin-left:8px;">{{ $starValue }}</span>@endif
