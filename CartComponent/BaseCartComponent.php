<?php

namespace LSB\OrderBundle\CartComponent;

use LSB\UserBundle\Entity\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class BaseCartComponent implements CartComponentInterface
{
    const DEFAULT = 'default';

    public function getAdditionalName(): string
    {
        return self::DEFAULT;
    }

    public function __construct(
        protected TokenStorageInterface $tokenStorage,
    ){}

    /**
     * @return bool
     */
    public function isBackorderEnabled(): bool
    {
        //TODO fix
        return true;
    }

    /**
     * @return TokenStorageInterface
     */
    public function getTokenStorage(): TokenStorageInterface
    {
        return $this->tokenStorage;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * @return UserInterface|null
     * @throws \Exception
     */
    protected function getUser(): ?UserInterface
    {
        if ($this->tokenStorage
            && $this->tokenStorage->getToken()
            && $this->tokenStorage->getToken()->getUser() instanceof UserInterface) {

            /**
             * @var UserInterface $user
             */
            $user = $this->tokenStorage->getToken()->getUser();

            if (!$user && !$this->ps->get('cart.for.notlogged')) {
                throw new \Exception('User not logged in.');
            }

            return $user;
        }

        return null;
    }
}