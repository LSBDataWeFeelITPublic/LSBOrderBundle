<?php
declare(strict_types=1);

namespace LSB\OrderBundle\DependencyInjection\Compiler;

use LSB\OrderBundle\DependencyInjection\Configuration;
use LSB\UtilityBundle\DependencyInjection\Compiler\BaseResolveEntitiesPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class AddResolveEntitiesPass
 * @package LSB\OrderBundle\DependencyInjection\Compiler
 */
class AddResolveEntitiesPass extends BaseResolveEntitiesPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        $this->processResources($container, Configuration::CONFIG_KEY);
    }
}
