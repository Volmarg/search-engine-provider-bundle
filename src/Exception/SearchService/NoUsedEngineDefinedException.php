<?php

namespace SearchEngineProvider\Exception\SearchService;

use Exception;

class NoUsedEngineDefinedException extends Exception
{
    public function __construct()
    {
        parent::__construct("No used engine was selected. Cannot make the searching.");
    }
}