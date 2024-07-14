<?php

namespace SearchEngineProvider\Service\SearchEngine\SimpleDomContent;

/**
 * Define method for search engines which work only with certain user agents
 */
interface UserAgentDependentInterface
{
    /**
     * Will return array of user agents with which the search engine can be scrapped with
     * @return array
     */
    public function getUserAgents(): array;
}