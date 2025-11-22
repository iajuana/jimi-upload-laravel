<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\Upload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportExistingMedia extends Command
{
    protected $signature = 'videos:import-existing {--root=/var/www/jimi-upload-server/videos}';
    protected $description = 'Escanea la carpeta de videos y registra dispositivos y uploads en BD sin duplicar archivos';

    public function handle(): int
    {
        $root = rtrim($this->option('root') ?? '', '/');
        if ($root === '' || !is_dir($root)) {
            $this->error("Raíz inválida: {$root}");
            return self::FAILURE;
        }
        $this->info("Escaneando: {$root}");

        $dir = opendir($root);
        if (!$dir) {
            $this->error('No se pudo abrir el directorio');
            return self::FAILURE;
        }
        $total = 0;
        while (($entry = readdir($dir)) !== false) {
            if ($entry === '.' || $entry === '..') continue;
            $full = "{$root}/{$entry}";
            if (!is_dir($full)) continue;
            // Aceptar IMEI (15 dígitos) o unknown_*
            if (!preg_match('/^\\d{15}$/', $entry) && !preg_match('/^unknown_[a-f0-9]{16}$/', $entry)) {
                continue;
            }
            $deviceFolder = $entry;
            $device = Device::firstOrCreate(['imei' => $deviceFolder]);
            foreach (['videos', 'fotos', 'otros'] as $cat) {
                $catDir = "{$full}/{$cat}";
                if (!is_dir($catDir)) continue;
                $dh = opendir($catDir);
                if (!$dh) continue;
                while (($f = readdir($dh)) !== false) {
                    if ($f === '.' || $f === '..') continue;
                    $abs = "{$catDir}/{$f}";
                    if (!is_file($abs)) continue;
                    // Evitar duplicados por filename exacto
                    $exists = Upload::where('device_id', $device->id)->where('filename', $f)->exists();
                    if ($exists) continue;
                    $stat = @stat($abs);
                    $size = $stat ? (int)$stat['size'] : 0;
                    $mtime = $stat ? (int)$stat['mtime'] : null;
                    $uploadedAt = $mtime ? date('c', $mtime) : null;
                    DB::transaction(function () use ($device, $cat, $f, $size, $abs, $uploadedAt) {
                        $up = new Upload();
                        $up->device_id = $device->id;
                        $up->category = $cat;
                        $up->filename = $f;
                        $up->size = $size;
                        $up->mimetype = null;
                        $up->path = $abs;
                        $up->uploaded_at = $uploadedAt;
                        $up->save();
                    });
                    $total++;
                }
                closedir($dh);
            }
        }
        closedir($dir);
        $this->info("Importados {$total} archivos a BD.");
        return self::SUCCESS;
    }
}


