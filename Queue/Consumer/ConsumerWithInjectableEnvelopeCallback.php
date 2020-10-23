<?php

declare(strict_types=1);

/**
 * File: ConsumerWithInjectableEnvelopeCallback.php
 *
 * @author Bartosz Kubicki bartosz.kubicki@lizardmedia.pl>
 * @copyright Copyright (C) 2020 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\MessageQueue\Queue\Consumer;

use Closure;
use LizardMedia\MessageQueue\Api\Queue\Consumer\EnvelopeCallbackFactoryInterface;
use Magento\Framework\MessageQueue\CallbackInvokerInterface;
use Magento\Framework\MessageQueue\ConsumerConfigurationInterface as UsedConsumerConfig;
use Magento\Framework\MessageQueue\ConsumerInterface;
use Magento\Framework\MessageQueue\EnvelopeInterface;

/**
 * Class ConsumerWithInjectableEnvelopeCallback
 * @package LizardMedia\MessageQueue\Queue\Consumer
 * @SuppressWarnings(PHPMD.LongVariable)
 * @codeCoverageIgnore
 */
class ConsumerWithInjectableEnvelopeCallback implements ConsumerInterface
{
    /**
     * @var string
     */
    private $envelopeCallbackType;

    /**
     * @var EnvelopeCallbackFactoryInterface
     */
    private $envelopeCallbackFactory;

    /**
     * @var CallbackInvokerInterface
     */
    private $invoker;

    /**
     * @var UsedConsumerConfig
     */
    private $usedConsumerConfig;

    /**
     * @param EnvelopeCallbackFactoryInterface $envelopeCallbackFactory
     * @param CallbackInvokerInterface $invoker
     * @param UsedConsumerConfig $configuration
     * @param string $envelopeCallbackType
     */
    public function __construct(
        EnvelopeCallbackFactoryInterface $envelopeCallbackFactory,
        CallbackInvokerInterface $invoker,
        UsedConsumerConfig $configuration,
        string $envelopeCallbackType
    ) {
        $this->envelopeCallbackType = $envelopeCallbackType;
        $this->envelopeCallbackFactory = $envelopeCallbackFactory;
        $this->invoker = $invoker;
        $this->usedConsumerConfig = $configuration;
    }

    /**
     * @param null $maxNumberOfMessages
     * @return void
     */
    public function process($maxNumberOfMessages = null): void
    {
        $queue = $this->usedConsumerConfig->getQueue();

        if (!isset($maxNumberOfMessages)) {
            $queue->subscribe($this->getTransactionCallback($this->usedConsumerConfig));
        } else {
            $this->invoker->invoke($queue, $maxNumberOfMessages, $this->getTransactionCallback($this->usedConsumerConfig));
        }
    }

    /**
     * @param UsedConsumerConfig $usedConsumerConfig
     * @return Closure
     */
    protected function getTransactionCallback(UsedConsumerConfig $usedConsumerConfig): Closure
    {
        $callbackInstance =  $this->envelopeCallbackFactory->create($this->envelopeCallbackType, $usedConsumerConfig);

        return static function (EnvelopeInterface $message) use ($callbackInstance) {
            $callbackInstance->execute($message);
        };
    }
}
