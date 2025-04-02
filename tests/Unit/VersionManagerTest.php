<?php

namespace Ultra\UltraConfigManager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ultra\UltraConfigManager\Services\VersionManager;


class VersionManagerTest extends TestCase
{
    public function test_getNextVersion_returns_incremented_value(): void
    {
        $manager = new VersionManager();
        $this->assertEquals(2, $manager->getNextVersion(1));
        $this->assertEquals(101, $manager->getNextVersion(100));
    }

    public function test_getNextVersion_defaults_to_1_when_null(): void
    {
        $manager = new VersionManager();
        $this->assertEquals(1, $manager->getNextVersion(null));
    }
}
