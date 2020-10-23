<?php

declare(strict_types=1);

/**
 * File: CommonFunctions.php
 *
 * @author Bartosz Kubicki bartosz.kubicki@lizardmedia.pl>
 * @copyright Copyright (C) 2020 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\MessageQueue\Queue\Consumer\EnvelopeCallback;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\EnvelopeInterface;
use Magento\Framework\MessageQueue\ConsumerConfigurationInterface as UsedConsumerConfig;
use Magento\Framework\MessageQueue\LockInterface;
use Magento\Framework\MessageQueue\MessageEncoder;

/**
 * Trait CommonFunctions
 * @package LizardMedia\MessageQueue\Queue\Consumer\EnvelopeCallback
 */
trait CommonFunctions
{
    /**
     * @param EnvelopeInterface $message
     * @param UsedConsumerConfig $usedConsumerConfig
     * @param MessageEncoder $messageEncoder
     * @return string|null
     * @throws LocalizedException
     */
    protected function dispatchMessage(
        EnvelopeInterface $message,
        UsedConsumerConfig $usedConsumerConfig,
        MessageEncoder $messageEncoder
    ): ?string {
        $properties = $message->getProperties();
        $topicName = $properties['topic_name'];
        $handlers = $usedConsumerConfig->getHandlers($topicName);
        $decodedMessage = $messageEncoder->decode($topicName, $message->getBody());

        if (isset($decodedMessage)) {
            foreach ($handlers as $callback) {
                $callback($decodedMessage);
            }
        }
        return null;
    }

    /**
     * @param ResourceConnection $resourceConnection
     * @param LockInterface $lock
     * @return void
     */
    private function removeLock(ResourceConnection $resourceConnection, LockInterface $lock): void
    {
        $resourceConnection->getConnection()
            ->delete($resourceConnection->getTableName('queue_lock'), ['id = ?' => $lock->getId()]);
    }
}
