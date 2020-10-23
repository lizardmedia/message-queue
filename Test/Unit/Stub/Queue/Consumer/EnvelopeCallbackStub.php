<?php

declare(strict_types=1);

/**
 * File: EnvelopeCallbackStub.php
 *
 * @author Bartosz Kubicki bartosz.kubicki@lizardmedia.pl>
 * @copyright Copyright (C) 2020 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\MessageQueue\Test\Unit\Stub\Queue\Consumer;

use LizardMedia\MessageQueue\Queue\Consumer\EnvelopeCallback\EnvelopeCallbackInterface;
use Magento\Framework\MessageQueue\EnvelopeInterface;

/**
 * Class EnvelopeCallbackStub
 * @package LizardMedia\MessageQueue\Test\Unit\Stub\Queue\Consumer
 */
class EnvelopeCallbackStub implements EnvelopeCallbackInterface
{
    /**
     * @param EnvelopeInterface $message
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(EnvelopeInterface $message): void
    {
    }
}
