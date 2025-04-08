<?php

namespace Ultra\UltraConfigManager\Tests\Unit;

use Ultra\UltraConfigManager\Tests\TestCase;
use Ultra\UltraConfigManager\Constants\GlobalConstants;


class GlobalConstantsTest extends TestCase
{
/**
 * TODO: [UDP] Describe purpose of 'test_getConstant_returns_value_if_exists'
 *
 * Semantic placeholder auto-inserted by Oracode.
 */
// TODO: ⛓️ Add Oracular signature to test 'test_getConstant_returns_value_if_exists'
    public function test_getConstant_returns_value_if_exists(): void
    {
        $result = GlobalConstants::getConstant('NO_USER', 99);
        $this->assertEquals(0, $result);
    }

/**
 * TODO: [UDP] Describe purpose of 'test_getConstant_returns_default_if_not_exists'
 *
 * Semantic placeholder auto-inserted by Oracode.
 */
// TODO: ⛓️ Add Oracular signature to test 'test_getConstant_returns_default_if_not_exists'
    public function test_getConstant_returns_default_if_not_exists(): void
    {
        $result = GlobalConstants::getConstant('NON_EXISTENT', 'default');
        $this->assertEquals('default', $result);
    }

/**
 * TODO: [UDP] Describe purpose of 'test_validateConstant_throws_exception_if_invalid'
 *
 * Semantic placeholder auto-inserted by Oracode.
 */
// TODO: ⛓️ Add Oracular signature to test 'test_validateConstant_throws_exception_if_invalid'
    public function test_validateConstant_throws_exception_if_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        GlobalConstants::validateConstant('NON_EXISTENT');
    }

/**
 * TODO: [UDP] Describe purpose of 'test_validateConstant_does_not_throw_if_valid'
 *
 * Semantic placeholder auto-inserted by Oracode.
 */
// TODO: ⛓️ Add Oracular signature to test 'test_validateConstant_does_not_throw_if_valid'
    public function test_validateConstant_does_not_throw_if_valid(): void
    {
        $this->expectNotToPerformAssertions();
        GlobalConstants::validateConstant('NO_USER');
    }
}
