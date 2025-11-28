<?php
defined('_JEXEC') or die;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;

use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;

use Jules\Component\AgentEngine\Administrator\Extension\AgentEngineComponent;


// Clear any cached bytecode to prevent stale class definitions
if (function_exists('opcache_reset')) {
    @opcache_reset();
}

// Manual load ONLY component class (autoloader broken on this hosting)
$componentFile = dirname(__DIR__) . '/src/Extension/AgentEngineComponent.php';
if (file_exists($componentFile) && !class_exists('Jules\\Component\\AgentEngine\\Administrator\\Extension\\AgentEngineComponent', false)) {
    require_once $componentFile;
}

// Controllers will use autoloader

return new class implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $namespace = 'Jules\\Component\\AgentEngine\\Administrator';

        $container->registerServiceProvider(new ComponentDispatcherFactory($namespace));
        $container->registerServiceProvider(new MVCFactory($namespace));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new AgentEngineComponent(
                    $container->get(ComponentDispatcherFactoryInterface::class)
                );
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                return $component;
            }
        );
    }
};
