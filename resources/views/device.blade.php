@extends('layouts.app')
@section('title','Dispositivo')
@section('content')
    <a class="btn" href="{{ url('/') }}">← Volver</a>
    <h2 style="display:inline-block; margin-left:8px;">Dispositivo: {{ $device->imei }} @if($device->name) ({{ $device->name }}) @endif</h2>
    <div class="grid2" style="margin-top:12px;">
        <div class="card">
            <h3>Vídeos</h3>
            @forelse($groupVideos as $dk => $items)
                @php
                    $parts = explode('_', $dk);
                    $dkHuman = count($parts)===3 ? ($parts[2].'-'.$parts[1].'-'.$parts[0]) : $dk;
                @endphp
                <details>
                    <summary>{{ $dkHuman }} ({{ count($items) }})</summary>
                    <table style="margin-top:8px;">
                        <thead><tr><th>Hora</th><th>Archivo</th><th>Tamaño</th><th>Acciones</th></tr></thead>
                        <tbody>
                        @foreach($items as $u)
                            @php
                                $isTs = preg_match('/\\.(ts|mts|m2ts)$/i', $u->filename);
                                $rel = 'videos/'.$device->imei.'/videos/'.$u->filename;
                                $play = $isTs ? url('/video/convert?path='.urlencode($rel)) : url('/'.$rel);
                            @endphp
                            <tr>
                                <td class="muted">{{ optional($u->uploaded_at ?? $u->created_at)->format('H:i:s') }}</td>
                                <td style="max-width:320px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $u->filename }}</td>
                                <td>{{ number_format(($u->size ?? 0)/1024/1024,2) }} MB</td>
                                <td>
                                    <a class="btn" href="{{ $play }}" target="_blank">Reproducir</a>
                                    <a class="btn" href="{{ url('/'.$rel) }}" download>Descargar</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </details>
            @empty
                <div class="muted">Sin vídeos</div>
            @endforelse
        </div>
        <div class="card">
            <h3>Imágenes</h3>
            @forelse($groupFotos as $dk => $items)
                @php
                    $parts = explode('_', $dk);
                    $dkHuman = count($parts)===3 ? ($parts[2].'-'.$parts[1].'-'.$parts[0]) : $dk;
                @endphp
                <details>
                    <summary>{{ $dkHuman }} ({{ count($items) }})</summary>
                    <table style="margin-top:8px;">
                        <thead><tr><th>Hora</th><th>Archivo</th><th>Tamaño</th><th>Acciones</th></tr></thead>
                        <tbody>
                        @foreach($items as $u)
                            @php
                                $rel = 'videos/'.$device->imei.'/fotos/'.$u->filename;
                            @endphp
                            <tr>
                                <td class="muted">{{ optional($u->uploaded_at ?? $u->created_at)->format('H:i:s') }}</td>
                                <td style="max-width:320px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $u->filename }}</td>
                                <td>{{ number_format(($u->size ?? 0)/1024/1024,2) }} MB</td>
                                <td>
                                    <a class="btn" href="{{ url('/'.$rel) }}" target="_blank">Ver</a>
                                    <a class="btn" href="{{ url('/'.$rel) }}" download>Descargar</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </details>
            @empty
                <div class="muted">Sin imágenes</div>
            @endforelse
        </div>
    </div>
@endsection


