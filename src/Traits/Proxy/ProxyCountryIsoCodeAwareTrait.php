<?php

namespace SearchEngineProvider\Traits\Proxy;

use Exception;

trait ProxyCountryIsoCodeAwareTrait
{
    /**
     * @return string|null
     *
     * @throws Exception
     */
    public function getProxyCountryIsoCode(): ?string
    {
        $this->ensurePropExists();
        return $this->proxyCountryIsoCode;
    }

    /**
     * @param string|null $proxyCountryIsoCode
     *
     * @throws Exception
     */
    public function setProxyCountryIsoCode(?string $proxyCountryIsoCode): void
    {
        $this->ensurePropExists();
        $this->proxyCountryIsoCode = $proxyCountryIsoCode;
    }

    /**
     * @throws Exception
     */
    private function ensurePropExists(): void
    {
        if (!property_exists($this, "proxyCountryIsoCode")) {
            throw new Exception("This object has no property with name: proxyCountryIsoCode");
        }
    }
}