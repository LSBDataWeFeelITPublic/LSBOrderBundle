<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use LSB\UtilityBundle\Attribute\Serialize;

#[Serialize]
class StepValidationResult
{
    public function __construct(
        protected array $errors,
        protected bool $success,
        protected int $errorsCnt
    ) {}

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     * @return StepValidationResult
     */
    public function setErrors(array $errors): StepValidationResult
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     * @return StepValidationResult
     */
    public function setSuccess(bool $success): StepValidationResult
    {
        $this->success = $success;
        return $this;
    }

    /**
     * @return int
     */
    public function getErrorsCnt(): int
    {
        return $this->errorsCnt;
    }

    /**
     * @param int $errorsCnt
     * @return StepValidationResult
     */
    public function setErrorsCnt(int $errorsCnt): StepValidationResult
    {
        $this->errorsCnt = $errorsCnt;
        return $this;
    }


}