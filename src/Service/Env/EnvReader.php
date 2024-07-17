<?php

namespace SearchEngineProvider\Service\Env;

/**
 * Handles reading $_ENV vars
 */
class EnvReader {

    /**
     * Check if proxy should be enabled or not
     *
     * @return bool
     */
    public static function isProxyEnabled(): bool
    {
        if (!isset($_ENV['IS_PROXY_ENABLED'])) {
            return false;
        }

        return ($_ENV['IS_PROXY_ENABLED'] == 'true' || $_ENV['IS_PROXY_ENABLED'] == 1 || $_ENV['IS_PROXY_ENABLED'] === true);
    }
}
