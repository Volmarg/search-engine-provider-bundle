<?php

namespace SearchEngineProvider\Traits\Proxy;

use Exception;

trait ProxyTargetUsageAwareTrait
{
    /**
     * @return string|null
     *
     * @throws Exception
     */
    public function getProxyUsage(): ?string
    {
        $this->ensureProxyUsagePropExists();
        return $this->proxyUsage;
    }

    /**
     * @param string|null $proxyUsage
     *
     * @throws Exception
     */
    public function setProxyUsage(?string $proxyUsage): void
    {
        $this->ensureProxyUsagePropExists();
        $this->proxyUsage = $proxyUsage;
    }


    /**
     * @throws Exception
     */
    private function ensureProxyUsagePropExists(): void
    {
        if (!property_exists($this, "proxyUsage")) {
            throw new Exception("This object has no property with name: proxyUsage");
        }
    }
}