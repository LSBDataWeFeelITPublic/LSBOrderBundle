<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use stdClass;
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
    protected bool $isSubmitted = false;

    /**
     * @var bool
     */
    protected bool $isValid = false;

    /**
     * @var array
     */
    protected array $result = [];

    /**
     * @var Request|null
     */
    protected ?Request $request = null;

    /**
     * @var Form|null
     */
    protected ?Form $form = null;

    /**
     * @var StdClass|null
     */
    protected ?StdClass $additionalData;

    /**
     * CartModuleFormHandleResponse constructor.
     */
    public function __construct()
    {
        $this->additionalData = new \stdClass();
    }

    /**
     * @return bool
     */
    public function isSubmitted(): bool
    {
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
        return $this->isValid;
    }

    /**
     * @param bool $isValid
     * @return CartModuleFormHandleResponse
     */
    public function setIsValid(bool $isValid): CartModuleFormHandleResponse
    {
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
     * @param ${ENTRY_HINT} $result
     *
     * @return CartModuleFormHandleResponse
     */
    public function addResult($result): CartModuleFormHandleResponse
    {
        if (false === in_array($result, $this->result, true)) {
            $this->result[] = $result;
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $result
     *
     * @return CartModuleFormHandleResponse
     */
    public function removeResult($result): CartModuleFormHandleResponse
    {
        if (true === in_array($result, $this->result, true)) {
            $index = array_search($result, $this->result);
            array_splice($this->result, $index, 1);
        }
        return $this;
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
     * @return Request|null
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * @param Request|null $request
     * @return CartModuleFormHandleResponse
     */
    public function setRequest(?Request $request): CartModuleFormHandleResponse
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return Form|null
     */
    public function getForm(): ?Form
    {
        return $this->form;
    }

    /**
     * @param Form|null $form
     * @return CartModuleFormHandleResponse
     */
    public function setForm(?Form $form): CartModuleFormHandleResponse
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @return stdClass|null
     */
    public function getAdditionalData(): ?stdClass
    {
        return $this->additionalData;
    }

    /**
     * @param stdClass|null $additionalData
     * @return CartModuleFormHandleResponse
     */
    public function setAdditionalData(?stdClass $additionalData): CartModuleFormHandleResponse
    {
        $this->additionalData = $additionalData;
        return $this;
    }
}