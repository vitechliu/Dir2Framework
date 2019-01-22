<?php

namespace D2F;

use PHPUnit\Framework\TestCase;
use D2F\D2F;
use D2F\VersionRange;

class TestTest extends TestCase {
    public function testD2F() {
        print_r("\nTest D2F::\n");
        $d = new D2F;
        $this->assertInstanceOf(D2F::class,$d);
    }

    public function testVersionRange(){ 
        print_r("\nTest VersionRange::\n");
        $v = new VersionRange(">1.0.0");
        $v->addRange("^2.0.0");
        $v->addRange("<2.3.1");
        print_r((string)$v);
        $this->assertInstanceOf(VersionRange::class,$v);
    }
}
