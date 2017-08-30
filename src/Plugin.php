<?php

namespace FondOf\Magento\Composer\Autoloader;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use FondOf\Magento\Composer\Autoloader\Plugin\Patcher;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * @var Patcher
     */
    protected $patcher;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * Apply plugin modifications to Composer
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Retrieve container
     */
    protected function getContainer()
    {
        if ($this->container === null) {
            $this->container = new ContainerBuilder();

            $this->container->set('composer', $this->composer);
            $this->container->set('io', $this->io);

            $loader = new XmlFileLoader($this->container, new FileLocator(__DIR__));
            $loader->load('../res/config/services.xml');
        }

        return $this->container;
    }

    /**
     * Retrieve patcher
     *
     * @return Patcher|object
     */
    protected function getPatcher()
    {
        if ($this->patcher === null) {
            $this->patcher = $this->getContainer()->get('patcher');
        }

        return $this->patcher;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     * * The method name to call (priority defaults to 0)
     * * An array composed of the method name to call and the priority
     * * An array of arrays composed of the method names to call and respective
     *   priorities, or 0 if unset
     *
     * For instance:
     *
     * * array('eventName' => 'methodName')
     * * array('eventName' => array('methodName', $priority))
     * * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'onPostInstallCmd',
            ScriptEvents::POST_UPDATE_CMD => 'onPostUpdateCmd'
        ];
    }

    /**
     * On post install cmd
     *
     * @param Event $event
     */
    public function onPostInstallCmd(Event $event)
    {
        $this->getPatcher()->patch();
    }

    /**
     * On post update cmd
     *
     * @param Event $event
     */
    public function onPostUpdateCmd(Event $event)
    {
        $this->getPatcher()->patch();
    }
}
