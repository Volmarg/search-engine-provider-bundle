<?php

namespace SearchEngineProvider\Traits;

trait ExcludedFileTypesTrait
{
    /**
     * @var array $excludedFileTypes
     */
    private array $excludedFileTypes = [];

    /**
     * @return array
     */
    public function getExcludedFileTypes(): array
    {
        return $this->excludedFileTypes;
    }

    /**
     * @param array $excludedFileTypes
     */
    public function setExcludedFileTypes(array $excludedFileTypes): void
    {
        $this->excludedFileTypes = $excludedFileTypes;
    }

}