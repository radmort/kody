<?php
/**
 * Konfigurácia AIS / SBAS – OAuth2 + REST cesty v štýle Berlin Group / slovenského SBAS.
 * Reálne URL získate v dokumentácii konkrétnej banky (registrácia TPP / sandbox).
 * Výber banky z katalógu: SbasBanksCatalog + session sbas_catalog_bank_id pri OAuth; provider_label = id banky.
 */

require_once __DIR__ . '/SbasBanksCatalog.php';

class SbasConfig
{
    /** @var array<string,mixed> */
    private array $data;

    private function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function configFilePath(): string
    {
        $env = getenv('HERMES_SBAS_CONFIG');
        if ($env !== false && $env !== '') {
            return $env;
        }
        $local = dirname(__DIR__, 2) . '/config/sbas.json';

        return is_file($local) ? $local : '/etc/hermes/sbas.json';
    }

    /** Základ zo súboru sbas.json (bez katalógu). */
    private static function rawDataFromConfigFile(): array
    {
        $path = self::configFilePath();
        if (!is_readable($path)) {
            return [
                'enabled' => false,
                'oauth'   => [],
                'ais'     => [],
                'mock'    => ['enabled' => false],
            ];
        }
        $raw = file_get_contents($path);
        $j   = json_decode($raw ?: '{}', true);

        return is_array($j) ? $j : [];
    }

    /**
     * Aktívna konfigurácia. Ak je v session sbas_catalog_bank_id (okrem „file“), zlúči URL z katalógu bánk.
     */
    public static function load(): self
    {
        $data = self::rawDataFromConfigFile();
        $cid  = $_SESSION['sbas_catalog_bank_id'] ?? null;
        if (is_string($cid) && $cid !== '' && $cid !== 'file' && SbasBanksCatalog::isValidBankId($cid)) {
            $data = SbasBanksCatalog::mergeInto($data, $cid);
        }

        return new self($data);
    }

    /**
     * Konfigurácia pre sync podľa uloženého bank_connections.provider_label (po OAuth).
     */
    public static function loadForBankConnection(string $providerLabel): self
    {
        $data = self::rawDataFromConfigFile();
        if ($providerLabel !== '' && SbasBanksCatalog::isValidBankId($providerLabel)) {
            $data = SbasBanksCatalog::mergeInto($data, $providerLabel);
        }

        return new self($data);
    }

    public function enabled(): bool
    {
        return !empty($this->data['enabled']);
    }

    public function mockEnabled(): bool
    {
        $m = $this->data['mock'] ?? [];

        return !empty($m['enabled']) || (getenv('SBAS_USE_MOCK') === '1');
    }

    /** @return array<string,mixed> */
    public function mockTransactions(): array
    {
        $m = $this->data['mock'] ?? [];

        return is_array($m['transactions'] ?? null) ? $m['transactions'] : [];
    }

    public function oauthAuthorizeUrl(): string
    {
        return (string)($this->data['oauth']['authorize_url'] ?? '');
    }

    public function oauthTokenUrl(): string
    {
        return (string)($this->data['oauth']['token_url'] ?? '');
    }

    public function oauthClientId(): string
    {
        return (string)($this->data['oauth']['client_id'] ?? '');
    }

    public function oauthClientSecret(): string
    {
        $env = getenv('SBAS_CLIENT_SECRET');

        return ($env !== false && $env !== '')
            ? $env
            : (string)($this->data['oauth']['client_secret'] ?? '');
    }

    public function oauthScope(): string
    {
        $s = (string)($this->data['oauth']['scope'] ?? '');

        return $s !== '' ? $s : 'AIS';
    }

    /**
     * Voliteľné query parametre pre authorize URL (napr. lang=sk pre Tatra banku).
     *
     * @return array<string,string>
     */
    public function oauthAuthorizeExtraParams(): array
    {
        $e = $this->data['oauth']['authorize_params'] ?? [];
        if (!is_array($e)) {
            return [];
        }
        $out = [];
        foreach ($e as $k => $v) {
            if (!is_string($k) || $k === '') {
                continue;
            }
            $out[$k] = is_scalar($v) ? (string)$v : '';
        }

        return $out;
    }

    public function usePkce(): bool
    {
        $oauth = $this->data['oauth'] ?? [];
        if (!array_key_exists('use_pkce', $oauth)) {
            return true;
        }

        return filter_var($oauth['use_pkce'], FILTER_VALIDATE_BOOLEAN);
    }

    public function aisApiBase(): string
    {
        return rtrim((string)($this->data['ais']['api_base'] ?? ''), '/');
    }

    public function aisAccountsPath(): string
    {
        $p = (string)($this->data['ais']['accounts_path'] ?? '/accounts');

        return str_starts_with($p, '/') ? $p : '/' . $p;
    }

    public function aisTransactionsPathTemplate(): string
    {
        return (string)($this->data['ais']['transactions_path_template'] ?? '/accounts/{accountId}/transactions');
    }

    /** @return array<string,string> */
    public function aisTransactionQueryDefaults(): array
    {
        $q = $this->data['ais']['transaction_query'] ?? [];
        if (!is_array($q)) {
            return ['bookingStatus' => 'booked'];
        }
        $out = [];
        foreach ($q as $k => $v) {
            $out[(string)$k] = (string)$v;
        }
        if (!isset($out['bookingStatus'])) {
            $out['bookingStatus'] = 'booked';
        }

        return $out;
    }

    /**
     * HTTP metóda pre endpoint transakcií: GET (Berlin Group štandard) alebo POST (Tatra Premium API).
     */
    public function aisTransactionsMethod(): string
    {
        $m = strtoupper(trim((string)($this->data['ais']['transactions_method'] ?? 'GET')));

        return $m === 'POST' ? 'POST' : 'GET';
    }

    public function providerLabel(): string
    {
        $l = trim((string)($this->data['provider_label'] ?? ''));

        return $l !== '' ? $l : 'default';
    }
}
