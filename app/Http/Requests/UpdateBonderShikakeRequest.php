<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBonderShikakeRequest extends FormRequest
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
            
            // BONDER process-specific fields
            'process_data.bonder_no' => 'nullable|string|max:255',
            'process_data.address' => 'nullable|string|max:255',
            'process_data.dies' => 'nullable|string|max:255',
            'process_data.to_machine' => 'nullable|string|max:255',
            'process_data.barcode_navigasi' => 'nullable|string|max:255',
            'process_data.barcode_process' => 'nullable|string|max:255',
            
            // Side A CCT/Bonder pairs (1-7)
            'process_data.cct_no_a_1' => 'nullable|string|max:255',
            'process_data.bonder_no_a_1' => 'nullable|string|max:255',
            'process_data.cct_no_a_2' => 'nullable|string|max:255',
            'process_data.bonder_no_a_2' => 'nullable|string|max:255',
            'process_data.cct_no_a_3' => 'nullable|string|max:255',
            'process_data.bonder_no_a_3' => 'nullable|string|max:255',
            'process_data.cct_no_a_4' => 'nullable|string|max:255',
            'process_data.bonder_no_a_4' => 'nullable|string|max:255',
            'process_data.cct_no_a_5' => 'nullable|string|max:255',
            'process_data.bonder_no_a_5' => 'nullable|string|max:255',
            'process_data.cct_no_a_6' => 'nullable|string|max:255',
            'process_data.bonder_no_a_6' => 'nullable|string|max:255',
            'process_data.cct_no_a_7' => 'nullable|string|max:255',
            'process_data.bonder_no_a_7' => 'nullable|string|max:255',
            
            // Side B CCT/Bonder pairs (1-7)
            'process_data.cct_no_b_1' => 'nullable|string|max:255',
            'process_data.bonder_no_b_1' => 'nullable|string|max:255',
            'process_data.cct_no_b_2' => 'nullable|string|max:255',
            'process_data.bonder_no_b_2' => 'nullable|string|max:255',
            'process_data.cct_no_b_3' => 'nullable|string|max:255',
            'process_data.bonder_no_b_3' => 'nullable|string|max:255',
            'process_data.cct_no_b_4' => 'nullable|string|max:255',
            'process_data.bonder_no_b_4' => 'nullable|string|max:255',
            'process_data.cct_no_b_5' => 'nullable|string|max:255',
            'process_data.bonder_no_b_5' => 'nullable|string|max:255',
            'process_data.cct_no_b_6' => 'nullable|string|max:255',
            'process_data.bonder_no_b_6' => 'nullable|string|max:255',
            'process_data.cct_no_b_7' => 'nullable|string|max:255',
            'process_data.bonder_no_b_7' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'process_data.bonder_no.required' => 'Bonder No is required for BONDER process.',
        ];
    }
}