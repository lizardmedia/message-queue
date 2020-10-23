<?php

declare(strict_types=1);

/**
 * File: RetryLimitOverflowResolverTest.php
 *
 * @author Bartosz Kubicki bartosz.kubicki@lizardmedia.pl>
 * @copyright Copyright (C) 2020 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\MessageQueue\Test\Unit\Envelope;

use LizardMedia\MessageQueue\Envelope\RetryLimitOverflowResolver;
use Magento\Framework\MessageQueue\Envelope;
use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit\Framework\TestCase;

/**
 * Class RetryLimitOverflowResolverTest
 * @package LizardMedia\MessageQueue\Test\Unit\Envelope
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class RetryLimitOverflowResolverTest extends TestCase
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
     * @var string
     */
    private $exampleQueue;

    /**
     * @var RetryLimitOverflowResolver
     */
    private $retryLimitOverflowResolver;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->exampleQueue = 'test';
        $this->retryLimitOverflowResolver = new RetryLimitOverflowResolver();
    }

    /**
     * Use data provider
     * @return void
     */
    public function testIsLimitReachedWhenThereAreNoXDeathParams(): void
    {
        $envelope = new Envelope('example-body');
        $this->assertFalse($this->retryLimitOverflowResolver->isLimitReached($envelope, $this->exampleQueue));
    }

    /**
     * @dataProvider provideDifferentParametersSetsForEnvelope
     * @param int $xdeathCount
     * @param bool $exceeded
     * @return void
     */
    public function testIsLimitReachedWhenThereAreXDeathParamsPresent(int $xdeathCount, bool $exceeded): void
    {
        $parameters = new AMQPTable();
        $parameters->set(
            self::X_DEATH_KEY,
            [
                0 => [
                    self::X_DEATH_COUNT_KEY => $xdeathCount,
                    self::QUEUE_KEY => $this->exampleQueue
                ]
            ]
        );

        $envelope = new Envelope('example-body', [
            self::APPLICATION_HEADERS_KEY => $parameters
        ]);

        $this->assertSame($exceeded, $this->retryLimitOverflowResolver->isLimitReached($envelope, $this->exampleQueue));
    }

    /**
     * @return void
     */
    public function testIsLimitReachedWhenThereAreNoXDeathParamsPresentForQueue(): void
    {
        $parameters = new AMQPTable();
        $parameters->set(
            self::X_DEATH_KEY,
            [
                0 => [
                    self::X_DEATH_COUNT_KEY => 3,
                    self::QUEUE_KEY => 'some-other-queue'
                ]
            ]
        );

        $envelope = new Envelope('example-body', [
            self::APPLICATION_HEADERS_KEY => $parameters
        ]);

        $this->assertFalse($this->retryLimitOverflowResolver->isLimitReached($envelope, $this->exampleQueue));
    }

    /**
     * @return array
     */
    public function provideDifferentParametersSetsForEnvelope(): array
    {
        return [
            'exceeded' => [5, true],
            'equal-so-no-exceeded' => [3, false],
            'no-exceeded' => [2, false]
        ];
    }
}
