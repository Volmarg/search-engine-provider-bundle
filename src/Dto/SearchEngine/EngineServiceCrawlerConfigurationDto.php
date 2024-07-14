<?php

namespace SearchEngineProvider\Dto\SearchEngine;

use SearchEngineProvider\Dto\DOM\DomElementConfigurationDto;
use SearchEngineProvider\Service\SearchEngine\Crawler\EngineServiceCrawler;
use SearchEngineProvider\Traits\Proxy\ProxyCountryIsoCodeAwareTrait;
use SearchEngineProvider\Traits\Proxy\ProxyIdentifierAwareTrait;
use SearchEngineProvider\Traits\Proxy\ProxyTargetUsageAwareTrait;
use SearchEngineProvider\Traits\Proxy\WithProxyAwareTrait;

/**
 * Configuration dto for result yielding method - due to necessity of passing to many params to the {@see EngineServiceCrawler} methods
 */
class EngineServiceCrawlerConfigurationDto
{
    use ProxyCountryIsoCodeAwareTrait;
    use ProxyIdentifierAwareTrait;
    use ProxyTargetUsageAwareTrait;
    use WithProxyAwareTrait;

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
     * @var array $descriptionExtractionAlternatives
     */
    private array $descriptionExtractionAlternatives = [];

    public function __construct(
        private DomElementConfigurationDto $resultBlockDomExtractionConfiguration,
        private DomElementConfigurationDto $linkDomExtractionConfiguration,
        private DomElementConfigurationDto $descriptionDomExtractionConfiguration,
        private DomElementConfigurationDto $titleDomExtractionConfiguration,
        private string                     $calledUrl,
        private string                     $host,
        private string                     $searchedString,
        private array                      $headers = [],
        private ?string                    $userAgent = null
    ) {

    }

    /**
     * @return DomElementConfigurationDto
     */
    public function getResultBlockDomExtractionConfiguration(): DomElementConfigurationDto
    {
        return $this->resultBlockDomExtractionConfiguration;
    }

    /**
     * @return DomElementConfigurationDto
     */
    public function getLinkDomExtractionConfiguration(): DomElementConfigurationDto
    {
        return $this->linkDomExtractionConfiguration;
    }

    /**
     * @return DomElementConfigurationDto
     */
    public function getDescriptionDomExtractionConfiguration(): DomElementConfigurationDto
    {
        return $this->descriptionDomExtractionConfiguration;
    }

    /**
     * @return DomElementConfigurationDto
     */
    public function getTitleDomExtractionConfiguration(): DomElementConfigurationDto
    {
        return $this->titleDomExtractionConfiguration;
    }

    /**
     * @return string
     */
    public function getCalledUrl(): string
    {
        return $this->calledUrl;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return string|null
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * @param string|null $userAgent
     */
    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getSearchedString(): string
    {
        return $this->searchedString;
    }

    /**
     * @return DomElementConfigurationDto[]
     */
    public function getDescriptionExtractionAlternatives(): array
    {
        return $this->descriptionExtractionAlternatives;
    }

    /**
     * @param DomElementConfigurationDto[] $descriptionExtractionAlternatives
     */
    public function setDescriptionExtractionAlternatives(array $descriptionExtractionAlternatives): void
    {
        $this->descriptionExtractionAlternatives = $descriptionExtractionAlternatives;
    }

}