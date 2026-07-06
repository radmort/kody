<?php
/**
 * Modul: Katalóg slovenských bánk
 *
 * Načítanie OAuth/AIS URL bánk zo súboru sbas_banks_catalog.json.
 * Zlučovanie konfigurácie katalógu s hlavným sbas.json.
 *
 * Hlavné metódy:
 *   banksForSelect() – zoznam bánk pre select v UI
 *   mergeInto()      – zlúčenie URL katalógu do aktívnej konfigurácie
 */

class SbasBanksCatalog
{
    public static function filePath(): string
    {
        $container = '/etc/hermes/sbas_banks_catalog.json';
        if (is_readable($container)) {
            return $container;
        }

        return dirname(__DIR__, 2) . '/config/sbas_banks_catalog.json';
    }

    /** @return array<string,mixed> */
    public static function raw(): array
    {
        $p = self::filePath();
        if (!is_readable($p)) {
            return ['banks' => []];
        }
        $j = json_decode((string)file_get_contents($p), true);

        return is_array($j) ? $j : ['banks' => []];
    }

    /** @return list<array{id:string,name:string,note_sk?:string,doc?:string}> */
    public static function banksForSelect(): array
    {
        $out = [];
        foreach (self::raw()['banks'] ?? [] as $b) {
            if (!is_array($b) || empty($b['id']) || empty($b['name'])) {
                continue;
            }
            $auth = (string)($b['oauth']['authorize_url'] ?? '');
            if ($auth === '') {
                continue;
            }
            $out[] = [
                'id'       => (string)$b['id'],
                'name'     => (string)$b['name'],
                'note_sk'  => isset($b['note_sk']) ? (string)$b['note_sk'] : '',
                'doc'      => isset($b['doc']) ? (string)$b['doc'] : '',
            ];
        }

        return $out;
    }

    /** @return ?array<string,mixed> */
    public static function byId(string $id): ?array
    {
        $id = trim($id);
        if ($id === '') {
            return null;
        }
        foreach (self::raw()['banks'] ?? [] as $b) {
            if (is_array($b) && (string)($b['id'] ?? '') === $id) {
                return $b;
            }
        }

        return null;
    }

    public static function isValidBankId(string $id): bool
    {
        $b = self::byId($id);

        return $b !== null && (string)($b['oauth']['authorize_url'] ?? '') !== '';
    }

    /**
     * Zlúči oauth a ais z katalógu do základu zo sbas.json (neprepisuje client_id, ak v katalógi nie je).
     *
     * @param array<string,mixed> $base
     * @return array<string,mixed>
     */
    public static function mergeInto(array $base, string $bankId): array
    {
        $bank = self::byId($bankId);
        if ($bank === null) {
            return $base;
        }
        foreach (['oauth', 'ais'] as $section) {
            if (!isset($bank[$section]) || !is_array($bank[$section])) {
                continue;
            }
            if (!isset($base[$section]) || !is_array($base[$section])) {
                $base[$section] = [];
            }
            foreach ($bank[$section] as $k => $v) {
                if ($k === 'transaction_query' && is_array($v)) {
                    $base[$section][$k] = array_merge(
                        is_array($base[$section][$k] ?? null) ? $base[$section][$k] : [],
                        $v
                    );
                    continue;
                }
                if ($k === 'authorize_params' && is_array($v)) {
                    $base[$section][$k] = array_merge(
                        is_array($base[$section][$k] ?? null) ? $base[$section][$k] : [],
                        $v
                    );
                    continue;
                }
                if ($v === '' || $v === null) {
                    continue;
                }
                $base[$section][$k] = $v;
            }
        }

        return $base;
    }
}
