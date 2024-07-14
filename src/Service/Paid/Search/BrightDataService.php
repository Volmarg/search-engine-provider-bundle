<?php

namespace SearchEngineProvider\Service\Paid\Search;

use Exception;
use ProxyProviderBridge\Enum\ProxyUsageEnum;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SearchEngineProvider\Exception\SearchService\EngineNotSupported;
use SearchEngineProvider\Exception\SearchService\NoUsedEngineDefinedException;
use SearchEngineProvider\Service\Env\EnvReader;
use SearchEngineProvider\Service\SearchEngine\SimpleDomContent\GoogleEngineService;

/**
 * This is a special use case service based on:
 * - {@link https://brightdata.com/}
 *
 * Using this service generates expenses, so should be used only as last resort solution,
 * It does not guarantee to work (the company itself cant guarantee it),
 *
 *
 * The {@see BrightDataService::isAcceptUsage()} exists on purpose to force understanding what risks does this
 * service presents
 */
class BrightDataService extends PaidSearchBase
{

    /**
     * @param string      $searchedString
     * @param string|null $targetCountry
     *
     * @return array
     *
     * @throws ContainerExceptionInterface
     * @throws EngineNotSupported
     * @throws NoUsedEngineDefinedException
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getSearchResults(string $searchedString, ?string $targetCountry = null): array
    {
        if (!$this->isAcceptUsage()) {
            $msg = "Bright Data can generate high costs for using their search engine proxy, thus it's required that it's understood / accepted.";
            throw new Exception($msg);
        }

        $cachedResults = $this->engineResultCacheService->load(self::class, $searchedString, $targetCountry);
        if (!empty($cachedResults)) {
            return $cachedResults;
        }

        $this->searchService->setForceAllowEngineFqns([GoogleEngineService::class]);
        $this->searchService->setUsedEnginesFqns([GoogleEngineService::class]);
        $this->searchService->setWithProxy(EnvReader::isProxyEnabled());
        $this->searchService->setProxyUsage(ProxyUsageEnum::SERP->value);
        $this->searchService->setProxyCountryIsoCode($targetCountry);
        $this->searchService->setNextEngine();

        $this->searchEngineLogger->info("[PaidEngineSearch] Searching for string: {$searchedString}", [
            "targetCountry" => $targetCountry,
            "proxyUsage"    => ProxyUsageEnum::SERP->value,
            "usedEngines"   => [GoogleEngineService::class],
            "service"       => self::class,
        ]);

        $results = $this->searchService->getFirstPageSearchResultLinks($searchedString);
        $this->engineResultCacheService->save($results, self::class, $searchedString, $targetCountry);
        $this->searchService->resetEngines();

        return $results;
    }

}