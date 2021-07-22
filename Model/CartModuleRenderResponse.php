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
     * @var mixed
     */
    protected $data;

    /**
     * @var CartModuleConfiguration|null
     */
    protected ?CartModuleConfiguration $configuration;

    /**
     * @var array
     */
    protected array $modulesToRefresh = [];

    /**
     * @var array
     */
    protected array $renderedModules = [];

    /**
     * @var array
     */
    protected array $serializationGroups = [];

    /**
     * CartModuleRenderResponse constructor.
     *
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
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     * @return CartModuleRenderResponse
     */
    public function setData(mixed $data): CartModuleRenderResponse
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return CartModuleConfiguration|null
     */
    public function getConfiguration(): ?CartModuleConfiguration
    {
        return $this->configuration;
    }

    /**
     * @param CartModuleConfiguration|null $configuration
     * @return CartModuleRenderResponse
     */
    public function setConfiguration(?CartModuleConfiguration $configuration): CartModuleRenderResponse
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @return array
     */
    public function getModulesToRefresh(): array
    {
        return $this->modulesToRefresh;
    }

    /**
     * @param ${ENTRY_HINT} $modulesToRefresh
     *
     * @return CartModuleRenderResponse
     */
    public function addModulesToRefresh($modulesToRefresh): CartModuleRenderResponse
    {
        if (false === in_array($modulesToRefresh, $this->modulesToRefresh, true)) {
            $this->modulesToRefresh[] = $modulesToRefresh;
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $modulesToRefresh
     *
     * @return CartModuleRenderResponse
     */
    public function removeModulesToRefresh($modulesToRefresh): CartModuleRenderResponse
    {
        if (true === in_array($modulesToRefresh, $this->modulesToRefresh, true)) {
            $index = array_search($modulesToRefresh, $this->modulesToRefresh);
            array_splice($this->modulesToRefresh, $index, 1);
        }
        return $this;
    }

    /**
     * @param array $modulesToRefresh
     * @return CartModuleRenderResponse
     */
    public function setModulesToRefresh(array $modulesToRefresh): CartModuleRenderResponse
    {
        $this->modulesToRefresh = $modulesToRefresh;
        return $this;
    }

    /**
     * @return array
     */
    public function getRenderedModules(): array
    {
        return $this->renderedModules;
    }

    /**
     * @param ${ENTRY_HINT} $renderedModule
     *
     * @return CartModuleRenderResponse
     */
    public function addRenderedModule($renderedModule): CartModuleRenderResponse
    {
        if (false === in_array($renderedModule, $this->renderedModules, true)) {
            $this->renderedModules[] = $renderedModule;
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $renderedModule
     *
     * @return CartModuleRenderResponse
     */
    public function removeRenderedModule($renderedModule): CartModuleRenderResponse
    {
        if (true === in_array($renderedModule, $this->renderedModules, true)) {
            $index = array_search($renderedModule, $this->renderedModules);
            array_splice($this->renderedModules, $index, 1);
        }
        return $this;
    }

    /**
     * @param array $renderedModules
     * @return CartModuleRenderResponse
     */
    public function setRenderedModules(array $renderedModules): CartModuleRenderResponse
    {
        $this->renderedModules = $renderedModules;
        return $this;
    }

    /**
     * @return array
     */
    public function getSerializationGroups(): array
    {
        return $this->serializationGroups;
    }

    /**
     * @param ${ENTRY_HINT} $serializationGroup
     *
     * @return CartModuleRenderResponse
     */
    public function addSerializationGroup($serializationGroup): CartModuleRenderResponse
    {
        if (false === in_array($serializationGroup, $this->serializationGroups, true)) {
            $this->serializationGroups[] = $serializationGroup;
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $serializationGroup
     *
     * @return CartModuleRenderResponse
     */
    public function removeSerializationGroup($serializationGroup): CartModuleRenderResponse
    {
        if (true === in_array($serializationGroup, $this->serializationGroups, true)) {
            $index = array_search($serializationGroup, $this->serializationGroups);
            array_splice($this->serializationGroups, $index, 1);
        }
        return $this;
    }

    /**
     * @param array $serializationGroups
     * @return CartModuleRenderResponse
     */
    public function setSerializationGroups(array $serializationGroups): CartModuleRenderResponse
    {
        $this->serializationGroups = $serializationGroups;
        return $this;
    }
}