<?php

namespace SearchEngineProvider\Controller\Debug;

use SearchEngineProvider\Service\SearchEngine\Other\DuckDuck\DuckDuckEngineService;
use SearchEngineProvider\Service\SearchService;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contains logic related to debugging anything in the project
 */
class DebugController
{

    public function __construct(
        private SearchService $searchService
    ){}

    #[Route("test", name: "debug.test")]
    public function testRoute(): never
    {
        $this->searchService->setUsedEnginesFqns([DuckDuckEngineService::class]);

        $this->searchService->setExcludedFileTypes(["pdf"]);
        $results = $this->searchService->getFirstPageSearchResultLinks("hp 2700 printer manual pdf");

        dd($results);
    }

}