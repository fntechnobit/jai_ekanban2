<?php

namespace App\Services\Listing;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Klien HTTP untuk API SIREP.
 *
 * Endpoint yang tersedia (per spesifikasi tim PPC):
 *   GET {base}/listing?conveyor=..&start_date=..&end_date=..
 *   GET {base}/conveyor
 *
 * Catatan bentuk respons: endpoint listing mengembalikan array telanjang,
 * sedangkan conveyor memakai amplop {"data": [...]}. unwrap() menangani keduanya
 * sehingga pemanggil tidak perlu tahu perbedaannya.
 */
class SirepApiClient
{
    private string $baseUrl;
    private int $timeout;
    private int $retry;
    private int $retryDelay;
    private ?string $token;
    private int $concurrency;

    public function __construct()
    {
        $config = config('sirep.api');

        $this->baseUrl     = rtrim($config['base_url'], '/');
        $this->timeout     = $config['timeout'];
        $this->retry       = max(1, $config['retry']);
        $this->retryDelay  = $config['retry_delay'];
        $this->token       = $config['token'] ?: null;
        $this->concurrency = max(1, $config['concurrency']);
    }

    /**
     * Ambil listing untuk satu conveyor pada satu rentang tanggal.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws \RuntimeException bila API tidak dapat dihubungi atau membalas galat
     */
    public function fetchListing(string $conveyor, string $startDate, string $endDate): array
    {
        $response = $this->request()->get($this->baseUrl . '/listing', [
            'conveyor'   => $conveyor,
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "API listing gagal untuk conveyor {$conveyor} ({$startDate}..{$endDate}): "
                . 'HTTP ' . $response->status()
            );
        }

        return $this->unwrap($response->json());
    }

    /**
     * Ambil listing untuk banyak kombinasi conveyor × rentang tanggal sekaligus.
     *
     * Permintaan dijalankan bersamaan (dibatasi `concurrency`) karena API hanya
     * menerima satu conveyor per panggilan; tanpa paralelisasi, satu sinkronisasi
     * berubah menjadi puluhan permintaan berurutan.
     *
     * @param  array<int, array{conveyor: string, start: string, end: string}>  $requests
     * @return array{rows: array<string, array<int, array<string, mixed>>>, errors: array<int, string>}
     *         rows di-key berdasarkan nama conveyor; hasil beberapa jendela tanggal digabung.
     */
    public function fetchListingBatch(array $requests): array
    {
        $rows   = [];
        $errors = [];

        foreach (array_chunk($requests, $this->concurrency) as $batch) {
            $responses = Http::pool(function (Pool $pool) use ($batch) {
                $calls = [];

                foreach ($batch as $i => $req) {
                    $request = $pool->as((string) $i)
                        ->timeout($this->timeout)
                        ->retry($this->retry, $this->retryDelay, throw: false)
                        ->acceptJson();

                    if ($this->token) {
                        $request = $request->withToken($this->token);
                    }

                    $calls[] = $request->get($this->baseUrl . '/listing', [
                        'conveyor'   => $req['conveyor'],
                        'start_date' => $req['start'],
                        'end_date'   => $req['end'],
                    ]);
                }

                return $calls;
            });

            foreach ($batch as $i => $req) {
                $label    = "{$req['conveyor']} ({$req['start']}..{$req['end']})";
                $response = $responses[(string) $i] ?? null;

                // Pool mengembalikan objek exception, bukan melemparkannya.
                if ($response instanceof \Throwable) {
                    $errors[] = "Gagal menghubungi API untuk {$label}: " . $response->getMessage();
                    Log::error('SIREP API request failed', ['request' => $req, 'error' => $response->getMessage()]);
                    continue;
                }

                if ($response === null || !$response->successful()) {
                    $status   = $response ? $response->status() : 'tidak ada respons';
                    $errors[] = "API membalas galat untuk {$label}: HTTP {$status}";
                    Log::error('SIREP API returned error', ['request' => $req, 'status' => $status]);
                    continue;
                }

                $conveyor = $req['conveyor'];
                $rows[$conveyor] = array_merge($rows[$conveyor] ?? [], $this->unwrap($response->json()));
            }
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * Ambil daftar conveyor beserta kapasitasnya.
     *
     * Field yang dikembalikan API: id, name, normal_capacity, overtime_capacity.
     * Nilai kapasitas dapat bernilai null untuk conveyor yang belum diatur di SIREP.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchConveyors(): array
    {
        $response = $this->request()->get($this->baseUrl . '/conveyor');

        if (!$response->successful()) {
            throw new \RuntimeException('API conveyor gagal: HTTP ' . $response->status());
        }

        return $this->unwrap($response->json());
    }

    /**
     * Uji ketersediaan API. Dipanggil sebelum sinkronisasi agar kegagalan
     * jaringan berhenti dengan pesan jelas, bukan menghasilkan data separuh jalan.
     *
     * @return array{ok: bool, message: string}
     */
    public function ping(): array
    {
        try {
            $response = Http::timeout(min(10, $this->timeout))
                ->acceptJson()
                ->get($this->baseUrl . '/conveyor');

            if (!$response->successful()) {
                return ['ok' => false, 'message' => 'API SIREP membalas HTTP ' . $response->status()];
            }

            return ['ok' => true, 'message' => 'API SIREP dapat dihubungi'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Tidak dapat menghubungi API SIREP: ' . $e->getMessage()];
        }
    }

    /**
     * Permintaan tunggal dengan timeout, retry, dan token bila tersedia.
     */
    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::timeout($this->timeout)
            ->retry($this->retry, $this->retryDelay, throw: false)
            ->acceptJson();

        return $this->token ? $request->withToken($this->token) : $request;
    }

    /**
     * Ambil isi array dari respons, baik yang berbentuk array telanjang
     * maupun yang dibungkus amplop {"data": [...]}.
     *
     * @param  mixed  $payload
     * @return array<int, array<string, mixed>>
     */
    private function unwrap($payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        if (array_key_exists('data', $payload) && is_array($payload['data'])) {
            $payload = $payload['data'];
        }

        // Sisakan hanya elemen berbentuk baris (array asosiatif).
        return array_values(array_filter($payload, 'is_array'));
    }
}
