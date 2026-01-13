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
        return 33; // After Mark B column (index 32)
    }

    /**
     * Get field mapping: database column => Excel header name
     * @return array
     */
    protected function getFieldMapping(): array
    {
        return [
            'cct_code' => 'CCT Code',
            'cct_no' => 'CCT No',
            'machine_twist' => 'Machine Twist',
            'sequence_2' => 'Sequence 2',
            'barcode_navigasi' => 'Barcode Navigasi',
            'barcode_process' => 'Barcode Process',
            'barcode_shikake' => 'Barcode Shikake',
            'to_store' => 'To Store',
            'cust_no' => 'Cust No',
            'kind' => 'Kind',
            'size' => 'Size',
            'color' => 'Color',
            'cl' => 'CL',
            'terminal_a' => 'Terminal A',
            'acc_1_a' => 'Acc 1 A',
            'tube_a' => 'Tube A',
            'note_a' => 'Note A',
            'strip_a' => 'Strip A',
            'mark_a' => 'Mark A',
            'terminal_b' => 'Terminal B',
            'acc_1_ab' => 'Acc 1 AB',
            'tube_b' => 'Tube B',
            'note_b' => 'Note B',
            'strip_b' => 'Strip B',
            'mark_b' => 'Mark B',
        ];
    }

    protected function mapProcessData(array $rowData, int $shikakeId): array
    {
        $data = ['master_shikake_id' => $shikakeId];
        
        foreach ($this->getFieldMapping() as $dbColumn => $headerName) {
            if ($dbColumn === 'sequence_2') {
                $data[$dbColumn] = ImportHelper::cleanNumeric($this->getValueByHeader($rowData, $headerName));
            } else {
                $data[$dbColumn] = ImportHelper::cleanValue($this->getValueByHeader($rowData, $headerName));
            }
        }
        
        return $data;
    }

    protected function createProcessRecord(array $processData): void
    {
        MasterShikakeTwist::create($processData);
    }
}
