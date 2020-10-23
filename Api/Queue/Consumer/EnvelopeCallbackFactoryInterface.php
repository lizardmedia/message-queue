<?php

declare(strict_types=1);

/**
 * File: EnvelopeCallbackFactoryInterface.php
 *
 * @author Bartosz Kubicki bartosz.kubicki@lizardmedia.pl>
 * @copyright Copyright (C) 2020 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\MessageQueue\Api\Queue\Consumer;

use InvalidArgumentException;
use LizardMedia\MessageQueue\Queue\Consumer\EnvelopeCallback\EnvelopeCallbackInterface;
use Magento\Framework\MessageQueue\ConsumerConfigurationInterface as UsedConsumerConfig;

/**
 * Interface EnvelopeCallbackFactoryInterface
 * @package LizardMedia\MessageQueue\Api\Queue\Consumer
 */
interface EnvelopeCallbackFactoryInterface
{
    /**
     * @param string $type
     * @param UsedConsumerConfig $usedConsumerConfiguration
     * @return EnvelopeCallbackInterface
     * @throws InvalidArgumentException
     */
    public function create(string $type, UsedConsumerConfig $usedConsumerConfiguration): EnvelopeCallbackInterface;
}
