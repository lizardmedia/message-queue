<?php

declare(strict_types=1);

/**
 * File: BaseWithSyncTopicsSupport.php
 *
 * @author Bartosz Kubicki bartosz.kubicki@lizardmedia.pl>
 * @copyright Copyright (C) 2020 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\MessageQueue\Queue\Consumer\EnvelopeCallback;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Communication\ConfigInterface as CommunicationConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\MessageQueue\ConnectionLostException;
use Magento\Framework\MessageQueue\Consumer\ConfigInterface as ConsumerConfig;
use Magento\Framework\MessageQueue\ConsumerConfigurationInterface as UsedConsumerConfig;
use Magento\Framework\MessageQueue\EnvelopeFactory;
use Magento\Framework\MessageQueue\EnvelopeInterface;
use Magento\Framework\MessageQueue\LockInterface;
use Magento\Framework\MessageQueue\MessageController;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\MessageQueue\MessageLockException;
use Magento\Framework\MessageQueue\MessageValidator;
use Magento\Framework\MessageQueue\QueueRepository;
use Psr\Log\LoggerInterface;

/**
 * Class BaseWithSyncTopicsSupport
 * @package LizardMedia\MessageQueue\Queue\Consumer\EnvelopeCallback
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BaseWithSyncTopicsSupport implements EnvelopeCallbackInterface
{
    use CommonFunctions;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var CommunicationConfig
     */
    private $communicationConfig;

    /**
     * @var ConsumerConfig
     */
    private $consumerConfig;

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
     * @var MessageValidator
     */
    private $messageValidator;

    /**
     * @var EnvelopeFactory
     */
    private $envelopeFactory;

    /**
     * @var QueueRepository
     */
    private $queueRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Initialize dependencies.
     *
     * @param ResourceConnection $resource
     * @param CommunicationConfig $communicationConfig
     * @param ConsumerConfig $consumerConfig
     * @param UsedConsumerConfig $configuration
     * @param MessageController $messageController
     * @param MessageEncoder $messageEncoder
     * @param MessageValidator $messageValidator
     * @param EnvelopeFactory $envelopeFactory
     * @param QueueRepository $queueRepository
     * @param LoggerInterface $logger
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ResourceConnection $resource,
        CommunicationConfig $communicationConfig,
        ConsumerConfig $consumerConfig,
        UsedConsumerConfig $configuration,
        MessageController $messageController,
        MessageEncoder $messageEncoder,
        MessageValidator $messageValidator,
        EnvelopeFactory $envelopeFactory,
        QueueRepository $queueRepository,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->communicationConfig = $communicationConfig;
        $this->consumerConfig = $consumerConfig;
        $this->usedConsumerConfig = $configuration;
        $this->messageController = $messageController;
        $this->messageEncoder = $messageEncoder;
        $this->messageValidator = $messageValidator;
        $this->envelopeFactory = $envelopeFactory;
        $this->queueRepository = $queueRepository;
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
            $topicConfig = $this->communicationConfig->getTopic($topicName);
            $lock = $this->messageController->lock($message, $this->usedConsumerConfig->getConsumerName());

            if ($topicConfig[CommunicationConfig::TOPIC_IS_SYNCHRONOUS]) {
                $responseBody = $this->dispatchMessage($message, true);
                $responseMessage = $this->envelopeFactory->create(
                    ['body' => $responseBody, 'properties' => $message->getProperties()]
                );
                $this->sendResponse($responseMessage);
            } else {
                $allowedTopics = $this->usedConsumerConfig->getTopicNames();
                if (in_array($topicName, $allowedTopics, true)) {
                    $this->dispatchMessage($message);
                } else {
                    $queue->reject($message);
                    return;
                }
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

    /**
     * @param EnvelopeInterface $message
     * @param boolean $isSync
     * @return string|null
     * @throws LocalizedException
     */
    protected function dispatchMessage(EnvelopeInterface $message, $isSync = false): ?string
    {
        $properties = $message->getProperties();
        $topicName = $properties['topic_name'];
        $handlers = $this->usedConsumerConfig->getHandlers($topicName);
        $decodedMessage = $this->messageEncoder->decode($topicName, $message->getBody());

        if (isset($decodedMessage)) {
            $messageSchemaType = $this->usedConsumerConfig->getMessageSchemaType($topicName);
            if ($messageSchemaType === CommunicationConfig::TOPIC_REQUEST_TYPE_METHOD) {
                foreach ($handlers as $callback) {
                    $result = call_user_func_array($callback, $decodedMessage);
                    return $this->processSyncResponse($topicName, $result);
                }
            } else {
                foreach ($handlers as $callback) {
                    $result = $callback($decodedMessage);
                    if ($isSync) {
                        return $this->processSyncResponse($topicName, $result);
                    }
                }
            }
        }
        return null;
    }

    /**
     * @param string $topicName
     * @param mixed $result
     * @return string
     * @throws LocalizedException
     */
    protected function processSyncResponse(string $topicName, $result): string
    {
        if (isset($result)) {
            $this->messageValidator->validate($topicName, $result, false);
            return $this->messageEncoder->encode($topicName, $result, false);
        }

        throw new LocalizedException(__('No reply message resulted in RPC.'));
    }

    /**
     * @param EnvelopeInterface $envelope
     * @return void
     * @throws LocalizedException
     */
    protected function sendResponse(EnvelopeInterface $envelope): void
    {
        $messageProperties = $envelope->getProperties();
        $connectionName = $this->consumerConfig
            ->getConsumer($this->usedConsumerConfig->getConsumerName())->getConnection();
        $queue = $this->queueRepository->get($connectionName, $messageProperties['reply_to']);
        $queue->push($envelope);
    }
}
