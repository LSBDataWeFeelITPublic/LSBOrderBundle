<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use Symfony\Component\Form\FormInterface;
use JMS\Serializer\Annotation\Groups;

/**
 * Class FormSubmitResult
 * @package LSB\CartBundle\Model
 */
class FormSubmitResult
{
    /**
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var bool
     */
    protected $isSuccess;

    /**
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var FormInterface
     */
    protected $form;

    /**
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var string|null
     */
    protected $message;

    /**
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var string|null
     */
    protected $data;

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
     * @param bool $isSuccess
     * @return FormSubmitResult
     */
    public function setIsSuccess(bool $isSuccess): FormSubmitResult
    {
        $this->isSuccess = $isSuccess;
        return $this;
    }

    /**
     * @return FormInterface
     */
    public function getForm(): FormInterface
    {
        return $this->form;
    }

    /**
     * @param FormInterface $form
     * @return FormSubmitResult
     */
    public function setForm(FormInterface $form): FormSubmitResult
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string|null $message
     * @return FormSubmitResult
     */
    public function setMessage(?string $message): FormSubmitResult
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getData(): ?string
    {
        return $this->data;
    }

    /**
     * @param string|null $data
     * @return FormSubmitResult
     */
    public function setData(?string $data): FormSubmitResult
    {
        $this->data = $data;
        return $this;
    }
}