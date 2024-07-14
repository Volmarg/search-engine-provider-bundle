<?php

namespace SearchEngineProvider\Service\SearchEngine\SimpleDomContent;

use SearchEngineProvider\Dto\DOM\DomElementConfigurationDto;
use SearchEngineProvider\Exception\SearchEngine\NoSearchResults;

/**
 * Info:
 * - data-sncf => this seems to be some kind of information about "what kind of result is this (with image, with list etc.).
 *
 * >WARNING< this one works only with proxy, as with normal calls it bans the calls pretty fast!
 *
 * @link http://google.com/
 */
class GoogleEngineService extends AbstractEngineService implements EngineServiceInterface, EngineServiceOrOperatorInterface
{

    /**
     * {@inheritDoc}
     */
    protected function getBaseUrl(): string
    {
        return "https://www.google.com/search";
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
        return [];
    }

    /**
     * {@inheritDoc}
     */
    protected function getLinkDomExtractionConfiguration(): DomElementConfigurationDto
    {
        $dto = new DomElementConfigurationDto(
            'div a',
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
        $dto = new DomElementConfigurationDto('#search div[data-hveid][data-ved] > div[data-snc]');
        return $dto;
    }

    /**
     * {@inheritDoc}
     */
    protected function getTitleDomExtractionConfiguration(): DomElementConfigurationDto
    {
        $dto = new DomElementConfigurationDto('div a > h3');
        return $dto;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDescriptionDomExtractionConfiguration(): DomElementConfigurationDto
    {
        $dto = new DomElementConfigurationDto('div:nth-of-type(2)[data-sncf="1"]');
        return $dto;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDescriptionExtractionAlternatives(): array
    {
        return [
            new DomElementConfigurationDto('div:nth-of-type(3)[data-snf]'),
            new DomElementConfigurationDto('div:nth-of-type(2)[data-sncf="0,1,2,3"] + div[data-sncf="2"]'),
        ];
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