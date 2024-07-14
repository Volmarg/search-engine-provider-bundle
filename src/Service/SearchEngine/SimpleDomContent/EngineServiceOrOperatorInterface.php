<?php

namespace SearchEngineProvider\Service\SearchEngine\SimpleDomContent;

interface EngineServiceOrOperatorInterface
{
    /**
     * Will return operator used as "OR" for searching for multiple keywords at once
     * If null is returned then no such operator is being used.
     * @return string|null
     */
    public function getOrOperator(): ?string;

}