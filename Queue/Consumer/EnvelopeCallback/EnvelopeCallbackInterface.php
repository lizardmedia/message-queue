<?php

declare(strict_types=1);

/**
 * File: EnvelopeCallbackInterface.php
 *
 * @author Bartosz Kubicki bartosz.kubicki@lizardmedia.pl>
 * @copyright Copyright (C) 2020 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\MessageQueue\Queue\Consumer\EnvelopeCallback;

use Magento\Framework\MessageQueue\EnvelopeInterface;

/**
 * Interface EnvelopeCallbackInterface
 * @package LizardMedia\MessageQueue\Queue\Consumer\EnvelopeCallback
 */
interface EnvelopeCallbackInterface
{
    /**
     * @param EnvelopeInterface $message
     * @return void
     */
    public function execute(EnvelopeInterface $message): void;
}
