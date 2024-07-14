<?php

namespace SearchEngineProvider\Traits\Proxy;

use Exception;

trait ProxyIdentifierAwareTrait
{
    /**
     * @return string|null
     *
     * @throws Exception
     */
    public function getUsedProxyIdentifier(): ?string
    {
        $this->ensureIdentifierPropExists();
        return $this->usedProxyIdentifier;
    }

    /**
     * @param string|null $usedProxyIdentifier
     *
     * @throws Exception
     */
    public function setUsedProxyIdentifier(?string $usedProxyIdentifier): void
    {
        $this->ensureIdentifierPropExists();
        $this->usedProxyIdentifier = $usedProxyIdentifier;
    }

    /**
     * @throws Exception
     */
    private function ensureIdentifierPropExists(): void
    {
        if (!property_exists($this, "usedProxyIdentifier")) {
            throw new Exception("This object has no property with name: usedProxyIdentifier");
        }
    }
}