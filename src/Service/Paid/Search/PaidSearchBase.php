<?php

namespace SearchEngineProvider\Service\Paid\Search;

use Psr\Log\LoggerInterface;
use SearchEngineProvider\Service\Cache\EngineResultCacheService;
use SearchEngineProvider\Service\SearchService;

abstract class PaidSearchBase implements PaidEngineSearchInterface
{
    public function __construct(
        protected readonly SearchService            $searchService,
        protected readonly LoggerInterface          $searchEngineLogger,
        protected readonly EngineResultCacheService $engineResultCacheService
    ) {
    }

    private bool $acceptUsage = false;

    /**
     * @return bool
     */
    public function isAcceptUsage(): bool
    {
        return $this->acceptUsage;
    }

    /**
     * @param bool $acceptUsage
     */
    public function setAcceptUsage(bool $acceptUsage): void
    {
        $this->acceptUsage = $acceptUsage;
    }
}