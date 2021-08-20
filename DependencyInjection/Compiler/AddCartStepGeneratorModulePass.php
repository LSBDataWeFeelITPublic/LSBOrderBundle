<?php
declare(strict_types=1);

namespace LSB\OrderBundle\DependencyInjection\Compiler;

use LSB\OrderBundle\Service\CartStepGeneratorInventory;
use LSB\OrderBundle\Service\CartStepGeneratorService;
use LSB\UtilityBundle\ModuleInventory\BaseModuleInventory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddCartStepGeneratorModulePass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(CartStepGeneratorInventory::class)) {
            return;
        }

        $def = $container->findDefinition(CartStepGeneratorInventory::class);

        foreach ($container->findTaggedServiceIds(CartStepGeneratorService::MODULE_TAG_NAME) as $id => $attrs) {
            $def->addMethodCall(BaseModuleInventory::ADD_MODULE_METHOD, [new Reference($id), $attrs]);
        }
    }
}
