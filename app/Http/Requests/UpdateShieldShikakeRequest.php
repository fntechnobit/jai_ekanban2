<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShieldShikakeRequest extends FormRequest
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
            'family' => 'nullable|string|max:255',
            'sequence' => 'nullable|integer|min:0',
            
            // SHIELD process-specific fields
            'process_data.shield_no' => 'required|string|max:255',
            'process_data.address' => 'nullable|string|max:255',
            'process_data.blade' => 'nullable|string|max:255',
            'process_data.to_machine' => 'nullable|string|max:255',
            'process_data.qrcode_drawing' => 'nullable|string|max:255',
            
            // CCT/Address pairs
            'process_data.cct_no_1' => 'nullable|string|max:255',
            'process_data.address_no_1_1' => 'nullable|string|max:255',
            'process_data.cct_no_2' => 'nullable|string|max:255',
            'process_data.address_no_1_2' => 'nullable|string|max:255',
            
            // To fields (1-9)
            'process_data.to_1' => 'nullable|string|max:255',
            'process_data.to_2' => 'nullable|string|max:255',
            'process_data.to_3' => 'nullable|string|max:255',
            'process_data.to_4' => 'nullable|string|max:255',
            'process_data.to_5' => 'nullable|string|max:255',
            'process_data.to_6' => 'nullable|string|max:255',
            'process_data.to_7' => 'nullable|string|max:255',
            'process_data.to_8' => 'nullable|string|max:255',
            'process_data.to_9' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'process_data.shield_no.required' => 'Shield No is required for SHIELD process.',
        ];
    }
}