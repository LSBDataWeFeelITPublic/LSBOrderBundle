<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use LSB\UtilityBundle\Attributes\Serialize\Serialize;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CartModuleProcessResponse
 * @package LSB\CartBundle\Model
 */
#[Serialize]class CartModuleProcessResponse
{

    /**
     * @var mixed
     */
    protected $moduleResponse;

    /**
     * @var CartModuleConfiguration
     */
    protected CartModuleConfiguration $moduleConfiguration;

    /**
     * @var array
     */
    protected array $modulesToRefresh = [];

    /**
     * @var array
     */
    protected array $renderedModules = [];

    /**
     * @var integer|null
     */
    protected ?int $status = null;

    /**
     * @var array
     */
    protected array $serializationGroups = [];

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
        ?int $status = Response::HTTP_OK,
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
    public function getModuleResponse(): mixed
    {
        return $this->moduleResponse;
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
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public function getSerializationGroups(): array
    {
        return $this->serializationGroups;
    }
}