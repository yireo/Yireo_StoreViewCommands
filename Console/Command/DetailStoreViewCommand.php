<?php declare(strict_types=1);

namespace Yireo\StoreViewCommands\Console\Command;

use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DetailStoreViewCommand extends Command
{
    public function __construct(
        private StoreManagerInterface $storeManager,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('store:view:detail');
        $this->setDescription('Show details of an existing store view');
        $this->addArgument('code', InputArgument::REQUIRED, 'Store View code');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $code = (string)$input->getArgument('code');
        $store = $this->storeManager->getStore($code);
        $output->writeln('Store View Code: ' . $store->getId());

        return Command::SUCCESS;
    }
}
