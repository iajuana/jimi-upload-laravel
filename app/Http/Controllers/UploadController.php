<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Upload;
use App\Models\Filelist;
use App\Models\FilelistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UploadController extends Controller
{
    private string $videosRoot;

    public function __construct()
    {
        // Mantener misma raíz de ficheros que el proyecto Node
        $this->videosRoot = '/var/www/jimi-upload-server/videos';
    }

    // POST /upload
    public function upload(Request $request)
    {
        $tsIso = now()->toIso8601String();

        // Caso FILELIST vía JSON: { imei: "...", fileNameList: "a.ts,b.ts,..." }
        if (!$request->hasFile('file')) {
            $jsonList = $request->input('fileNameList') ?? $request->input('files') ?? $request->input('list');
            $imeiFromBody = $request->input('imei');
            if ($jsonList && $imeiFromBody) {
                $deviceId = preg_replace('/\D/', '', (string)$imeiFromBody) ?: 'unknown';
                $items = collect(preg_split('/[,\r\n]+/', (string)$jsonList))
                    ->map(fn($s) => trim($s))
                    ->filter(fn($s) => $s !== '' && preg_match('/\.(ts|mts|m2ts|mp4|avi|mov|mkv)$/i', $s));

                // Guardar JSON en disco para compatibilidad
                if (!is_dir($this->videosRoot)) {
                    @mkdir($this->videosRoot, 0775, true);
                }
                $listData = [
                    'filename' => 'inline-json',
                    'originalName' => 'inline-json',
                    'imei' => $deviceId,
                    'uploadDate' => $tsIso,
                    'videoCount' => $items->count(),
                    'videos' => $items->values()->map(fn($v) => [
                        'filename' => $v,
                        'requested' => false,
                        'uploaded' => false,
                    ])->all(),
                ];
                $outPath = $this->videosRoot . '/filelist_' . $deviceId . '_' . now()->valueOf() . '.json';
                @file_put_contents($outPath, json_encode($listData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                // Persistir en BD
                DB::transaction(function () use ($deviceId, $items, $tsIso, $request) {
                    $device = Device::firstOrCreate(['imei' => $deviceId]);
                    $fl = new Filelist();
                    $fl->device_id = $device->id;
                    $fl->original_name = $request->input('originalName') ?? 'inline-json';
                    $fl->uploaded_at = $tsIso;
                    $fl->save();
                    foreach ($items as $v) {
                        $it = new FilelistItem();
                        $it->filelist_id = $fl->id;
                        $it->filename = $v;
                        $it->requested = false;
                        $it->uploaded = false;
                        $it->save();
                    }
                });

                return response()->json(['status' => 'ok', 'list' => $listData]);
            }

            return response()->json(['status' => 'error', 'message' => 'No se recibió ningún archivo'], 400);
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $filenameSafe = $this->sanitizeFilename($originalName);
        $category = $this->classifyCategory($file->getClientMimeType(), $originalName);

        // Resolver IMEI: query/body > nombre de archivo > unknown
        $imeiParam = (string)($request->input('imei') ?? '');
        $deviceFolder = $imeiParam !== '' ? preg_replace('/\D/', '', $imeiParam) : $this->extractImeiFromFilename($originalName);
        if ($deviceFolder === '' || $deviceFolder === null) {
            $deviceFolder = 'unknown_' . substr(md5($request->ip() . $originalName . microtime(true)), 0, 16);
        }

        // Preparar rutas
        $targetDir = $this->videosRoot . '/' . $deviceFolder . '/' . $category;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }
        $finalName = now()->valueOf() . '-' . $filenameSafe;
        $targetPath = $targetDir . '/' . $finalName;

        // Mover archivo
        $file->move($targetDir, $finalName);
        $size = @filesize($targetPath) ?: 0;

        // Persistir en BD
        try {
            DB::transaction(function () use ($deviceFolder, $category, $finalName, $size, $file, $targetPath) {
                $device = Device::firstOrCreate(['imei' => $deviceFolder]);
                $up = new Upload();
                $up->device_id = $device->id;
                $up->category = $category;
                $up->filename = $finalName;
                $up->size = $size;
                $up->mimetype = $file->getClientMimeType();
                $up->path = $targetPath;
                $up->uploaded_at = now();
                $up->save();
            });
        } catch (\Throwable $e) {
            // continuar, pero reportar
        }

        return response()->json([
            'status' => 'ok',
            'filename' => $finalName,
            'originalName' => $originalName,
            'device' => $deviceFolder,
            'isImei' => preg_match('/^\d{15}$/', $deviceFolder) === 1,
            'size' => $size,
            'mimetype' => $file->getClientMimeType(),
            'path' => $targetPath,
            'folder' => 'videos/' . $deviceFolder . '/' . $category . '/',
            'category' => $category,
            'timestamp' => $tsIso,
            'isFileList' => false,
        ]);
    }

    // GET /files/imei/{imei}
    public function filesByImei(Request $request, string $imei)
    {
        $imei = preg_replace('/\D/', '', $imei);
        if ($imei === '') {
            return response()->json(['status' => 'error', 'message' => 'IMEI inválido'], 400);
        }

        $device = Device::where('imei', $imei)->first();
        $files = [];

        if ($device) {
            $uploads = Upload::where('device_id', $device->id)
                ->orderByDesc('uploaded_at')
                ->limit(5000)
                ->get();
            foreach ($uploads as $u) {
                $files[] = [
                    'filename' => $u->filename,
                    'imei' => $imei,
                    'category' => $u->category,
                    'size' => (int)$u->size,
                    'created' => optional($u->uploaded_at)->toISOString() ?? null,
                    'modified' => optional($u->updated_at)->toISOString() ?? null,
                    'path' => $this->relativePath($u->path),
                ];
            }
        }

        // Fallback: escanear disco si no hay en BD
        if (empty($files)) {
            $base = $this->videosRoot . '/' . $imei;
            foreach (['videos', 'fotos', 'otros'] as $cat) {
                $dir = $base . '/' . $cat;
                if (!is_dir($dir)) continue;
                $dh = opendir($dir);
                if (!$dh) continue;
                while (($entry = readdir($dh)) !== false) {
                    if ($entry === '.' || $entry === '..') continue;
                    $full = $dir . '/' . $entry;
                    if (!is_file($full)) continue;
                    $stat = @stat($full);
                    $files[] = [
                        'filename' => $entry,
                        'imei' => $imei,
                        'category' => $cat,
                        'size' => $stat ? (int)$stat['size'] : 0,
                        'created' => $stat ? date('c', (int)$stat['mtime']) : null,
                        'modified' => $stat ? date('c', (int)$stat['mtime']) : null,
                        'path' => $this->relativePath($full),
                    ];
                }
                closedir($dh);
            }
            // Ordenar por fecha desc
            usort($files, fn($a, $b) => strcmp($b['created'] ?? '', $a['created'] ?? ''));
        }

        return response()->json([
            'status' => 'ok',
            'imei' => $imei,
            'count' => count($files),
            'files' => $files,
        ]);
    }

    private function classifyCategory(?string $mime, string $name): string
    {
        $n = strtolower($name);
        if (preg_match('/\\.(jpg|jpeg|png|gif|webp|bmp|heic|heif|tif|tiff)$/', $n)) {
            return 'fotos';
        }
        if (preg_match('/\\.(mp4|avi|mov|mkv|ts|mts|webm)$/', $n)) {
            return 'videos';
        }
        if ($mime && str_starts_with($mime, 'image/')) return 'fotos';
        if ($mime && str_starts_with($mime, 'video/')) return 'videos';
        return 'otros';
    }

    private function extractImeiFromFilename(string $filename): ?string
    {
        // Busca secuencia de 15 dígitos o patrón EVENT_<IMEI>_
        if (preg_match('/\\b(\\d{15})\\b/', $filename, $m)) {
            return $m[1];
        }
        if (preg_match('/EVENT_(\\d{10,17})_/', $filename, $m)) {
            return preg_replace('/\\D/', '', $m[1]);
        }
        return null;
    }

    private function sanitizeFilename(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        $name = basename($name);
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
        return $name ?: 'file';
    }

    private function relativePath(string $abs): string
    {
        // Devuelve ruta relativa "videos/..." si pertenece a la raíz
        if (str_starts_with($abs, $this->videosRoot . '/')) {
            return 'videos/' . ltrim(substr($abs, strlen($this->videosRoot . '/')), '/');
        }
        return $abs;
    }
}


