<?php

namespace App\Imports;

use App\Models\MasterShikakeDblCrimp;
use App\Config\ShikakeTemplateConfig;
use App\Helpers\ImportHelper;

class MasterShikakeDblCrimpImport extends BaseShikakeImport
{
    public function __construct($conveyorId, $process = 'DBL CRIMP')
    {
        parent::__construct($conveyorId, $process);
    }

    protected function getExpectedHeaders(): array
    {
        return ShikakeTemplateConfig::getDblCrimpHeaders();
    }

    protected function getProcessName(): string
    {
        return 'Dbl Crimp';
    }

    protected function getAssyStartColumn(): int
    {
        return 10; // After Dbl Crimp column (index 9)
    }

    protected function mapProcessData(array $rowData, int $shikakeId): array
    {
        return [
            'master_shikake_id' => $shikakeId,
            'shield_no' => ImportHelper::cleanValue($rowData[8] ?? null),
            'dbl_crimp' => ImportHelper::cleanValue($rowData[9] ?? null),
        ];
    }

    protected function createProcessRecord(array $processData): void
    {
        MasterShikakeDblCrimp::create($processData);
    }
}
