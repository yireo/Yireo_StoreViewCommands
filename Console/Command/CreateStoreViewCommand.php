<?php declare(strict_types=1);

namespace Yireo\StoreViewCommands\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\ResourceModel\Store as StoreResourceModel;
use Magento\Store\Model\StoreFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yireo\StoreViewCommands\Util\StoreViewNameFilter;

class CreateStoreViewCommand extends Command
{
    public function __construct(
        private StoreFactory $storeFactory,
        private StoreResourceModel $storeResourceModel,
        private WebsiteRepositoryInterface $websiteRepository,
        private GroupRepositoryInterface $groupRepository,
        private StoreViewNameFilter $storeViewNameFilter,
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
        $this->addOption(
            'group-id',
            null,
            InputOption::VALUE_REQUIRED,
            'Store group ID (defaults to the default store group of the website)'
        );
        $this->addOption('inactive', null, InputOption::VALUE_NONE, 'Create the store view as inactive');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $website = $this->getWebsite((string)$input->getArgument('website_id'));
            $group = $this->getGroup($website, $input->getOption('group-id'));
            $name = $this->storeViewNameFilter->filter((string)$input->getArgument('name'));

            $storeModel = $this->storeFactory->create();
            $storeModel->setCode((string)$input->getArgument('code'));
            $storeModel->setName($name);
            $storeModel->setWebsiteId((int)$website->getId());
            $storeModel->setGroupId((int)$group->getId());
            $storeModel->setIsActive($input->getOption('inactive') ? 0 : 1);
            $storeModel->setSortOrder(0);

            $this->storeResourceModel->save($storeModel);
        } catch (LocalizedException $exception) {
            $output->writeln('<error>' . OutputFormatter::escape($exception->getMessage()) . '</error>');
            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            'Created store view "%s" with ID %d',
            OutputFormatter::escape((string)$storeModel->getCode()),
            (int)$storeModel->getId()
        ));

        return Command::SUCCESS;
    }

    /**
     * @throws LocalizedException
     */
    private function getWebsite(string $websiteId): WebsiteInterface
    {
        if (!ctype_digit($websiteId) || (int)$websiteId <= 0) {
            throw new LocalizedException(__('The website ID must be a positive integer'));
        }

        return $this->websiteRepository->getById((int)$websiteId);
    }

    /**
     * @throws LocalizedException
     */
    private function getGroup(WebsiteInterface $website, mixed $groupId): GroupInterface
    {
        if ($groupId === null) {
            $groupId = (int)$website->getDefaultGroupId();
            if ($groupId <= 0) {
                throw new LocalizedException(__('Website %1 has no default store group', $website->getId()));
            }
        } elseif (!ctype_digit((string)$groupId) || (int)$groupId <= 0) {
            throw new LocalizedException(__('The store group ID must be a positive integer'));
        }

        $group = $this->groupRepository->get((int)$groupId);
        if ((int)$group->getWebsiteId() !== (int)$website->getId()) {
            throw new LocalizedException(
                __('Store group %1 does not belong to website %2', $group->getId(), $website->getId())
            );
        }

        return $group;
    }
}
