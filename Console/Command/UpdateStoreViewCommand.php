<?php declare(strict_types=1);

namespace Yireo\StoreViewCommands\Console\Command;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Type\Config as ConfigCacheType;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ResourceModel\Store as StoreResourceModel;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yireo\StoreViewCommands\Util\StoreViewNameFilter;

class UpdateStoreViewCommand extends Command
{
    public function __construct(
        private StoreResourceModel $storeResourceModel,
        private StoreManagerInterface $storeManager,
        private StoreViewNameFilter $storeViewNameFilter,
        private TypeListInterface $cacheTypeList,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('store:view:update');
        $this->setDescription('Update an existing store view');
        $this->addArgument('id', InputArgument::REQUIRED, 'Current Store View ID or code');
        $this->addArgument('code', InputArgument::REQUIRED, 'New Store View code');
        $this->addArgument('name', InputArgument::REQUIRED, 'New Store View name');
        $this->addArgument('website_id', InputArgument::REQUIRED, 'Website ID the store view belongs to');
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Allow changing the code of the default store view of a store group'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storeView = $this->findStoreView(
            (string)$input->getArgument('id'),
            (string)$input->getArgument('website_id')
        );

        if (null === $storeView) {
            $output->writeln('<error>No StoreView found</error>');
            return Command::INVALID;
        }

        $originalCode = (string)$storeView->getCode();
        $storeViewCode = (string)$input->getArgument('code');
        $codeChanged = $originalCode !== $storeViewCode;

        if ($codeChanged && $storeView->isDefault() && !$input->getOption('force')) {
            $output->writeln(
                '<error>This is the default store view of its store group. '
                . 'Changing its code may break the storefront. Use --force to continue.</error>'
            );
            return Command::INVALID;
        }

        try {
            $storeView->setCode($storeViewCode);
            $storeView->setName($this->storeViewNameFilter->filter((string)$input->getArgument('name')));
            $this->storeResourceModel->save($storeView);
        } catch (LocalizedException $exception) {
            $output->writeln('<error>' . OutputFormatter::escape($exception->getMessage()) . '</error>');
            return Command::FAILURE;
        }

        if ($codeChanged) {
            $this->cacheTypeList->cleanType(ConfigCacheType::TYPE_IDENTIFIER);
        }

        return Command::SUCCESS;
    }

    private function findStoreView(string $identifier, string $websiteId): ?Store
    {
        $isNumeric = ctype_digit($identifier);
        foreach ($this->storeManager->getStores() as $currentStore) {
            if (!$currentStore instanceof Store || (string)$currentStore->getWebsiteId() !== $websiteId) {
                continue;
            }

            if ($isNumeric && (int)$currentStore->getId() === (int)$identifier) {
                return $currentStore;
            }

            if (!$isNumeric && (string)$currentStore->getCode() === $identifier) {
                return $currentStore;
            }
        }

        return null;
    }
}
