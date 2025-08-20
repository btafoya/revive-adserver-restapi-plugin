<?php
namespace App\Controllers;

use App\Support\ReviveConfig;
use PDO;

final class UploadsController
{
    public function attachToBanner(array $params)
    {
        $bannerId = (int)($params['bannerId'] ?? 0);
        if ($bannerId <= 0) return $this->json(400, ['error'=>'invalid bannerId']);
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return $this->json(400, ['error'=>'file missing or upload error']);
        }

        $tmp  = $_FILES['file']['tmp_name'];
        $name = $_FILES['file']['name'];
        $size = (int)$_FILES['file']['size'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $tmp) ?: '';
        finfo_close($finfo);

        $allowed = ['image/png'=>'png','image/jpeg'=>'jpg','image/gif'=>'gif','image/webp'=>'webp'];
        if (!isset($allowed[$mime])) return $this->json(415, ['error'=>'unsupported file type','mime'=>$mime]);

        $dim = @getimagesize($tmp);
        if (!$dim) return $this->json(422, ['error'=>'image could not be parsed']);
        [$width,$height] = $dim;
        $ext = $allowed[$mime];

        $hash = sha1_file($tmp);
        $destDir = ReviveConfig::imagesDir();
        if (!is_dir($destDir)) @mkdir($destDir, 0775, true);
        $fileName = $hash.'.'.$ext;
        $destPath = $destDir.'/'.$fileName;

        if (!@move_uploaded_file($tmp, $destPath)) {
            if (!@rename($tmp, $destPath)) return $this->json(500, ['error'=>'failed to store file']);
        }
        @chmod($destPath, 0644);

        $pdo = $this->pdo();
        $tbl = ReviveConfig::table('banners');
        $stmt = $pdo->prepare("UPDATE `$tbl` SET storagetype='web', filename=:f, imageurl=NULL, contenttype=:m, width=:w, height=:h WHERE bannerid=:id LIMIT 1");
        $stmt->execute([':f'=>$fileName, ':m'=>$mime, ':w'=>$width, ':h'=>$height, ':id'=>$bannerId]);

        $publicUrl = ReviveConfig::imagesUrlBase().'/'.$fileName;
        return $this->json(200, ['bannerId'=>$bannerId,'storedAs'=>$fileName,'mime'=>$mime,'width'=>$width,'height'=>$height,'filesize'=>$size,'publicUrl'=>$publicUrl]);
    }

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
