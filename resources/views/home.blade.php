@extends('layouts.app')
@section('title','Inicio')
@section('content')
    <h2>Dispositivos</h2>
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>IMEI</th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Vídeos</th>
                    <th>Imágenes</th>
                    <th>Tamaño total</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse($devices as $d)
                @php
                    $stats = $byDevice[$d->id] ?? [];
                    $v = $stats['videos']['count'] ?? 0;
                    $p = $stats['fotos']['count'] ?? 0;
                    $sz = ($stats['videos']['size'] ?? 0) + ($stats['fotos']['size'] ?? 0);
                    $fmt = function($bytes){ if(!$bytes) return '0 B'; $k=1024; $u=['B','KB','MB','GB']; $i=floor(log($bytes, $k)); return round($bytes/pow($k,$i),2).' '.$u[$i]; };
                @endphp
                <tr>
                    <td>{{ $d->imei }}</td>
                    <td>{{ $d->name }}</td>
                    <td>{{ $d->phone_number }}</td>
                    <td>{{ $v }}</td>
                    <td>{{ $p }}</td>
                    <td>{{ $fmt($sz) }}</td>
                    <td><a class="btn" href="{{ url('/device/'.$d->imei) }}">Ver</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted" style="text-align:center">Sin dispositivos</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection


