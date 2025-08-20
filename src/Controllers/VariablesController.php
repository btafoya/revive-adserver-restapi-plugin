<?php
namespace App\Controllers;

final class VariablesController
{
    public function formatSite()
    {
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $kv = (isset($in['kv']) && is_array($in['kv'])) ? $in['kv'] : [];
        $cmp = (string)($in['comparison'] ?? '==');
        $lgc = strtolower((string)($in['logical'] ?? 'and'));

        $rules = [];
        foreach ($kv as $k => $v) {
            $k = trim((string)$k); $v = trim((string)$v);
            if ($k === '' || $v === '') continue;
            $rules[] = ['type'=>'Site:Variable','comparison'=>$cmp,'data'=>$k.'|'.$v,'logical'=>$lgc];
        }
        return $this->json(200, ['rules'=>$rules]);
    }
    private function json(int $status, array $payload){ http_response_code($status); header('Content-Type: application/json'); echo json_encode($payload); }
}
