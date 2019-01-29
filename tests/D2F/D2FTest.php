<?php

namespace D2F;

use PHPUnit\Framework\TestCase;
use vitech\D2F\D2F;
use vitech\D2F\VersionRange;

class D2FTest extends TestCase {
    public function testD2F() {
        print_r("\nTest D2F::\n");
        $d = new D2F;
        $testdir = [
            "/app","/bootstrap","/config"
        ];
        print_r($d->analyze($testdir));
        $this->assertInstanceOf(D2F::class,$d);
    }
}
