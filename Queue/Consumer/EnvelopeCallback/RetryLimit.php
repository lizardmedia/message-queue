<?php

declare(strict_types=1);

/**
 * File: RetryLimit.php
 *
 * @author Bartosz Kubicki bartosz.kubicki@lizardmedia.pl>
 * @copyright Copyright (C) 2020 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\MessageQueue\Queue\Consumer\EnvelopeCallback;

use Exception;
use LizardMedia\MessageQueue\Api\Envelope\RetryLimitOverflowResolverInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\MessageQueue\ConnectionLostException;
use Magento\Framework\MessageQueue\ConsumerConfigurationInterface as UsedConsumerConfig;
use Magento\Framework\MessageQueue\EnvelopeInterface;
use Magento\Framework\MessageQueue\LockInterface;
use Magento\Framework\MessageQueue\MessageController;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\MessageQueue\MessageLockException;
use Psr\Log\LoggerInterface;

/**
 * Class RetryLimit
 * @package LizardMedia\MessageQueue\Queue\Consumer\EnvelopeCallback
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RetryLimit implements EnvelopeCallbackInterface
{
    use CommonFunctions;

    /**
     * @var RetryLimitOverflowResolverInterface
     */
    private $retryLimitOverflowResolver;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var UsedConsumerConfig
     */
    private $usedConsumerConfig;

    /**
     * @var MessageController
     */
    private $messageController;

    /**
     * @var MessageEncoder
     */
    private $messageEncoder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Initialize dependencies.
     *
     * @param RetryLimitOverflowResolverInterface $retryLimitOverflowResolver
     * @param ResourceConnection $resource
     * @param UsedConsumerConfig $configuration
     * @param MessageController $messageController
     * @param MessageEncoder $messageEncoder
     * @param LoggerInterface $logger
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        RetryLimitOverflowResolverInterface $retryLimitOverflowResolver,
        ResourceConnection $resource,
        UsedConsumerConfig $configuration,
        MessageController $messageController,
        MessageEncoder $messageEncoder,
        LoggerInterface $logger
    ) {
        $this->retryLimitOverflowResolver = $retryLimitOverflowResolver;
        $this->resource = $resource;
        $this->usedConsumerConfig = $configuration;
        $this->messageController = $messageController;
        $this->messageEncoder = $messageEncoder;
        $this->logger = $logger;
    }

    /**
     * @param EnvelopeInterface $message
     * @return void
     */
    public function execute(EnvelopeInterface $message): void
    {
        $queue = $this->usedConsumerConfig->getQueue();

        /** @var LockInterface $lock */
        $lock = null;
        try {
            $topicName = $message->getProperties()['topic_name'];
            $lock = $this->messageController->lock($message, $this->usedConsumerConfig->getConsumerName());

            if ($this->retryLimitOverflowResolver->isLimitReached($message, $this->usedConsumerConfig->getQueueName())) {
                $queue->acknowledge($message);
                $this->logger->warning('Message exceeded max retry limit', [$message]);
                return;
            }

            $allowedTopics = $this->usedConsumerConfig->getTopicNames();
            if (in_array($topicName, $allowedTopics, true)) {
                $this->dispatchMessage($message, $this->usedConsumerConfig, $this->messageEncoder);
            } else {
                $queue->reject($message);
                return;
            }
            $queue->acknowledge($message);
        } catch (MessageLockException $exception) {
            $queue->acknowledge($message);
        } catch (ConnectionLostException $exception) {
            if ($lock) {
                $this->removeLock($this->resource, $lock);
            }
        } catch (NotFoundException $exception) {
            $queue->acknowledge($message);
            $this->logger->warning($exception->getMessage());
        } catch (Exception $exception) {
            $queue->reject($message, false, $exception->getMessage());
            if ($lock) {
                $this->removeLock($this->resource, $lock);
            }
        }
    }
}
