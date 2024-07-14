<?php

namespace SearchEngineProvider\Constants;

class RegexpConstants
{
    /**
     * Not perfect, will also catch emails, must be excluded later on
     * Might also be catching new lines, tabs, etc.
     * Based on:
     * - {@link https://stackoverflow.com/questions/6038061/regular-expression-to-find-urls-within-a-string}
     */
    public const REGEX_MATCH_URL_IN_STRING = '(?<URL>((http|https):\/\/)?([\w_-]+(?:(?:\.[\w_-]+)+))([\w.,@?^=%&:\/~+#-]*[\w@?^=%&\/~+#-]))';

}