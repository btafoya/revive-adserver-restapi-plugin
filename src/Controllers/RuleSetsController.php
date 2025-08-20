<?php
namespace App\Controllers;

use App\Support\ReviveConfig;
use App\Services\TargetingCompiler;
use PDO;

final class RuleSetsController
{
    public function index()
    {
        $pdo = $this->pdo();
        $sets = $pdo->query("SELECT id, name, description, created_at, updated_at FROM mcp_rule_sets ORDER BY id DESC")->fetchAll();
        $this->json(200, ['items'=>$sets]);
    }
    public function show(array $params)
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) return $this->json(400, ['error'=>'invalid id']);
        $pdo = $this->pdo();
        $set = $pdo->prepare("SELECT id, name, description, created_at, updated_at FROM mcp_rule_sets WHERE id=?");
        $set->execute([$id]);
        $row = $set->fetch();
        if (!$row) return $this->json(404, ['error'=>'not found']);
        $rules = $pdo->prepare("SELECT `order`, json_rule FROM mcp_rule_set_rules WHERE rule_set_id=? ORDER BY `order` ASC, id ASC");
        $rules->execute([$id]);
        $items = [];
        foreach ($rules as $r) { $node = json_decode($r['json_rule'], true); if ($node) $items[] = $node; }
        $this->json(200, ['set'=>$row, 'rules'=>$items]);
    }
    public function create()
    {
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $name = trim((string)($in['name'] ?? '')); $desc = (string)($in['description'] ?? ''); $rules = $in['rules'] ?? [];
        if ($name === '' or !is_array($rules)) return $this->json(400, ['error'=>'name and rules required']);
        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO mcp_rule_sets (name, description) VALUES (?,?)")->execute([$name,$desc]);
            $id = (int)$pdo->lastInsertId();
            $ins = $pdo->prepare("INSERT INTO mcp_rule_set_rules (rule_set_id, `order`, json_rule) VALUES (?,?,?)");
            $order = 1;
            foreach ($rules as $node) $ins->execute([$id, $order++, json_encode($node, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]);
            $pdo->commit();
            $this->json(201, ['id'=>$id, 'name'=>$name]);
        } catch (\Throwable $e) { $pdo->rollBack(); $this->json(500, ['error'=>'failed to create', 'detail'=>$e->getMessage()]); }
    }
    public function update(array $params)
    {
        $id = (int)($params['id'] ?? 0); if ($id <= 0) return $this->json(400, ['error'=>'invalid id']);
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $pdo = $this->pdo(); $pdo->beginTransaction();
        try {
            if (isset($in['name'])) $pdo->prepare("UPDATE mcp_rule_sets SET name=? WHERE id=?")->execute([trim((string)$in['name']),$id]);
            if (isset($in['description'])) $pdo->prepare("UPDATE mcp_rule_sets SET description=? WHERE id=?")->execute([(string)$in['description'],$id]);
            if (isset($in['rules'])) {
                if (!is_array($in['rules'])) throw new \RuntimeException('rules must be array');
                $pdo->prepare("DELETE FROM mcp_rule_set_rules WHERE rule_set_id=?")->execute([$id]);
                $ins = $pdo->prepare("INSERT INTO mcp_rule_set_rules (rule_set_id, `order`, json_rule) VALUES (?,?,?)");
                $order = 1;
                for ($i=0;$i<count($in['rules']);$i++) $ins->execute([$id,$order++,json_encode($in['rules'][$i], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]);
            }
            $pdo->commit(); $this->json(200, ['updated'=>true]);
        } catch (\Throwable $e) { $pdo->rollBack(); $this->json(500, ['error'=>'failed to update', 'detail'=>$e->getMessage()]); }
    }
    public function delete(array $params)
    {
        $id = (int)($params['id'] ?? 0); if ($id <= 0) return $this->json(400, ['error'=>'invalid id']);
        $pdo = $this->pdo(); $pdo->prepare("DELETE FROM mcp_rule_sets WHERE id=?")->execute([$id]);
        $this->json(200, ['deleted'=>true]);
    }

    // preview + export + import + apply (merge/replace) are omitted here because other controllers handle them in the project.

    private function pdo(): PDO
    {
        $c = $GLOBALS['_MAX']['CONF']['database'];
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['name']);
        return new PDO($dsn, $c['username'], $c['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    private function json(int $status, array $payload){ http_response_code($status); header('Content-Type: application/json'); echo json_encode($payload); }
}
