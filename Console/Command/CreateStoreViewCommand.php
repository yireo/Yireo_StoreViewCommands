<?php declare(strict_types=1);

namespace Yireo\StoreViewCommands\Console\Command;

use Magento\Framework\Event\Manager;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Model\ResourceModel\Store as StoreResourceModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateStoreViewCommand extends Command
{
    public function __construct(
        private StoreFactory $storeFactory,
        private StoreResourceModel $storeResourceModel,
        private WebsiteRepositoryInterface $websiteRepository,
        private GroupRepositoryInterface $groupRepository,
        private Manager $eventManager,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('store:view:create');
        $this->setDescription('Create a new store view');
        $this->addArgument('code', InputArgument::REQUIRED, 'Store View code');
        $this->addArgument('name', InputArgument::REQUIRED, 'Store View name');
        $this->addArgument('website_id', InputArgument::REQUIRED, 'Website ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $websiteId = (int)$input->getArgument('website_id');
        $website = $this->websiteRepository->getById($websiteId);

        $groupId = 0;

        foreach ($this->groupRepository->getList() as $group) {
            if ($websiteId == $group->getWebsiteId()) {
                $groupId = $group->getId();
                break;
            }
        }

        $group = $this->groupRepository->get($groupId);

        $storeModel = $this->storeFactory->create();
        $storeModel->setCode($input->getArgument('code'));
        $storeModel->setName($input->getArgument('name'));
        $storeModel->setWebsite($website);
        $storeModel->setGroup($group);
        $storeModel->setIsActive(1);
        $storeModel->setSortOrder(0);

        $this->storeResourceModel->save($storeModel);
        $this->eventManager->dispatch('store_add', ['store' => $storeModel]);

        return Command::SUCCESS;
    }
}
