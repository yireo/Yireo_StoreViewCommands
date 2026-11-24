<?php declare(strict_types=1);

namespace Yireo\StoreViewCommands\Test\Integration\Console\Command;

use Magento\Store\Model\ResourceModel\Store as StoreResourceModel;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\StoreViewCommands\Console\Command\DetailStoreViewCommand;

/**
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class DetailStoreViewCommandTest extends TestCase
{
    use StoreViewCleanupTrait;

    public function testShowsDetailsOfStoreView(): void
    {
        $commandTester = $this->execute('default');

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('Store View ID: 1', $display);
        $this->assertStringContainsString('Store View Code: default', $display);
        $this->assertStringContainsString('Active: yes', $display);
        $this->assertStringContainsString('Website: ', $display);
        $this->assertStringContainsString('Store Group: ', $display);
    }

    public function testEscapesConsoleFormattingInStoredValues(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->storeCodes[] = 'yireo_svd_escape';
        $store = $objectManager->get(StoreFactory::class)->create();
        $store->setData([
            'code' => 'yireo_svd_escape',
            'name' => '<error>Injected</error>',
            'website_id' => 1,
            'group_id' => 1,
            'is_active' => 1,
        ]);
        $objectManager->get(StoreResourceModel::class)->save($store);
        $objectManager->get(StoreManagerInterface::class)->reinitStores();

        $commandTester = $this->execute('yireo_svd_escape', ['decorated' => true]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Store View Name: <error>Injected</error>', $commandTester->getDisplay());
    }

    public function testFailsForUnknownStoreView(): void
    {
        $commandTester = $this->execute('yireo_svd_unknown');

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    private function execute(string $code, array $options = []): CommandTester
    {
        $command = Bootstrap::getObjectManager()->create(DetailStoreViewCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['code' => $code], $options + ['verbosity' => OutputInterface::VERBOSITY_NORMAL]);

        return $commandTester;
    }
}
