<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use Symfony\Component\Form\FormInterface;

/**
 * Class FormSubmitResult
 * @package LSB\OrderBundle\Model
 */
class FormSubmitResult
{
    /**
     * @var bool
     */
    protected bool $isSuccess;

    /**
     * @var FormInterface
     */
    protected FormInterface $form;

    /**
     * @var string|null
     */
    protected ?string $message;

    /**
     * @var string|null
     */
    protected ?string $data;

    /**
     * FormSubmitResult constructor.
     * @param bool $isSuccess
     * @param FormInterface $form
     * @param string|null $message
     * @param null $data
     */
    public function __construct(
        bool $isSuccess,
        FormInterface $form,
        ?string $message = null,
        $data = null
    ) {
        $this->isSuccess = $isSuccess;
        $this->form = $form;
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    /**
     * @return FormInterface
     */
    public function getForm(): FormInterface
    {
        return $this->form;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @return string|null
     */
    public function getData(): ?string
    {
        return $this->data;
    }
}