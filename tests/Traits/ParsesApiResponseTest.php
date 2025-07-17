<?php

namespace Tests\Traits;

use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Kroderdev\LaravelMicroserviceCore\Traits\ParsesApiResponse;
use Orchestra\Testbench\TestCase;

class DummyParser
{
    use ParsesApiResponse;

    public static function parse(mixed $data): array
    {
        return static::parseResponse($data);
    }
}

class ArrayableStub
{
    public function toArray(): array
    {
        return ['foo' => 'bar'];
    }
}

class ParsesApiResponseTest extends TestCase
{
    /** @test */
    public function it_returns_empty_array_for_null()
    {
        $this->assertSame([], DummyParser::parse(null));
    }

    /** @test */
    public function it_parses_http_response()
    {
        $response = new HttpResponse(new \GuzzleHttp\Psr7\Response(200, [], '{"foo":"bar"}'));
        $this->assertSame(['foo' => 'bar'], DummyParser::parse($response));
    }

    /** @test */
    public function it_parses_json_response()
    {
        $response = new JsonResponse(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], DummyParser::parse($response));
    }

    /** @test */
    public function it_parses_array()
    {
        $this->assertSame(['foo' => 'bar'], DummyParser::parse(['foo' => 'bar']));
    }

    /** @test */
    public function it_parses_collection()
    {
        $collection = new Collection(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], DummyParser::parse($collection));
    }

    /** @test */
    public function it_parses_json_string()
    {
        $json = '{"foo":"bar"}';
        $this->assertSame(['foo' => 'bar'], DummyParser::parse($json));
    }

    /** @test */
    public function it_parses_object_with_to_array()
    {
        $object = new ArrayableStub();
        $this->assertSame(['foo' => 'bar'], DummyParser::parse($object));
    }
}
