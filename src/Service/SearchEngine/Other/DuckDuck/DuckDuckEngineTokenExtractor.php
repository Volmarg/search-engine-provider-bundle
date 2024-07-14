<?php

namespace SearchEngineProvider\Service\SearchEngine\Other\DuckDuck;

use Exception;

/**
 * Handles extracting the VQD token from the page source (it's unknown what the token really is).
 * The token is used by the DuckDuck to obtain the search results from the server.
 *
 * Some JS is then inserting the data obtained from the server into the DOM.
 */
class DuckDuckEngineTokenExtractor
{
    private const TOKEN_MATCH_REGEXP = "vqd=['\"]{1}(?<TOKEN>.*)['\"]{1},safe_ddg";

    /**
     * Will attempt to extract the `VQD` token used for fetching the results from the server
     *
     * @throws Exception
     */
    public function extractToken(string $pageContent): string
    {
        preg_match("#" . self::TOKEN_MATCH_REGEXP . "#", $pageContent, $matches);

        if (
                empty($matches)
            ||  !array_key_exists("TOKEN", $matches)
        ) {
            throw new Exception("Could not extract the `vqd` token from DuckDuck page content result");
        }

        $token = $matches['TOKEN'];

        return $token;
    }
}