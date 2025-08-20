<?php
namespace App\Controllers;

final class TargetingSchemaController
{
    public function schema()
    {
        $types = [
            ['type'=>'Site:Variable','label'=>'Site Variable','dataShape'=>'string "param|value" | array<string> | kv{param:value}','comparisons'=>['==','!='],'listable'=>true,'range'=>false],
            ['type'=>'Site:Domain','label'=>'Site Domain','dataShape'=>'string | array<string>','comparisons'=>['==','!='],'listable'=>true,'range'=>false],
            ['type'=>'Source:Source','label'=>'Source Tag','dataShape'=>'string | array<string>','comparisons'=>['==','!='],'listable'=>true,'range'=>false],
            ['type'=>'Geo:Country','label'=>'Geo Country (ISO)','dataShape'=>'string ISO code | array<string>','comparisons'=>['==','!='],'listable'=>true,'range'=>false],
            ['type'=>'Client:Browser','label'=>'Client Browser','dataShape'=>'string | array<string>','comparisons'=>['==','!='],'listable'=>true,'range'=>false],
            ['type'=>'Client:Language','label'=>'Client Language','dataShape'=>'string | array<string> (RFC 2616)','comparisons'=>['==','!='],'listable'=>true,'range'=>false],
            ['type'=>'Time:DayOfWeek','label'=>'Time: Day of Week','dataShape'=>'int 0–6 | array<int>','comparisons'=>['=='],'listable'=>true,'range'=>false],
            ['type'=>'Time:HourOfDay','label'=>'Time: Hour of Day','dataShape'=>'int 0–23','comparisons'=>['==','!=','>','<','>=','<='],'listable'=>false,'range'=>true],
            ['type'=>'Time:HourRange','label'=>'Time: Hour Range','dataShape'=>'object {from:int,to:int,local?:bool}','comparisons'=>['(implicit >=, <=)'],'listable'=>false,'range'=>true]
        ];
        $logical = ['and','or','not']; $groupModes = ['all','any','none'];
        $this->json(200, ['types'=>$types,'logical'=>$logical,'groupModes'=>$groupModes]);
    }
    private function json(int $status, array $payload){ http_response_code($status); header('Content-Type: application/json'); echo json_encode($payload); }
}
