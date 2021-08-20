<?php
declare(strict_types=1);

namespace LSB\OrderBundle\DependencyInjection\Compiler;

use LSB\OrderBundle\Service\CartModuleInventory;
use LSB\OrderBundle\Service\CartModuleService;
use LSB\UtilityBundle\ModuleInventory\BaseModuleInventory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddCartModulePass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(CartModuleInventory::class)) {
            return;
        }

        $def = $container->findDefinition(CartModuleInventory::class);

        foreach ($container->findTaggedServiceIds(CartModuleService::CART_MODULE_TAG_NAME) as $id => $attrs) {
            $def->addMethodCall(BaseModuleInventory::ADD_MODULE_METHOD, [new Reference($id), $attrs]);
        }
    }
}
