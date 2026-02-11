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
        return 20; // After Address 5 column (index 19)
    }

    /**
     * Get field mapping: database column => Excel header name
     * @return array
     */
    protected function getFieldMapping(): array
    {
        return [
            'drawing_no' => 'Drawing No',
            'address' => 'Address',
            'barcode_mesin' => 'Barcode Mesin',
            'to_machine' => 'To Machine',
            'cct_no_1' => 'CCT No 1',
            'address_1' => 'Address 1',
            'cct_no_2' => 'CCT No 2',
            'address_2' => 'Address 2',
            'cct_no_3' => 'CCT No 3',
            'address_3' => 'Address 3',
            'cct_no_4' => 'CCT No 4',
            'address_4' => 'Address 4',
            'cct_no_5' => 'CCT No 5',
            'address_5' => 'Address 5',
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
        MasterShikakeDblCrimp::create($processData);
    }
}
