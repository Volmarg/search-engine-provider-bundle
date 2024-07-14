<?php

namespace SearchEngineProvider\Service\Url;

use Exception;
use SearchEngineProvider\Enum\Url\SchemeEnum;
use SearchEngineProvider\Service\SearchEngine\SimpleDomContent\EngineServiceInterface;

/**
 * Handles any `url` related logic
 */
class UrlService
{
    /**
     * Return all supported url schemes
     *
     * @return array
     */
    public static function getSchemes(): array
    {
        return [
          SchemeEnum::SCHEME_HTTP->value,
          SchemeEnum::SCHEME_HTTPS->value,
        ];
    }

    /**
     * Attempt to obtain the host from url
     *
     * @param string $url
     *
     * @return string
     */
    public static function getHostFromUrl(string $url): string
    {
        $urlInfo = parse_url($url);
        $host    = $urlInfo['host'];

        return $host;
    }

    /**
     * Will normalize string & make it be usable as part of url
     *
     * @param string $string
     *
     * @return string
     */
    public static function normalizeStringForUrl(string $string): string
    {
        $normalizedString = urlencode($string);

        return $normalizedString;
    }

    /**
     * Will validate if url structure is valid
     *
     * @param string $url
     *
     * @throws Exception
     */
    public static function validateUrlConsistency(string $url): void
    {
        $parsedUrl = parse_url($url);
        $scheme    = $parsedUrl['scheme'];
        if (empty($scheme)) {
            throw new Exception("Scheme is missing in provided url: {$url}");
        }

        if (!in_array($scheme, self::getSchemes())) {
            throw new Exception("Incorrect scheme provided. Got {$scheme}, expected one of: "
                                . json_encode(self::getSchemes()));
        }
    }

}