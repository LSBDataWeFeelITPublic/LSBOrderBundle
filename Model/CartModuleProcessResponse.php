<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CartModuleProcessResponse
 * @package LSB\CartBundle\Model
 */
class CartModuleProcessResponse
{

    /**
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var mixed
     */
    protected $moduleResponse;

    /**
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var CartModuleConfiguration
     */
    protected $moduleConfiguration;

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
     * @var integer
     */
    protected $status;

    /**
     * @var array
     */
    protected $serializationGroups;

    /**
     * CartModuleProcessResponse constructor.
     * @param $moduleResponse
     * @param CartModuleConfiguration $configuration
     * @param array $modulesToRefresh
     * @param array $renderedModules
     * @param int|null $status
     * @param array $serializationGroups
     */
    public function __construct(
        $moduleResponse,
        CartModuleConfiguration $configuration,
        array $modulesToRefresh = [],
        array $renderedModules = [],
        int $status = Response::HTTP_OK,
        array $serializationGroups = []
    ) {
        $this->moduleResponse = $moduleResponse;
        $this->modulesToRefresh = $modulesToRefresh;
        $this->renderedModules = $renderedModules;
        $this->status = $status;
        $this->moduleConfiguration = $configuration;
        $this->serializationGroups = $serializationGroups;
    }

    /**
     * @return mixed
     */
    public function getModuleResponse()
    {
        return $this->moduleResponse;
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
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return CartModuleConfiguration
     */
    public function getModuleConfiguration(): CartModuleConfiguration
    {
        return $this->moduleConfiguration;
    }

    /**
     * @return array
     */
    public function getSerializationGroups(): array
    {
        return $this->serializationGroups;
    }
}