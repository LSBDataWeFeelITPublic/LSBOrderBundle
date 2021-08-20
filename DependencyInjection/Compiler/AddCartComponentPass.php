<?php
declare(strict_types=1);

namespace LSB\OrderBundle\DependencyInjection\Compiler;

use LSB\OrderBundle\Service\CartComponentInventory;
use LSB\OrderBundle\Service\CartComponentService;
use LSB\UtilityBundle\ModuleInventory\BaseModuleInventory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddCartComponentPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(CartComponentInventory::class)) {
            return;
        }

        $def = $container->findDefinition(CartComponentInventory::class);

        foreach ($container->findTaggedServiceIds(CartComponentService::CART_COMPONENT_TAG_NAME) as $id => $attrs) {
            $def->addMethodCall(BaseModuleInventory::ADD_MODULE_METHOD, [new Reference($id), $attrs]);
        }
    }
}
