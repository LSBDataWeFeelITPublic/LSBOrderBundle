<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartModule;

use LSB\ContractorBundle\Entity\ContractorInterface;
use LSB\OrderBundle\CartComponent\DataCartComponent;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Event\CartEvent;
use LSB\OrderBundle\Model\CartModuleConfiguration;
use LSB\OrderBundle\Model\CartModuleProcessResult;
use LSB\OrderBundle\Model\FormSubmitResult;
use LSB\UserBundle\Entity\UserInterface;
use LSB\UtilityBundle\ModuleInventory\BaseModuleInventory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BaseModule
 * @package LSB\CartBundle\Module
 */
abstract class BaseCartModule extends BaseModuleInventory implements CartModuleInterface
{
    const NAME = 'abstract_module';

    const FORM_CLASS = 'Form';

    protected bool $isConfigured = false;

    protected mixed $nameConverter = null;

    public function __construct(protected DataCartComponent $dataCartComponent)
    {
        $this->isConfigured = true;
        $this->nameConverter = new CamelCaseToSnakeCaseNameConverter();
    }


    /**
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * @inheritDoc
     */
    public function getAdditionalName(): string
    {
        return static::ADDITIONAL_NAME_DEFAULT;
    }

    /**
     *
     */
    public function __toString()
    {
        $this->getName();
    }

    /**
     * @param CartInterface $cart
     */
    public function validateDependencies(CartInterface $cart): void
    {
    }

    /**
     * @param CartInterface $cart
     * @return array
     */
    public function validate(CartInterface $cart): array
    {
        return [];
    }


    /**
     * @return string|null
     */
    public function getDefaultFormClass(): ?string
    {
        if (defined('static::FORM_CLASS')) {
            return static::FORM_CLASS;
        }

        return null;
    }

    /**
     * @param $dataObject
     * @param array $options
     * @param bool $useCart
     * @return FormInterface|null
     * @throws \Exception
     */
    public function getDefaultForm($dataObject, array $options = [], bool $useCart = true): ?FormInterface
    {
        if (!$dataObject && $useCart) {
            $dataObject = $this->getCart();
        }

        if ($this->getDefaultFormClass()) {
            return $this->dataCartComponent->getFormFactory()->create($this->getDefaultFormClass(), $dataObject, array_merge($options, $this->getDefaultFormOptions($useCart ? $dataObject : null)));
        }

        return null;
    }

    /**
     * @param CartInterface|null $cart
     * @return array
     */
    protected function getDefaultFormOptions(?CartInterface $cart): array
    {
        return [];
    }

    /**
     * @param CartInterface $cart
     * @param null|Request $request
     * @param bool $isInitialRender
     * @return array|string|null
     * @throws \Exception
     */
    public function render(CartInterface $cart, ?Request $request = null, bool $isInitialRender = false)
    {
        switch ($this->dataCartComponent->getPs()->get('cart.render.format')) {
            case static::RENDER_FORMAT_JSON:
            case static::RENDER_FORMAT_XML:
                return $this->getDataForSerialize($cart, $request);
            //return $this->serializer->serialize(, $this->dataCartComponent->getPs()->getParameter('cart.render.format'));
            case static::RENDER_FORMAT_HTML:
            default:
                $data = $this->getDataForRender($cart, $request);

                return $this->dataCartComponent->getTemplating()->render(
                    'LSBFrontendBundle:Cart:/' . $this->dataCartComponent->getPs()->get('app.customViewDir') . '/modules/' . $this->getName() . '/' . $this->getName() . '.html.twig',
                    $data
                );
        }

        throw new \Exception('You should not reach this place');
    }

    /**
     * @param CartInterface $cart
     */
    public function prepare(CartInterface $cart)
    {
        return;
    }

    /**
     * @inheritdoc
     */
    public function getDataForRender(CartInterface $cart, ?Request $request = null): array
    {
        return [
            'cart' => $cart,
            'moduleName' => $this->getName(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getDataForSerialize(CartInterface $cart, ?Request $request = null): array
    {
        return $this->getDataForRender($cart, $request);
    }

    /**
     * @inheritdoc
     */
    public function getRedirect(): ?string
    {
        return null;
    }

    /**
     * @return UserInterface|null
     * @throws \Exception
     */
    protected function getUser(): ?UserInterface
    {
        return $this->dataCartComponent->getUser();
    }

    /**
     * @param bool $refresh
     * @return CartInterface
     * @throws \Exception
     */
    protected function getCart(bool $refresh = false): CartInterface
    {
        return $this->dataCartComponent->getCart($refresh);
    }


    /**
     * @inheritdoc
     */
    public function apiProcess(?CartInterface $cart, Request $request)
    {
        return $this->process($cart, $request);
    }

    /**
     * @return bool
     */
    public function isForApiUsage(): bool
    {
        return match ($this->dataCartComponent->getPs()->get('cart.render.format')) {
            CartModuleInterface::RENDER_FORMAT_JSON, CartModuleInterface::RENDER_FORMAT_XML => true,
            default => false,
        };

    }

    /**
     * @param CartInterface|null $cart
     * @param Request|null $request
     * @return array
     */
    public function getModulesToRefresh(?CartInterface $cart = null, ?Request $request = null): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getConfiguration(
        CartInterface        $cart,
        ?UserInterface       $user = null,
        ?ContractorInterface $contractor = null,
        ?Request             $request = null,
        bool                 $isInitialRender = false
    ): CartModuleConfiguration {
        return new CartModuleConfiguration(
            false,
            $this->isViewable($cart, $user, $contractor, $request, $isInitialRender),
            $this->getFormSchema($cart, $user, $contractor, $request, $isInitialRender)
        );
    }

    /**
     * @inheritDoc
     */
    public function getFormSchema(
        CartInterface        $cart,
        ?UserInterface       $user = null,
        ?ContractorInterface $customer = null,
        ?Request             $request = null,
        bool                 $isInitialRender = false
    ): array {

        return [];
    }

    /**
     * @inheritDoc
     */
    public function isViewable(
        CartInterface        $cart,
        ?UserInterface       $user = null,
        ?ContractorInterface $customer = null,
        ?Request             $request = null,
        bool                 $isInitialRender = false
    ): bool {
        return $this->dataCartComponent->isViewable();
    }

    /**
     * @inheritDoc
     */
    public function isAccessible(
        CartInterface        $cart,
        ?UserInterface       $user = null,
        ?ContractorInterface $customer = null,
        ?Request             $request = null,
        bool                 $isInitialRender = false
    ): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function handleDefaultFormSubmit(CartInterface $cart, Request $request): FormSubmitResult
    {
        $form = $this->getDefaultForm($cart);

        $result = new FormSubmitResult(
            false,
            $form
        );

        if ($request->getMethod() === Request::METHOD_POST) {
            if ($this->isForApiUsage()) {
                $data = json_decode($request->getContent(), true);
                $form->submit($data);
            } else {
                $form->handleRequest($request);
            }

            if ($form->isValid()) {
                $result = new FormSubmitResult(true, $form);
            } else {
                $result = new FormSubmitResult(
                    false,
                    $form
                );
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function dispatchSuccessEvent(CartInterface $cart): void
    {
        $this->dataCartComponent->getEventDispatcher()->dispatch(
            new CartEvent($cart),
            sprintf('cart.%s.success', $this->nameConverter->normalize($this->getName()))
        );
    }

    /**
     * @param CartInterface $cart
     */
    public function dispatchFailEvent(CartInterface $cart): void
    {
        $this->dataCartComponent->getEventDispatcher()->dispatch(
            new CartEvent($cart),
            sprintf('cart.%s.fail', $this->nameConverter->normalize($this->getName()))
        );
    }

    /**
     * @inheritDoc
     */
    public function process(?CartInterface $cart, Request $request)
    {
        if (!$cart) {
            $cart = $this->getCart();
        }

        $result = null;

        if (!$this->isViewable($cart, null, null, $request, false)) {
            return new CartModuleProcessResult($result, Response::HTTP_NOT_ACCEPTABLE);
        }

        $form = $this->getDefaultForm($cart);

        if ($form instanceof FormInterface) {
            $status = Response::HTTP_NOT_ACCEPTABLE;

            if (!$cart) {
                $cart = $this->getCart();
            }

            $formSubmitResult = $this->handleDefaultFormSubmit($cart, $request);

            if ($formSubmitResult->isSuccess()) {
                $this->dispatchSuccessEvent($cart);
                $status = Response::HTTP_OK;
                $this->dataCartComponent->getCartManager()->flush();
            } else {
                $this->dispatchFailEvent($cart);
                $result = $formSubmitResult->getForm();
            }
        } else {
            $status = Response::HTTP_OK;
        }

        return new CartModuleProcessResult($result, $status);
    }

    /**
     * @inheritDoc
     */
    public function getSerializationGroups(CartInterface $cart, ?Request $request = null): array
    {
        return [];
    }

    /** @inheritDoc */
    public function blockDataRenderWhileRefreshing(CartInterface $cart, ?Request $request): array
    {
        return [];
    }
}
