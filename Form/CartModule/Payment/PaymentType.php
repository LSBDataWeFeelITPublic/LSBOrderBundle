<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Form\CartModule\Payment;

use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Manager\CartManager;
use LSB\PaymentBundle\Entity\Method;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentType extends AbstractType
{
    public function __construct(
        protected CartManager $cartManager
    ) {}

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        /**
         * @var CartInterface $cart
         */
        $cart = $builder->getData();

        $paymentMethods = $options['availablePaymentMethods'];

        $builder
            ->add(
                'paymentMethod',
                EntityType::class,
                [
                    'choices' => $paymentMethods,
                    'data' => $cart && $cart->getPaymentMethod() ? $cart->getPaymentMethod() : $options['defaultPaymentMethod'],
                    'required' => true,
                    'class' => Method::class,
                    'choice_value' => 'uuid'
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
            'defaultPaymentMethod' => null,
            'availablePaymentMethods' => []
        ]);
    }
}