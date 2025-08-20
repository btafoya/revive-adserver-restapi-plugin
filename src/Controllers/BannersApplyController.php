<?php
namespace App\Controllers;

use App\Support\ReviveConfig;
use App\Services\TargetingCompiler;
use App\Services\TargetingValidator;
use PDO;

final class BannersApplyController
{
    public function apply()
    {
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $bannerIds = $in['bannerIds'] ?? [];
        $mode = strtolower((string)($in['mode'] ?? 'replace'));
        if (!in_array($mode, ['replace','merge'], true)) $mode = 'replace';
        if (!is_array($bannerIds) || !$bannerIds) return $this->json(400, ['error'=>'bannerIds required']);

        if (isset($in['ruleSetId'])) {
            $rules = $this->loadRuleSetRules((int)$in['ruleSetId']);
            if ($rules === null) return $this->json(404, ['error'=>'rule set not found']);
        } else {
            $rules = $in['rules'] ?? null;
            if (!is_array($rules)) return $this->json(400, ['error'=>'rules must be array or provide ruleSetId']);
        }

        $validated = TargetingValidator::validate($rules);
        $normalizedNew = $validated['normalized'];
        $newLeaves = $this->flattenLeaves($normalizedNew);

        $pdo = $this->pdo();
        $tblAcls = ReviveConfig::table('acls');
        $tblBanners = ReviveConfig::table('banners');

        $results = [];
        foreach ($bannerIds as $rawBid) {
            $bid = (int)$rawBid;
            if ($bid <= 0) { $results[]=['bannerId'=>$rawBid,'ok'=>false,'error'=>'invalid id']; continue; }
            try {
                $pdo->beginTransaction();

                $combinedLeaves = $newLeaves;
                if ($mode === 'merge') {
                    $existing = $pdo->prepare("SELECT logical, type, comparison, data, executionorder FROM `$tblAcls` WHERE bannerid=? ORDER BY executionorder ASC");
                    $existing->execute([$bid]);
                    $prev = [];
                    foreach ($existing as $row) {
                        $val = $row['data']; $decoded = json_decode($val, true);
                        $prev[] = ['logical'=>strtolower($row['logical'] ?? 'and'),'type'=>(string)$row['type'],'comparison'=>(string)($row['comparison'] ?? '=='),'data'=>$decoded ?? $val];
                    }
                    $combinedLeaves = array_merge($prev, $newLeaves);
                }

                $pdo->prepare("DELETE FROM `$tblAcls` WHERE bannerid=?")->execute([$bid]);
                $ins = $pdo->prepare("INSERT INTO `$tblAcls` (bannerid, logical, type, comparison, data, executionorder) VALUES (:bid,:logical,:type,:cmp,:data,:ord)");
                $ord = 1;
                foreach ($combinedLeaves as $leaf) {
                    $ins->execute([':bid'=>$bid, ':logical'=>strtolower($leaf['logical'] ?? 'and'), ':type'=>(string)$leaf['type'], ':cmp'=>(string)($leaf['comparison'] ?? '=='), ':data'=>is_array($leaf['data'])?json_encode($leaf['data'], JSON_UNESCAPED_SLASHES):(string)($leaf['data'] ?? ''), ':ord'=>$ord++]);
                }

                $compileTree = [[ 'group'=>'all', 'logical'=>'and', 'rules'=>$combinedLeaves ]];
                $compiled = TargetingCompiler::compile($compileTree);
                $pdo->prepare("UPDATE `$tblBanners` SET compiledlimitation=:lim, acls_updated=NOW() WHERE bannerid=:id LIMIT 1")->execute([':lim'=>$compiled, ':id'=>$bid]);

                $pdo->commit();
                $results[] = ['bannerId'=>$bid,'ok'=>true,'mode'=>$mode,'compiledLength'=>strlen($compiled)];
            } catch (\Throwable $e) { $pdo->rollBack(); $results[] = ['bannerId'=>$bid,'ok'=>false,'error'=>$e->getMessage()]; }
        }

        return $this->json(200, ['mode'=>$mode, 'ruleSetUsed'=>($in['ruleSetId'] ?? null), 'warnings'=>$validated['warnings'], 'results'=>$results]);
    }

    private function flattenLeaves(array $nodes): array
    {
        $out = [];
        $this->walk($nodes, function($leaf) use (&$out){ $out[] = $leaf; });
        return $out;
    }
    private function walk(array $nodes, callable $fn): void
    {
        foreach ($nodes as $n) {
            if (isset($n['group'])) $this->walk($n['rules'] ?? [], $fn);
            elseif (isset($n['type'])) $fn($n);
        }
    }

    private function pdo(): PDO
    {
        $c = $GLOBALS['_MAX']['CONF']['database'];
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['name']);
        return new PDO($dsn, $c['username'], $c['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    }
    private function json(int $status, array $payload){ http_response_code($status); header('Content-Type: application/json'); echo json_encode($payload); }

    private function loadRuleSetRules(int $id): ?array
    {
        $pdo = $this->pdo();
        $set = $pdo->prepare("SELECT id FROM mcp_rule_sets WHERE id=?");
        $set->execute([$id]); if (!$set->fetch()) return null;
        $rows = $pdo->prepare("SELECT json_rule, `order` FROM mcp_rule_set_rules WHERE rule_set_id=? ORDER BY `order` ASC, id ASC");
        $rows->execute([$id]); $rules = [];
        foreach ($rows as $r) { $node = json_decode($r['json_rule'], true); if ($node) $rules[] = $node; }
        return $rules;
    }
}
