<?php

namespace SearchEngineProvider\Dto\DOM;

/**
 * This DTO represents dom element configuration necessary to get the target DOM element and its content
 */
class DomElementConfigurationDto
{
    /**
     * @param string|null $cssSelector
     * @param string|null $targetAttributeName
     * @param bool        $getDataFromInnerText
     * @param bool        $getDataFromAttribute
     */
    public function __construct(
        private ?string $cssSelector = null,
        private ?string $targetAttributeName = null,
        private bool $getDataFromInnerText = true,
        private bool $getDataFromAttribute = false
    ) {
    }

    /**
     * @return string|null
     */
    public function getCssSelector(): ?string
    {
        return $this->cssSelector;
    }

    /**
     * @param string|null $cssSelector
     */
    public function setCssSelector(?string $cssSelector): void
    {
        $this->cssSelector = $cssSelector;
    }

    /**
     * @return string|null
     */
    public function getTargetAttributeName(): ?string
    {
        return $this->targetAttributeName;
    }

    /**
     * @param string|null $targetAttributeName
     */
    public function setTargetAttributeName(?string $targetAttributeName): void
    {
        $this->targetAttributeName = $targetAttributeName;
    }

    /**
     * @return bool
     */
    public function isGetDataFromInnerText(): bool
    {
        return $this->getDataFromInnerText;
    }

    /**
     * @param bool $getDataFromInnerText
     */
    public function setGetDataFromInnerText(bool $getDataFromInnerText): void
    {
        $this->getDataFromInnerText = $getDataFromInnerText;
    }

    /**
     * @return bool
     */
    public function isGetDataFromAttribute(): bool
    {
        return $this->getDataFromAttribute;
    }

    /**
     * @param bool $getDataFromAttribute
     */
    public function setGetDataFromAttribute(bool $getDataFromAttribute): void
    {
        $this->getDataFromAttribute = $getDataFromAttribute;
    }

}