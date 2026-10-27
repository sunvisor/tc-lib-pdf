<?php

declare(strict_types=1);

namespace Test;

final class VariationSequencesTest extends TestUtil
{
    public static function setUpBeforeClass(): void
    {
        self::setUpFontsPath();
    }

    /** @throws \Throwable */
    private function document(): TestableText
    {
        $pdf = new TestableText('mm', true, false, false);
        $pdf->font->insert($pdf->pon, 'freeserif', '', 12, spacing: 2);
        return $pdf;
    }

    /** @throws \Throwable */
    public function testLayoutDoesNotSplitVariationSequences(): void
    {
        $pdf = $this->document();
        [, $ords, $dim] = $pdf->exposePrepareText("A\u{E0100}A\u{E0100}");
        self::assertSame([65, 0xE_0100, 65, 0xE_0100], $ords);
        self::assertEqualsWithDelta($pdf->font->getOrdArrWidth([65, 65]), $dim['totwidth'], 0.0001);
        $lines = $pdf->exposeSplitLines($ords, $dim, $pdf->font->getCharWidth(65) + 0.1);
        self::assertSame([2, 2], \array_column($lines, 'chars'));
    }

    /** @throws \Throwable */
    public function testTruncationKeepsTheSelectorWithItsBase(): void
    {
        $pdf = $this->document();
        $ords = [65, 0xE_0100, 65, 0xE_0100, 65, 0xE_0100];
        $method = new \ReflectionMethod($pdf, 'fitTextCellByTruncation');
        $width = $pdf->font->getOrdArrWidth([65, 0xE_0100, 46, 46, 46]) + 0.1;
        /** @var mixed $trimmed */
        $trimmed = $method->invoke($pdf, $ords, $width, $pdf->font->getCurrentFont()['height'] + 0.1, 0.0, 0.0);
        self::assertIsArray($trimmed);
        self::assertContains(65, $trimmed);
        self::assertLessThan(\count($ords) + 3, \count($trimmed));
        /** @var mixed $ord */
        foreach ($trimmed as $index => $ord) {
            self::assertIsInt($index);
            self::assertIsInt($ord);
            if ($ord === 65) {
                self::assertSame(0xE_0100, $trimmed[$index + 1] ?? null);
            }
        }
    }
}
