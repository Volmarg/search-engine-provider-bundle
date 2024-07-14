<?php

namespace SearchEngineProvider\Exception\SearchEngine;

use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Indicates that no search results were returned by the search engine
 */
class NoSearchResults extends Exception
{
    public const STATUS_NO_SEARCH_RESULTS_RETURNED               = 'NO_SEARCH_RESULTS_RETURNED';
    public const STATUS_ENGINE_RESPONDED_WITH_NO_RESULTS_MESSAGE = 'ENGINE_RESPONDED_WITH_NO_RESULTS_MESSAGE';

    private string $status;

    /**
     * @return string
     */
    public function noSearchResultsFoundMessage(): string
    {
        return ($this->status === self::STATUS_ENGINE_RESPONDED_WITH_NO_RESULTS_MESSAGE);
    }

    /**
     * @param string $searchEngineName
     * @param string $searchedString
     * @param string $status
     * @param string $calledUrl
     */
    public function __construct(string $searchEngineName, string $searchedString, string $status = self::STATUS_NO_SEARCH_RESULTS_RETURNED, string $calledUrl = '')
    {
        $this->status = $status;

        $message = match ($status) {
            self::STATUS_NO_SEARCH_RESULTS_RETURNED               => "No results were returned by the engine: `{$searchEngineName}`, for string: `{$searchedString}`",
            self::STATUS_ENGINE_RESPONDED_WITH_NO_RESULTS_MESSAGE => "Engine: `{$searchEngineName}` responded with `no results found`, for string: {$searchedString}",
            default                                               => "The exception `" . self::class . "` was called in wrong context, anyway seems like no results are present for engine `{$searchEngineName}` and string `{$searchedString}`"
        };

        if (!empty($calledUrl)) {
            $message .= " Called url: {$calledUrl}";
        }

        parent::__construct($message, Response::HTTP_NOT_FOUND);
    }
}