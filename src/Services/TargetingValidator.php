<?php
namespace App\Services;

final class TargetingValidator
{
    private const SUPPORTED_TYPES = [
        'Site:Variable','Site:Domain','Source:Source','Geo:Country',
        'Client:Browser','Client:Language','Time:HourOfDay','Time:DayOfWeek','Time:HourRange',
    ];
    private const ALLOWED_CMP = ['==','!=','>','<','>=','<='];

    public static function validate(array $input): array
    {
        $warnings = [];
        $seq = 1;
        $normalized = self::normalize($input, $warnings, $seq);

        $aclPreview = [];
        $order = 1;
        self::walk($normalized, function($node) use (&$aclPreview, &$order) {
            if (isset($node['type'])) {
                $aclPreview[] = [
                    'logical'    => strtolower($node['logical'] ?? 'and'),
                    'type'       => (string)$node['type'],
                    'comparison' => (string)($node['comparison'] ?? '=='),
                    'data'       => is_array($node['data']) ? json_encode($node['data'], JSON_UNESCAPED_SLASHES) : (string)($node['data'] ?? ''),
                    'order'      => $order++,
                ];
            }
        });

        $compiled = \App\Services\TargetingCompiler::compile($normalized);

        return [
            'normalized' => $normalized,
            'warnings'   => $warnings,
            'aclPreview' => $aclPreview,
            'compiled'   => $compiled,
        ];
    }

    private static function normalize(array $nodes, array &$warnings, int &$seq): array
    {
        $out = [];
        for ($i=0; $i<count($nodes); $i++) {
            $n = $nodes[$i];

            if (isset($n['group'])) {
                $mode = strtolower((string)($n['group'] ?? 'all'));
                if (!in_array($mode, ['all','any','none'], true)) { $warnings[] = "Unknown group mode at index $i; defaulting to 'all'."; $mode = 'all'; }
                $logical = strtolower((string)($n['logical'] ?? 'and'));
                if (!in_array($logical, ['and','or','not'], true)) { $warnings[] = "Unknown group logical at index $i; defaulting to 'and'."; $logical = 'and'; }
                $children = is_array($n['rules'] ?? null) ? $n['rules'] : [];
                if (!$children) $warnings[] = "Empty group at index $i.";
                $out[] = ['group'=>$mode, 'logical'=>$logical, 'rules'=>self::normalize($children, $warnings, $seq)];
                continue;
            }

            $type = (string)($n['type'] ?? '');
            if ($type === '') { $warnings[] = "Rule missing type at index $i."; continue; }
            if (!in_array($type, self::SUPPORTED_TYPES, true)) $warnings[] = "Unsupported rule type '$type' at index $i; will be commented.";

            $cmp = (string)($n['comparison'] ?? '==');
            if (!in_array($cmp, self::ALLOWED_CMP, true)) { $warnings[] = "Invalid comparison '$cmp' at index $i; defaulting to '=='. "; $cmp = '=='; }

            $logical = strtolower((string)($n['logical'] ?? 'and'));
            if (!in_array($logical, ['and','or','not'], true)) { $warnings[] = "Invalid logical '$logical' at index $i; defaulting to 'and'."; $logical = 'and'; }

            $data = $n['data'] ?? '';

            if ($type === 'Site:Variable' && isset($n['kv']) && is_array($n['kv'])) {
                $pairs = [];
                foreach ($n['kv'] as $k => $v) {
                    $k = trim((string)$k); $v = trim((string)$v);
                    if ($k === '' || $v === '') { $warnings[] = "Site:Variable kv contains empty key/value; skipping."; continue; }
                    $pairs[] = $k . '|' . $v;
                }
                if (!empty($pairs)) $data = isset($n['data']) ? (is_array($n['data']) ? array_merge($n['data'], $pairs) : array_merge([$n['data']], $pairs)) : $pairs;
            }

            if ($type === 'Time:DayOfWeek') {
                $vals = is_array($data) ? $data : [$data];
                $ok = [];
                foreach ($vals as $v) {
                    if (is_numeric($v) && (int)$v >= 0 && (int)$v <= 6) $ok[] = (int)$v;
                    else $warnings[] = "Time:DayOfWeek value '$v' is out of range 0–6.";
                }
                $data = $ok || $vals;
            } elseif ($type === 'Time:HourRange') {
                $from = $data['from'] ?? null; $to = $data['to'] ?? null;
                if (!is_numeric($from) || !is_numeric($to)) {
                    $warnings[] = "Time:HourRange requires numeric 'from' && 'to'.";
                } else {
                    if ((int)$from < 0 || (int)$from > 23) $warnings[] = "HourRange 'from' out of range (0–23).";
                    if ((int)$to   < 0 || (int)$to   > 23) $warnings[] = "HourRange 'to' out of range (0–23).";
                    if ((int)$from > (int)$to) $warnings[] = "HourRange from>to; split into two ranges for overnight windows.";
                }
            }

            $out[] = [
                'logical'    => $logical,
                'type'       => $type,
                'comparison' => $cmp,
                'data'       => $data,
                'order'      => $seq++,
            ];
        }
        return $out;
    }

    private static function walk(array $nodes, callable $fn): void
    {
        foreach ($nodes as $n) {
            if (isset($n['group'])) self::walk($n['rules'] ?? [], $fn);
            elseif (isset($n['type'])) $fn($n);
        }
    }
}
