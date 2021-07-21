<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;

/**
 * Class CartModuleRenderResponse
 * @package LSB\CartBundle\Model
 */
class CartModuleRenderResponse
{
    /**
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var mixed
     */
    protected $data;

    /**
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var mixed
     */
    protected $configuration;

    /**
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var array
     */
    protected $modulesToRefresh;

    /**
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var array
     */
    protected $renderedModules;

    /**
     * @var array
     */
    protected $serializationGroups;

    /**
     * CartModuleRenderResponse constructor.
     * @param $data
     * @param CartModuleConfiguration $configuration
     * @param array $renderedModules
     * @param array $modulesToRefresh
     * @param array $serializationGroups
     */
    public function __construct(
        $data,
        CartModuleConfiguration $configuration,
        array $renderedModules = [],
        array $modulesToRefresh = [],
        array $serializationGroups = []
    ) {
        $this->data = $data;
        $this->configuration = $configuration;
        $this->modulesToRefresh = $modulesToRefresh;
        $this->renderedModules = $renderedModules;
        $this->serializationGroups = $serializationGroups;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return CartModuleConfiguration
     */
    public function getConfiguration(): CartModuleConfiguration
    {
        return $this->configuration;
    }

    /**
     * @return array
     */
    public function getModulesToRefresh(): array
    {
        return $this->modulesToRefresh;
    }

    /**
     * @return array
     */
    public function getRenderedModules(): array
    {
        return $this->renderedModules;
    }

    /**
     * @return array
     */
    public function getSerializationGroups(): array
    {
        return $this->serializationGroups;
    }
}