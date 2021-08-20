<?php

namespace LSB\CartBundle\Interfaces;

interface CartItemConfigurationInterface
{
    public function setConfiguration($configuration);

    public function getConfiguration();
}