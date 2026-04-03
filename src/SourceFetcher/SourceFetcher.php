<?php

declare(strict_types=1);

namespace App\SourceFetcher;

use App\Parser\JsonParserInterface;
use Caldera\LuftModel\Model\Value;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SourceFetcher implements SourceFetcherInterface
{
    public function __construct(
        protected JsonParserInterface $parser,
        protected HttpClientInterface $httpClient,
        #[Autowire('%env(LUFTDATEN_API_URL)%')]
        protected string $apiUrl,
    ) {
    }

    /** @return Value[] */
    public function fetch(): array
    {
        $response = $this->httpClient->request('GET', $this->apiUrl);

        return $this->parser->parse($response->getContent());
    }
}
