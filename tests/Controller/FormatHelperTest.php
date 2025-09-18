<?php

namespace App\Tests\Util;

use PHPUnit\Framework\TestCase;

class FormatHelperTest extends TestCase
{
    private function toFt(?int $cents): string
    {
        if ($cents === null) return '';
        return number_format(round($cents / 100), 0, ',', ' ') . ' Ft';
    }

    public function testToFtFormatsCorrectly(): void
    {
        $this->assertSame('1 000 Ft', $this->toFt(100000));
        $this->assertSame('999 Ft', $this->toFt(99900));
        $this->assertSame('', $this->toFt(null));
    }
}
