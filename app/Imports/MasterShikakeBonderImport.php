<?php

namespace App\Imports;

use App\Models\MasterShikakeBonder;
use App\Config\ShikakeTemplateConfig;
use App\Helpers\ImportHelper;

class MasterShikakeBonderImport extends BaseShikakeImport
{
    public function __construct($conveyorId, $process = 'BONDER')
    {
        parent::__construct($conveyorId, $process);
    }

    protected function getExpectedHeaders(): array
    {
        return ShikakeTemplateConfig::getBonderHeaders();
    }

    protected function getProcessName(): string
    {
        return 'Bonder';
    }

    protected function getAssyStartColumn(): int
    {
        return 43; // After Bonder No B 7 column (index 42)
    }

    protected function mapProcessData(array $rowData, int $shikakeId): array
    {
        return [
            'master_shikake_id' => $shikakeId,
            'bonder_no' => ImportHelper::cleanValue($rowData[8] ?? null),
            'address' => ImportHelper::cleanValue($rowData[9] ?? null),
            'dies' => ImportHelper::cleanValue($rowData[10] ?? null),
            'to_machine' => ImportHelper::cleanValue($rowData[11] ?? null),
            'barcode_navigasi' => ImportHelper::cleanValue($rowData[12] ?? null),
            'barcode_process' => ImportHelper::cleanValue($rowData[13] ?? null),
            'released_date' => ImportHelper::cleanDate($rowData[14] ?? null),
            'cct_no_a_1' => ImportHelper::cleanValue($rowData[15] ?? null),
            'bonder_no_a_1' => ImportHelper::cleanValue($rowData[16] ?? null),
            'cct_no_a_2' => ImportHelper::cleanValue($rowData[17] ?? null),
            'bonder_no_a_2' => ImportHelper::cleanValue($rowData[18] ?? null),
            'cct_no_a_3' => ImportHelper::cleanValue($rowData[19] ?? null),
            'bonder_no_a_3' => ImportHelper::cleanValue($rowData[20] ?? null),
            'cct_no_a_4' => ImportHelper::cleanValue($rowData[21] ?? null),
            'bonder_no_a_4' => ImportHelper::cleanValue($rowData[22] ?? null),
            'cct_no_a_5' => ImportHelper::cleanValue($rowData[23] ?? null),
            'bonder_no_a_5' => ImportHelper::cleanValue($rowData[24] ?? null),
            'cct_no_a_6' => ImportHelper::cleanValue($rowData[25] ?? null),
            'bonder_no_a_6' => ImportHelper::cleanValue($rowData[26] ?? null),
            'cct_no_a_7' => ImportHelper::cleanValue($rowData[27] ?? null),
            'bonder_no_a_7' => ImportHelper::cleanValue($rowData[28] ?? null),
            'cct_no_b_1' => ImportHelper::cleanValue($rowData[29] ?? null),
            'bonder_no_b_1' => ImportHelper::cleanValue($rowData[30] ?? null),
            'cct_no_b_2' => ImportHelper::cleanValue($rowData[31] ?? null),
            'bonder_no_b_2' => ImportHelper::cleanValue($rowData[32] ?? null),
            'cct_no_b_3' => ImportHelper::cleanValue($rowData[33] ?? null),
            'bonder_no_b_3' => ImportHelper::cleanValue($rowData[34] ?? null),
            'cct_no_b_4' => ImportHelper::cleanValue($rowData[35] ?? null),
            'bonder_no_b_4' => ImportHelper::cleanValue($rowData[36] ?? null),
            'cct_no_b_5' => ImportHelper::cleanValue($rowData[37] ?? null),
            'bonder_no_b_5' => ImportHelper::cleanValue($rowData[38] ?? null),
            'cct_no_b_6' => ImportHelper::cleanValue($rowData[39] ?? null),
            'bonder_no_b_6' => ImportHelper::cleanValue($rowData[40] ?? null),
            'cct_no_b_7' => ImportHelper::cleanValue($rowData[41] ?? null),
            'bonder_no_b_7' => ImportHelper::cleanValue($rowData[42] ?? null),
        ];
    }

    protected function createProcessRecord(array $processData): void
    {
        MasterShikakeBonder::create($processData);
    }
}
