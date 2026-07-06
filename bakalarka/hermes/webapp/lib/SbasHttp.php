<?php
/**
 * Modul: HTTP klient pre SBAS / PSD2
 *
 * Nízkoúrovňové HTTP volania (GET, POST form, POST JSON) cez stream_context.
 * Používa sa v SbasOAuth na komunikáciu s bankovým API.
 */

class SbasHttp
{
    /**
     * @param array<string,string> $headers
     * @return array{ok:bool, status:int, body:string, json: ?array}
     */
    public static function request(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $hdrLines = [];
        foreach ($headers as $k => $v) {
            $hdrLines[] = $k . ': ' . $v;
        }
        $ctx = [
            'http' => [
                'method'        => $method,
                'header'        => implode("\r\n", $hdrLines),
                'content'       => $body ?? '',
                'timeout'       => 60,
                'ignore_errors' => true,
            ],
        ];
        $stream = @fopen($url, 'rb', false, stream_context_create($ctx));
        if ($stream === false) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'json' => null];
        }
        $meta = stream_get_meta_data($stream);
        $response = stream_get_contents($stream) ?: '';
        fclose($stream);
        $status = 0;
        if (!empty($meta['wrapper_data']) && is_array($meta['wrapper_data'])) {
            foreach ($meta['wrapper_data'] as $line) {
                if (preg_match('#^HTTP/\S+\s+(\d+)#', $line, $m)) {
                    $status = (int)$m[1];
                }
            }
        }
        $json = json_decode($response, true);

        return [
            'ok'     => $status >= 200 && $status < 300,
            'status' => $status,
            'body'   => $response,
            'json'   => is_array($json) ? $json : null,
        ];
    }

    /** application/x-www-form-urlencoded POST */
    public static function postForm(string $url, array $fields, array $extraHeaders = []): array
    {
        $body = http_build_query($fields, '', '&', PHP_QUERY_RFC3986);
        $headers = array_merge([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept'       => 'application/json',
        ], $extraHeaders);

        return self::request('POST', $url, $headers, $body);
    }
}
