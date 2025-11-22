<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HistoricoController extends Controller
{
    private string $videosRoot = '/var/www/jimi-upload-server/videos';

    public function index()
    {
        // Traer últimos items de filelists con su dispositivo
        $rows = DB::table('filelist_items as fi')
            ->join('filelists as fl', 'fl.id', '=', 'fi.filelist_id')
            ->join('devices as d', 'd.id', '=', 'fl.device_id')
            ->select('fi.id','fi.filename','fi.camera','fi.uploaded','fi.uploaded_path','fl.uploaded_at','d.imei','d.phone_number')
            ->orderByDesc('fl.uploaded_at')
            ->limit(1500)
            ->get();

        $items = [];
        foreach ($rows as $r) {
            $uploadedPath = $r->uploaded_path;
            if (!$uploadedPath) {
                $uploadedPath = $this->findUploadedPath($r->imei, $r->filename);
            }
            $items[] = [
                'imei' => $r->imei,
                'phone' => $r->phone_number,
                'filename' => $r->filename,
                'camera' => $r->camera ?? 1,
                'uploaded' => $uploadedPath ? true : (bool)$r->uploaded,
                'uploaded_path' => $uploadedPath,
                'date_key' => $this->dateKeyFromFilename($r->filename, $r->uploaded_at),
                'time' => $this->timeFromFilename($r->filename),
            ];
        }

        // Agrupar por fecha y cámara
        $grouped = [];
        foreach ($items as $it) {
            $dk = $it['date_key'] ?? 'desconocida';
            $cam = $it['camera'] ?? 1;
            $grouped[$dk] = $grouped[$dk] ?? [];
            $grouped[$dk][$cam] = $grouped[$dk][$cam] ?? [];
            $grouped[$dk][$cam][] = $it;
        }
        // Ordenar fecha desc
        uksort($grouped, fn($a,$b)=>strcmp($b,$a));
        return view('historico', compact('grouped'));
    }

    private function findUploadedPath(string $imei, string $filename): ?string
    {
        // Buscar en carpeta del IMEI
        $dir = $this->videosRoot . '/' . $imei . '/videos';
        if (is_dir($dir)) {
            $rel = $this->findBySuffix($dir, $filename);
            if ($rel) return 'videos/' . $imei . '/videos/' . $rel;
        }
        // Buscar en unknown_*/videos
        $root = $this->videosRoot;
        $dh = @opendir($root);
        if ($dh) {
            while (($e = readdir($dh)) !== false) {
                if ($e === '.' || $e === '..') continue;
                if (strpos($e, 'unknown_') === 0) {
                    $rel = $this->findBySuffix($root . '/' . $e . '/videos', $filename);
                    if ($rel) {
                        closedir($dh);
                        return 'videos/' . $e . '/videos/' . $rel;
                    }
                }
            }
            closedir($dh);
        }
        return null;
    }

    private function findBySuffix(string $dir, string $filename): ?string
    {
        if (!is_dir($dir)) return null;
        $dh = @opendir($dir);
        if (!$dh) return null;
        while (($e = readdir($dh)) !== false) {
            if ($e === '.' || $e === '..') continue;
            if (!is_file($dir . '/' . $e)) continue;
            if (str_ends_with($e, $filename)) {
                closedir($dh);
                return $e;
            }
        }
        closedir($dh);
        return null;
    }

    private function dateKeyFromFilename(string $filename, $uploadedAt): string
    {
        // Intentar YYYY_MM_DD del nombre
        if (preg_match('/(\\d{4})_(\\d{2})_(\\d{2})_\\d{2}_\\d{2}_\\d{2}/', $filename, $m)) {
            return "{$m[1]}_{$m[2]}_{$m[3]}";
        }
        if ($uploadedAt) {
            return date('Y_m_d', strtotime($uploadedAt));
        }
        return 'desconocida';
    }

    private function timeFromFilename(string $filename): ?string
    {
        if (preg_match('/\\d{4}_\\d{2}_\\d{2}_(\\d{2})_(\\d{2})_(\\d{2})/', $filename, $m)) {
            return "{$m[1]}:{$m[2]}:{$m[3]}";
        }
        return null;
    }
}


