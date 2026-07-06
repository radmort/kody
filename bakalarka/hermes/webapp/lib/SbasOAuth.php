<?php
/**
 * Modul: OAuth2 klient pre bankové AIS
 *
 * Zostavenie authorize URL, výmena kódu za tokeny, PKCE, Bearer GET/POST
 * volania a parsovanie zoznamov účtov a transakcií (Berlin Group JSON).
 *
 * Hlavné metódy:
 *   buildAuthorizeUrl()       – OAuth2 authorize URL s PKCE
 *   exchangeCode()            – výmena authorization code za access token
 *   refresh()                 – obnovenie tokenu cez refresh_token
 *   fetchAccounts()           – AIS: zoznam účtov
 *   fetchTransactions()       – AIS: transakcie účtu (GET alebo POST)
 *   parseAccountsList()       – parsovanie JSON odpovede účtov
 *   parseBookedTransactions() – parsovanie zaúčtovaných transakcií
 */

class SbasOAuth
{
    public static function redirectUri(): string
    {
        if (HERMES_PUBLIC_URL === '') {
            return '';
        }

        return HERMES_PUBLIC_URL . '/index.php?page=bank_oauth';
    }

    public static function randomUrlSafe(int $bytes = 32): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    public static function pkceChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    public static function buildAuthorizeUrl(SbasConfig $cfg, string $state, ?string $pkceVerifier): string
    {
        $base = $cfg->oauthAuthorizeUrl();
        $parsed = parse_url($base);
        if ($parsed === false || empty($parsed['scheme']) || empty($parsed['host'])) {
            $parsed = null;
        }

        $fromUrl = [];
        if ($parsed !== null && !empty($parsed['query'])) {
            parse_str($parsed['query'], $fromUrl);
        }

        $core = [
            'response_type' => 'code',
            'client_id'     => $cfg->oauthClientId(),
            'redirect_uri'  => self::redirectUri(),
            'scope'         => $cfg->oauthScope(),
            'state'         => $state,
        ];
        if ($cfg->usePkce() && $pkceVerifier !== null) {
            $core['code_challenge'] = self::pkceChallenge($pkceVerifier);
            $core['code_challenge_method'] = 'S256';
        }

        // Zlúčenie bez duplicitných kľúčov: základ z URL (ak bol), potom authorize_params, nakoniec povinné Hermes hodnoty.
        $params = array_merge($fromUrl, $cfg->oauthAuthorizeExtraParams(), $core);

        $qs = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        if ($parsed !== null) {
            $port = isset($parsed['port']) ? ':' . (int)$parsed['port'] : '';
            $path = ($parsed['path'] ?? '') !== '' ? $parsed['path'] : '/';
            $baseUrl = $parsed['scheme'] . '://' . $parsed['host'] . $port . $path;

            return $baseUrl . ($qs !== '' ? '?' . $qs : '');
        }

        $pathOnly = explode('?', $base, 2)[0];

        return $pathOnly . ($qs !== '' ? '?' . $qs : '');
    }

    /** @return array{access_token:string,refresh_token?:string,expires_in?:int} */
    public static function exchangeCode(SbasConfig $cfg, string $code, ?string $pkceVerifier): array
    {
        $fields = [
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'redirect_uri' => self::redirectUri(),
            'client_id'    => $cfg->oauthClientId(),
        ];
        if ($cfg->usePkce() && $pkceVerifier !== null) {
            $fields['code_verifier'] = $pkceVerifier;
        }
        $secret = $cfg->oauthClientSecret();
        if ($secret !== '') {
            $fields['client_secret'] = $secret;
        }
        $res = SbasHttp::postForm($cfg->oauthTokenUrl(), $fields);
        if (!$res['ok'] || !is_array($res['json'])) {
            throw new RuntimeException('Token exchange failed HTTP ' . $res['status'] . ': ' . substr($res['body'], 0, 500));
        }
        $j = $res['json'];
        if (empty($j['access_token'])) {
            throw new RuntimeException('Token response bez access_token.');
        }

        return $j;
    }

    /** @return array{access_token:string,refresh_token?:string,expires_in?:int} */
    public static function refresh(SbasConfig $cfg, string $refreshToken): array
    {
        $fields = [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id'     => $cfg->oauthClientId(),
        ];
        $secret = $cfg->oauthClientSecret();
        if ($secret !== '') {
            $fields['client_secret'] = $secret;
        }
        $res = SbasHttp::postForm($cfg->oauthTokenUrl(), $fields);
        if (!$res['ok'] || !is_array($res['json'])) {
            throw new RuntimeException('Refresh token failed HTTP ' . $res['status'] . ': ' . substr($res['body'], 0, 500));
        }
        $j = $res['json'];
        if (empty($j['access_token'])) {
            throw new RuntimeException('Refresh response bez access_token.');
        }

        return $j;
    }

    /**
     * GET JSON s Bearer tokenom.
     *
     * @return array{ok:bool,status:int,json:?array,body:string}
     */
    public static function bearerGet(string $url, string $accessToken): array
    {
        $r = SbasHttp::request('GET', $url, [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ]);

        return [
            'ok'     => $r['ok'],
            'status' => $r['status'],
            'json'   => $r['json'],
            'body'   => $r['body'],
        ];
    }

    /**
     * POST JSON s Bearer tokenom (Tatra Premium API štýl).
     *
     * @return array{ok:bool,status:int,json:?array,body:string}
     */
    public static function bearerPostJson(string $url, string $accessToken, array $body): array
    {
        $r = SbasHttp::request('POST', $url, [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ], json_encode($body, JSON_UNESCAPED_UNICODE));

        return [
            'ok'     => $r['ok'],
            'status' => $r['status'],
            'json'   => $r['json'],
            'body'   => $r['body'],
        ];
    }

    /**
     * @return list<array{resourceId:string,iban:?string,currency:?string}>
     */
    public static function parseAccountsList(?array $json): array
    {
        if ($json === null) {
            return [];
        }
        $lists = [];
        foreach (['accounts', 'accountList', 'account'] as $k) {
            if (isset($json[$k])) {
                $lists[] = $json[$k];
            }
        }
        $flat = [];
        foreach ($lists as $block) {
            if (!is_array($block)) {
                continue;
            }
            if (isset($block['iban']) || isset($block['resourceId'])) {
                $flat[] = $block;
            } else {
                foreach ($block as $row) {
                    if (is_array($row)) {
                        $flat[] = $row;
                    }
                }
            }
        }
        $out = [];
        foreach ($flat as $row) {
            $rid = $row['resourceId'] ?? $row['resource_id'] ?? $row['id'] ?? null;
            if ($rid === null || $rid === '') {
                continue;
            }
            $iban = $row['iban'] ?? $row['ibanCode'] ?? null;
            if (is_array($iban)) {
                $iban = $iban['iban'] ?? $iban['ibanCode'] ?? null;
            }
            $ccy = $row['currency'] ?? $row['balances'][0]['balanceAmount']['currency'] ?? null;
            if (is_array($ccy)) {
                $ccy = $ccy['currency'] ?? null;
            }
            $out[] = [
                'resourceId' => (string)$rid,
                'iban'       => $iban !== null ? preg_replace('/\s+/', '', (string)$iban) : null,
                'currency'   => $ccy !== null ? strtoupper((string)$ccy) : null,
            ];
        }

        return $out;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function parseBookedTransactions(?array $json): array
    {
        if ($json === null) {
            return [];
        }
        $booked = null;
        if (isset($json['transactions']['booked']) && is_array($json['transactions']['booked'])) {
            $booked = $json['transactions']['booked'];
        } elseif (isset($json['booked']) && is_array($json['booked'])) {
            $booked = $json['booked'];
        } elseif (isset($json['transactionDetails']) && is_array($json['transactionDetails'])) {
            $booked = $json['transactionDetails'];
        }
        if (!is_array($booked)) {
            return [];
        }
        $out = [];
        foreach ($booked as $row) {
            if (is_array($row)) {
                $out[] = $row;
            }
        }

        return $out;
    }

    public static function fetchAccounts(SbasConfig $cfg, string $accessToken): array
    {
        $url = $cfg->aisApiBase() . $cfg->aisAccountsPath();

        return self::bearerGet($url, $accessToken);
    }

    public static function fetchTransactions(
        SbasConfig $cfg,
        string $accessToken,
        string $accountResourceId,
        string $dateFrom,
        string $dateTo
    ): array {
        $tpl = $cfg->aisTransactionsPathTemplate();
        $path = str_replace(
            ['{accountId}', '{accountResourceId}'],
            [rawurlencode($accountResourceId), rawurlencode($accountResourceId)],
            $tpl
        );
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        $q = $cfg->aisTransactionQueryDefaults();
        $q['dateFrom'] = $dateFrom;
        $q['dateTo'] = $dateTo;

        if ($cfg->aisTransactionsMethod() === 'POST') {
            $url = $cfg->aisApiBase() . $path;

            return self::bearerPostJson($url, $accessToken, $q);
        }

        $url = $cfg->aisApiBase() . $path . '?' . http_build_query($q, '', '&', PHP_QUERY_RFC3986);

        return self::bearerGet($url, $accessToken);
    }
}
