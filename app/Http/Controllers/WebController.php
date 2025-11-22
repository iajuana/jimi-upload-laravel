<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebController extends Controller
{
    public function home()
    {
        // Resumen por dispositivo
        $devices = Device::orderBy('id', 'desc')->get();
        $byDevice = [];
        if ($devices->count() > 0) {
            $counts = Upload::select('device_id', 'category', DB::raw('COUNT(*) as c'), DB::raw('COALESCE(SUM(size),0) as s'))
                ->groupBy('device_id', 'category')->get();
            foreach ($counts as $row) {
                $byDevice[$row->device_id][$row->category] = ['count' => (int)$row->c, 'size' => (int)$row->s];
            }
        }
        return view('home', compact('devices', 'byDevice'));
    }

    public function device(string $imei)
    {
        $imei = preg_replace('/\D/', '', $imei);
        $device = Device::where('imei', $imei)->firstOrFail();
        $uploads = Upload::where('device_id', $device->id)->orderByDesc('uploaded_at')->limit(5000)->get();
        // Separar por categoría y deduplicar ts/mp4 por nombre base
        $videos = [];
        $fotos = [];
        foreach ($uploads as $u) {
            if ($u->category === 'videos') $videos[] = $u;
            elseif ($u->category === 'fotos') $fotos[] = $u;
        }
        $videos = $this->dedupeVideos($videos);
        // Agrupar por día
        $groupVideos = $this->groupByDate($videos);
        $groupFotos = $this->groupByDate($fotos);
        return view('device', compact('device', 'groupVideos', 'groupFotos'));
    }

    private function groupByDate(array $items): array
    {
        $out = [];
        foreach ($items as $u) {
            $ts = $u->uploaded_at ?: $u->created_at;
            $key = $ts ? $ts->format('Y_m_d') : 'desconocida';
            $out[$key] = $out[$key] ?? [];
            $out[$key][] = $u;
        }
        // Ordenar por fecha desc
        uksort($out, fn($a,$b) => strcmp($b, $a));
        return $out;
    }

    private function baseNameNoExt(string $name): string
    {
        $i = strrpos($name, '.');
        return $i !== false ? substr($name, 0, $i) : $name;
    }

    private function isTs(string $name): bool { return (bool)preg_match('/\.(ts|mts|m2ts)$/i', $name); }
    private function isMp4(string $name): bool { return (bool)preg_match('/\.mp4$/i', $name); }

    private function dedupeVideos(array $list): array
    {
        $byBase = [];
        foreach ($list as $u) {
            $base = $this->baseNameNoExt($u->filename);
            $byBase[$base] = $byBase[$base] ?? [];
            $byBase[$base][] = $u;
        }
        $out = [];
        foreach ($byBase as $arr) {
            $chosen = null;
            foreach ($arr as $u) { if ($this->isTs($u->filename)) { $chosen = $u; break; } }
            if (!$chosen) { foreach ($arr as $u) { if ($this->isMp4($u->filename)) { $chosen = $u; break; } } }
            $out[] = $chosen ?: $arr[0];
        }
        return $out;
    }
}


