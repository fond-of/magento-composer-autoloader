<?php

namespace FondOf\Magento\Composer\Autoloader\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use JsonSchema\Exception\InvalidConfigException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class Patcher
{
    const COMPOSER_AUTOLOADER_LINE = 'require_once BP . DS . \'..\' . DS . \'vendor\' . DS . \'autoload.php\';' . PHP_EOL;
    const VARIEN_AUTOLOADER_LINE = 'Varien_Autoload::register();' . PHP_EOL;

    /**
     * @var \Composer\Package\RootPackageInterface
     */
    protected $package;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Constructor
     *
     * @param Composer $composer
     * @param IOInterface $io
     * @param Filesystem $fileSystem
     */
    public function __construct(Composer $composer, IOInterface $io, Filesystem $fileSystem)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->filesystem = $fileSystem;
        $this->package = $composer->getPackage();
    }

    /**
     * Patch Mage.php
     *
     * @return $this
     */
    public function patch()
    {
        if (!$this->canPatch()) {
            return $this;
        }

        $pathToMagePhp = $this->getPathToMagePhp();

        if ($this->isAlreadyPatched($pathToMagePhp)) {
            return $this;
        }

        $magePhp = file_get_contents($pathToMagePhp);

        $search = self::VARIEN_AUTOLOADER_LINE;
        $replace = $search . self::COMPOSER_AUTOLOADER_LINE;

        $patchedMagePhp = str_replace($search, $replace, $magePhp);

        file_put_contents($pathToMagePhp, $patchedMagePhp);

        $this->io->write('File "Mage.php" is successfully patched.');

        return $this;
    }

    /**
     * Can patch Mage.php
     *
     * @return bool
     */
    protected function canPatch()
    {
        $extra = $this->package->getExtra();

        return array_key_exists('patch-mage-php', $extra) && $extra['patch-mage-php'] === true;
    }

    /**
     * Is Mage.php already patched
     *
     * @param $pathToMagePhp
     *
     * @return bool
     */
    protected function isAlreadyPatched($pathToMagePhp)
    {
        $magePhp = file_get_contents($pathToMagePhp);

        if (strpos($magePhp, self::COMPOSER_AUTOLOADER_LINE) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve path to Mage.php
     *
     * @return string
     */
    protected function getPathToMagePhp() {
        $extra = $this->package->getExtra();

        if (!array_key_exists('magento-root-dir', $extra) || $extra['magento-root-dir'] === '') {
            throw new InvalidConfigException('The setting "magento-root-dir" is required.');
        }

        $pathToMagePhp = $extra['magento-root-dir'] . '/app/Mage.php';

        if (!$this->filesystem->exists($pathToMagePhp)) {
            throw new FileNotFoundException('File "Mage.php" not found.');
        }

        if (!is_readable($pathToMagePhp)) {
            throw new IOException('File "Mage.php" is not readable');
        }

        if (!is_writable($pathToMagePhp)) {
            throw new IOException('File "Mage.php" is not writable');
        }

        return $pathToMagePhp;
    }
}
