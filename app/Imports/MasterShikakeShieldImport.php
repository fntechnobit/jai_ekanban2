<?php

namespace App\Imports;

use App\Models\MasterShikakeShield;
use App\Config\ShikakeTemplateConfig;
use App\Helpers\ImportHelper;

class MasterShikakeShieldImport extends BaseShikakeImport
{
    public function __construct($conveyorId, $process = 'SHIELD')
    {
        parent::__construct($conveyorId, $process);
    }

    protected function getExpectedHeaders(): array
    {
        return ShikakeTemplateConfig::getShieldHeaders();
    }

    protected function getProcessName(): string
    {
        return 'Shield';
    }

    protected function getAssyStartColumn(): int
    {
        return 24; // After To 9 column (index 23)
    }

    protected function mapProcessData(array $rowData, int $shikakeId): array
    {
        return [
            'master_shikake_id' => $shikakeId,
            'shield_no' => ImportHelper::cleanValue($rowData[8] ?? null),
            'address' => ImportHelper::cleanValue($rowData[9] ?? null),
            'blade' => ImportHelper::cleanValue($rowData[10] ?? null),
            'cct_no_1' => ImportHelper::cleanValue($rowData[11] ?? null),
            'bonder_no_1' => ImportHelper::cleanValue($rowData[12] ?? null),
            'cct_no_2' => ImportHelper::cleanValue($rowData[13] ?? null),
            'bonder_no_2' => ImportHelper::cleanValue($rowData[14] ?? null),
            'to_1' => ImportHelper::cleanValue($rowData[15] ?? null),
            'to_2' => ImportHelper::cleanValue($rowData[16] ?? null),
            'to_3' => ImportHelper::cleanValue($rowData[17] ?? null),
            'to_4' => ImportHelper::cleanValue($rowData[18] ?? null),
            'to_5' => ImportHelper::cleanValue($rowData[19] ?? null),
            'to_6' => ImportHelper::cleanValue($rowData[20] ?? null),
            'to_7' => ImportHelper::cleanValue($rowData[21] ?? null),
            'to_8' => ImportHelper::cleanValue($rowData[22] ?? null),
            'to_9' => ImportHelper::cleanValue($rowData[23] ?? null),
        ];
    }

    protected function createProcessRecord(array $processData): void
    {
        MasterShikakeShield::create($processData);
    }
}
