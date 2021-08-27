<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model\CartItemModule;

class UpdateCounter
{
    public function __construct(
        protected int $created = 0,
        protected int $updated = 0,
        protected int $removed = 0,
        protected int $skipped = 0
    ) {}

    /**
     * @param int $add
     */
    public function increaseCreated(int $add = 1): void
    {
        $this->created += $add;
    }

    /**
     * @param int $add
     */
    public function increaseUpdated(int $add = 1): void
    {
        $this->updated += $add;
    }

    /**
     * @param int $add
     */
    public function increaseRemoved(int $add = 1): void
    {
        $this->removed += $add;
    }

    /**
     * @param int $add
     */
    public function increaseSkipped(int $add = 1): void
    {
        $this->skipped += $add;
    }

    /**
     * @param int $add
     */
    public function decreaseCreated(int $add = 1): void
    {
        $this->created -= $add;
    }

    /**
     * @param int $add
     */
    public function decreaseUpdated(int $add = 1): void
    {
        $this->updated -= $add;
    }

    /**
     * @param int $add
     */
    public function decreaseRemoved(int $add = 1): void
    {
        $this->removed -= $add;
    }

    /**
     * @param int $add
     */
    public function decreaseSkipped(int $add = 1): void
    {
        $this->skipped -= $add;
    }

    /**
     * @return int
     */
    public function getCreated(): int
    {
        return $this->created;
    }

    /**
     * @param int $created
     * @return UpdateCounter
     */
    public function setCreated(int $created): UpdateCounter
    {
        $this->created = $created;
        return $this;
    }

    /**
     * @return int
     */
    public function getUpdated(): int
    {
        return $this->updated;
    }

    /**
     * @param int $updated
     * @return UpdateCounter
     */
    public function setUpdated(int $updated): UpdateCounter
    {
        $this->updated = $updated;
        return $this;
    }

    /**
     * @return int
     */
    public function getRemoved(): int
    {
        return $this->removed;
    }

    /**
     * @param int $removed
     * @return UpdateCounter
     */
    public function setRemoved(int $removed): UpdateCounter
    {
        $this->removed = $removed;
        return $this;
    }

    /**
     * @return int
     */
    public function getSkipped(): int
    {
        return $this->skipped;
    }

    /**
     * @param int $skipped
     * @return UpdateCounter
     */
    public function setSkipped(int $skipped): UpdateCounter
    {
        $this->skipped = $skipped;
        return $this;
    }


}