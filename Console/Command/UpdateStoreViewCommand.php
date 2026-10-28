<?php declare(strict_types=1);

namespace Yireo\StoreViewCommands\Console\Command;

use Magento\Store\Model\ResourceModel\Store as StoreResourceModel;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateStoreViewCommand extends Command
{
    public function __construct(
        private StoreResourceModel $storeResourceModel,
        private StoreManagerInterface $storeManager,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('store:view:update');
        $this->setDescription('Update an existing store view');
        $this->addArgument('id', InputArgument::REQUIRED, 'Current Store View ID');
        $this->addArgument('code', InputArgument::REQUIRED, 'Store View code');
        $this->addArgument('name', InputArgument::REQUIRED, 'Store View name');
        $this->addArgument('website_id', InputArgument::REQUIRED, 'Website ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storeView = false;
        $websiteId = (int)$input->getArgument('website_id');
        $storeViewId = $input->getArgument('id');
        foreach ($this->storeManager->getStores() as $currentStore) {
            if ((int)$currentStore->getWebsiteId() !== $websiteId) {
                continue;
            }

            if ((string)$currentStore->getCode() === (string)$storeViewId || (int)$currentStore->getId() === (int)$storeViewId) {
                $storeView = $currentStore;
                break;
            }
        }

        if (false === $storeView) {
            $output->writeln('No StoreView found');
            return Command::INVALID;
        }

        $storeViewCode = $input->getArgument('code');
        $storeViewName = $input->getArgument('name');

        $storeView->setCode($storeViewCode);
        $storeView->setName($storeViewName);

        $this->storeResourceModel->save($storeView);

        return Command::SUCCESS;

    }
}
