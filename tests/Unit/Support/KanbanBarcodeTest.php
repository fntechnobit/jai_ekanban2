<?php

namespace Tests\Unit\Support;

use App\Support\KanbanBarcode;
use PHPUnit\Framework\TestCase;

class KanbanBarcodeTest extends TestCase
{
    /**
     * Tiru pembacaan scan STO Circuit (jai_sto_wip sto/views/circuit_scan/index_js.php):
     * bertitik → drawing, selain itu → CCT single per posisi tetap.
     */
    private function readAsSto(string $kanban): array
    {
        if (str_contains($kanban, '.')) {
            [$carline, $drawing, $issue, $qty, $nys] = array_pad(explode('.', $kanban, 5), 5, null);

            return ['type' => 'drawing', 'carline' => $carline, 'drawing' => $drawing, 'issue' => $issue, 'qty' => $qty, 'nys' => $nys];
        }

        return [
            'type' => 'cct',
            'carline' => substr($kanban, 0, 1),
            'cct' => substr($kanban, 1, 4),
            'issue' => substr($kanban, 5, 3),
            'qty' => substr($kanban, 8, 2),
            'nys' => substr($kanban, 10, 4),
        ];
    }

    public function test_circuit_kanban_is_read_by_sto_as_single_cct(): void
    {
        $barcode = KanbanBarcode::circuit('G', 'GE64', '003/012', 40, 4174);

        $this->assertSame('GGE64003404174', $barcode);
        $this->assertSame(
            ['type' => 'cct', 'carline' => 'G', 'cct' => 'GE64', 'issue' => '003', 'qty' => '40', 'nys' => '4174'],
            $this->readAsSto($barcode)
        );
        $this->assertNull(KanbanBarcode::stoProblem($barcode));
    }

    public function test_circuit_single_digit_qty_is_zero_padded_so_sto_reads_the_right_qty(): void
    {
        $barcode = KanbanBarcode::circuit('H', 'HA97', '025/030', 4, 768);

        $this->assertSame('HHA97025040768', $barcode);
        $this->assertSame('04', $this->readAsSto($barcode)['qty']);
        $this->assertSame('0768', $this->readAsSto($barcode)['nys']);
    }

    public function test_joint_kanban_is_read_by_sto_as_drawing(): void
    {
        $barcode = KanbanBarcode::drawing('P', 'B-0154XEB', '008/010', 16, 24);

        $this->assertSame('P.B-0154XEB.008.16.0024', $barcode);
        $this->assertSame(
            ['type' => 'drawing', 'carline' => 'P', 'drawing' => 'B-0154XEB', 'issue' => '008', 'qty' => '16', 'nys' => '0024'],
            $this->readAsSto($barcode)
        );
        $this->assertNull(KanbanBarcode::stoProblem($barcode));
    }

    public function test_drawing_keeps_single_digit_qty_unpadded(): void
    {
        $this->assertSame('C.B-L63P.013.3.5026', KanbanBarcode::drawing('C', 'B-L63P', '013/020', 3, 5026));
    }

    public function test_parse_concatenated_shikake_barcode_from_before_the_fix(): void
    {
        $this->assertSame(['P', 'B-0154XEB'], KanbanBarcode::parse('PB-0154XEB008160024', 'P', '008/010', 16, 24));
    }

    public function test_parse_dotted_barcode(): void
    {
        $this->assertSame(['T', 'TD36'], KanbanBarcode::parse('T.TD36.002.40.0002', 'T', '002/005', 40, 2));
    }

    public function test_parse_circuit_barcode_with_and_without_padded_qty(): void
    {
        $this->assertSame(['H', 'HA97'], KanbanBarcode::parse('HHA9702540768', 'H', '025/030', 4, 768));
        $this->assertSame(['H', 'HA97'], KanbanBarcode::parse('HHA97025040768', 'H', '025/030', 4, 768));
    }

    public function test_parse_rejects_barcode_that_does_not_match_the_kanban(): void
    {
        $this->assertNull(KanbanBarcode::parse('PB-0154XEB008160024', 'Q', '008/010', 16, 24));
        $this->assertNull(KanbanBarcode::parse('PB-0154XEB008160024', 'P', '009/010', 16, 24));
        $this->assertNull(KanbanBarcode::parse('CV1-TD36-0002', 'T', '002/005', 40, 2));
    }

    public function test_sto_problem_flags_circuit_barcodes_that_break_fixed_positions(): void
    {
        $this->assertNotNull(KanbanBarcode::stoProblem(KanbanBarcode::circuit('S', 'SC86A', '001/001', 20, 1)));
        $this->assertNotNull(KanbanBarcode::stoProblem(KanbanBarcode::circuit('G', 'GE64', '001/001', 100, 1)));
        $this->assertNotNull(KanbanBarcode::stoProblem(KanbanBarcode::circuit('GA', 'GE64', '001/001', 40, 1)));
    }

    public function test_sto_problem_flags_drawing_code_containing_a_dot(): void
    {
        $this->assertNotNull(KanbanBarcode::stoProblem(KanbanBarcode::drawing('P', 'B-01.5', '001/001', 16, 1)));
    }
}
