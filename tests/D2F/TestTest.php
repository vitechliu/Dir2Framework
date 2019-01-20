<?php

namespace D2F;

use PHPUnit\Framework\TestCase;
use D2F\D2F;

class TestTest extends TestCase {
    public function testAdd() {
        $d = new D2F;
        //$d->print();
        $this->assertInstanceOf(D2F::class,$d);
    }
}
