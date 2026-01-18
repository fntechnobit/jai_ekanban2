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
        return 40; // After Bonder No B 7 column (index 39)
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
            'dies' => 'Dies',
            'to_machine' => 'To Machine',
            'barcode_navigasi' => 'Barcode Navigasi',
            'barcode_process' => 'Barcode Process',
            'cct_no_a_1' => 'CCT No A 1',
            'bonder_no_a_1' => 'Bonder No A 1',
            'cct_no_a_2' => 'CCT No A 2',
            'bonder_no_a_2' => 'Bonder No A 2',
            'cct_no_a_3' => 'CCT No A 3',
            'bonder_no_a_3' => 'Bonder No A 3',
            'cct_no_a_4' => 'CCT No A 4',
            'bonder_no_a_4' => 'Bonder No A 4',
            'cct_no_a_5' => 'CCT No A 5',
            'bonder_no_a_5' => 'Bonder No A 5',
            'cct_no_a_6' => 'CCT No A 6',
            'bonder_no_a_6' => 'Bonder No A 6',
            'cct_no_a_7' => 'CCT No A 7',
            'bonder_no_a_7' => 'Bonder No A 7',
            'cct_no_b_1' => 'CCT No B 1',
            'bonder_no_b_1' => 'Bonder No B 1',
            'cct_no_b_2' => 'CCT No B 2',
            'bonder_no_b_2' => 'Bonder No B 2',
            'cct_no_b_3' => 'CCT No B 3',
            'bonder_no_b_3' => 'Bonder No B 3',
            'cct_no_b_4' => 'CCT No B 4',
            'bonder_no_b_4' => 'Bonder No B 4',
            'cct_no_b_5' => 'CCT No B 5',
            'bonder_no_b_5' => 'Bonder No B 5',
            'cct_no_b_6' => 'CCT No B 6',
            'bonder_no_b_6' => 'Bonder No B 6',
            'cct_no_b_7' => 'CCT No B 7',
            'bonder_no_b_7' => 'Bonder No B 7',
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
        MasterShikakeBonder::create($processData);
    }
}
