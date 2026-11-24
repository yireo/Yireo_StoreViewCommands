<?php declare(strict_types=1);

namespace Yireo\StoreViewCommands\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DetailStoreViewCommand extends Command
{
    public function __construct(
        private StoreManagerInterface $storeManager,
        private WebsiteRepositoryInterface $websiteRepository,
        private GroupRepositoryInterface $groupRepository,
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
        try {
            $store = $this->storeManager->getStore((string)$input->getArgument('code'));
            $website = $this->websiteRepository->getById((int)$store->getWebsiteId());
            $group = $this->groupRepository->get((int)$store->getStoreGroupId());
        } catch (LocalizedException $exception) {
            $output->writeln('<error>' . OutputFormatter::escape($exception->getMessage()) . '</error>');
            return Command::FAILURE;
        }

        $details = [
            'Store View ID' => $store->getId(),
            'Store View Code' => $store->getCode(),
            'Store View Name' => $store->getName(),
            'Active' => $store->getIsActive() ? 'yes' : 'no',
            'Website' => $website->getName() . ' (' . $website->getId() . ')',
            'Store Group' => $group->getName() . ' (' . $group->getId() . ')',
        ];

        foreach ($details as $label => $value) {
            $output->writeln($label . ': ' . OutputFormatter::escape((string)$value));
        }

        return Command::SUCCESS;
    }
}
