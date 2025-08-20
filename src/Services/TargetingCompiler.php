<?php
namespace App\Services;

final class TargetingCompiler
{
    private const MAP = [
        'Site:Variable'   => ['fn' => 'MAX_checkSite_Variable',  'supportsRange' => false],
        'Site:Domain'     => ['fn' => 'MAX_checkSite_Domain',    'supportsRange' => false],
        'Source:Source'   => ['fn' => 'MAX_checkSource',         'supportsRange' => false],
        'Geo:Country'     => ['fn' => 'MAX_checkGeo_Country',    'supportsRange' => false],
        'Client:Browser'  => ['fn' => 'MAX_checkClient_Browser', 'supportsRange' => false],
        'Client:Language' => ['fn' => 'MAX_checkClient_Language','supportsRange' => false],
        'Time:HourOfDay'  => ['fn' => 'MAX_checkTime_HourOfDay', 'supportsRange' => true ],
        'Time:DayOfWeek'  => ['fn' => 'MAX_checkTime_DayOfWeek', 'supportsRange' => false],
    ];

    public static function compile(array $items): string
    {
        if (!$items) return '';
        $parts = [];
        foreach ($items as $i => $item) {
            $logical = strtolower($item['logical'] ?? ($i ? 'and' : ''));
            $expr = self::compileItem($item);
            if ($expr === '') continue;
            if ($i === 0 || $logical === '' || $logical === 'and') {
                $parts[] = $expr;
            } elseif ($logical === 'or') {
                $parts[] = 'OR ' . $expr;
            } elseif ($logical === 'not') {
                $parts[] = 'AND NOT (' . $expr . ')';
            } else {
                $parts[] = $expr;
            }
        }
        $compiled = '';
        foreach ($parts as $idx => $piece) {
            if ($idx === 0) $compiled .= preg_replace('/^(AND |OR )/','', $piece);
            else $compiled .= ' ' . $piece;
        }
        return trim($compiled);
    }

    private static function compileItem(array $item): string
    {
        if (isset($item['group'])) return self::compileGroup($item);
        return self::compileRule($item);
    }

    private static function compileGroup(array $group): string
    {
        $mode = strtolower($group['group'] ?? 'all'); // all|any|none
        $rules = $group['rules'] ?? [];
        if (!is_array($rules) || !$rules) return '';
        $compiledChildren = [];
        foreach ($rules as $child) {
            $expr = self::compileItem($child);
            if ($expr !== '') $compiledChildren[] = $expr;
        }
        if (!$compiledChildren) return '';
        $glue = ($mode === 'any') ? ' OR ' : ' AND ';
        $inner = '(' . implode($glue, array_map(fn($x)=> self::parenthesize($x), $compiledChildren)) . ')';
        if ($mode === 'none') $inner = 'NOT ' . $inner;
        return $inner;
    }

    private static function compileRule(array $r): string
    {
        $type = (string)($r['type'] ?? '');
        $cmp  = (string)($r['comparison'] ?? '==');
        $data = $r['data'] ?? '';
        if ($type === '') return '';

        if ($type === 'Time:DayOfWeek') {
            $vals = is_array($data) ? $data : [$data];
            $clauses = [];
            foreach ($vals as $v) {
                $clauses[] = "MAX_checkTime_DayOfWeek('" . self::esc((string)$v) . "', '==')";
            }
            return self::parenthesize(implode(' OR ', $clauses));
        }
        if ($type === 'Time:HourRange') {
            $from = (string)($data['from'] ?? '');
            $to   = (string)($data['to']   ?? '');
            if ($from === '' || $to === '') return '';
            $a = "MAX_checkTime_HourOfDay('" . self::esc($from) . "', '>=')";
            $b = "MAX_checkTime_HourOfDay('" . self::esc($to)   . "', '<=')";
            return self::parenthesize("$a AND $b");
        }

        $map = self::MAP[$type] ?? null;
        if (!$map) {
            $d = is_array($data) ? json_encode($data) : (string)$data;
            return "/* unsupported:$type $cmp " . self::q($d) . " */ 1";
        }
        $fn = $map['fn'];

        if (is_array($data)) {
            if (!$data) return '';
            $pieces = [];
            foreach ($data as $val) {
                $pieces[] = "{$fn}(" . self::q((string)$val) . ", " . self::q(self::normalizeCmp($cmp)) . ")";
            }
            return self::parenthesize(implode(' OR ', $pieces));
        }

        return "{$fn}(" . self::q((string)$data) . ", " . self::q(self::normalizeCmp($cmp)) . ")";
    }

    private static function normalizeCmp(string $cmp): string
    {
        $cmp = trim($cmp);
        $allow = ['==','!=','>','<','>=','<='];
        return in_array($cmp, $allow, true) ? $cmp : '==';
    }
    private static function q(string $v): string { return "'" . self::esc($v) . "'"; }
    private static function esc(string $v): string { return str_replace("'", "\'", $v); }
    private static function parenthesize(string $expr): string
    {
        $expr = trim($expr);
        if ($expr == '' ) return '';
        if ($expr[0] == '(' and substr($expr, -1) == ')') return $expr;
        return '(' . $expr . ')';
    }
}
