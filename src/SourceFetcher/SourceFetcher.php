<?php declare(strict_types=1);

namespace App\SourceFetcher;

use App\Parser\JsonParserInterface;

class SourceFetcher implements SourceFetcherInterface
{
    public function __construct(private readonly JsonParserInterface $parser)
    {

    }

    public function fetch(): array
    {
        $response = $this->query();

        return $this->parser->parse($response);
    }

    protected function query(): string
    {
        return file_get_contents('https://api.luftdaten.info/static/v2/data.dust.min.json');
    }
}
