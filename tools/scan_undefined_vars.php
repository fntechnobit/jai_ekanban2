<?php
/**
 * Pemindai variabel liar: dibaca tetapi tidak pernah ditugaskan.
 *
 *     php tools/scan_undefined_vars.php app
 *
 * Dibuat setelah tiga kali kejadian serupa lolos ke produksi ($fullDemand,
 * $cutOff5Capacity, dan sebuah method yang sudah dihapus): sisa refactor yang
 * terlewat di dalam blok Log::info atau array balasan. `php -l` tidak dapat
 * menangkapnya karena secara sintaks benar, dan di PHP 8 variabel tak terdefinisi
 * melempar Error sehingga layar berhenti dengan pesan yang membingungkan.
 *
 * Sengaja longgar: hanya melaporkan variabel yang TIDAK PERNAH ditulis di dalam
 * fungsinya, jadi urutan baris tidak diperhitungkan. Dengan begitu hasilnya
 * sedikit dan hampir selalu benar.
 *
 * Dua bentuk yang masih dilaporkan sebagai temuan palsu — periksa sekilas
 * sebelum percaya:
 *   - argumen by-reference milik fungsi bawaan, mis. proc_open($cmd, $spec, $pipes)
 *   - pembongkaran gaya lama, mis. list($a, $b) = explode(...)
 *
 * Keluar dengan kode 1 bila ada temuan, supaya bisa dipasang di CI bila perlu.
 */

$dirs = $argv[1] ?? 'app';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirs));
$temuan = [];

foreach ($rii as $f) {
    if ($f->isDir() || $f->getExtension() !== 'php') continue;
    $src = file_get_contents($f->getPathname());
    $t = token_get_all($src);
    $n = count($t);

    for ($i = 0; $i < $n; $i++) {
        if (!is_array($t[$i]) || $t[$i][0] !== T_FUNCTION) continue;

        // kumpulkan parameter
        $j = $i;
        while ($j < $n && $t[$j] !== '(') $j++;
        $depth = 0; $params = [];
        for (; $j < $n; $j++) {
            if ($t[$j] === '(') $depth++;
            elseif ($t[$j] === ')') { $depth--; if ($depth === 0) { $j++; break; } }
            elseif (is_array($t[$j]) && $t[$j][0] === T_VARIABLE) $params[$t[$j][1]] = true;
        }
        // use (...) pada closure
        while ($j < $n && (is_array($t[$j]) && in_array($t[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))) $j++;
        if (is_array($t[$j] ?? null) && $t[$j][0] === T_USE) {
            $depth = 0;
            for (; $j < $n; $j++) {
                if ($t[$j] === '(') $depth++;
                elseif ($t[$j] === ')') { $depth--; if ($depth === 0) { $j++; break; } }
                elseif (is_array($t[$j]) && $t[$j][0] === T_VARIABLE) $params[$t[$j][1]] = true;
            }
        }
        // badan fungsi
        while ($j < $n && $t[$j] !== '{' && $t[$j] !== ';') $j++;
        if (($t[$j] ?? null) === ';') continue; // abstract / interface

        $depth = 0; $body = [];
        for (; $j < $n; $j++) {
            if ($t[$j] === '{') $depth++;
            elseif ($t[$j] === '}') { $depth--; if ($depth === 0) break; }
            $body[] = $t[$j];
        }

        $ditulis = $params; $dibaca = [];
        $m = count($body);

        // Pra-jalan: variabel pada header foreach (sesudah "as") adalah penulisan.
        for ($k = 0; $k < $m; $k++) {
            if (!is_array($body[$k]) || $body[$k][0] !== T_FOREACH) continue;

            $p2 = $k; $lihatAs = false; $d3 = 0;
            while ($p2 < $m && $body[$p2] !== '(') $p2++;
            for (; $p2 < $m; $p2++) {
                if ($body[$p2] === '(') $d3++;
                elseif ($body[$p2] === ')') { $d3--; if ($d3 === 0) break; }
                elseif (is_array($body[$p2]) && $body[$p2][0] === T_AS) $lihatAs = true;
                elseif ($lihatAs && is_array($body[$p2]) && $body[$p2][0] === T_VARIABLE) $ditulis[$body[$p2][1]] = true;
            }
        }

        // Pra-jalan: parameter closure/fn/catch di dalam badan ini juga "tertulis".
        for ($k = 0; $k < $m; $k++) {
            $tok = $body[$k];
            if (!is_array($tok)) continue;
            $isFn = in_array($tok[0], [T_FUNCTION, T_FN, T_CATCH], true)
                 || (defined('T_USE') && $tok[0] === T_USE);
            if (!$isFn) continue;

            $p2 = $k;
            while ($p2 < $m && $body[$p2] !== '(' && $body[$p2] !== '{' && $body[$p2] !== ';') $p2++;
            if (($body[$p2] ?? null) !== '(') continue;

            $d3 = 0;
            for (; $p2 < $m; $p2++) {
                if ($body[$p2] === '(') $d3++;
                elseif ($body[$p2] === ')') { $d3--; if ($d3 === 0) break; }
                elseif (is_array($body[$p2]) && $body[$p2][0] === T_VARIABLE) $ditulis[$body[$p2][1]] = true;
            }
        }

        for ($k = 0; $k < $m; $k++) {
            $tok = $body[$k];
            if (!is_array($tok) || $tok[0] !== T_VARIABLE) continue;
            $nama = $tok[1];
            if ($nama === '$this' || $nama === '$GLOBALS') continue;

            // token berikutnya yang bermakna
            $p = $k + 1;
            while ($p < $m && is_array($body[$p]) && in_array($body[$p][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) $p++;
            $next = $body[$p] ?? null;

            // lompati indeks/rantai: $a['x']['y'] =
            while ($next === '[') {
                $d2 = 0;
                for (; $p < $m; $p++) {
                    if ($body[$p] === '[') $d2++;
                    elseif ($body[$p] === ']') { $d2--; if ($d2 === 0) { $p++; break; } }
                }
                while ($p < $m && is_array($body[$p]) && in_array($body[$p][0], [T_WHITESPACE, T_COMMENT], true)) $p++;
                $next = $body[$p] ?? null;
            }

            $assignOps = [T_PLUS_EQUAL, T_MINUS_EQUAL, T_MUL_EQUAL, T_DIV_EQUAL, T_CONCAT_EQUAL,
                          T_MOD_EQUAL, T_AND_EQUAL, T_OR_EQUAL, T_XOR_EQUAL, T_SL_EQUAL, T_SR_EQUAL,
                          T_COALESCE_EQUAL, T_INC, T_DEC];

            $isWrite = ($next === '=') || (is_array($next) && in_array($next[0], $assignOps, true));

            // token sebelumnya yang bermakna
            $q = $k - 1;
            while ($q >= 0 && is_array($body[$q]) && in_array($body[$q][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) $q--;
            $prev = $body[$q] ?? null;

            if (is_array($prev) && in_array($prev[0], [T_AS, T_GLOBAL, T_STATIC], true)) $isWrite = true;
            if ($prev === '&') $isWrite = true;                 // by-reference
            if (is_array($prev) && $prev[0] === T_STRING && $q >= 1) {
                // catch (\Throwable $e)
                $r = $q - 1;
                while ($r >= 0 && is_array($body[$r]) && $body[$r][0] === T_WHITESPACE) $r--;
                if (is_array($body[$r] ?? null) && $body[$r][0] === T_NS_SEPARATOR) $isWrite = true;
            }
            // list/array destructuring:  [$a, $b] = ...  atau  list($a) = ...
            if ($prev === '[' || $prev === ',' || (is_array($prev) && $prev[0] === T_LIST)) {
                $d2 = 0;
                for ($r = $k; $r < $m; $r++) {
                    if ($body[$r] === '[' || $body[$r] === '(') $d2++;
                    elseif ($body[$r] === ']' || $body[$r] === ')') {
                        if ($d2 === 0) {
                            $u = $r + 1;
                            while ($u < $m && is_array($body[$u]) && $body[$u][0] === T_WHITESPACE) $u++;
                            if (($body[$u] ?? null) === '=') $isWrite = true;
                            break;
                        }
                        $d2--;
                    } elseif ($body[$r] === ';') break;
                }
            }

            if ($isWrite) $ditulis[$nama] = true;
            else $dibaca[$nama] = $tok[2];
        }

        foreach ($dibaca as $nama => $baris) {
            if (!isset($ditulis[$nama])) {
                $temuan[] = sprintf('%s:%d  %s', $f->getPathname(), $baris, $nama);
            }
        }
    }
}

$temuan = array_unique($temuan);

echo $temuan ? implode(PHP_EOL, $temuan) . PHP_EOL : 'bersih' . PHP_EOL;
echo count($temuan) . ' temuan' . PHP_EOL;

exit($temuan ? 1 : 0);
