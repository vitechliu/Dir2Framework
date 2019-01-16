<?php

namespace D2F;

use PHPUnit\Framework\TestCase;
use D2F\TestD;

class TestTest extends TestCase {
    public function testAdd() {
        $this->assertEquals(TestD::testAdd(1),2);
    }
}
