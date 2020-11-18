<?php declare(strict_types=1);

namespace App\Api;

use App\Model\Value;
use GuzzleHttp\Client;
use JMS\Serializer\SerializerInterface;

class ValueApi implements ValueApiInterface
{
    protected Client $client;
    protected SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->client = new Client([
            'base_uri' => 'https://localhost:8000/',
            'verify' => false,
        ]);

        $this->serializer = $serializer;
    }

    public function putValue(Value $value): void
    {
        $this->client->put('/api/value', [
            'body' => $this->serializer->serialize($value, 'json'),
        ]);
    }

    public function putValues(array $valueList): void
    {
        $this->client->put('/api/value', [
            'body' => $this->serializer->serialize($valueList, 'json'),
        ]);
    }
}