<?php

namespace Rector\PHPUnit\Tests\Rector\MethodCall\AssertPropertyExistsRector\Fixture;

final class SkipStaticCall extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
        self::assertFalse(SomeClass::someMethod());
    }
}
