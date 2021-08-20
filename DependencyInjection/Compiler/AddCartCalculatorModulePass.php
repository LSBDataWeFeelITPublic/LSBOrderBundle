<?php
declare(strict_types=1);

namespace LSB\OrderBundle\DependencyInjection\Compiler;

use LSB\OrderBundle\Service\CartCalculatorInventory;
use LSB\OrderBundle\Service\CartCalculatorService;
use LSB\UtilityBundle\ModuleInventory\BaseModuleInventory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddCartCalculatorModulePass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(CartCalculatorInventory::class)) {
            return;
        }

        $def = $container->findDefinition(CartCalculatorInventory::class);

        foreach ($container->findTaggedServiceIds(CartCalculatorService::MODULE_TAG_NAME) as $id => $attrs) {
            $def->addMethodCall(BaseModuleInventory::ADD_MODULE_METHOD, [new Reference($id), $attrs]);
        }
    }
}
