<?php

namespace App\Support;

/**
 * Isi QR kanban. Formatnya mengikuti scan STO Circuit di jai_sto_wip
 * (sto/views/circuit_scan/index_js.php), yang membedakan dua jenis kanban dari
 * ada-tidaknya titik:
 *
 * - Circuit (CCT single): tanpa titik, dipotong dari kedua ujung
 *   carline(1) + cct(4-10) + issue(3) + qty(2) + nomor urut(4).
 *   Contoh: GGE64003404174 → G | GE64 | 003 | 40 | 4174, SSC86A001200001 → S | SC86A | ...
 *
 * - Drawing (joint, bonder, shield, double crimp, twist): dipisah titik
 *   carline.drawing.issue.qty.nomor_urut. STO mencari semua CCT milik drawing
 *   tersebut dan mencatat qty kanban untuk masing-masing CCT.
 *   Contoh: P.B-0154XEB.008.16.0024
 */
final class KanbanBarcode
{
    private const CARLINE = '/^[A-Z0-9]$/i';
    private const CCT = '/^[A-Z0-9-]{4,10}$/i';
    private const DRAWING = '/^[A-Z0-9][A-Z0-9 -]*$/i';
    private const CIRCUIT_MAX_QTY = 99;
    private const DRAWING_MAX_QTY = 999;

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
     * Alasan kanban circuit tidak bisa dibaca scan STO, atau null bila terbaca.
     * Hanya memeriksa bentuknya; keberadaan CCT di master STO tidak.
     */
    public static function circuitProblem(string $carline, string $cctCode, int $qty): ?string
    {
        return match (true) {
            !preg_match(self::CARLINE, $carline) => "carline '{$carline}' harus 1 huruf/angka",
            !preg_match(self::CCT, $cctCode) => "CCT '{$cctCode}' harus 4-10 karakter huruf/angka/strip",
            $qty < 0 || $qty > self::CIRCUIT_MAX_QTY => "qty {$qty} lebih dari 2 digit",
            default => null,
        };
    }

    /**
     * Alasan kanban drawing tidak bisa dibaca scan STO, atau null bila terbaca.
     * Hanya memeriksa bentuknya; keberadaan drawing di master STO tidak.
     */
    public static function drawingProblem(string $carline, string $code, int $qty): ?string
    {
        return match (true) {
            !preg_match(self::CARLINE, $carline) => "carline '{$carline}' harus 1 huruf/angka",
            !preg_match(self::DRAWING, $code) => "kode '{$code}' hanya boleh huruf/angka/strip/spasi (tanpa titik)",
            $qty < 0 || $qty > self::DRAWING_MAX_QTY => "qty {$qty} lebih dari 3 digit",
            default => null,
        };
    }
}
