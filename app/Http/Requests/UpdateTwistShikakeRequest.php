<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTwistShikakeRequest extends FormRequest
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
            
            // TWIST process-specific fields
            'process_data.cct_no' => 'required|string|max:255',
            'process_data.cct_code' => 'required|string|max:255',
            'process_data.machine_twist' => 'nullable|string|max:255',
            'process_data.sequence_2' => 'nullable|integer',
            'process_data.barcode_navigasi' => 'nullable|string|max:255',
            'process_data.barcode_process' => 'nullable|string|max:255',
            'process_data.barcode_shikake' => 'nullable|string|max:255',
            'process_data.to_store' => 'nullable|string|max:255',
            'process_data.cust_no' => 'nullable|string|max:255',
            'process_data.kind' => 'nullable|string|max:255',
            'process_data.size' => 'nullable|string|max:255',
            'process_data.color' => 'nullable|string|max:255',
            'process_data.cl' => 'nullable|string|max:255',
            'process_data.terminal_a' => 'nullable|string|max:255',
            'process_data.acc_1_a' => 'nullable|string|max:255',
            'process_data.tube_a' => 'nullable|string|max:255',
            'process_data.note_a' => 'nullable|string|max:255',
            'process_data.strip_a' => 'nullable|string|max:255',
            'process_data.mark_a' => 'nullable|string|max:255',
            'process_data.terminal_b' => 'nullable|string|max:255',
            'process_data.acc_1_ab' => 'nullable|string|max:255',
            'process_data.tube_b' => 'nullable|string|max:255',
            'process_data.note_b' => 'nullable|string|max:255',
            'process_data.strip_b' => 'nullable|string|max:255',
            'process_data.mark_b' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'process_data.cct_no.required' => 'CCT No is required for TWIST process.',
            'process_data.cct_code.required' => 'CCT Code is required for TWIST process.',
        ];
    }
}