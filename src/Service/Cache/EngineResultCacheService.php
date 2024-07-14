<?php

namespace SearchEngineProvider\Service\Cache;

use Exception;
use Psr\Cache\InvalidArgumentException;
use SearchEngineProvider\Dto\SearchEngine\SearchEngineResultDto;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Requires given changes in project using this bundle.
 * Adding cache pool in framework.yaml:
 *
 * cache:
 *   pools:
 *     engine_results_pool:
 *       adapters: engine_results_filesystem_cache
 */
class EngineResultCacheService
{
    public function __construct(
        private readonly TagAwareCacheInterface $engineResultsPool
    ) {

    }

    /**
     * Stores the page content, where url is the key
     *
     * @param SearchEngineResultDto[] $results
     * @param string                  $usedClass
     * @param string                  $searchString
     * @param string|null             $targetCountry
     *
     * @throws InvalidArgumentException
     */
    public function save(array $results, string $usedClass, string $searchString, ?string $targetCountry = null): void
    {
        $key = $this->buildCacheKey($usedClass, $searchString, $targetCountry);

        // create a new item by trying to get it from the cache
        $cacheEntry = $this->engineResultsPool->get($key, function(ItemInterface $item) {
            return $item;
        });

        // because if key entry exists, but it's empty then trying to set new key will just return that empty string
        if (!($cacheEntry instanceof CacheItem)) {
            $this->engineResultsPool->delete($key);
            $cacheEntry = $this->engineResultsPool->get($key, function(ItemInterface $item) {
                return $item;
            });
        }

        $json = $this->resultsToJson($results);

        $cacheEntry->set($json);
        $this->engineResultsPool->save($cacheEntry);
    }

    /**
     * Returns the page content for url, or null if nothing is yet stored for that url
     *
     * @param string      $usedClass
     * @param string      $searchString
     * @param string|null $targetCountry
     *
     * @return SearchEngineResultDto[]
     *
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function load(string $usedClass, string $searchString, ?string $targetCountry = null): array
    {
        $key = $this->buildCacheKey($usedClass, $searchString, $targetCountry);
        $cacheEntry = $this->engineResultsPool->get($key, function(ItemInterface $item) {
            return $item;
        });

        if ($cacheEntry instanceof CacheItem) {
            return [];
        }

        $arrayOfDtosData = json_decode($cacheEntry, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $msg = "Could not decode json of search results dto from cache. Json error: " . json_last_error_msg() . ", cache key: {$key}";
            throw new Exception($msg);
        }

        return array_map(
            fn(array $dataArray) => SearchEngineResultDto::fromArray($dataArray),
            $arrayOfDtosData
        );
    }

    /**
     * @param SearchEngineResultDto[] $results
     *
     * @return string
     */
    private function resultsToJson(array $results): string
    {
        return json_encode(array_map(
            fn(SearchEngineResultDto $engineResultDto) => $engineResultDto->toArray(),
            $results
        ));
    }

    /**
     * @param string      $usedClass
     * @param string      $searchString
     * @param string|null $targetCountry
     *
     * @return string
     */
    private function buildCacheKey(string $usedClass, string $searchString, ?string $targetCountry = null): string
    {
        return $usedClass . str_replace(" ", "_", $searchString) . ($targetCountry ?? '');
    }
}
