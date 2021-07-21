<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CartSummary
 * @package LSB\CartBundle\Model
 */
class CartModuleFormHandleResponse
{

    /**
     * @var bool
     */
    protected $isSubmitted = false;

    /**
     * @var bool
     */
    protected $isValid = false;

    /**
     * @var array
     */
    protected $result = [];

    /**
     * @var Request
     */
    protected $request = null;

    /**
     * @var Form
     */
    protected $form = null;

    /**
     * @var \StdClass
     */
    protected $additionalData;

    public function __construct()
    {
        $this->additionalData = new \stdClass();
    }

    /**
     * @return bool
     */
    public function isSubmitted(): bool
    {
        if ($this->form) {
            return $this->form->isSubmitted();
        }

        return $this->isSubmitted;
    }

    /**
     * @param bool $isSubmitted
     * @return CartModuleFormHandleResponse
     */
    public function setIsSubmitted(bool $isSubmitted): CartModuleFormHandleResponse
    {
        $this->isSubmitted = $isSubmitted;

        return $this;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        if ($this->form && $this->form->isSubmitted()) {
            return $this->form->isValid();
        }

        return $this->isValid;
    }

    /**
     * @param bool $isValid
     * @return CartModuleFormHandleResponse
     */
    public function setIsValid(bool $isValid): CartModuleFormHandleResponse
    {
        $this->isSubmitted = true; //Ustawienie isValid jest równoznaczne z tym, że formularz został odebrany
        $this->isValid = $isValid;

        return $this;
    }

    /**
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * @param array $result
     * @return CartModuleFormHandleResponse
     */
    public function setResult(array $result): CartModuleFormHandleResponse
    {
        $this->result = $result;

        return $this;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param Request $request
     * @return CartModuleFormHandleResponse
     */
    public function setRequest(Request $request): CartModuleFormHandleResponse
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return Form
     */
    public function getForm(): Form
    {
        return $this->form;
    }

    /**
     * @param Form $form
     * @return CartModuleFormHandleResponse
     */
    public function setForm(Form $form): CartModuleFormHandleResponse
    {
        $this->form = $form;

        return $this;
    }

    /**
     * @return \StdClass
     */
    public function getAdditionalData(): \StdClass
    {
        return $this->additionalData;
    }

    /**
     * @param \StdClass $additionalData
     * @return CartModuleFormHandleResponse
     */
    public function setAdditionalData(\StdClass $additionalData): CartModuleFormHandleResponse
    {
        $this->additionalData = $additionalData;

        return $this;
    }

    /**
     * @param string $property
     * @param $data
     */
    public function addAdditionalDataProperty(string $property, $data) {
        $this->additionalData->$property = $data;

        return $this;
    }

    /**
     * @param string $property
     * @return null
     */
    public function getAdditionalDataProperty(string $property) {
        if (isset($this->additionalData->$property)) {
            return $this->additionalData->$property;
        }

        return null;
    }

}