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
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var mixed
     */
    protected $content;

    /**
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var mixed
     */
    protected $status;

    /**
     * CartModuleProcessResult constructor.
     * @param $content
     * @param int $status
     */
    public function __construct(
        $content,
        int $status = Response::HTTP_OK
    ) {
        $this->content = $content;
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     * @return CartModuleProcessResult
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     * @return CartModuleProcessResult
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
}