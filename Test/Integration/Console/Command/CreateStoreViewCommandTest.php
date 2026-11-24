<?php declare(strict_types=1);

namespace Yireo\StoreViewCommands\Test\Integration\Console\Command;

use Magento\Framework\Console\CommandListInterface;
use Magento\Store\Model\ResourceModel\Store as StoreResourceModel;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\StoreViewCommands\Console\Command\CreateStoreViewCommand;

/**
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class CreateStoreViewCommandTest extends TestCase
{
    use StoreViewCleanupTrait;

    public function testCommandIsRegistered(): void
    {
        $commands = Bootstrap::getObjectManager()->get(CommandListInterface::class)->getCommands();
        $this->assertInstanceOf(
            CreateStoreViewCommand::class,
            $commands['Yireo_StoreViewCommands::createStoreView'] ?? null
        );
    }

    public function testCreatesActiveStoreViewInDefaultGroup(): void
    {
        $commandTester = $this->execute(['code' => 'yireo_svc_create', 'name' => 'Yireo Test', 'website_id' => '1']);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $store = $this->loadStore('yireo_svc_create');
        $this->assertSame('Yireo Test', $store->getName());
        $this->assertSame(1, (int)$store->getWebsiteId());
        $this->assertSame(1, (int)$store->getGroupId());
        $this->assertSame(1, (int)$store->getIsActive());
        $this->assertStringContainsString('yireo_svc_create', $commandTester->getDisplay());
    }

    public function testStripsTagsFromName(): void
    {
        $commandTester = $this->execute([
            'code' => 'yireo_svc_tags',
            'name' => '<img src=x onerror=alert(1)><b>Clean</b>',
            'website_id' => '1',
        ]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertSame('Clean', $this->loadStore('yireo_svc_tags')->getName());
    }

    public function testRejectsNameThatIsEmptyAfterFiltering(): void
    {
        $commandTester = $this->execute(['code' => 'yireo_svc_empty', 'name' => '<b></b>', 'website_id' => '1']);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertSame(0, (int)$this->loadStore('yireo_svc_empty')->getId());
    }

    public function testCreatesInactiveStoreView(): void
    {
        $commandTester = $this->execute([
            'code' => 'yireo_svc_inactive',
            'name' => 'Inactive',
            'website_id' => '1',
            '--inactive' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertSame(0, (int)$this->loadStore('yireo_svc_inactive')->getIsActive());
    }

    /**
     * @dataProvider invalidWebsiteIdProvider
     */
    public function testRejectsInvalidWebsiteId(string $websiteId): void
    {
        $commandTester = $this->execute(['code' => 'yireo_svc_website', 'name' => 'Test', 'website_id' => $websiteId]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertSame(0, (int)$this->loadStore('yireo_svc_website')->getId());
    }

    public static function invalidWebsiteIdProvider(): array
    {
        return [['0'], ['-1'], ['abc'], ['1abc'], ['99999']];
    }

    public function testRejectsGroupOfAnotherWebsite(): void
    {
        $commandTester = $this->execute([
            'code' => 'yireo_svc_group',
            'name' => 'Test',
            'website_id' => '1',
            '--group-id' => '0',
        ]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertSame(0, (int)$this->loadStore('yireo_svc_group')->getId());
    }

    public function testAcceptsGroupOfWebsite(): void
    {
        $commandTester = $this->execute([
            'code' => 'yireo_svc_group_ok',
            'name' => 'Test',
            'website_id' => '1',
            '--group-id' => '1',
        ]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertSame(1, (int)$this->loadStore('yireo_svc_group_ok')->getGroupId());
    }

    public function testRejectsInvalidCodeWithFriendlyError(): void
    {
        $commandTester = $this->execute(['code' => '1-invalid', 'name' => 'Test', 'website_id' => '1']);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('store code', $commandTester->getDisplay());
    }

    private function execute(array $input): CommandTester
    {
        $this->storeCodes[] = (string)$input['code'];
        $command = Bootstrap::getObjectManager()->create(CreateStoreViewCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute($input);

        return $commandTester;
    }

    private function loadStore(string $code): Store
    {
        $objectManager = Bootstrap::getObjectManager();
        $store = $objectManager->get(StoreFactory::class)->create();
        $objectManager->get(StoreResourceModel::class)->load($store, $code, 'code');

        return $store;
    }
}
