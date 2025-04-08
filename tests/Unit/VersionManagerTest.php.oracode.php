<?php

namespace Ultra\UltraConfigManager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ultra\UltraConfigManager\Services\VersionManager;


class VersionManagerTest extends TestCase
{
/**
 * TODO: [UDP] Describe purpose of 'test_getNextVersion_returns_incremented_value'
 *
 * Semantic placeholder auto-inserted by Oracode.
 */
// TODO: ⛓️ Add Oracular signature to test 'test_getNextVersion_returns_incremented_value'
    public function test_getNextVersion_returns_incremented_value(): void
    {
        $manager = new VersionManager();
        $this->assertEquals(2, $manager->getNextVersion(1));
        $this->assertEquals(101, $manager->getNextVersion(100));
    }

/**
 * TODO: [UDP] Describe purpose of 'test_getNextVersion_defaults_to_1_when_null'
 *
 * Semantic placeholder auto-inserted by Oracode.
 */
// TODO: ⛓️ Add Oracular signature to test 'test_getNextVersion_defaults_to_1_when_null'
    public function test_getNextVersion_defaults_to_1_when_null(): void
    {
        $manager = new VersionManager();
        $this->assertEquals(1, $manager->getNextVersion(null));
    }
}
