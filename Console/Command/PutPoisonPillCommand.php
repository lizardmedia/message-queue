<?php

declare(strict_types=1);

/**
 * File: PutPoisonPillCommand.php
 *
 * @author Bartosz Kubicki bartosz.kubicki@lizardmedia.pl>
 * @copyright Copyright (C) 2020 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\MessageQueue\Console\Command;

use Exception;
use Magento\Framework\Console\Cli;
use Magento\Framework\MessageQueue\PoisonPill\PoisonPillPutInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PutPoisonPillCommand
 * @package LizardMedia\MessageQueue\Console\Command
 * @codeCoverageIgnore
 */
class PutPoisonPillCommand extends Command
{
    /**
     * @var PoisonPillPutInterface
     */
    private $poisonPillPut;

    /**
     * PutPoisonPill constructor.
     * @param PoisonPillPutInterface $poisonPillPut
     * @param string|null $name
     */
    public function __construct(PoisonPillPutInterface $poisonPillPut, string $name = null)
    {
        parent::__construct($name);
        $this->poisonPillPut = $poisonPillPut;
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('lm:queue:consumers:poison')
            ->setDescription('Poison queue consumers');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $poisonPillVersion = $this->poisonPillPut->put();
        $output->writeln('Queue consumers have been poisoned...');
        $output->writeln(sprintf('New Poison Pill Version: %s', $poisonPillVersion));
        return Cli::RETURN_SUCCESS;
    }
}
