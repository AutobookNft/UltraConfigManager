<?php

namespace Ultra\UltraConfigManager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ultra\UltraConfigManager\UltraConfigManager;
use Ultra\UltraConfigManager\Constants\GlobalConstants;
use Ultra\UltraConfigManager\Services\VersionManager;
use Ultra\UltraConfigManager\Dao\ConfigDaoInterface;

class UltraConfigManagerTest extends TestCase
{
    public function test_get_returns_default_when_config_is_missing(): void
    {
        $dao = $this->createMock(ConfigDaoInterface::class);

        $manager = new UltraConfigManager(
            new GlobalConstants(),
            new VersionManager(),
            $dao
        );

        $result = $manager->get('nonexistent.key', 'fallback');

        $this->assertEquals('fallback', $result);
    }
}
