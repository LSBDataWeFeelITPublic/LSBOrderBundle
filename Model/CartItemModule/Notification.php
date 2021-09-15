<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model\CartItemModule;

use LSB\UtilityBundle\Attribute\Serialize;

#[Serialize]
class Notification
{
    const TYPE_WARNING = 'warning';
    const TYPE_SUCCESS = 'success';
    const TYPE_DEFAULT = 'default';
    const TYPE_ERROR = 'error';

    /**
     * @param string $type
     * @param string|null $content
     */
    public function __construct(
        protected string  $type,
        protected ?string $content = null
    ) {
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Notification
     */
    public function setType(string $type): Notification
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string|null $content
     * @return Notification
     */
    public function setContent(?string $content): Notification
    {
        $this->content = $content;
        return $this;
    }
}