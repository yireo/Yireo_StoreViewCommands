<?php declare(strict_types=1);

namespace Yireo\StoreViewCommands\Test\Integration\Console\Command;

use Magento\Framework\Registry;
use Magento\Store\Model\ResourceModel\Store as StoreResourceModel;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

trait StoreViewCleanupTrait
{
    /**
     * @var string[]
     */
    private array $storeCodes = [];

    protected function tearDown(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $registry = $objectManager->get(Registry::class);
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);

        $storeResourceModel = $objectManager->get(StoreResourceModel::class);
        foreach ($this->storeCodes as $storeCode) {
            $store = $objectManager->get(StoreFactory::class)->create();
            $storeResourceModel->load($store, $storeCode, 'code');
            if ($store->getId()) {
                $storeResourceModel->delete($store);
            }
        }

        $registry->unregister('isSecureArea');
        $objectManager->get(StoreManagerInterface::class)->reinitStores();
        $this->storeCodes = [];
    }
}
