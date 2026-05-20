<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatabaseBackupController extends Controller
{
    /**
     * Display the database backup page.
     */
    public function index()
    {
        $connection = config('database.default');
        $host       = config("database.connections.{$connection}.host", '127.0.0.1');
        $database   = config("database.connections.{$connection}.database");
        $username   = config("database.connections.{$connection}.username");

        $tableCount = 0;
        $dbSizeMb   = 0;

        try {
            $tables = DB::select('SHOW TABLES');
            $tableCount = count($tables);

            $sizeResult = DB::select("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = ?
            ", [$database]);

            $dbSizeMb = $sizeResult[0]->size_mb ?? 0;
        } catch (\Exception $e) {
            // non-blocking; will show 0 on error
        }

        return view('system.database_backup.index', compact(
            'host', 'database', 'username', 'tableCount', 'dbSizeMb', 'connection'
        ));
    }

    /**
     * Stream a mysqldump .sql file to the browser for download.
     */
    public function download()
    {
        $connection = config('database.default');

        if ($connection !== 'mysql') {
            return back()->withErrors(['error' => 'Database backup hanya mendukung koneksi MySQL.']);
        }

        $host     = config("database.connections.{$connection}.host", '127.0.0.1');
        $port     = (string) config("database.connections.{$connection}.port", '3306');
        $database = config("database.connections.{$connection}.database");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password", '');

        $mysqldump = $this->findMysqldump();

        if (!$mysqldump) {
            return back()->withErrors(['error' => 'Executable mysqldump tidak ditemukan. Pastikan MySQL client tools sudah terinstall.']);
        }

        $filename = 'backup_' . now()->format('Y_m_d_His') . '.sql';

        // Build the command string with properly escaped arguments
        $cmd = escapeshellarg($mysqldump)
            . ' --host='    . escapeshellarg($host)
            . ' --port='    . escapeshellarg($port)
            . ' --user='    . escapeshellarg($username)
            . ' --single-transaction'
            . ' --routines'
            . ' --triggers'
            . ' --add-drop-table'
            . ' --no-tablespaces'
            . ' --default-character-set=utf8mb4';

        if ($password !== '' && $password !== null) {
            $cmd .= ' --password=' . escapeshellarg($password);
        }

        $cmd .= ' ' . escapeshellarg($database);

        return response()->streamDownload(function () use ($cmd) {
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = proc_open($cmd, $descriptors, $pipes);

            if (!is_resource($process)) {
                echo "-- ERROR: Gagal menjalankan mysqldump.\n";
                return;
            }

            fclose($pipes[0]);

            while (!feof($pipes[1])) {
                echo fread($pipes[1], 8192);
                flush();
            }

            $stderr = stream_get_contents($pipes[2]);

            fclose($pipes[1]);
            fclose($pipes[2]);

            $exitCode = proc_close($process);

            if ($exitCode !== 0 && $stderr) {
                echo "\n-- MYSQLDUMP ERROR (exit {$exitCode}): " . $stderr . "\n";
            }
        }, $filename, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }

    /**
     * Locate the mysqldump executable.
     * Checks common XAMPP/WAMP/system paths.
     */
    private function findMysqldump(): ?string
    {
        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\xampp64\\mysql\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.27\\bin\\mysqldump.exe',
            'C:\\wamp\\bin\\mysql\\mysql8.0.27\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
            'mysqldump',      // PATH fallback (Unix)
            'mysqldump.exe',  // PATH fallback (Windows)
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Last resort: check PATH using `where` (Windows) or `which` (Unix)
        $which = PHP_OS_FAMILY === 'Windows' ? 'where mysqldump 2>NUL' : 'which mysqldump 2>/dev/null';
        $result = trim(shell_exec($which) ?? '');

        if ($result && file_exists($result)) {
            return $result;
        }

        return null;
    }
}
