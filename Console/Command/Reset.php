<?php
declare(strict_types=1);

namespace StorvixLabs\CacheInsight\Console\Command;

use StorvixLabs\CacheInsight\Model\Storage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * bin/magento storvixlabs:cache-insight:reset
 */
class Reset extends Command
{
    public function __construct(
        private readonly Storage $storage
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('storvixlabs:cache-insight:reset')->setDescription('Delete all Cache Insight data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->storage->truncate();
        $output->writeln('<info>Cache Insight data deleted.</info>');

        return Command::SUCCESS;
    }
}
