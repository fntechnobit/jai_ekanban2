<?php

namespace App\Imports;

use App\Models\MasterShikakeTwist;
use App\Config\ShikakeTemplateConfig;
use App\Helpers\ImportHelper;

class MasterShikakeTwistImport extends BaseShikakeImport
{
    public function __construct($conveyorId, $process = 'TWIST')
    {
        parent::__construct($conveyorId, $process);
    }

    protected function getExpectedHeaders(): array
    {
        return ShikakeTemplateConfig::getTwistHeaders();
    }

    protected function getProcessName(): string
    {
        return 'Twist';
    }

    protected function getAssyStartColumn(): int
    {
        return 34; // After Released Date column (index 33)
    }

    protected function mapProcessData(array $rowData, int $shikakeId): array
    {
        return [
            'master_shikake_id' => $shikakeId,
            'cct_code' => ImportHelper::cleanValue($rowData[8] ?? null),
            'cct_no' => ImportHelper::cleanValue($rowData[9] ?? null),
            'machine_twist' => ImportHelper::cleanValue($rowData[10] ?? null),
            'sequence_2' => ImportHelper::cleanNumeric($rowData[11] ?? null),
            'barcode_navigasi' => ImportHelper::cleanValue($rowData[12] ?? null),
            'barcode_process' => ImportHelper::cleanValue($rowData[13] ?? null),
            'barcode_shikake' => ImportHelper::cleanValue($rowData[14] ?? null),
            'to_store' => ImportHelper::cleanValue($rowData[15] ?? null),
            'cust_no' => ImportHelper::cleanValue($rowData[16] ?? null),
            'kind' => ImportHelper::cleanValue($rowData[17] ?? null),
            'size' => ImportHelper::cleanValue($rowData[18] ?? null),
            'color' => ImportHelper::cleanValue($rowData[19] ?? null),
            'cl' => ImportHelper::cleanValue($rowData[20] ?? null),
            'terminal_a' => ImportHelper::cleanValue($rowData[21] ?? null),
            'acc_1_a' => ImportHelper::cleanValue($rowData[22] ?? null),
            'tube_a' => ImportHelper::cleanValue($rowData[23] ?? null),
            'note_a' => ImportHelper::cleanValue($rowData[24] ?? null),
            'strip_a' => ImportHelper::cleanValue($rowData[25] ?? null),
            'mark_a' => ImportHelper::cleanValue($rowData[26] ?? null),
            'terminal_b' => ImportHelper::cleanValue($rowData[27] ?? null),
            'acc_1_ab' => ImportHelper::cleanValue($rowData[28] ?? null),
            'tube_b' => ImportHelper::cleanValue($rowData[29] ?? null),
            'note_b' => ImportHelper::cleanValue($rowData[30] ?? null),
            'strip_b' => ImportHelper::cleanValue($rowData[31] ?? null),
            'mark_b' => ImportHelper::cleanValue($rowData[32] ?? null),
            'released_date' => ImportHelper::cleanDate($rowData[33] ?? null),
        ];
    }

    protected function createProcessRecord(array $processData): void
    {
        MasterShikakeTwist::create($processData);
    }
}
