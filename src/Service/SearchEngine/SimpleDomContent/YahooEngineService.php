<?php

namespace SearchEngineProvider\Service\SearchEngine\SimpleDomContent;

use SearchEngineProvider\Dto\DOM\DomElementConfigurationDto;
use SearchEngineProvider\Exception\SearchEngine\NoSearchResults;

/**
 * Handles logic related to the Yahoo search engine
 * @link https://search.yahoo.com/
 */
class YahooEngineService extends AbstractEngineService implements EngineServiceInterface, EngineServiceOrOperatorInterface
{

    /**
     * {@inheritDoc}
     */
    protected function getBaseUrl(): string
    {
        return "https://search.yahoo.com/search";
    }

    /**
     * {@inheritDoc}
     */
    protected function getSearchStringQueryParameter(): string
    {
        return "p";
    }

    /**
     * If necessary this might need to be changed to handle parameters dynamically
     *
     * {@inheritDoc}
     */
    protected function getAdditionalQueryParameters(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    protected function getLinkDomExtractionConfiguration(): DomElementConfigurationDto
    {
        $dto = new DomElementConfigurationDto(
            'h3.title a',
            "href",
            false,
            true
        );

        return $dto;
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
     * @return DomElementConfigurationDto
     */
    protected function getResultBlockDomExtractionConfiguration(): DomElementConfigurationDto
    {
        $dto = new DomElementConfigurationDto('ol.searchCenterMiddle>li .algo');
        return $dto;
    }

    /**
     * {@inheritDoc}
     */
    protected function getTitleDomExtractionConfiguration(): DomElementConfigurationDto
    {
        $dto = new DomElementConfigurationDto('h3.title a');
        return $dto;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDescriptionDomExtractionConfiguration(): DomElementConfigurationDto
    {
        $dto = new DomElementConfigurationDto('div.compText p');
        return $dto;
    }

    /**
     * {@inheritDoc}
     */
    protected function getHeaders(): array
    {
        return [];
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
        if (str_contains($pageContent, "We did not find results for")) {
            throw new NoSearchResults(
                self::class,
                $searchedString,
                NoSearchResults::STATUS_ENGINE_RESPONDED_WITH_NO_RESULTS_MESSAGE,
                $calledUrl
            );
        }
    }

}