<?php

declare(strict_types=1);

/**
 * File: EnvelopeCallbackFactory.php
 *
 * @author Bartosz Kubicki bartosz.kubicki@lizardmedia.pl>
 * @copyright Copyright (C) 2020 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\MessageQueue\Queue\Consumer;

use InvalidArgumentException;
use LizardMedia\MessageQueue\Api\Queue\Consumer\EnvelopeCallbackFactoryInterface;
use LizardMedia\MessageQueue\Queue\Consumer\EnvelopeCallback\EnvelopeCallbackInterface;
use Magento\Framework\MessageQueue\ConsumerConfigurationInterface as UsedConsumerConfig;
use Magento\Framework\MessageQueue\QueueInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class EnvelopeCallbackFactory
 * @package LizardMedia\MessageQueue\Queue\Consumer
 */
class EnvelopeCallbackFactory implements EnvelopeCallbackFactoryInterface
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * EnvelopeCallbackFactory constructor.
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $type
     * @param UsedConsumerConfig $usedConsumerConfiguration
     * @return EnvelopeCallbackInterface
     * @throws InvalidArgumentException
     */
    public function create(string $type, UsedConsumerConfig $usedConsumerConfiguration): EnvelopeCallbackInterface
    {
        $this->validateType($type);
        return $this->objectManager->create($type, ['configuration' => $usedConsumerConfiguration]);
    }

    /**
     * @param string $type
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateType(string $type): void
    {
        $interfaces = class_implements($type);
        if (empty($interfaces) || !in_array(EnvelopeCallbackInterface::class, $interfaces, true)) {
            throw new InvalidArgumentException(sprintf('Object has to implement interface %s', EnvelopeCallbackInterface::class));
        }
    }
}
