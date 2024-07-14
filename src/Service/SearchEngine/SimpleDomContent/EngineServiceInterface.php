<?php

namespace SearchEngineProvider\Service\SearchEngine\SimpleDomContent;

/**
 * Either contains common logic for search engine service or data to prevent classes from bloating
 */
interface EngineServiceInterface
{
    // this indicates that something could go wrong with extracting results
    public const MIN_EXPECTED_SEARCH_RESULTS  = 4;

    /**
     * Will return first page search results
     *
     * @param string $searchedString
     *
     * @return array
     */
    public function getFirstPageSearchResultLinks(string $searchedString): array;

    /**
     * @param bool $isWithProxy
     */
    public function setWithProxy(bool $isWithProxy): void;

    /**
     * @return bool
     */
    public function isUsingProxy(): bool;

    /**
     * @return string|null
     */
    public function getUsedProxyIdentifier(): ?string;

    /**
     * @param string|null $usedProxyIdentifier
     */
    public function setUsedProxyIdentifier(?string $usedProxyIdentifier): void;

    /**
     * @return string|null
     */
    public function getProxyUsage(): ?string;

    /**
     * @param string|null $proxyUsage
     */
    public function setProxyUsage(?string $proxyUsage): void;

    /**
     * @return string|null
     */
    public function getProxyCountryIsoCode(): ?string;

    /**
     * @param string|null $proxyCountryIsoCode
     */
    public function setProxyCountryIsoCode(?string $proxyCountryIsoCode): void;

}