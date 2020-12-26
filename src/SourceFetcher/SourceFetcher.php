<?php declare(strict_types=1);

namespace App\SourceFetcher;

use App\Parser\JsonParserInterface;
use GuzzleHttp\Client;

class SourceFetcher implements SourceFetcherInterface
{
    protected Client $client;

    protected JsonParserInterface $parser;

    public function __construct(JsonParserInterface $parser)
    {
        $this->client = new Client();
        $this->parser = $parser;
    }

    public function fetch(): array
    {
        $response = $this->query();

        return $this->parser->parse($response);
    }

    protected function query(): string
    {
        $result = $this->client->get('https://api.luftdaten.info/static/v2/data.dust.min.json');

        return $result->getBody()->getContents();
    }
}
