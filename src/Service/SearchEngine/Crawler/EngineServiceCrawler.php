<?php

namespace SearchEngineProvider\Service\SearchEngine\Crawler;

use Exception;
use LogicException;
use Psr\Log\LoggerInterface;
use SearchEngineProvider\Dto\SearchEngine\EngineServiceCrawlerConfigurationDto;
use SearchEngineProvider\Dto\SearchEngine\EngineServiceCrawlerResultDto;
use SearchEngineProvider\Dto\SearchEngine\SearchEngineResultDto;
use Symfony\Component\DomCrawler\Crawler;
use WebScrapperBundle\DTO\CrawlerConfigurationDto;
use WebScrapperBundle\Service\CrawlerEngine\CrawlerEngineServiceInterface;
use WebScrapperBundle\Service\CrawlerService;

/**
 * Contains the logic responsible for doing the actual search and extraction of the results
 */
class EngineServiceCrawler
{
    public function __construct(
        private readonly LoggerInterface $searchEngineLogger, // name matters as it will use the parent configured logger
        private readonly string          $engineResultDirectory
    ){}

    /**
     * Will return the search results by using the {@see CrawlerEngineServiceInterface}
     *
     * @param CrawlerEngineServiceInterface        $crawlerEngine
     * @param EngineServiceCrawlerConfigurationDto $engineServiceCrawlerConfigurationDto
     *
     * @return EngineServiceCrawlerResultDto
     *
     * @throws Exception
     */
    public function crawlWithEngine(
        CrawlerEngineServiceInterface        $crawlerEngine,
        EngineServiceCrawlerConfigurationDto $engineServiceCrawlerConfigurationDto,
    ): EngineServiceCrawlerResultDto {

        // doesn't matter which engine is passed to dto if engine service is called directly with configuration
        $crawlerConfigurationDto = new CrawlerConfigurationDto(
            $engineServiceCrawlerConfigurationDto->getCalledUrl(),
            CrawlerService::CRAWLER_ENGINE_GOUTTE
        );

        $crawlerConfigurationDto->setWithProxy($engineServiceCrawlerConfigurationDto->isUsingProxy());
        $crawlerConfigurationDto->setUsedProxyIdentifier($engineServiceCrawlerConfigurationDto->getUsedProxyIdentifier());
        $crawlerConfigurationDto->setProxyUsage($engineServiceCrawlerConfigurationDto->getProxyUsage());
        $crawlerConfigurationDto->setProxyCountryIsoCode($engineServiceCrawlerConfigurationDto->getProxyCountryIsoCode());
        $crawlerConfigurationDto->setHeaders($engineServiceCrawlerConfigurationDto->getHeaders());
        if (!empty($userAgent)) {
            $crawlerConfigurationDto->setUserAgent($userAgent);
        }

        $crawler = $crawlerEngine->crawl($crawlerConfigurationDto);

        try {
            $results = $this->crawlAndYieldResults($crawler, $engineServiceCrawlerConfigurationDto);
        } catch (Exception $e) {
            $this->logYieldingException($crawler, $engineServiceCrawlerConfigurationDto->getCalledUrl());
            throw $e;
        }

        return new EngineServiceCrawlerResultDto($results, $crawler);
    }

    /**
     * Will:
     * - filter out the results using provided {@see Crawler} and configuration
     *
     * @param Crawler                              $crawler
     * @param EngineServiceCrawlerConfigurationDto $engineServiceCrawlerConfigurationDto
     *
     * @return SearchEngineResultDto[]
     */
    private function crawlAndYieldResults(
        Crawler                              $crawler,
        EngineServiceCrawlerConfigurationDto $engineServiceCrawlerConfigurationDto,
    ): array {
        $filteredNodes = $crawler->filter(
            $engineServiceCrawlerConfigurationDto->getResultBlockDomExtractionConfiguration()->getCssSelector()
        );
        if ($filteredNodes->count() === 0) {
            return [];
        }

        $cssSelctor = $engineServiceCrawlerConfigurationDto->getResultBlockDomExtractionConfiguration()->getCssSelector();
        $resultDtos = [];
        foreach ($crawler->filter($cssSelctor) as $searchResultBlockNode) {
            $dataExtractionCrawler = new Crawler($searchResultBlockNode);
            $linkNode = $dataExtractionCrawler->filter(
                $engineServiceCrawlerConfigurationDto->getLinkDomExtractionConfiguration()->getCssSelector()
            );

            $titleNode = $dataExtractionCrawler->filter(
                $engineServiceCrawlerConfigurationDto->getTitleDomExtractionConfiguration()->getCssSelector()
            );

            $description = $this->extractDescriptionFromNode($dataExtractionCrawler, $engineServiceCrawlerConfigurationDto);

            $link  = $linkNode->text();
            $title = $titleNode->text();
            if ($engineServiceCrawlerConfigurationDto->getLinkDomExtractionConfiguration()->isGetDataFromAttribute()) {
                $link = $linkNode->attr(
                    $engineServiceCrawlerConfigurationDto->getLinkDomExtractionConfiguration()->getTargetAttributeName()
                );

                if (empty($link)) {
                    throw new LogicException("Could not retrieve the attribute value for dto: "
                                             . json_encode($engineServiceCrawlerConfigurationDto->getLinkDomExtractionConfiguration()));
                }
            }

            if (!filter_var($link, FILTER_VALIDATE_URL)) {
                $this->searchEngineLogger->warning("Not a valid link for search results (skipping): {$link}");
                continue;
            }

            $dto = new SearchEngineResultDto();
            $dto->setDescription($description);
            $dto->setEngineUrl($engineServiceCrawlerConfigurationDto->getHost());
            $dto->setTitle($title);
            $dto->setLink($link);
            $dto->setSearchedString($engineServiceCrawlerConfigurationDto->getSearchedString());

            $resultDtos[] = $dto;
        }

        return $resultDtos;
    }

    /**
     * @param Crawler                              $crawler
     * @param EngineServiceCrawlerConfigurationDto $engineServiceCrawlerConfigurationDto
     *
     * @return string
     */
    private function extractDescriptionFromNode(
        Crawler                              $crawler,
        EngineServiceCrawlerConfigurationDto $engineServiceCrawlerConfigurationDto
    ): string
    {
        $description     = null;
        $descriptionNode = $crawler->filter(
            $engineServiceCrawlerConfigurationDto->getDescriptionDomExtractionConfiguration()->getCssSelector()
        );

        if ($descriptionNode->count() !== 0) { // some search result entries don't event have description
            $description = $descriptionNode->text();
        }

        if (!empty($description)) {
            return $description;
        }

        if (empty($engineServiceCrawlerConfigurationDto->getDescriptionExtractionAlternatives())) {
            return '';
        }

        foreach ($engineServiceCrawlerConfigurationDto->getDescriptionExtractionAlternatives() as $config) {
            $descriptionNode = $crawler->filter($config->getCssSelector());
            if ($descriptionNode->count() !== 0) { // some search result entries don't event have description
                $description = $descriptionNode->text();
            }

            if (!empty($description)) {
                return $description;
            }
        }

        return '';
    }

    /**
     * @param Crawler $crawler
     * @param string  $calledUrl
     *
     * @return void
     */
    public function logYieldingException(Crawler $crawler, string $calledUrl): void {
        if (!file_exists($this->engineResultDirectory)) {
            mkdir($this->engineResultDirectory, 777,true);
        }

        $resultFilePath = $this->engineResultDirectory . uniqid() . ".html";
        if (!empty($crawler)) {
            file_put_contents($resultFilePath, $crawler->html());
        }

        $this->searchEngineLogger->emergency("Could not yield engine results with: " . self::class, [
            "lastCalledUrl"      => $calledUrl,
            "fileWithLastResult" => $resultFilePath,
        ]);
    }

}