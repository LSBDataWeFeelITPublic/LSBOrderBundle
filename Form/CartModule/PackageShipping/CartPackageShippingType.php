<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Form\CartModule\PackageShipping;

use LSB\OrderBundle\Entity\CartPackage;
use LSB\OrderBundle\Manager\CartPackageManager;
use LSB\ShippingBundle\Entity\Method;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CartPackageShippingType extends AbstractType
{

    public function __construct(
        protected CartPackageManager $cartPackageManager
    ) {}

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->onPreSetData($builder, $options);
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    protected function onPreSetData(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use (&$options) {
                $form = $event->getForm();
                /**
                 * @var CartPackage|null $cartPackage
                 */
                $cartPackage = $event->getData();

                $form
                    ->add(
                        'shippingMethod',
                        EntityType::class,
                        [
                            'class' => Method::class,
                            'label' => 'Cart.Package.column.shippingForm',
                            'required' => true,
                            'choice_value' => 'uuid',
                            'choices' => $this->getShippingFormsForCartPackage($options['available_shipping_forms'], $cartPackage)
                        ]
                    );
            }
        );
    }

    /**
     * @param array $availableShippingForms
     * @param CartPackage|null $cartPackage
     * @return array
     */
    protected function getShippingFormsForCartPackage(
        array $availableShippingForms,
        ?CartPackage $cartPackage = null
    ): array {
        if ($cartPackage && array_key_exists($cartPackage->getUuid(), $availableShippingForms)) {
            return $availableShippingForms[$cartPackage->getUuid()];
        }

        return [];
    }

    /**
     * @param OptionsResolver $resolver
     * @throws \Exception
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->cartPackageManager->getResourceEntityClass(),
            'translation_domain' => 'Cart',
            'csrf_protection' => false,
            'cart' => null,
            'available_shipping_forms' => []
        ]);
    }
}
