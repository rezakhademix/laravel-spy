<?php

namespace Farayaz\LaravelSpy;

use Farayaz\LaravelSpy\Models\HttpLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class LaravelSpy
{
    public static function boot()
    {
        Http::globalMiddleware(static function (callable $handler): callable {
            return static function (RequestInterface $request, array $options) use ($handler) {
                $httpLog = null;
                info($request->getUri());
                if (! Str::contains($request->getUri(), config('spy.exclude_urls'))) {
                    $body = self::parseContent($request->getBody(), $request->getHeaderLine('Content-Type'));
                    $httpLog = HttpLog::create([
                        'url' => self::obfuscate($request->getUri(), config('spy.obfuscates')),
                        'method' => $request->getMethod(),
                        'request_headers' => self::obfuscate($request->getHeaders(), config('spy.obfuscates')),
                        'request_body' => self::obfuscate($body, config('spy.obfuscates')),
                    ]);
                }

                $responsePromise = $handler($request, $options);

                return $responsePromise->then(
                    function (ResponseInterface $response) use ($httpLog) {
                        if ($httpLog) {
                            $httpLog->update([
                                'status' => $response->getStatusCode(),
                                'response_body' => self::parseContent($response->getBody(), $response->getHeaderLine('Content-Type')),
                                'response_headers' => $response->getHeaders(),
                            ]);
                        }

                        return $response;
                    },
                    function (\Exception $exception) use ($httpLog) {
                        if ($httpLog) {
                            $httpLog->update([
                                'status' => 0,
                                'response_body' => self::parseContent($exception->getMessage(), 'text/plain'),
                            ]);
                        }

                        throw $exception;
                    }
                );
            };
        });
    }

    public static function parseContent($content, $contentType)
    {
        $content = (string) $content;
        $data = empty($contnet) ? null : [$content];

        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode($content, true);
        } elseif (strpos($contentType, 'text/xml') !== false) {
            $data = json_decode(json_encode(simplexml_load_string($content), true));
        } elseif (strpos($contentType, 'text/plain') !== false) {
            $data = explode("\n", $content);
        } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($content, $data);
        } elseif (json_decode($content, true) != null) {
            $data = json_decode($content, true);
        }

        return $data;
    }

    public static function obfuscate($data, $search, $replace = '🫣')
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (in_array($key, $search)) {
                    $data[$key] = $replace;
                }
            }
        } elseif (is_string($data)) {
            $data = str_replace($search, $replace, $data);
        } elseif ($data instanceof \GuzzleHttp\Psr7\Uri) {
            parse_str($data->getQuery(), $queryParams);
            $query = urldecode(http_build_query(self::obfuscate($queryParams, $search, $replace)));

            return str_replace($data->getQuery(), $query, (string) $data);
        }

        return $data;
    }
}
