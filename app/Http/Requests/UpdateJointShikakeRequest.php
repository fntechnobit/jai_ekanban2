<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJointShikakeRequest extends FormRequest
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
            
            // JOINT process-specific fields
            'process_data.bonder_no' => 'nullable|string|max:255',
            'process_data.address' => 'nullable|string|max:255',
            'process_data.address_store' => 'nullable|string|max:255',
            'process_data.to_machine' => 'nullable|string|max:255',
            'process_data.barcode_process' => 'nullable|string|max:255',
            
            // CCT/Bonder pairs (1-5)
            'process_data.cct_no_1' => 'nullable|string|max:255',
            'process_data.bonder_no_1' => 'nullable|string|max:255',
            'process_data.cct_no_2' => 'nullable|string|max:255',
            'process_data.bonder_no_2' => 'nullable|string|max:255',
            'process_data.cct_no_3' => 'nullable|string|max:255',
            'process_data.bonder_no_3' => 'nullable|string|max:255',
            'process_data.cct_no_4' => 'nullable|string|max:255',
            'process_data.bonder_no_4' => 'nullable|string|max:255',
            'process_data.cct_no_5' => 'nullable|string|max:255',
            'process_data.bonder_no_5' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'process_data.bonder_no.required' => 'Bonder No is required for JOINT process.',
            'process_data.address.required' => 'Address is required for JOINT process.',
        ];
    }
}