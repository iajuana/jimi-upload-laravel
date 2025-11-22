<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

class MediaController extends Controller
{
    private string $videosRoot = '/var/www/jimi-upload-server/videos';

    // GET /videos/{path}
    public function show(Request $request, string $path): BinaryFileResponse
    {
        $safePath = ltrim($path, '/');
        $abs = realpath($this->videosRoot . '/' . $safePath);
        if ($abs === false || !str_starts_with($abs, $this->videosRoot . '/')) {
            abort(404);
        }
        if (!is_file($abs)) {
            abort(404);
        }
        $mime = $this->guessMime($abs);
        return response()->file($abs, [
            'Content-Type' => $mime,
        ]);
    }

    private function guessMime(string $path): string
    {
        $n = strtolower($path);
        if (preg_match('/\\.(jpg|jpeg)$/', $n)) return 'image/jpeg';
        if (preg_match('/\\.(png)$/', $n)) return 'image/png';
        if (preg_match('/\\.(gif)$/', $n)) return 'image/gif';
        if (preg_match('/\\.(webp)$/', $n)) return 'image/webp';
        if (preg_match('/\\.(mp4)$/', $n)) return 'video/mp4';
        if (preg_match('/\\.(ts|mts|m2ts)$/', $n)) return 'video/mp2t';
        if (preg_match('/\\.(mov)$/', $n)) return 'video/quicktime';
        if (preg_match('/\\.(avi)$/', $n)) return 'video/x-msvideo';
        if (preg_match('/\\.(mkv)$/', $n)) return 'video/x-matroska';
        return 'application/octet-stream';
    }

    // GET /video/convert?path=videos/<imei>/videos/<archivo.ts>
    public function convert(Request $request)
    {
        $rel = $request->query('path');
        if (!$rel || !is_string($rel)) {
            return response()->json(['status' => 'error', 'message' => 'Parámetro path requerido'], 400);
        }
        $rel = ltrim($rel, '/');
        if (!str_starts_with($rel, 'videos/')) {
            return response()->json(['status' => 'error', 'message' => 'Ruta inválida'], 400);
        }
        $abs = realpath($this->videosRoot . '/' . substr($rel, strlen('videos/')));
        if ($abs === false || !is_file($abs)) {
            return response()->json(['status' => 'error', 'message' => 'Archivo no encontrado'], 404);
        }
        $outAbs = preg_replace('/\\.(ts|mts|m2ts)$/i', '.mp4', $abs);
        if (!$outAbs) {
            $outAbs = $abs . '.mp4';
        }
        // Si ya existe el MP4, servirlo
        if (is_file($outAbs)) {
            return response()->file($outAbs, ['Content-Type' => 'video/mp4']);
        }
        // Asegurar directorio
        @mkdir(dirname($outAbs), 0775, true);
        // Estrategias de conversión
        $strategies = [
            ['-y', '-i', $abs, '-c:v', 'copy', '-c:a', 'copy', $outAbs],
            ['-y', '-i', $abs, '-c:v', 'copy', '-c:a', 'aac', '-b:a', '128k', $outAbs],
            ['-y', '-i', $abs, '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '23', '-c:a', 'aac', '-b:a', '128k', $outAbs],
        ];
        $lastErr = null;
        foreach ($strategies as $args) {
            try {
                $proc = new Process(array_merge(['ffmpeg'], $args));
                $proc->setTimeout(300);
                $proc->run();
                if ($proc->isSuccessful() && is_file($outAbs) && filesize($outAbs) > 0) {
                    return response()->file($outAbs, ['Content-Type' => 'video/mp4']);
                }
                $lastErr = $proc->getErrorOutput() ?: $proc->getOutput();
            } catch (\Throwable $e) {
                $lastErr = $e->getMessage();
            }
            // Limpiar salida fallida
            if (is_file($outAbs) && filesize($outAbs) === 0) {
                @unlink($outAbs);
            }
        }
        return response()->json(['status' => 'error', 'message' => 'No se pudo convertir', 'detail' => $lastErr], 500);
    }
}


