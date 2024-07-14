<?php

namespace SearchEngineProvider\Exception\SearchService;

use Exception;

class EngineNotSupported extends Exception
{

    public function __construct(array $providedEngines, array $supportedEngines)
    {
        $enginesJson         = json_encode($supportedEngines);
        $providedEnginesJson = json_encode($providedEngines);
        parent::__construct("Expected one of given engines: {$enginesJson}, got: {$providedEnginesJson}");
    }

}