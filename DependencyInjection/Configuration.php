<?php
declare(strict_types=1);

namespace LSB\OrderBundle\DependencyInjection;

use LSB\OrderBundle\Entity\EntityInterface;
use LSB\OrderBundle\Entity\EntityTranslationInterface;
use LSB\OrderBundle\Factory\EntityFactory;
use LSB\OrderBundle\Form\EntityTranslationType;
use LSB\OrderBundle\Form\EntityType;
use LSB\OrderBundle\LSBMessengerBundle;
use LSB\OrderBundle\LSBOrderBundle;
use LSB\OrderBundle\Manager\EntityManager;
use LSB\OrderBundle\Repository\EntityRepository;
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
            ->scalarNode(BE::CONFIG_KEY_TRANSLATION_DOMAIN)->defaultValue((new \ReflectionClass(LSBOrderBundle::class))->getShortName())->end()
            ->arrayNode(BE::CONFIG_KEY_RESOURCES)
            ->children()
            // Start Order

            // End Order
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
