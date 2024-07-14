<?php

namespace SearchEngineProvider\Exception\SearchEngine;

use Exception;
use Throwable;

/**
 * Indicates that something went wrong while trying to scrap data from page
 */
class PageContentScrappingException extends Exception
{
    public function __construct(string $url, Throwable $previous)
    {
        $message = "Something went wrong while trying to obtain search result for page: {$url}. Message: {$previous->getMessage()}";
        parent::__construct($message, $previous->getCode(), $previous);
    }
}