<?php

namespace App\Imports;

use App\Models\MasterShikakeJoint;
use App\Config\ShikakeTemplateConfig;
use App\Helpers\ImportHelper;

class MasterShikakeJointImport extends BaseShikakeImport
{
    public function __construct($conveyorId, $process = 'JOINT')
    {
        parent::__construct($conveyorId, $process);
    }

    protected function getExpectedHeaders(): array
    {
        return ShikakeTemplateConfig::getJointHeaders();
    }

    protected function getProcessName(): string
    {
        return 'Joint';
    }

    protected function getAssyStartColumn(): int
    {
        return 24; // After Bonder No 5 column (index 23)
    }

    protected function mapProcessData(array $rowData, int $shikakeId): array
    {
        return [
            'master_shikake_id' => $shikakeId,
            'bonder_no' => ImportHelper::cleanValue($rowData[8] ?? null),
            'address' => ImportHelper::cleanValue($rowData[9] ?? null),
            'address_store' => ImportHelper::cleanValue($rowData[10] ?? null),
            'to_machine' => ImportHelper::cleanValue($rowData[11] ?? null),
            'barcode_process' => ImportHelper::cleanValue($rowData[12] ?? null),
            'released_date' => ImportHelper::cleanDate($rowData[13] ?? null),
            'cct_no_1' => ImportHelper::cleanValue($rowData[14] ?? null),
            'bonder_no_1' => ImportHelper::cleanValue($rowData[15] ?? null),
            'cct_no_2' => ImportHelper::cleanValue($rowData[16] ?? null),
            'bonder_no_2' => ImportHelper::cleanValue($rowData[17] ?? null),
            'cct_no_3' => ImportHelper::cleanValue($rowData[18] ?? null),
            'bonder_no_3' => ImportHelper::cleanValue($rowData[19] ?? null),
            'cct_no_4' => ImportHelper::cleanValue($rowData[20] ?? null),
            'bonder_no_4' => ImportHelper::cleanValue($rowData[21] ?? null),
            'cct_no_5' => ImportHelper::cleanValue($rowData[22] ?? null),
            'bonder_no_5' => ImportHelper::cleanValue($rowData[23] ?? null),
        ];
    }

    protected function createProcessRecord(array $processData): void
    {
        MasterShikakeJoint::create($processData);
    }
}
