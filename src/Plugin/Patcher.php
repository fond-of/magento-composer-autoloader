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
    const COMPOSER_AUTOLOADER_LINE = 'require_once BP . \'..\' . DS . \'vendor\' . DS . \'autoload.php\';' . PHP_EOL;

    const VARIEN_AUTOLOADER_LINE = 'Varien_Autoload::register();' . PHP_EOL;

    protected $config;

    protected $composer;

    protected $io;

    protected $filesystem;

    /**
     * Patcher constructor.
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
        $this->config = $composer->getConfig();
    }

    /**
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
     * @return bool
     */
    protected function canPatch()
    {
        $patchMagePhp = $this->config->get('patch-mage-php');

        if ($patchMagePhp === null || $patchMagePhp === false) {
            return false;
        }

        return true;
    }

    protected function isAlreadyPatched($pathToMagePhp)
    {
        $magePhp = file_get_contents($pathToMagePhp);

        if (strpos(self::COMPOSER_AUTOLOADER_LINE, $magePhp) !== false) {
            return true;
        }

        return false;
    }

    protected function getPathToMagePhp() {
        $magentoRootDir = $this->config->get('magento-root-dir');

        if ($magentoRootDir === null) {
            throw new InvalidConfigException('The setting "magento-root-dir" is required.');
        }

        $pathToMagePhp = $magentoRootDir . '/app/Mage.php';

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