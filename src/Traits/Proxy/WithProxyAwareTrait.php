<?php

namespace SearchEngineProvider\Traits\Proxy;

use Exception;

trait WithProxyAwareTrait
{
    /**
     * @param bool $isWithProxy
     *
     * @throws Exception
     */
    public function setWithProxy(bool $isWithProxy): void
    {
        $this->ensureIsWithProxyPropExists();
        $this->isWithProxy = $isWithProxy;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isUsingProxy(): bool
    {
        $this->ensureIsWithProxyPropExists();
        return $this->isWithProxy;
    }

    /**
     * @throws Exception
     */
    private function ensureIsWithProxyPropExists(): void
    {
        if (!property_exists($this, "isWithProxy")) {
            throw new Exception("This object has no property with name: isWithProxy");
        }
    }
}