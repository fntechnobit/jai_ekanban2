<?php

namespace App\Services\Listing;

/**
 * Hasil pengambilan listing dari sebuah sumber data.
 */
class ListingFetchResult
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     *         Baris yang sudah dinormalkan ke bentuk kolom listing_stage.
     * @param  array<int, string>  $errors
     *         Kegagalan yang terjadi; conveyor yang gagal TIDAK boleh ikut direkonsiliasi.
     * @param  array<int, string>|null  $scopeConveyors
     *         Daftar conveyor yang berhasil diambil seluruhnya. null berarti seluruh
     *         rentang tanggal terambil tanpa pembatasan conveyor (jalur database lama).
     */
    public function __construct(
        public readonly array $rows,
        public readonly array $errors = [],
        public readonly ?array $scopeConveyors = null,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->rows === [];
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }
}
