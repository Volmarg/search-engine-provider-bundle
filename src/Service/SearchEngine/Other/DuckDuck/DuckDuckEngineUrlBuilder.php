<?php

namespace SearchEngineProvider\Service\SearchEngine\Other\DuckDuck;

/**
 * Handles building the urls that will be called / used to extract data from the {@link https://duckduckgo.com/}
 *
 * DuckDuck (non html version) seems to be using some kind of token called (VQD) as way to validate calls,
 * - token seems to be consisting of 2 parts separated by `-`,
 * - each searched string has always the same token,
 * - the token is required to actually obtain the search result,
 * - seems like it's some kind of "CSRF" token,
 *
 * Everything seems to be working like this:
 * - the token is returned from the server and placed in the DOM,
 * - the same token is then used by some js file to fetch the results from the server,
 */
class DuckDuckEngineUrlBuilder
{
    private const QUERY       = "q";
    private const SAFE_SEARCH = "kp"; // 1 / -1 / -2
    private const VQD_TOKEN   = "vqd";

    /**
     * @param string $searchedString
     *
     * @return string
     */
    public function buildCalledUrlForToken(string $searchedString): string
    {
        $tokenQueryParamsString = http_build_query($this->getTokenUrlQueryParams($searchedString), );

        return "{$this->getBaseUrl()}{$this->getTokenUri()}?{$tokenQueryParamsString}";
    }

    /**
     * @param string $searchedString
     * @param string $vqdToken
     *
     * @return string
     */
    public function buildCalledUrlForResults(string $searchedString, string $vqdToken): string
    {
        $tokenQueryParamsString = http_build_query($this->getSearchResultsQueryParams($searchedString, $vqdToken), "", null, PHP_QUERY_RFC3986);

        return "{$this->getLinksBaseUrl()}{$this->getSearchResultsUri()}?{$tokenQueryParamsString}";
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return "https://duckduckgo.com/";
    }

    /**
     * @return string
     */
    public function getLinksBaseUrl(): string
    {
        return 'https://links.duckduckgo.com/';
    }

    /**
     * @return string
     */
    private function getSearchResultsUri(): string
    {
        return "d.js";
    }

    /**
     * @param string $searchedString
     * @param string $vqdToken
     *
     * @return array
     */
    private function getSearchResultsQueryParams(string $searchedString, string $vqdToken): array
    {
        // info: seems that duck is very strict about the params sent & order of them might be important
        return [
            self::QUERY     => $searchedString,
            "l"             => "pl-pl", // ??
            "p"             => "1",     // ??
            "s"             => "0",     // ??
            "a"             => "h_",    // ??
            "dl"            => "pl",    // ??
            "ct"            => "PL",    // ??
            self::VQD_TOKEN => $vqdToken,
            "p_ent"         => "",      // ??
        ];
    }


    private function getTokenUri(): string
    {
        return "";
    }

    /**
     * @param string $searchedString
     *
     * @return string[]
     */
    private function getTokenUrlQueryParams(string $searchedString): array
    {
        return [
            self::QUERY       => $searchedString,
            self::SAFE_SEARCH => -1, // sounds stupid but without this the engine often returns "No results found"
        ];
    }

}