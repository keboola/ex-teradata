<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Tests\Unit\src;

use Keboola\ExTeradata\ConfigDefinition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinitionTest extends TestCase
{
    public function testGetParametersDefinitionReturnsArrayNodeDefinition(): void
    {
        $configDefinition = new ConfigDefinition();
        $this->assertInstanceOf(ArrayNodeDefinition::class, $configDefinition->getParametersDefinition());
    }
}
