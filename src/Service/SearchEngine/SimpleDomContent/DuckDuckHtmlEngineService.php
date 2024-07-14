<?php

namespace SearchEngineProvider\Service\SearchEngine\SimpleDomContent;

use SearchEngineProvider\Dto\DOM\DomElementConfigurationDto;
use SearchEngineProvider\Dto\SearchEngine\SearchEngineResultDto;
use SearchEngineProvider\Exception\SearchEngine\NoSearchResults;

/**
 * Handles logic related to the Bing search engine
 * @link https://html.duckduckgo.com/html/
 */
class DuckDuckHtmlEngineService extends AbstractEngineService implements EngineServiceInterface, UserAgentDependentInterface, EngineServiceOrOperatorInterface
{
    private const OFFSET      = "dc"; // value must be multiplication of 30, 0 is equal to 1st page
    private const SAFE_SEARCH = "kp"; // 1 / -1 / -2

    /**
     * {@inheritDoc}
     */
    public function getUserAgents(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    protected function getHeaders(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    protected function getBaseUrl(): string
    {
        return "https://html.duckduckgo.com/html";
    }

    /**
     * {@inheritDoc}
     */
    protected function getSearchStringQueryParameter(): string
    {
        return "q";
    }

    /**
     * If necessary this might need to be changed to handle parameters dynamically
     *
     * {@inheritDoc}
     */
    protected function getAdditionalQueryParameters(): array
    {
        return [
            self::OFFSET      => 0,
            self::SAFE_SEARCH => -1, // sounds stupid but without this the engine often returns "No results found"
        ];
    }

    /**
     * {@inheritDoc}
     * @return string|null
     */
    public function getOrOperator(): ?string
    {
        return "OR";
    }

    /**
     * {@inheritDoc}
     */
    protected function getLinkDomExtractionConfiguration(): DomElementConfigurationDto
    {
        $dto = new DomElementConfigurationDto(
            'h2.result__title a',
            "href",
            false,
            true
        );

        return $dto;
    }

    /**
     * @return DomElementConfigurationDto
     */
    protected function getResultBlockDomExtractionConfiguration(): DomElementConfigurationDto
    {
        $dto = new DomElementConfigurationDto('#links .results_links>.links_deep');
        return $dto;
    }

    /**
     * {@inheritDoc}
     */
    protected function getTitleDomExtractionConfiguration(): DomElementConfigurationDto
    {
        $dto = new DomElementConfigurationDto('.result__title');
        return $dto;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDescriptionDomExtractionConfiguration(): DomElementConfigurationDto
    {
        /**
         * More selectors are needed as sometimes Bing returns results with:
         * - small images attached with different class names
         * - some big box on top with extra tabs in it
         * - some big box on top without the tabs in it
         * etc.
         */
        $dto = new DomElementConfigurationDto('
        .result__snippet
        ');
        return $dto;
    }

    /**
     * {@inheritDoc}
     */
    protected function handleResults(array $searchResults): array
    {
        $filteredResults   = $this->filterOutResults($searchResults);
        $normalizedResults = [];
        foreach ($filteredResults as $searchResult) {
            $clonedSearchResult = clone $searchResult;
            $clonedSearchResult->setLink($this->normalizeLink($searchResult->getLink()));
            $normalizedResults[] = $clonedSearchResult;
        }

        return $normalizedResults;
    }

    /**
     * @param string $pageContent
     * @param string $searchedString
     * @param string $calledUrl
     *
     * @throws NoSearchResults
     */
    protected function checkNoResults(string $pageContent, string $searchedString, string $calledUrl): void
    {
        if (preg_match("#class=[\"']{1}no-results[\"']{1}#", $pageContent)) {
            throw new NoSearchResults(
                self::class,
                $searchedString,
                NoSearchResults::STATUS_ENGINE_RESPONDED_WITH_NO_RESULTS_MESSAGE,
                $calledUrl
            );
        }
    }

    /**
     * DuckDuck adds some weird parameters to the links, even if the links are just fine in the page source
     * it still probably somehow detects the crawling and changes the urls.
     *
     * Urls must be normalized, else are not fully usable for crawling etc.
     *
     * @param string $link
     *
     * @return string
     */
    private function normalizeLink(string $link): string
    {
        $normalizedLink = str_replace("//duckduckgo.com/l/?uddg=", "", $link);
        $normalizedLink = preg_replace("#&rut.*|%26rut.*#", "", $normalizedLink);
        $normalizedLink = urldecode($normalizedLink);

        return $normalizedLink;
    }

    /**
     * Takes the array of {@see SearchEngineResultDto}
     * Removes unwanted search results
     *
     * Returns the array of {@see SearchEngineResultDto} but without filtered results
     *
     * @param SearchEngineResultDto[] $searchResults
     *
     * @return SearchEngineResultDto[]
     */
    private function filterOutResults(array $searchResults): array
    {
        return array_filter(
            $searchResults,
            fn(SearchEngineResultDto $resultDto) => !str_contains($resultDto->getLink(), "ad_provider")
        );
    }
}