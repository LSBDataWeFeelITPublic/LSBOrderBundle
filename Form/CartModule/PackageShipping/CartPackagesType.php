<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Form\CartModule\PackageShipping;

use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Manager\CartManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class CartPackageType
 * @package SHOP\CartBundle\Form
 */
class CartPackagesType extends AbstractType
{

    public function __construct(
        protected CartManager $cartManager
    ) {}

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @throws \Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * @var CartInterface $cart
         */
        $cart = $builder->getData() instanceof CartInterface ? $builder->getData() : null;

        $builder->add(
            'cartPackages',
            CollectionType::class,
            [
                'entry_type' => CartPackageShippingType::class,
                'label' => 'Cart.Column.cartPackages',
                'translation_domain' => 'LSBOrderBundleCart',
                'prototype' => true,
                'allow_add' => $cart->getCartPackages()->count() ? false : true,
                'by_reference' => false,
                'required' => true,
                'entry_options' => [
                    'cart' => $builder->getData(),
                    'available_shipping_forms' => $options['available_shipping_forms'],
                ]
            ]
        );
    }

    /**
     * @param OptionsResolver $resolver
     * @throws \Exception
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->cartManager->getResourceEntityClass(),
            'translation_domain' => 'Cart',
            'csrf_protection' => false,
            'validation_groups' => ['Default'],
            'allow_add' => true,
        ]);

        $resolver->setRequired('available_shipping_forms');
    }
}
