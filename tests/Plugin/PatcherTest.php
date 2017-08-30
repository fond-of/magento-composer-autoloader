<?php

namespace Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use FondOf\Magento\Composer\Autoloader\Plugin\Patcher;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Filesystem\Filesystem;

class PatcherTest extends TestCase
{
    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @var Patcher
     */
    protected $patcher;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Composer
     */
    protected $composerMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Package
     */
    protected $packageMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|IOInterface
     */
    protected $ioMock;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->composerMock = $this->getMockBuilder(Composer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->packageMock = $this->getMockBuilder(Package::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->ioMock = $this->getMockBuilder(IOInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileSystem = new Filesystem();

        $this->composerMock->expects($this->atLeastOnce())
            ->method('getPackage')
            ->willReturn($this->packageMock);

        $this->patcher = new Patcher($this->composerMock, $this->ioMock, $this->fileSystem);
    }

    /**
     * @test
     */
    public function testPatchWithDisabledFlag()
    {
        $extra = ['patch-mage-php' => false];

        $this->packageMock->expects($this->atLeastOnce())
            ->method('getExtra')
            ->willReturn($extra);

        $this->assertEquals($this->patcher, $this->patcher->patch());
    }

    /**
     * @test
     * @expectedException \JsonSchema\Exception\InvalidConfigException
     */
    public function testPatchWithoutRequiredConfig()
    {
        $extra = ['patch-mage-php' => true];

        $this->packageMock->expects($this->atLeastOnce())
            ->method('getExtra')
            ->willReturn($extra);

        $this->patcher->patch();
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Filesystem\Exception\FileNotFoundException
     */
    public function testPatchWithoutExistingMagePhp()
    {
        $extra = ['patch-mage-php' => true, 'magento-root-dir' => 'www'];

        $this->packageMock->expects($this->atLeastOnce())
            ->method('getExtra')
            ->willReturn($extra);

        $this->fileSystem->mkdir('www/app');

        $this->patcher->patch();
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Filesystem\Exception\IOException
     */
    public function testPatchWithNotReadableMagePhp()
    {
        $magePhpContent = '<?php' . PHP_EOL . Patcher::VARIEN_AUTOLOADER_LINE;

        $extra = ['patch-mage-php' => true, 'magento-root-dir' => 'www'];

        $this->packageMock->expects($this->atLeastOnce())
            ->method('getExtra')
            ->willReturn($extra);

        $this->fileSystem->mkdir('www/app');
        $this->fileSystem->dumpFile('www/app/Mage.php', $magePhpContent);
        $this->fileSystem->chmod('www/app/Mage.php', 0000);

        $this->patcher->patch();
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Filesystem\Exception\IOException
     */
    public function testPatchWithNotWritableMagePhp()
    {
        $magePhpContent = '<?php' . PHP_EOL . Patcher::VARIEN_AUTOLOADER_LINE;

        $extra = ['patch-mage-php' => true, 'magento-root-dir' => 'www'];

        $this->packageMock->expects($this->atLeastOnce())
            ->method('getExtra')
            ->willReturn($extra);

        $this->fileSystem->mkdir('www/app');
        $this->fileSystem->dumpFile('www/app/Mage.php', $magePhpContent);
        $this->fileSystem->chmod('www/app/Mage.php', 0555);

        $this->patcher->patch();
    }

    /**
     * @test
     */
    public function testPatchWithAlreadyPatchedMagePhp()
    {
        $magePhpContent = '<?php' . PHP_EOL . Patcher::VARIEN_AUTOLOADER_LINE . Patcher::COMPOSER_AUTOLOADER_LINE;

        $extra = ['patch-mage-php' => true, 'magento-root-dir' => 'www'];

        $this->packageMock->expects($this->atLeastOnce())
            ->method('getExtra')
            ->willReturn($extra);

        $this->fileSystem->mkdir('www/app');
        $this->fileSystem->dumpFile('www/app/Mage.php', $magePhpContent);
        $this->fileSystem->chmod('www/app/Mage.php', 0775);

        $this->patcher->patch();
    }

    /**
     * @test
     */
    public function testPatch()
    {
        $magePhpContent = '<?php' . PHP_EOL . Patcher::VARIEN_AUTOLOADER_LINE;

        $extra = ['patch-mage-php' => true, 'magento-root-dir' => 'www'];

        $this->packageMock->expects($this->atLeastOnce())
            ->method('getExtra')
            ->willReturn($extra);

        $this->fileSystem->mkdir('www/app');
        $this->fileSystem->dumpFile('www/app/Mage.php', $magePhpContent);
        $this->fileSystem->chmod('www/app/Mage.php', 0775);

        $this->patcher->patch();

        $newMagePhpContent = file_get_contents('www/app/Mage.php');

        $this->assertEquals($magePhpContent . Patcher::COMPOSER_AUTOLOADER_LINE, $newMagePhpContent);
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->fileSystem->remove('www');
    }
}
