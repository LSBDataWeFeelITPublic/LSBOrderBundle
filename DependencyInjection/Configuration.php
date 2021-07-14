<?php
declare(strict_types=1);

namespace LSB\OrderBundle\DependencyInjection;

use LSB\OrderBundle\Entity\Order;
use LSB\OrderBundle\Entity\OrderInterface;
use LSB\OrderBundle\Entity\OrderNote;
use LSB\OrderBundle\Entity\OrderNoteInterface;
use LSB\OrderBundle\Entity\OrderPackage;
use LSB\OrderBundle\Entity\OrderPackageInterface;
use LSB\OrderBundle\Entity\OrderPackageItem;
use LSB\OrderBundle\Entity\OrderPackageItemInterface;
use LSB\OrderBundle\Factory\OrderFactory;
use LSB\OrderBundle\Factory\OrderNoteFactory;
use LSB\OrderBundle\Factory\OrderPackageFactory;
use LSB\OrderBundle\Factory\OrderPackageItemFactory;
use LSB\OrderBundle\Form\OrderNoteType;
use LSB\OrderBundle\Form\OrderPackageItemType;
use LSB\OrderBundle\Form\OrderPackageType;
use LSB\OrderBundle\Form\OrderType;
use LSB\OrderBundle\LSBOrderBundle;
use LSB\OrderBundle\Manager\OrderManager;
use LSB\OrderBundle\Manager\OrderNoteManager;
use LSB\OrderBundle\Manager\OrderPackageItemManager;
use LSB\OrderBundle\Manager\OrderPackageManager;
use LSB\OrderBundle\Repository\OrderNoteRepository;
use LSB\OrderBundle\Repository\OrderPackageItemRepository;
use LSB\OrderBundle\Repository\OrderPackageRepository;
use LSB\OrderBundle\Repository\OrderRepository;
use LSB\UtilityBundle\Config\Definition\Service\ServicesConfiguration;
use LSB\UtilityBundle\DependencyInjection\BaseExtension as BE;
use LSB\UtilityBundle\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    const CONFIG_KEY = 'lsb_order';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::CONFIG_KEY);

        $treeBuilder
            ->getRootNode()
            ->children()
            //poczatek zmiany
//            ->arrayNode('services')
//                ->useAttributeAsKey('code')
//                ->prototype('scalar')->end()
//                ->defaultValue(['xxx' => 'yyy'])
//            ->end()

            ->addServicesNodeConfiguration(
                (new ServicesConfiguration)
                    ->add(OrderInterface::class, OrderManager::class)
            )
            //koniec zmiany
            ->bundleTranslationDomainScalar(LSBOrderBundle::class)->end()
            ->arrayNode(BE::CONFIG_KEY_RESOURCES)
            ->children()
            ->resourceNode(
                'order',
                Order::class,
                OrderInterface::class,
                OrderFactory::class,
                OrderRepository::class,
                OrderManager::class,
                OrderType::class
            )
            ->end()
            ->resourceNode(
                'order_package',
                OrderPackage::class,
                OrderPackageInterface::class,
                OrderPackageFactory::class,
                OrderPackageRepository::class,
                OrderPackageManager::class,
                OrderPackageType::class
            )
            ->end()
            ->resourceNode(
                'order_package_item',
                OrderPackageItem::class,
                OrderPackageItemInterface::class,
                OrderPackageItemFactory::class,
                OrderPackageItemRepository::class,
                OrderPackageItemManager::class,
                OrderPackageItemType::class
            )
            ->end()
            ->resourceNode(
                'order_note',
                OrderNote::class,
                OrderNoteInterface::class,
                OrderNoteFactory::class,
                OrderNoteRepository::class,
                OrderNoteManager::class,
                OrderNoteType::class
            )
            ->end()
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
