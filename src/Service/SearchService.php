<?php

namespace SearchEngineProvider\Service;

use Exception;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use SearchEngineProvider\Dto\SearchEngine\SearchEngineResultDto;
use SearchEngineProvider\Exception\SearchService\EngineNotSupported;
use SearchEngineProvider\Exception\SearchService\NoUsedEngineDefinedException;
use SearchEngineProvider\Service\SearchEngine\Other\DuckDuck\DuckDuckEngineService;
use SearchEngineProvider\Service\SearchEngine\SimpleDomContent\AbstractEngineService;
use SearchEngineProvider\Service\SearchEngine\SimpleDomContent\BingEngineService;
use SearchEngineProvider\Service\SearchEngine\SimpleDomContent\DuckDuckHtmlEngineService;
use SearchEngineProvider\Service\SearchEngine\SimpleDomContent\GoogleEngineService;
use SearchEngineProvider\Service\SearchEngine\SimpleDomContent\YahooEngineService;
use SearchEngineProvider\Traits\Proxy\ProxyCountryIsoCodeAwareTrait;
use SearchEngineProvider\Traits\Proxy\ProxyIdentifierAwareTrait;
use SearchEngineProvider\Traits\Proxy\ProxyTargetUsageAwareTrait;
use SearchEngineProvider\Traits\Proxy\WithProxyAwareTrait;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

/**
 * This service aggregates all the search engines
 *
 * Serves as an easy way to define which engines should be used
 */
class SearchService
{
    use ProxyCountryIsoCodeAwareTrait;
    use ProxyIdentifierAwareTrait;
    use ProxyTargetUsageAwareTrait;
    use WithProxyAwareTrait;

    /**
     * Keep in mind that {@see GoogleEngineService} is banned here on purpose!
     */
    public const SIMPLE_DOM_CONTENT_AVAILABLE_ENGINES = [
        BingEngineService::class,
        DuckDuckHtmlEngineService::class,
        YahooEngineService::class,
    ];

    public const OTHER_AVAILABLE_ENGINES = [
        DuckDuckEngineService::class,
    ];

    /**
     * @return string[]
     */
    public function getAllAvailableEngines(): array
    {
        return [
            ...self::SIMPLE_DOM_CONTENT_AVAILABLE_ENGINES,
            ...self::OTHER_AVAILABLE_ENGINES,
        ];
    }

    /**
     * @var bool $isWithProxy
     */
    private bool $isWithProxy = false;

    /**
     * @var string|null $usedProxyIdentifier
     */
    private ?string $usedProxyIdentifier = null;

    /**
     * @var string|null $proxyUsage
     */
    private ?string $proxyUsage = null;

    /**
     * @var string|null $proxyCountryIsoCode
     */
    private ?string $proxyCountryIsoCode = null;

    /**
     * @var ContainerInterface $container
     */
    private ContainerInterface $container;

    /**
     * @var array $usedEnginesFqns
     */
    private $usedEnginesFqns = [];

    /**
     * The engines that were already called in current run
     *
     * @var array $alreadyHandledEnginesFqns
     */
    private array $alreadyHandledEnginesFqns = [];

    /**
     * @var string $currentlyUsedEngineFqn
     */
    private string $currentlyUsedEngineFqn;

    /**
     * That's a special flag to force allowing using some engines, as some engines can be used only
     * under certain circumstances
     *
     * @var array $forceAllowEngineFqns
     */
    private $forceAllowEngineFqns = [];

    /**
     * @var array $excludedFileTypes
     */
    private array $excludedFileTypes = [];

    /**
     * @return array
     */
    public function getForceAllowEngineFqns(): array
    {
        return $this->forceAllowEngineFqns;
    }

    /**
     * @param array $forceAllowEngineFqns
     */
    public function setForceAllowEngineFqns(array $forceAllowEngineFqns): void
    {
        $this->forceAllowEngineFqns = $forceAllowEngineFqns;
    }

    /**
     * Must be FQN, at least one of the {@see SIMPLE_DOM_CONTENT_AVAILABLE_ENGINES}
     *
     * @param array $usedEnginesFqns
     */
    public function setUsedEnginesFqns(array $usedEnginesFqns): void
    {
        $this->usedEnginesFqns = $usedEnginesFqns;
    }

    /**
     * @return array
     */
    public function getUsedEnginesFqns(): array
    {
        return $this->usedEnginesFqns;
    }

    /**
     * @return array
     */
    public function getExcludedFileTypes(): array
    {
        return $this->excludedFileTypes;
    }

    /**
     * @param array $excludedFileTypes
     */
    public function setExcludedFileTypes(array $excludedFileTypes): void
    {
        $this->excludedFileTypes = $excludedFileTypes;
    }

    public function __construct(
        KernelInterface                                  $kernel,
        private LoggerInterface                          $searchEngineLogger, // name matters as it will use the parent configured logger
        private readonly SearchEngineResultFilterService $engineResultFilterService
    ) {
        $this->container = $kernel->getContainer();
    }

    /**
     * Will return array which contains links scrapped from first search result page
     *
     * @param string $searchedString
     *
     * @return SearchEngineResultDto[]
     *
     * @throws EngineNotSupported
     * @throws NoUsedEngineDefinedException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getFirstPageSearchResultLinks(string $searchedString): array
    {
        $this->validateUsedEngines();

        if (!isset($this->currentlyUsedEngineFqn)) {
            throw new Exception("Currently used engine is not set. Call: `firstOrNextEngine` each time You want to change engine");
        }

        $this->searchEngineLogger->info("Searching with engine: {$this->currentlyUsedEngineFqn}", []);

        $results = [];
        try {
            /** @var AbstractEngineService $engine */
            $engine  = $this->container->get($this->currentlyUsedEngineFqn);
            $engine->setWithProxy($this->isUsingProxy());
            $engine->setUsedProxyIdentifier($this->getUsedProxyIdentifier());
            $engine->setProxyCountryIsoCode($this->getProxyCountryIsoCode());
            $engine->setProxyUsage($this->getProxyUsage());
            $results = $engine->getFirstPageSearchResultLinks($searchedString);
        } catch (Exception|TypeError $e) {
            // The message part: "Search engine call failed" is used in mailpit, it can't extract the text from data bag
            $this->searchEngineLogger->warning("Search engine call failed - exception was thrown, will try with next one", [
                "note"        => "Search engine call failed", // this string is used on Grafana monitoring!
                "message"     => $e->getMessage(),
                "engineClass" => $this->currentlyUsedEngineFqn, // this key is used on Grafana monitoring!
                "class"       => $e::class,
                "trace"       => $e->getTrace(),
            ]);
        }

        if (empty($results)) {
            $this->searchEngineLogger->warning("Could not get any search results for string: {$searchedString}, with engine: {$this->currentlyUsedEngineFqn}");
        }

        $this->engineResultFilterService->setExcludedFileTypes($this->getExcludedFileTypes());

        $filteredResults = $this->engineResultFilterService->filter($results);

        return $filteredResults;
    }

    /**
     * @return string
     */
    public function getCurrentlyUsedEngineClass(): string
    {
        $fqnParts = explode("\\", $this->currentlyUsedEngineFqn);
        return array_pop($fqnParts);
    }

    /**
     * Resets the array of already called engines, allows calling them again
     *
     * @return void
     */
    public function resetEngines(): void
    {
        $this->alreadyHandledEnginesFqns = [];
    }

    /**
     * Sets engine that is currently in use,
     * if that's the first call then it takes first engine, else any next one.
     *
     * If no engines to use are left then returns false,
     * else returns true
     *
     * @return bool
     */
    public function setNextEngine(): bool
    {
        if (!$this->hasNextEngine()) {
            return false;
        }

        $foundMatch = null;
        foreach ($this->usedEnginesFqns as $usedEngineFqn) {
            if (in_array($usedEngineFqn, $this->alreadyHandledEnginesFqns)) {
                continue;
            }

            $foundMatch                        = $usedEngineFqn;
            $this->currentlyUsedEngineFqn      = $usedEngineFqn;
            $this->alreadyHandledEnginesFqns[] = $usedEngineFqn;
            break;
        }

        return !is_null($foundMatch);
    }

    /**
     * Check if there is any more engine to handle
     *
     * @return bool
     */
    public function hasNextEngine(): bool
    {
        return !empty(array_diff($this->usedEnginesFqns, $this->alreadyHandledEnginesFqns));
    }

    /**
     * Will validate the used engines,
     * if validation fails then exception is thrown
     *
     * @return void
     *
     * @throws EngineNotSupported
     * @throws NoUsedEngineDefinedException
     */
    private function validateUsedEngines(): void
    {
        if (empty($this->usedEnginesFqns)) {
            throw new NoUsedEngineDefinedException();
        }

        $notMatchingEngines = array_diff($this->getUsedEnginesFqns(), $this->getAllAvailableEngines());
        $notForceAllowedEngines = array_diff($notMatchingEngines, $this->getForceAllowEngineFqns());
        if (!empty($notMatchingEngines) && !empty($notForceAllowedEngines)) {
            throw new EngineNotSupported($this->getUsedEnginesFqns(), $this->getAllAvailableEngines());
        }
    }

}