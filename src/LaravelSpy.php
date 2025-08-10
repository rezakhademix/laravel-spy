<?php

namespace Farayaz\LaravelSpy;

use Farayaz\LaravelSpy\Models\HttpLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class LaravelSpy
{
    public static function boot(): void
    {
        Http::globalMiddleware(static function (callable $handler): callable {
            return static function (RequestInterface $request, array $options) use ($handler) {
                if (! config('spy.enabled')) {
                    return $handler($request, $options);
                }

                $httpLog = self::shouldLog($request) ? self::handleRequest($request) : null;

                $responsePromise = $handler($request, $options);

                return $responsePromise->then(
                    fn (ResponseInterface $response) => self::handleResponse($response, $httpLog),
                    fn (\Exception $e) => self::handleException($e, $httpLog)
                );
            };
        });
    }

    protected static function shouldLog(RequestInterface $request): bool
    {
        return ! Str::contains((string) $request->getUri(), config('spy.exclude_urls', []));
    }

    protected static function handleRequest(RequestInterface $request): ?HttpLog
    {
        $body = self::parseContent($request->getBody(), $request->getHeaderLine('Content-Type'));

        return HttpLog::create([
            'url' => urldecode(self::obfuscate($request->getUri(), config('spy.obfuscates', []))),
            'method' => $request->getMethod(),
            'request_headers' => self::obfuscate($request->getHeaders(), config('spy.obfuscates', [])),
            'request_body' => self::obfuscate($body, config('spy.obfuscates', [])),
        ]);
    }

    protected static function handleResponse(ResponseInterface $response, ?HttpLog $httpLog): ResponseInterface
    {
        if ($httpLog) {
            $httpLog->update([
                'status' => $response->getStatusCode(),
                'response_body' => self::parseContent($response->getBody(), $response->getHeaderLine('Content-Type')),
                'response_headers' => $response->getHeaders(),
            ]);
        }

        return $response;
    }

    protected static function handleException(\Exception $exception, ?HttpLog $httpLog): void
    {
        if ($httpLog) {
            $httpLog->update([
                'status' => 0,
                'response_body' => $exception->getMessage(),
            ]);
        }

        throw $exception;
    }

    public static function parseContent($content, string $contentType): mixed
    {
        $content = (string) $content;

        if (empty($content)) {
            return null;
        }

        if (str_contains($contentType, 'application/json') || json_decode($content, true) !== null) {
            return json_decode($content, true);
        }

        if (str_contains($contentType, 'text/xml')) {
            return json_decode(json_encode(simplexml_load_string($content)), true);
        }

        if (str_contains($contentType, 'text/plain')) {
            return explode("\n", $content);
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($content, $data);

            return $data;
        }

        return $content;
    }

    public static function obfuscate(mixed $data, array $keys, string $mask = '🫣'): mixed
    {
        if (is_array($data)) {
            foreach ($data as $k => &$v) {
                if (is_array($v)) {
                    $v = self::obfuscate($v, $keys, $mask);
                } elseif (in_array($k, $keys, true)) {
                    $v = $mask;
                }
            }
        } elseif (is_string($data)) {
            $data = str_replace($keys, $mask, $data);
        } elseif ($data instanceof \GuzzleHttp\Psr7\Uri) {
            parse_str($data->getQuery(), $query);

            return $data->withQuery(http_build_query(self::obfuscate($query, $keys, $mask)));
        }

        return $data;
    }
}
