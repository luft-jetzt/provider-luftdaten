<?php declare(strict_types=1);

namespace App\SourceFetcher;

use App\Parser\JsonParserInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SourceFetcher implements SourceFetcherInterface
{
    public function __construct(
        protected JsonParserInterface $parser,
        protected HttpClientInterface $httpClient,
    ) {
    }

    public function fetch(): array
    {
        $response = $this->httpClient->request('GET', 'https://api.luftdaten.info/static/v2/data.dust.min.json');

        return $this->parser->parse($response->getContent());
    }
}
