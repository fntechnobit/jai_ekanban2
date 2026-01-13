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
            'process_data.drawing_no' => 'nullable|string|max:255',
            'process_data.address' => 'nullable|string|max:255',
            'process_data.barcode_mesin' => 'nullable|string|max:255',
            'process_data.to_machine' => 'nullable|string|max:255',
            'process_data.cct_no_1' => 'nullable|string|max:255',
            'process_data.address_1' => 'nullable|string|max:255',
            'process_data.cct_no_2' => 'nullable|string|max:255',
            'process_data.address_2' => 'nullable|string|max:255',
            'process_data.cct_no_3' => 'nullable|string|max:255',
            'process_data.address_3' => 'nullable|string|max:255',
            'process_data.cct_no_4' => 'nullable|string|max:255',
            'process_data.address_4' => 'nullable|string|max:255',
            'process_data.cct_no_5' => 'nullable|string|max:255',
            'process_data.address_5' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            //
        ];
    }
}