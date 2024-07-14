<?php

namespace SearchEngineProvider\Service\SearchEngine;

use SearchEngineProvider\Service\SearchEngine\SimpleDomContent\EngineServiceInterface;
use SearchEngineProvider\Traits\Proxy\ProxyCountryIsoCodeAwareTrait;
use SearchEngineProvider\Traits\Proxy\ProxyIdentifierAwareTrait;
use SearchEngineProvider\Traits\Proxy\ProxyTargetUsageAwareTrait;
use SearchEngineProvider\Traits\Proxy\WithProxyAwareTrait;

abstract class ProxySupportedEngine implements EngineServiceInterface
{
    use ProxyCountryIsoCodeAwareTrait;
    use ProxyIdentifierAwareTrait;
    use ProxyTargetUsageAwareTrait;
    use WithProxyAwareTrait;

    /**
     * @var bool $isWithProxy
     */
    private bool $isWithProxy = false;

    /**
     * @var string|null $usedProxyIdentifier
     */
    private ?string $usedProxyIdentifier = null;

    /**
     * @var string|null $proxyUsage
     */
    private ?string $proxyUsage = null;

    /**
     * @var string|null $proxyCountryIsoCode
     */
    private ?string $proxyCountryIsoCode = null;

}