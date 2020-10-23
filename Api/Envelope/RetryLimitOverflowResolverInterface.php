<?php

declare(strict_types=1);

/**
 * File: RetryLimitOverflowResolverInterface.php
 *
 * @author Bartosz Kubicki bartosz.kubicki@lizardmedia.pl>
 * @copyright Copyright (C) 2020 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\MessageQueue\Api\Envelope;

use Magento\Framework\MessageQueue\EnvelopeInterface;
use Magento\Framework\MessageQueue\QueueInterface;

/**
 * Interface RetryLimitOverflowResolverInterface
 * @package LizardMedia\MessageQueue\Api\Envelope
 */
interface RetryLimitOverflowResolverInterface
{
    /**
     * @param EnvelopeInterface $envelope
     * @param string $queueName
     * @return bool
     */
    public function isLimitReached(EnvelopeInterface $envelope, string $queueName): bool;
}
