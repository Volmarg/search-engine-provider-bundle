<?php

namespace SearchEngineProvider\Service;

use Psr\Log\LoggerInterface;
use SearchEngineProvider\Dto\SearchEngine\SearchEngineResultDto;

/**
 * Handles filtering out the search engine results.
 */
class SearchEngineResultFilterService
{
    /**
     * @var array $excludedFileTypes
     */
    private array $excludedFileTypes = [];

    public function __construct(
        private readonly LoggerInterface $logger
    ){}

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

    /**
     * Filter out the search results
     *
     * @param SearchEngineResultDto[] $searchResults
     *
     * @return SearchEngineResultDto[]
     */
    public function filter(array $searchResults): array
    {
        $filteredByExtensions = $this->filterExtensions($searchResults);

        return $filteredByExtensions;
    }

    /**
     * @param SearchEngineResultDto[] $searchResults
     *
     * @return array
     */
    private function filterExtensions(array $searchResults): array
    {
        if (empty($this->getExcludedFileTypes())) {
            return $searchResults;
        }

        $filteredResults = [];
        $removalCount    = 0;
        foreach ($searchResults as $result) {
            $extension = pathinfo($result->getLink(), PATHINFO_EXTENSION);
            if (in_array($extension, $this->getExcludedFileTypes())) {
                $removalCount++;
                continue;
            }

            $filteredResults[] = $result;
        }

        if ($removalCount === 0) {
            return $filteredResults;
        }

        $this->logger->info("Some search engine results were filtered out due to extension filter", [
            "originalResultCount" => count($searchResults),
            "countAfterFiltering" => count($filteredResults),
        ]);

        return $filteredResults;
    }
}