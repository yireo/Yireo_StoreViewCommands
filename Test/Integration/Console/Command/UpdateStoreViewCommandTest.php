<?php declare(strict_types=1);

namespace Yireo\StoreViewCommands\Test\Integration\Console\Command;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Store\Model\ResourceModel\Store as StoreResourceModel;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\StoreViewCommands\Console\Command\CreateStoreViewCommand;
use Yireo\StoreViewCommands\Console\Command\UpdateStoreViewCommand;

/**
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class UpdateStoreViewCommandTest extends TestCase
{
    use StoreViewCleanupTrait;

    private TypeListInterface&MockObject $cacheTypeList;

    protected function setUp(): void
    {
        $this->cacheTypeList = $this->createMock(TypeListInterface::class);
    }

    public function testUpdatesStoreViewByCode(): void
    {
        $this->createStoreView('yireo_svu_code');
        $this->storeCodes[] = 'yireo_svu_code_new';
        $this->cacheTypeList->expects($this->once())->method('cleanType')->with('config');

        $commandTester = $this->execute([
            'id' => 'yireo_svu_code',
            'code' => 'yireo_svu_code_new',
            'name' => '<script>x</script>New name',
            'website_id' => '1',
        ]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $store = $this->loadStore('yireo_svu_code_new');
        $this->assertGreaterThan(0, (int)$store->getId());
        $this->assertSame('xNew name', $store->getName());
    }

    public function testUpdatesStoreViewByIdWithoutCleaningCacheWhenCodeIsUnchanged(): void
    {
        $storeId = $this->createStoreView('yireo_svu_id');
        $this->cacheTypeList->expects($this->never())->method('cleanType');

        $commandTester = $this->execute([
            'id' => (string)$storeId,
            'code' => 'yireo_svu_id',
            'name' => 'Renamed',
            'website_id' => '1',
        ]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertSame('Renamed', $this->loadStore('yireo_svu_id')->getName());
    }

    public function testDoesNotMatchIdWithTrailingCharacters(): void
    {
        $storeId = $this->createStoreView('yireo_svu_loose');

        $commandTester = $this->execute([
            'id' => $storeId . 'abc',
            'code' => 'yireo_svu_loose',
            'name' => 'Renamed',
            'website_id' => '1',
        ]);

        $this->assertSame(Command::INVALID, $commandTester->getStatusCode());
        $this->assertSame('Original', $this->loadStore('yireo_svu_loose')->getName());
    }

    public function testDoesNotMatchStoreViewOfAnotherWebsite(): void
    {
        $this->createStoreView('yireo_svu_website');

        $commandTester = $this->execute([
            'id' => 'yireo_svu_website',
            'code' => 'yireo_svu_website',
            'name' => 'Renamed',
            'website_id' => '2',
        ]);

        $this->assertSame(Command::INVALID, $commandTester->getStatusCode());
    }

    public function testRefusesToChangeCodeOfDefaultStoreViewWithoutForce(): void
    {
        $this->storeCodes[] = 'yireo_svu_default';
        $this->cacheTypeList->expects($this->never())->method('cleanType');

        $commandTester = $this->execute([
            'id' => 'default',
            'code' => 'yireo_svu_default',
            'name' => 'Default Store View',
            'website_id' => '1',
        ]);

        $this->assertSame(Command::INVALID, $commandTester->getStatusCode());
        $this->assertStringContainsString('--force', $commandTester->getDisplay());
        $this->assertGreaterThan(0, (int)$this->loadStore('default')->getId());
    }

    public function testRejectsEmptyName(): void
    {
        $this->createStoreView('yireo_svu_empty');

        $commandTester = $this->execute([
            'id' => 'yireo_svu_empty',
            'code' => 'yireo_svu_empty',
            'name' => '   ',
            'website_id' => '1',
        ]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertSame('Original', $this->loadStore('yireo_svu_empty')->getName());
    }

    private function createStoreView(string $code): int
    {
        $this->storeCodes[] = $code;
        $command = Bootstrap::getObjectManager()->create(CreateStoreViewCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['code' => $code, 'name' => 'Original', 'website_id' => '1']);
        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode(), $commandTester->getDisplay());

        return (int)$this->loadStore($code)->getId();
    }

    private function execute(array $input): CommandTester
    {
        $command = Bootstrap::getObjectManager()->create(
            UpdateStoreViewCommand::class,
            ['cacheTypeList' => $this->cacheTypeList]
        );
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
