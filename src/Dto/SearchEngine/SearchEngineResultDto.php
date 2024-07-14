<?php

namespace SearchEngineProvider\Dto\SearchEngine;

use Exception;

/**
 * Search engine result
 */
class SearchEngineResultDto
{
    public function __construct(
        private string  $searchedString = "",
        private string  $engineUrl = "",
        private string  $link = "",
        private string  $title = "",
        private ?string $description = null,
    ){}

    /**
     * @return string
     */
    public function getSearchedString(): string
    {
        return $this->searchedString;
    }

    /**
     * @param string $searchedString
     */
    public function setSearchedString(string $searchedString): void
    {
        $this->searchedString = $searchedString;
    }

    /**
     * @return string
     */
    public function getEngineUrl(): string
    {
        return $this->engineUrl;
    }

    /**
     * @param string $engineUrl
     */
    public function setEngineUrl(string $engineUrl): void
    {
        $this->engineUrl = $engineUrl;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $arr = [];
        foreach ($this as $prop => $val) {
            $arr[$prop] = $val;
        }

        return $arr;
    }

    /**
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        $obj = new self();
        foreach ($data as $key => $value) {
            if (!property_exists($obj, $key)) {
                throw new Exception("Object: " . self::class . " has no key named: {$key}");
            }
            $obj->{$key} = $value;
        }

        return $obj;
    }

}