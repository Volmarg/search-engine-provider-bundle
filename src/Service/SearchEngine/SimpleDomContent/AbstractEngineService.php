<?php

namespace SearchEngineProvider\Service\SearchEngine\SimpleDomContent;

use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SearchEngineProvider\Dto\SearchEngine\EngineServiceCrawlerConfigurationDto;
use SearchEngineProvider\Dto\SearchEngine\EngineServiceCrawlerResultDto;
use SearchEngineProvider\Dto\SearchEngine\SearchEngineResultDto;
use SearchEngineProvider\Exception\SearchEngine\NoSearchResults;
use SearchEngineProvider\Exception\SearchEngine\PageContentScrappingException;
use Exception;
use SearchEngineProvider\Dto\DOM\DomElementConfigurationDto;
use SearchEngineProvider\Service\Cache\EngineResultCacheService;
use SearchEngineProvider\Service\SearchEngine\Crawler\EngineServiceCrawler;
use SearchEngineProvider\Service\SearchEngine\ProxySupportedEngine;
use SearchEngineProvider\Service\Url\UrlService;
use Symfony\Component\DomCrawler\Crawler;
use TypeError;
use WebScrapperBundle\Constants\UserAgentConstants;
use WebScrapperBundle\Service\CrawlerEngine\CrawlerEngineServiceInterface;

/**
 * Provides common logic for search engine services
 */
abstract class AbstractEngineService extends ProxySupportedEngine
{
    public const DEFAULT_USER_AGENT = UserAgentConstants::CHROME_43;

    /**
     * @var int $countFailedUserAgents
     */
    private int $countFailedUserAgents = 0;

    public function __construct(
        private CrawlerEngineServiceInterface     $crawlerEngine,
        private LoggerInterface                   $searchEngineLogger, // name matters as it will use the parent configured logger
        private EngineServiceCrawler              $engineServiceCrawler,
        private readonly string                   $engineResultDirectory,
        private readonly string                   $sleepTime,
        private readonly EngineResultCacheService $engineResultCacheService
    ) {}

    /**
     * Will return CSS DOM selector used to extract the link from result block
     *
     * @return DomElementConfigurationDto
     */
    protected abstract function getLinkDomExtractionConfiguration(): DomElementConfigurationDto;

    /**
     * Will return CSS DOM selector used to extract the result block themselves from scrapped search results
     *
     * @return DomElementConfigurationDto
     */
    protected abstract function getResultBlockDomExtractionConfiguration(): DomElementConfigurationDto;

    /**
     * Will return CSS DOM selector used to extract the title from result block
     *
     * @return DomElementConfigurationDto
     */
    protected abstract function getTitleDomExtractionConfiguration(): DomElementConfigurationDto;

    /**
     * Will return CSS DOM selector used to extract the description from result block
     *
     * @return DomElementConfigurationDto
     */
    protected abstract function getDescriptionDomExtractionConfiguration(): DomElementConfigurationDto;

    /**
     * Same as:
     * - {@see getDescriptionDomExtractionConfiguration}
     *
     * Just returns array of configurations which can be used as alternatives, that's because
     * CSS does not provide any OR selector and "," in selectors is not working this way
     *
     * @return DomElementConfigurationDto[]
     */
    protected function getDescriptionExtractionAlternatives(): array
    {
        return [];
    }

    /**
     * Will return base url containing:
     * - scheme (http/s),
     * - domain
     *
     * @return string
     */
    protected abstract function getBaseUrl(): string;

    /**
     * Will return the query parameter used for searching the string
     *
     * @return string
     */
    protected abstract function getSearchStringQueryParameter(): string;

    /**
     * Will provide any user defined query parameters attached to the url
     *
     * @return array
     */
    protected abstract function getAdditionalQueryParameters(): array;

    /**
     * Provides some extra headers per engine
     *
     * @return array
     */
    protected abstract function getHeaders(): array;

    /**
     * Will normalize / process / decode results as each search engine might:
     * - have some ad based results,
     * - some encoding of urls etc.
     *
     * The abstract class does nothing with it, but child classed might overwrite this to provide some additional handling
     *
     * @param SearchEngineResultDto[] $searchResults
     *
     * @return SearchEngineResultDto[]
     */
    protected function handleResults(array $searchResults): array {
        return $searchResults;
    }

    /**
     * Check if the `no results found` message is returned, or if there are no results at all (engine configuration changed etc.)
     * Needs to be overwritten by the child class if the `no results returned` check should be performed
     *
     * @param string $pageContent
     * @param string $searchedString
     * @param string $calledUrl
     */
    protected function checkNoResults(string $pageContent, string $searchedString, string $calledUrl): void
    {}

    /**
     * Will return array which contains links scrapped from first search result page
     *
     * Info:
     *      User-Agent has to be assigned dynamically, because some engines might work only with specified agent names
     *      Loop is used to "fallback" if there are more agents with which the engine works with and it
     *      allows to point out that some/none of the agents are working anymore which could mean that engine has changed
     *      the way it works or some kind of ban was set
     *
     * @param string $searchedString
     *
     * @return SearchEngineResultDto[]
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getFirstPageSearchResultLinks(string $searchedString): array
    {
        $cachedResults = $this->engineResultCacheService->load(static::class, $searchedString);
        if (!empty($cachedResults)) {
            return $cachedResults;
        }

        $supportedUserAgents = $this->getSupportedUserAgents();
        $calledUrl           = $this->generateUrl($searchedString);
        $host                = UrlService::getHostFromUrl($calledUrl);

        try {
            $this->countFailedUserAgents          = 0;
            $engineServiceCrawlerConfigurationDto = $this->buildEngineServiceCrawlerConfigurationDto($calledUrl, $host, $searchedString);
            $engineServiceCrawlerConfigurationDto->setWithProxy($this->isUsingProxy());
            $engineServiceCrawlerConfigurationDto->setUsedProxyIdentifier($this->getUsedProxyIdentifier());
            $engineServiceCrawlerConfigurationDto->setProxyUsage($this->getProxyUsage());
            $engineServiceCrawlerConfigurationDto->setProxyCountryIsoCode($this->getProxyCountryIsoCode());
            $engineServiceCrawlerConfigurationDto->setDescriptionExtractionAlternatives($this->getDescriptionExtractionAlternatives());

            foreach ($supportedUserAgents as $supportedAgent) {

                try {
                    $engineCrawlResult = $this->crawlForUserAgent($supportedAgent, $engineServiceCrawlerConfigurationDto);
                } catch (NoSearchResults $noe) {

                    // if any user agent returns "no results found" then it's no use to crawl further with other user agents
                    if ($noe->noSearchResultsFoundMessage()) {
                        return [];
                    }

                    throw new PageContentScrappingException($calledUrl, $noe);
                }

                if (!empty($engineCrawlResult->getResultDtos())) {
                    break;
                }
            }

            if(
                    empty($engineCrawlResult->getResultDtos())
                &&  $this->countFailedUserAgents == count($supportedUserAgents)
            ){
                $this->logAllUserAgentsFetchAttemptsFailed($engineCrawlResult->getCrawler(), $supportedUserAgents, $calledUrl);
            }

        } catch (Exception|TypeError $e) {
            throw new PageContentScrappingException($calledUrl, $e);
        } finally {
            // that's on purpose to prevent calling search engine to often
            usleep($this->sleepTime);
        }

        $processedResults = $this->handleResults($engineCrawlResult->getResultDtos());

        $this->engineResultCacheService->save($processedResults, static::class, $searchedString);

        return $processedResults;
    }

    /**
     * @param Crawler|null $lastCrawler
     * @param array        $supportedUserAgents
     * @param string       $calledUrl
     *
     * @return void
     */
    public function logAllUserAgentsFetchAttemptsFailed(?Crawler $lastCrawler, array $supportedUserAgents, string $calledUrl): void {
        if (!file_exists($this->engineResultDirectory)) {
            mkdir($this->engineResultDirectory, 777,true);
        }

        $resultFilePath = $this->engineResultDirectory . uniqid() . ".html";
        if (!empty($lastCrawler)) {
            file_put_contents($resultFilePath, $lastCrawler->html());
        }

        // The message part: "Search engine call failed" is used in mailpit, it can't extract the text from data bag
        $this->searchEngineLogger->warning("Search engine call failed. Tried to obtain data with all supported user-agents but failed", [
            "note"               => "Search engine call failed", // this string is used on Grafana monitoring!
            "info"               => "It's highly possible that engine started banning, or search result structure has changed",
            "allUserAgents"      => $supportedUserAgents,
            "engineClass"        => $this::class, // this key is used on Grafana monitoring!
            "lastCalledUrl"      => $calledUrl,
            "fileWithLastResult" => $resultFilePath,
        ]);
    }

    /**
     * Will generate url which contains searched string
     *
     * @param string $searchedString
     *
     * @return string
     * @throws Exception
     */
    private function generateUrl(string $searchedString): string
    {
        $normalizedSearchedString = UrlService::normalizeStringForUrl($searchedString);
        UrlService::validateUrlConsistency($this->getBaseUrl());

        $callableUrl = $this->getBaseUrl()
                       . "?"
                       . $this->getSearchStringQueryParameter()
                       . "="
                       . $normalizedSearchedString;

        if ($this->hasAdditionalQueryParameters()) {
            $stringifyedParams = http_build_query($this->getAdditionalQueryParameters());
            $callableUrl      .= "&" . $stringifyedParams;
        }

        return $callableUrl;
    }

    /**
     * Will check if any additional query parameters are set or not
     *
     * @return bool
     */
    private function hasAdditionalQueryParameters(): bool
    {
        return !empty($this->getAdditionalQueryParameters());
    }

    /**
     * Will merge default heders with extra one set for the engine itself,
     * If Engine based header exists in default headers array then the engine based one is taken
     *
     * @param string $host
     * @param string $userAgent
     *
     * @return array
     */
    private function buildHeaders(string $host, string $userAgent): array {
        // these headers are required, else most engine services will yield error or empty result set
        $defaultHeaders = [
            'User-Agent' => $userAgent,
            "Accept"     => "*/*",
            "Host"       => $host,
        ];

        $allHeaders = array_merge($defaultHeaders, $this->getHeaders());
        return $allHeaders;
    }

    /**
     * Will build {@see EngineServiceCrawlerConfigurationDto} for {@see EngineServiceCrawler}
     *
     * @param string $calledUrl
     * @param string $host
     * @param string $searchedString
     *
     * @return EngineServiceCrawlerConfigurationDto
     *
     * @throws Exception
     */
    private function buildEngineServiceCrawlerConfigurationDto(string $calledUrl, string $host, string $searchedString): EngineServiceCrawlerConfigurationDto {
        $engineServiceCrawlerConfigurationDto = new EngineServiceCrawlerConfigurationDto(
            $this->getResultBlockDomExtractionConfiguration(),
            $this->getLinkDomExtractionConfiguration(),
            $this->getDescriptionDomExtractionConfiguration(),
            $this->getTitleDomExtractionConfiguration(),
            $calledUrl,
            $host,
            $searchedString,
        );

        $engineServiceCrawlerConfigurationDto->setWithProxy($this->isUsingProxy());
        $engineServiceCrawlerConfigurationDto->setProxyCountryIsoCode($this->getProxyCountryIsoCode());
        $engineServiceCrawlerConfigurationDto->setProxyUsage($this->getProxyUsage());
        $engineServiceCrawlerConfigurationDto->setUsedProxyIdentifier($this->getUsedProxyIdentifier());
        $engineServiceCrawlerConfigurationDto->setDescriptionExtractionAlternatives($this->getDescriptionExtractionAlternatives());

        return $engineServiceCrawlerConfigurationDto;
    }

    /**
     * Will return user agents used for the crawling calls
     *
     * @return array
     */
    private function getSupportedUserAgents(): array {
        $supportedUserAgents = [self::DEFAULT_USER_AGENT];

        if (
                $this instanceof UserAgentDependentInterface
            &&  !empty($this->getUserAgents())
        ) {
            $supportedUserAgents = $this->getUserAgents();
        }

        return $supportedUserAgents;
    }

    /**
     * Handles getting the search results by using the {@see CrawlerEngineServiceInterface}
     *
     * @param EngineServiceCrawlerConfigurationDto $engineServiceCrawlerConfigurationDto
     *
     * @return EngineServiceCrawlerResultDto
     * @throws Exception
     */
    private function getResultsWithCrawlEngine(EngineServiceCrawlerConfigurationDto $engineServiceCrawlerConfigurationDto): EngineServiceCrawlerResultDto
    {
        $engineCrawlResult = $this->engineServiceCrawler->crawlWithEngine(
            $this->crawlerEngine,
            $engineServiceCrawlerConfigurationDto
        );

        if (empty($engineCrawlResult->getResultDtos())) {
            $resultFilePath = $this->engineResultDirectory . uniqid() . ".html";
            file_put_contents($resultFilePath, $engineCrawlResult->getCrawler()->html());

            // The message part: "Search engine call failed" is used in mailpit, it can't extract the text from data bag
            $this->searchEngineLogger->warning("Search engine call failed", [
                "note"              => "Search engine call failed", // this string is used on Grafana monitoring!
                "info"              => "Could not obtain data from search engine by using class",
                "info2"             => "Results dto is empty!",
                "fileWithResult"    => $resultFilePath,
                "usedCrawlerEngine" => $this->crawlerEngine::class,
                "engineClass"       => $this::class, // this key is used on Grafana monitoring!
                "usedUserAgent"     => $engineServiceCrawlerConfigurationDto->getUserAgent(),
                "calledUrl"         => $engineServiceCrawlerConfigurationDto->getCalledUrl(),
            ]);

            $this->countFailedUserAgents++;
        }

        return $engineCrawlResult;
    }

    /**
     * Will crawl the engine for single user agent.
     * Returns the {@see EngineServiceCrawlerResultDto}, or throws the {@see NoSearchResults} if no results are found / returned
     *
     * @param string                               $supportedAgent
     * @param EngineServiceCrawlerConfigurationDto $engineServiceCrawlerConfigurationDto
     *
     * @return EngineServiceCrawlerResultDto
     * @throws Exception
     */
    private function crawlForUserAgent(string $supportedAgent, EngineServiceCrawlerConfigurationDto $engineServiceCrawlerConfigurationDto): EngineServiceCrawlerResultDto
    {
        $headers = $this->buildHeaders($engineServiceCrawlerConfigurationDto->getHost(), $supportedAgent);

        $engineServiceCrawlerConfigurationDto->setHeaders($headers);
        $engineServiceCrawlerConfigurationDto->setUserAgent($supportedAgent);

        $engineCrawlResult = $this->getResultsWithCrawlEngine($engineServiceCrawlerConfigurationDto);

        $this->checkNoResults(
            $engineCrawlResult->getCrawler()->html(),
            $engineServiceCrawlerConfigurationDto->getSearchedString(),
            $engineServiceCrawlerConfigurationDto->getCalledUrl()
        );

        if ($engineCrawlResult->getResultsCount() < EngineServiceInterface::MIN_EXPECTED_SEARCH_RESULTS) {
            $this->searchEngineLogger->warning(
                "[" . self::class . "] To few results, expected min: " . EngineServiceInterface::MIN_EXPECTED_SEARCH_RESULTS
                . " search results, got: " . $engineCrawlResult->getResultsCount()
            );
        }
        return $engineCrawlResult;
    }

}