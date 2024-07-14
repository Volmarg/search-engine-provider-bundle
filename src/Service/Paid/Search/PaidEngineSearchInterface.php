<?php

namespace SearchEngineProvider\Service\Paid\Search;

interface PaidEngineSearchInterface
{

    /**
     * @param string      $searchedString
     * @param string|null $targetCountry
     *
     * @return array
     */
    public function getSearchResults(string $searchedString, ?string $targetCountry = null): array;

    /**
     * @return bool
     */
    public function isAcceptUsage(): bool;

    /**
     * @param bool $acceptUsage
     */
    public function setAcceptUsage(bool $acceptUsage): void;

}