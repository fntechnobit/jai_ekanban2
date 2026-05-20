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
        return 22; // After Bonder No 5 column (index 21)
    }

    /**
     * Get field mapping: database column => Excel header name
     * @return array
     */
    protected function getFieldMapping(): array
    {
        return [
            'bonder_no' => 'Bonder No',
            'address' => 'Address',
            'address_store' => 'Address Store',
            'to_machine' => 'To Machine',
            'barcode_process' => 'Barcode Process',
            'qrcode_drawing' => 'QRCode Drawing',
            'cct_no_1' => 'CCT No 1',
            'bonder_no_1' => 'Bonder No 1',
            'cct_no_2' => 'CCT No 2',
            'bonder_no_2' => 'Bonder No 2',
            'cct_no_3' => 'CCT No 3',
            'bonder_no_3' => 'Bonder No 3',
            'cct_no_4' => 'CCT No 4',
            'bonder_no_4' => 'Bonder No 4',
            'cct_no_5' => 'CCT No 5',
            'bonder_no_5' => 'Bonder No 5',
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
        MasterShikakeJoint::create($processData);
    }

    protected function deleteProcessRecord(int $shikakeId): void
    {
        MasterShikakeJoint::where('master_shikake_id', $shikakeId)->delete();
    }
}
