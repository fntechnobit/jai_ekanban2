<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManualListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan bentuk saja. Aturan yang butuh data lain (assy code dari riwayat,
     * kunci ganda) dicek ManualListingService supaya form dan import sama.
     */
    public function rules(): array
    {
        return [
            'listing_date' => ['required', 'date'],
            // Hanya conveyor yang masih aktif di SIREP yang dijadwalkan saat generate.
            'conveyor' => [
                'required',
                'string',
                Rule::exists('master_conveyor', 'conveyor')->where(function ($query) {
                    $query->whereNull('deleted_at')->where('is_active', 1);
                }),
            ],
            'assy' => ['required', 'string', 'max:255'],
            'assycode' => ['nullable', 'string', 'max:255'],
            'qty' => ['required', 'integer', 'min:1'],
            'seq' => ['required', 'integer', 'min:0'],
            'carline' => ['nullable', 'string', 'max:255'],
            'snp' => ['nullable', 'integer', 'min:0'],
            'snpa' => ['nullable', 'integer', 'min:0'],
            'plt' => ['nullable', 'integer', 'min:0'],
            'mode' => ['nullable', 'integer', Rule::in([0, 1, 2])],
            'remark' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'listing_date' => 'Tanggal',
            'conveyor' => 'Conveyor',
            'assy' => 'Assy Number',
            'assycode' => 'Assy Code',
            'qty' => 'Qty',
            'seq' => 'Seq',
            'carline' => 'Carline',
            'snp' => 'SNP',
            'snpa' => 'SNPA',
            'plt' => 'PLT',
            'mode' => 'Mode',
            'remark' => 'Keterangan',
        ];
    }

    public function messages(): array
    {
        return [
            'conveyor.exists' => 'Conveyor tidak ditemukan atau sudah tidak aktif di SIREP.',
        ];
    }
}
