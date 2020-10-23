<?php

declare(strict_types=1);

/**
 * File: ModuleConfigTest.php
 *
 * @author Bartosz Kubicki bartosz.kubicki@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */
namespace LizardMedia\MessageQueue\Test\Integration;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Module\ModuleList;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class ModuleConfigTest
 * @package LizardMedia\MessageQueue\Test\Integration
 */
class ModuleConfigTest extends TestCase
{
    /**
     * @var string
     */
    private const MODULE_NAME = 'LizardMedia_MessageQueue';

    /**
     * @return void
     */
    public function testTheModuleIsRegistered(): void
    {
        $registrar = new ComponentRegistrar();
        $this->assertArrayHasKey(self::MODULE_NAME, $registrar->getPaths(ComponentRegistrar::MODULE));
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testTheModuleIsConfiguredAndEnabled(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $moduleList = $objectManager->create(ModuleList::class);
        $this->assertTrue($moduleList->has(self::MODULE_NAME));
    }
}
