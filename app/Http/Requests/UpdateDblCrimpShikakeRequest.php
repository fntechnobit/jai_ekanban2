<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDblCrimpShikakeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Main shikake fields
            'conveyor_id' => 'nullable|exists:master_conveyor,id',
            'process' => 'required|string',
            'machine' => 'nullable|string|max:255',
            'qty' => 'nullable|integer|min:0',
            'issue' => 'nullable|string|max:255',
            'barcode_kanban' => 'nullable|string|max:255',
            'family' => 'nullable|string|max:255',
            'released_date' => 'nullable|date',
            'released_note' => 'nullable|string|max:500',
            'sequence' => 'nullable|integer|min:0',
            
            // DBL CRIMP process-specific fields
            'process_data.shield_no' => 'required|string|max:255',
            'process_data.dbl_crimp' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'process_data.shield_no.required' => 'Shield No is required for DBL CRIMP process.',
            'process_data.dbl_crimp.required' => 'DBL Crimp is required for DBL CRIMP process.',
        ];
    }
}