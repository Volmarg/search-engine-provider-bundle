<?php

namespace SearchEngineProvider\Service\SearchEngine\Other\DuckDuck;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use SearchEngineProvider\Dto\SearchEngine\SearchEngineResultDto;
use SearchEngineProvider\Exception\SearchEngine\NoSearchResults;
use SearchEngineProvider\Service\SearchEngine\ProxySupportedEngine;
use SearchEngineProvider\Service\SearchEngine\SimpleDomContent\DuckDuckHtmlEngineService as DuckDuckHtmlEngineService;
use SearchEngineProvider\Service\SearchEngine\SimpleDomContent\EngineServiceInterface;
use TypeError;
use WebScrapperBundle\Constants\UserAgentConstants;
use WebScrapperBundle\DTO\CrawlerConfigurationDto;
use WebScrapperBundle\Service\CrawlerService;
use WebScrapperBundle\Service\ScrapEngine\RawCurlScrapEngine;
use WebScrapperBundle\Service\ScrapEngine\ScrapEngineInterface;

/**
 * This class is using the {@link https://duckduckgo.com}, there is already class related to this engine:
 * - {@see DuckDuckHtmlEngineService}
 *
 * But turns out that the `html` version is not as accurate as the normal version is, yet the normal html version
 * works pretty much out of box, and stays to be used in some `worst case` scenarios / as fallback
 *
 */
class DuckDuckEngineService extends ProxySupportedEngine implements EngineServiceInterface
{
    public const DEFAULT_USER_AGENT = UserAgentConstants::CHROME_101;

    public const ENGINE_NAME = "DuckDuck - Full";

    public function __construct(
        private readonly CrawlerService                 $crawlerService,
        private readonly DuckDuckEngineTokenExtractor   $duckDuckEngineTokenExtractor,
        private readonly DuckDuckEngineResultsExtractor $duckEngineResultsExtractor,
        private readonly DuckDuckEngineUrlBuilder       $duckDuckEngineUrlBuilder,
        private readonly LoggerInterface                $searchEngineLogger, // name matters as it will use the parent configured logger
        private readonly RawCurlScrapEngine             $rawCurlScrapEngine,
        private readonly string                         $sleepTime
    ){}

    /**
     * @param string $searchedString
     *
     * @return SearchEngineResultDto[]
     *
     * @throws Exception
     */
    public function getFirstPageSearchResultLinks(string $searchedString): array
    {
        try {
            $tokenPageContent = $this->getTokenPageContent($searchedString);
            $vqdToken         = $this->duckDuckEngineTokenExtractor->extractToken($tokenPageContent);

            $resultPageContent = $this->getSearchResultPageContent($searchedString, $vqdToken);
            $searchResult      = $this->duckEngineResultsExtractor->extractSearchResults($resultPageContent, $searchedString, $vqdToken);
        } catch (NoSearchResults $noe) {
            if ($noe->noSearchResultsFoundMessage()) {
                return [];
            }

            $this->logFailedObtainingResults($noe);

            $searchResult = [];
        } catch (Exception|TypeError $e) {
            $this->logFailedObtainingResults($e);

            $searchResult = [];
        } finally {
            // that's on purpose to prevent calling search engine to often
            usleep($this->sleepTime);
        }

        if (count($searchResult) < EngineServiceInterface::MIN_EXPECTED_SEARCH_RESULTS) {
            $this->searchEngineLogger->warning(
                "[" . self::class . "] To few results, expected min: " . self::MIN_EXPECTED_SEARCH_RESULTS
                . " search results, got: " . count($searchResult)
            );
        }

        return $searchResult;
    }

    /**
     * @param string $searchedString
     *
     * @return string
     *
     * @throws Exception
     */
    private function getTokenPageContent(string $searchedString): string
    {
        $calledUrl = $this->duckDuckEngineUrlBuilder->buildCalledUrlForToken($searchedString);

        return $this->crawlWithEngine($calledUrl);
    }

    /**
     * Info1: `{@see RawCurlScrapEngine}` usage - required due to all the engines working with DOM content, and the content
     *          on the search result has no DOM structure, rather it's a JS file content
     *
     * @param string $searchedString
     * @param string $token
     *
     * @return string
     *
     * @throws Exception|GuzzleException
     */
    private function getSearchResultPageContent(string $searchedString, string $token): string
    {
        $calledUrl = $this->duckDuckEngineUrlBuilder->buildCalledUrlForResults($searchedString, $token);

        // It's super important that the "User-Agent" matches to the "Sec-ch-ua" else calls return empty data set
        $config[ScrapEngineInterface::CONFIGURATION_HEADERS] = [
            'User-Agent'                => self::DEFAULT_USER_AGENT,
            "Host"                      => parse_url($calledUrl, PHP_URL_HOST),
            "Accept"                    => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
            "Accept-language"           => "pl-PL,pl;q=0.9,en-US;q=0.8,en;q=0.7,de;q=0.6,fi;q=0.5",
            "Cache-control"             => "no-cache",
            "Pragma"                    => "no-cache",
            "Sec-ch-ua"                 => 'Not A;Brand";v="99", "Chromium";v="101", "Google Chrome";v="101"',
            "Sec-ch-ua-mobile"          => "?0",
            "Sec-ch-ua-platform"        => 'Linux',
            "Sec-fetch-dest"            => "document",
            "Sec-fetch-mode"            => "navigate",
            "Sec-fetch-site"            => "none",
            "Sec-fetch-user"            => "?1",
            "Upgrade-insecure-requests" => "1",
        ];

        $config[ScrapEngineInterface::CONFIGURATION_USER_AGENT] = self::DEFAULT_USER_AGENT;

        return $this->rawCurlScrapEngine->scrap($calledUrl, $config);
    }

    /**
     * @param string $calledUrl
     * @param bool   $returnTextContent
     *
     * @return string
     * @throws Exception
     */
    private function crawlWithEngine(string $calledUrl, bool $returnTextContent = false): string
    {
        $crawlerConfiguration = new CrawlerConfigurationDto($calledUrl, CrawlerService::CRAWLER_ENGINE_GOUTTE);
        $crawlerConfiguration->setUserAgent(self::DEFAULT_USER_AGENT);
        $crawlerConfiguration->setWithProxy($this->isUsingProxy());
        $crawlerConfiguration->setUsedProxyIdentifier($this->getUsedProxyIdentifier());
        $crawlerConfiguration->setProxyUsage($this->getProxyUsage());
        $crawlerConfiguration->setProxyCountryIsoCode($this->getProxyCountryIsoCode());
        $crawlerConfiguration->setHeaders([
            'User-Agent' => self::DEFAULT_USER_AGENT,
            "Accept"     => "*/*",
            "Host"       => parse_url($calledUrl, PHP_URL_HOST),
        ]);

        $crawler = $this->crawlerService->crawl($crawlerConfiguration);

        if ($returnTextContent) {
            return $crawler->innerText();
        }

        return $crawler->html();
    }

    /**
     * @param Exception $e
     */
    private function logFailedObtainingResults(Exception $e): void
    {
        // The message part: "Search engine call failed" is used in mailpit, it can't extract the text from data bag
        $this->searchEngineLogger->warning("Search engine call failed", [
            "note"        => "Search engine call failed", // this string is used on Grafana monitoring!
            "engineClass" => self::class, // this key is used on Grafana monitoring!
            "exception"   => [
                "message" => $e->getMessage(),
                "class"   => $e::class,
                "trace"   => $e->getTraceAsString(),
            ]
        ]);
    }

}