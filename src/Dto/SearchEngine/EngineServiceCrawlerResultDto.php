<?php

namespace SearchEngineProvider\Dto\SearchEngine;

use SearchEngineProvider\Service\SearchEngine\Crawler\EngineServiceCrawler;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Result of crawling using one of the methods available in {@see EngineServiceCrawler}
 */
class EngineServiceCrawlerResultDto
{
    public function __construct(
        private array   $resultDtos,
        private Crawler $crawler
    ) {

    }

    /**
     * @return SearchEngineResultDto[]
     */
    public function getResultDtos(): array
    {
        return $this->resultDtos;
    }

    /**
     * The crawler that was used to obtain current result set
     * @return Crawler
     */
    public function getCrawler(): Crawler
    {
        return $this->crawler;
    }

    /**
     * @return int
     */
    public function getResultsCount(): int
    {
        return count($this->getResultDtos());
    }

}