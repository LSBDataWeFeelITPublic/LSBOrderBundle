<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use JMS\Serializer\Annotation\Groups;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CartModuleProcessResult
 * @package LSB\CartBundle\Model
 */
class CartModuleProcessResult
{
    /**
     * @var mixed
     */
    protected $content;

    /**
     * @var int|null
     */
    protected ?int $status = null;

    /**
     * CartModuleProcessResult constructor.
     * @param $content
     * @param int|null $status
     */
    public function __construct(
        $content,
        ?int $status = Response::HTTP_OK
    ) {
        $this->content = $content;
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     * @return CartModuleProcessResult
     */
    public function setContent(mixed $content): CartModuleProcessResult
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @param int|null $status
     * @return CartModuleProcessResult
     */
    public function setStatus(?int $status): CartModuleProcessResult
    {
        $this->status = $status;
        return $this;
    }
}