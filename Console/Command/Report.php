<?php
declare(strict_types=1);

namespace StorvixLabs\CacheInsight\Console\Command;

use StorvixLabs\CacheInsight\Model\Outcome;
use StorvixLabs\CacheInsight\Model\Report\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * bin/magento storvixlabs:cache-insight:report [--days=7]
 */
class Report extends Command
{
    public function __construct(
        private readonly Reader $reader
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('storvixlabs:cache-insight:report')
            ->setDescription('Show full-page cache misses by page type and the blocks and cookies behind them')
            ->addOption('days', 'd', InputOption::VALUE_REQUIRED, 'Days to include', '7');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = min(90, max(1, (int)$input->getOption('days')));
        $from = gmdate('Y-m-d', time() - ($days - 1) * 86400);
        $to = gmdate('Y-m-d');

        $table = new Table($output);
        $codes = array_keys(Outcome::describe());
        $table->setHeaders(array_merge(['Page type', 'Misses'], $codes));
        foreach ($this->reader->getPageTypes($from, $to) as $page) {
            $table->addRow(array_merge(
                [$page['page_type'], $page['total']],
                array_map(static fn (string $code): int => $page['outcomes'][$code] ?? 0, $codes)
            ));
        }
        $table->render();
        foreach (['block' => 'Blocks that stop caching', 'set_cookie' => 'Cookies set', 'cookie' => 'Cookies on requests'] as $kind => $title) {
            $output->writeln('<info>' . $title . '</info>');
            foreach ($this->reader->getCauses($kind, $from, $to, 10) as $cause) {
                $output->writeln(sprintf('  %-45s %6d  %s', $cause['name'], $cause['requests'], implode(', ', array_keys($cause['pages']))));
            }
        }

        return Command::SUCCESS;
    }
}
