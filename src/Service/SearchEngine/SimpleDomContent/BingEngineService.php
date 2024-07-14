<?php

namespace SearchEngineProvider\Service\SearchEngine\SimpleDomContent;

use SearchEngineProvider\Dto\DOM\DomElementConfigurationDto;
use SearchEngineProvider\Exception\SearchEngine\NoSearchResults;
use WebScrapperBundle\Constants\UserAgentConstants;

/**
 * Handles logic related to the Bing search engine
 * @link https://www.bing.com/
 *
 * Building better queries:
 * @link https://seosly.com/bing-search-operators/
 */
class BingEngineService extends AbstractEngineService implements EngineServiceInterface, UserAgentDependentInterface, EngineServiceOrOperatorInterface
{
    private const MAX_RESULTS_PER_PAGE = "count";

    /**
     * {@inheritDoc}
     */
    public function getUserAgents(): array
    {
        return [
            UserAgentConstants::CHROME_101,
            // UserAgentConstants::CHROME_43,
            // UserAgentConstants::FIREFOX_24,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getHeaders(): array
    {
        return [
            "cookie"                     => "MUIDB=26276020566B621D079871B157C16326; SRCHHPGUSR=SRCHLANG=de; SRCHUSR=DOB=20220425; SRCHUID=V=2&GUID=4C1D140C2BFF4B0484988B229AC1FD6E&dmnchg=1; _EDGE_V=1; _EDGE_S=F=1&SID=062C1E644EAB67DD00BA0FF54F016606; SRCHD=AF=NOFORM; MUID=26276020566B621D079871B157C16326; SUID=M; _SS=SID=062C1E644EAB67DD00BA0FF54F016606",
            "accept"                     => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
            "cache-control"              => "no-cache",
            "sec-ch-ua"                  => '" Not A;Brand";v="99", "Chromium";v="101", "Google Chrome";v="101"',  // INFO: very important to keep this in sync with the supported user agents!
            "referer"                    => "https://www.bing.com/",
            "sec-ch-ua-arch"             => 'x86',
            "sec-ch-ua-bitness"          => '64',
            "sec-ch-ua-full-version"     => '101.0.4951.64', // INFO: very important to keep this in sync with the supported user agents!
            "sec-ch-ua-mobile"           => '?0',
            "sec-ch-ua-model"            => '',
            "sec-ch-ua-platform"         => '"Linux"',
            "sec-ch-ua-platform-version" => '"5.13.0"',
            "sec-fetch-dest"             => 'document',
            "sec-fetch-mode"             => 'navigate',
            "sec-fetch-site"             => 'same-origin',
            "sec-fetch-user"             => '?1',
            "upgrade-insecure-requests"  => '1',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getBaseUrl(): string
    {
        return "https://www.bing.com/search";
    }

    /**
     * {@inheritDoc}
     */
    protected function getSearchStringQueryParameter(): string
    {
        return "q";
    }

    /**
     * If necessary this might need to be changed to handle parameters dynamically
     *
     * {@inheritDoc}
     */
    protected function getAdditionalQueryParameters(): array
    {
        return [
            self::MAX_RESULTS_PER_PAGE => 8,
        ];
    }

    /**
     * @param string $pageContent
     * @param string $searchedString
     * @param string $calledUrl
     *
     * @throws NoSearchResults
     */
    protected function checkNoResults(string $pageContent, string $searchedString, string $calledUrl): void
    {
        if (preg_match("#class=[\"']{1}b_no[\"']{1}#", $pageContent)) {
            throw new NoSearchResults(
                self::class,
                $searchedString,
                NoSearchResults::STATUS_ENGINE_RESPONDED_WITH_NO_RESULTS_MESSAGE,
                $calledUrl
            );
        }
    }

    /**
     * {@inheritDoc}
     * @return string|null
     */
    public function getOrOperator(): ?string
    {
        return "OR";
    }

    /**
     * {@inheritDoc}
     */
    protected function getLinkDomExtractionConfiguration(): DomElementConfigurationDto
    {
        $dto = new DomElementConfigurationDto(
            'h2 a',
            "href",
            false,
            true
        );

        return $dto;
    }

    /**
     * @return DomElementConfigurationDto
     */
    protected function getResultBlockDomExtractionConfiguration(): DomElementConfigurationDto
    {
        $dto = new DomElementConfigurationDto('ol#b_results li.b_algo');
        return $dto;
    }

    /**
     * {@inheritDoc}
     */
    protected function getTitleDomExtractionConfiguration(): DomElementConfigurationDto
    {
        $dto = new DomElementConfigurationDto('h2, h2 > a');
        return $dto;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDescriptionDomExtractionConfiguration(): DomElementConfigurationDto
    {
        /**
         * More selectors are needed as sometimes Bing returns results with:
         * - small images attached with different class names
         * - some big box on top with extra tabs in it
         * - some big box on top without the tabs in it
         * etc.
         */
        $dto = new DomElementConfigurationDto('
        .b_caption p, 
        .b_imgcap_altitle p,
        .tab-content > div[data-priority=""],
        .b_caption .b_richcard .b_mText .b_divsec span,
        .b_caption p,
        .b_snippetBigText
        ');
        return $dto;
    }
}