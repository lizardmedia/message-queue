<?php

declare(strict_types=1);

/**
 * File: RetryLimitOverflowResolver.php
 *
 * @author Bartosz Kubicki bartosz.kubicki@lizardmedia.pl>
 * @copyright Copyright (C) 2020 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\MessageQueue\Envelope;

use Magento\Framework\MessageQueue\QueueInterface;
use function array_key_exists;
use LizardMedia\MessageQueue\Api\Envelope\RetryLimitOverflowResolverInterface;
use Magento\Framework\MessageQueue\EnvelopeInterface;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Class RetryLimitOverflowResolver
 * @package LizardMedia\MessageQueue\Envelope
 */
class RetryLimitOverflowResolver implements RetryLimitOverflowResolverInterface
{
    /**
     * @var string
     */
    private const APPLICATION_HEADERS_KEY = 'application_headers';

    /**
     * @var string
     */
    private const X_DEATH_KEY = 'x-death';

    /**
     * @var string
     */
    private const QUEUE_KEY = 'queue';

    /**
     * @var string
     */
    private const X_DEATH_COUNT_KEY = 'count';

    /**
     * @var int
     */
    private $limit;

    /**
     * RetryLimitOverflowResolver constructor.
     * @param int $limit
     */
    public function __construct(int $limit = 3)
    {
        $this->limit = $limit;
    }

    /**
     * @param EnvelopeInterface $envelope
     * @param string $queueName
     * @return bool
     */
    public function isLimitReached(EnvelopeInterface $envelope, string $queueName): bool
    {
        $xdeathParams = $this->getXDeathParameters($envelope, $queueName);
        return !empty($xdeathParams[self::X_DEATH_COUNT_KEY])
            && (int) $xdeathParams[self::X_DEATH_COUNT_KEY] > $this->limit;
    }

    /**
     * @param EnvelopeInterface $envelope
     * @param string $queueName
     * @return array
     */
    private function getXDeathParameters(EnvelopeInterface $envelope, string $queueName): array
    {
        $properties = $envelope->getProperties();
        if (!array_key_exists(self::APPLICATION_HEADERS_KEY, $properties)) {
            return [];
        }

        /** @var $applicationHeaders AMQPTable */
        $applicationHeaders = $properties[self::APPLICATION_HEADERS_KEY];
        if ($applicationHeaders === null) {
            return [];
        }

        $data = $applicationHeaders->getNativeData();

        if (empty($data[self::X_DEATH_KEY])) {
            return [];
        }

        $xdeathParamsArray = $data[self::X_DEATH_KEY];

        foreach ($xdeathParamsArray as $key => $queueXdeathParams) {
            if (isset($queueXdeathParams[self::QUEUE_KEY]) && $queueXdeathParams[self::QUEUE_KEY] === $queueName) {
                return $xdeathParamsArray[$key];
            }
        }

        return [];
    }
}
