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
        return 24; // After To 9 column (index 23) — 24 fixed columns total
    }

    /**
     * Get field mapping: database column => Excel header name
     * @return array
     */
    protected function getFieldMapping(): array
    {
        return [
            'shield_no' => 'Shield No',
            'address' => 'Address',
            'blade' => 'Blade',
            'qrcode_drawing' => 'QRCode Drawing',
            'cct_no_1' => 'CCT No 1',
            'address_no_1_1' => 'Address 1',
            'cct_no_2' => 'CCT No 2',
            'address_no_1_2' => 'Address 2',
            'to_1' => 'To 1',
            'to_2' => 'To 2',
            'to_3' => 'To 3',
            'to_4' => 'To 4',
            'to_5' => 'To 5',
            'to_6' => 'To 6',
            'to_7' => 'To 7',
            'to_8' => 'To 8',
            'to_9' => 'To 9',
        ];
    }

    protected function mapProcessData(array $rowData, int $shikakeId): array
    {
        $data = ['master_shikake_id' => $shikakeId];
        
        foreach ($this->getFieldMapping() as $dbColumn => $headerName) {
            $data[$dbColumn] = ImportHelper::cleanValue($this->getValueByHeader($rowData, $headerName));
        }
        
        return $data;
    }

    protected function createProcessRecord(array $processData): void
    {
        MasterShikakeShield::create($processData);
    }

    protected function deleteProcessRecord(int $shikakeId): void
    {
        MasterShikakeShield::where('master_shikake_id', $shikakeId)->delete();
    }
}
