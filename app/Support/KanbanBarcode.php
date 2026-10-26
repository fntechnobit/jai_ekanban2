<?php

namespace App\Support;

/**
 * Isi QR kanban. Formatnya mengikuti scan STO Circuit di jai_sto_wip, yang
 * membedakan dua jenis kanban dari ada-tidaknya titik:
 *
 * - Circuit (CCT single): tanpa titik, dipotong per posisi tetap
 *   carline(1) + cct(4) + issue(3) + qty(2) + nomor urut(4).
 *   Contoh: GGE64003404174 → G | GE64 | 003 | 40 | 4174
 *
 * - Drawing (joint, bonder, shield, double crimp, twist): dipisah titik
 *   carline.drawing.issue.qty.nomor_urut. STO mencari semua CCT milik drawing
 *   tersebut dan mencatat qty kanban untuk masing-masing CCT.
 *   Contoh: P.B-0154XEB.008.16.0024
 */
final class KanbanBarcode
{
    public const CIRCUIT_CARLINE_LENGTH = 1;
    public const CIRCUIT_CCT_LENGTH = 4;
    public const CIRCUIT_MAX_QTY = 99;

    public static function circuit(string $carline, string $cctCode, string $issue, int $qty, int $nomorUrut): string
    {
        return sprintf('%s%s%s%02d%04d', $carline, $cctCode, self::issueNumber($issue), $qty, $nomorUrut);
    }

    public static function drawing(string $carline, string $code, string $issue, int $qty, int $nomorUrut): string
    {
        return sprintf('%s.%s.%s.%d.%04d', $carline, $code, self::issueNumber($issue), $qty, $nomorUrut);
    }

    /** Ambil bagian XXX dari issue XXX/YYY. */
    public static function issueNumber(string $issue): string
    {
        return explode('/', $issue)[0];
    }

    /**
     * Pecah barcode yang sudah tersimpan menjadi [carline, kode] memakai issue, qty
     * dan nomor urut kanban-nya. Menerima format bertitik maupun tanpa titik (qty
     * dengan atau tanpa nol di depan). Null bila barcode tidak dikenali.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function parse(string $barcode, string $carline, string $issue, int $qty, int $nomorUrut): ?array
    {
        $parts = explode('.', $barcode);
        if (count($parts) === 5) {
            return $parts[1] === '' ? null : [$parts[0], $parts[1]];
        }

        $nys = sprintf('%04d', $nomorUrut);
        foreach (array_unique([(string) $qty, sprintf('%02d', $qty)]) as $qtyText) {
            $suffix = self::issueNumber($issue) . $qtyText . $nys;
            if (!str_ends_with($barcode, $suffix)) {
                continue;
            }

            $prefix = substr($barcode, 0, -strlen($suffix));
            if ($carline !== '' && str_starts_with($prefix, $carline) && strlen($prefix) > strlen($carline)) {
                return [$carline, substr($prefix, strlen($carline))];
            }
        }

        return null;
    }

    /**
     * Alasan barcode tidak bisa dibaca scan STO Circuit, atau null bila terbaca.
     * Hanya memeriksa bentuknya; keberadaan CCT / drawing di master STO tidak.
     */
    public static function stoProblem(string $barcode): ?string
    {
        if (str_contains($barcode, '.')) {
            $parts = explode('.', $barcode);

            return match (true) {
                count($parts) !== 5 => 'kode drawing mengandung titik',
                $parts[1] === '' => 'kode drawing kosong',
                default => null,
            };
        }

        $expected = self::CIRCUIT_CARLINE_LENGTH + self::CIRCUIT_CCT_LENGTH + 3 + 2 + 4;

        return strlen($barcode) === $expected
            ? null
            : sprintf('panjang %d karakter, STO membaca circuit sebagai %d karakter (carline 1, CCT 4, qty maks 2 digit)', strlen($barcode), $expected);
    }
}
