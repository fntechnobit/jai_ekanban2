<?php

namespace Tests\Unit\Support;

use App\Support\KanbanBarcode;
use PHPUnit\Framework\TestCase;

class KanbanBarcodeTest extends TestCase
{
    /**
     * Tiru pembacaan scan STO Circuit (regex KANBAN_DRAWING / KANBAN_CCT di
     * jai_sto_wip sto/views/circuit_scan/index_js.php). Null bila ditolak STO.
     */
    private function readAsSto(string $kanban): ?array
    {
        if (preg_match('/^([A-Z0-9])\.([A-Z0-9][A-Z0-9 -]*(?:\.[A-Z][A-Z0-9 -]*)*)\.(\d{3})\.(\d{1,3})\.(\d{4})$/i', $kanban, $m)) {
            return ['type' => 'drawing', 'carline' => $m[1], 'drawing' => str_replace('.', '', $m[2]), 'issue' => $m[3], 'qty' => $m[4], 'nys' => $m[5]];
        }
        if (preg_match('/^([A-Z0-9])([A-Z0-9-]{4,10})(\d{3})(\d{2})(\d{4})$/i', $kanban, $m)) {
            return ['type' => 'cct', 'carline' => $m[1], 'cct' => $m[2], 'issue' => $m[3], 'qty' => $m[4], 'nys' => $m[5]];
        }

        return null;
    }

    public function test_circuit_kanban_is_read_by_sto_as_single_cct(): void
    {
        $barcode = KanbanBarcode::circuit('G', 'GE64', '003/012', 40, 4174);

        $this->assertSame('GGE64003404174', $barcode);
        $this->assertSame(
            ['type' => 'cct', 'carline' => 'G', 'cct' => 'GE64', 'issue' => '003', 'qty' => '40', 'nys' => '4174'],
            $this->readAsSto($barcode)
        );
        $this->assertNull(KanbanBarcode::circuitProblem('G', 'GE64', 40));
    }

    public function test_circuit_kanban_with_five_char_cct_is_read_whole(): void
    {
        $barcode = KanbanBarcode::circuit('S', 'SC86A', '001/003', 20, 1);

        $this->assertSame('SSC86A001200001', $barcode);
        $this->assertSame('SC86A', $this->readAsSto($barcode)['cct']);
        $this->assertNull(KanbanBarcode::circuitProblem('S', 'SC86A', 20));
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
        $this->assertNull(KanbanBarcode::drawingProblem('P', 'B-0154XEB', 16));
    }

    public function test_drawing_keeps_single_digit_qty_unpadded(): void
    {
        $this->assertSame('C.B-L63P.013.3.5026', KanbanBarcode::drawing('C', 'B-L63P', '013/020', 3, 5026));
    }

    public function test_drawing_drops_dots_to_match_sto_master(): void
    {
        // Master shikake menulis B-L148.A, master STO B-L148A
        $barcode = KanbanBarcode::drawing('C', 'B-L148.A', '001/003', 3, 101);

        $this->assertSame('C.B-L148A.001.3.0101', $barcode);
        $this->assertSame('B-L148A', $this->readAsSto($barcode)['drawing']);
        $this->assertNull(KanbanBarcode::drawingProblem('C', 'B-L148.A', 3));
    }

    public function test_parse_barcode_whose_drawing_still_has_a_dot(): void
    {
        $this->assertSame(['C', 'B-L148.A'], KanbanBarcode::parse('C.B-L148.A.001.3.0101', 'C', '001/003', 3, 101));
        $this->assertSame(['C', 'B-L148.A'], KanbanBarcode::parse('CB-L148.A00130101', 'C', '001/003', 3, 101));
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

    public function test_circuit_problem_flags_what_sto_cannot_read(): void
    {
        $this->assertNotNull(KanbanBarcode::circuitProblem('G', 'GE64', 100));
        $this->assertNotNull(KanbanBarcode::circuitProblem('GA', 'GE64', 40));
        $this->assertNotNull(KanbanBarcode::circuitProblem('', 'GE64', 40));
        $this->assertNotNull(KanbanBarcode::circuitProblem('G', 'GE6', 40));
        // carline 2 huruf: STO mengambil 1 huruf, sisanya ikut terbaca sebagai CCT
        $this->assertSame('AGE64', $this->readAsSto(KanbanBarcode::circuit('GA', 'GE64', '001/001', 40, 1))['cct']);
    }

    public function test_drawing_problem_flags_what_sto_cannot_read(): void
    {
        $this->assertNotNull(KanbanBarcode::drawingProblem('P', 'B-01/5', 16));
        $this->assertNotNull(KanbanBarcode::drawingProblem('PX', 'B-0154XEB', 16));
        $this->assertNotNull(KanbanBarcode::drawingProblem('P', 'B-0154XEB', 1000));
        $this->assertNull(KanbanBarcode::drawingProblem('K', 'BC-0138-1 B', 10));
        $this->assertNull($this->readAsSto(KanbanBarcode::drawing('P', 'B-01/5', '001/001', 16, 1)));
    }
}
