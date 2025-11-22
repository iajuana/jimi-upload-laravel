@extends('layouts.app')
@section('title','Histórico')
@section('content')
    <h2>Histórico</h2>
    @forelse($grouped as $dk => $cams)
        @php
            $parts = explode('_', $dk);
            $dkHuman = (count($parts)===3) ? ($parts[2].'-'.$parts[1].'-'.$parts[0]) : $dk;
        @endphp
        <details open class="card">
            <summary>{{ $dkHuman }}</summary>
            @foreach($cams as $cam => $items)
                <details style="margin-top:8px;">
                    <summary>Cámara {{ $cam }} ({{ count($items) }})</summary>
                    <table style="margin-top:8px;">
                        <thead><tr><th>Hora</th><th>IMEI</th><th>Archivo</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody>
                        @foreach($items as $it)
                            @php
                                $viewUrl = null;
                                if ($it['uploaded'] && $it['uploaded_path']) {
                                    $isTs = preg_match('/\\.(ts|mts|m2ts)$/i', $it['filename']);
                                    $viewUrl = $isTs ? url('/video/convert?path='.urlencode($it['uploaded_path'])) : url('/'.$it['uploaded_path']);
                                }
                                // Construir comando HVIDEO: ddmmyy
                                $ddmmyy = null;
                                if (preg_match('/(\\d{4})_(\\d{2})_(\\d{2})_/', $it['filename'], $m)) {
                                    $ddmmyy = $m[3].$m[2].substr($m[1],2,2);
                                }
                                $cmd = $ddmmyy ? ('HVIDEO,'.$ddmmyy.','.$it['filename'].','.$it['camera']) : ('HVIDEO,'.$it['filename'].','.$it['camera']);
                                $prepUrl = url('/comandos?phone='.urlencode((string)($it['phone'] ?? '')).'&command='.urlencode($cmd));
                            @endphp
                            <tr>
                                <td class="muted">{{ $it['time'] ?? '' }}</td>
                                <td>{{ $it['imei'] }}</td>
                                <td style="max-width:320px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $it['filename'] }}</td>
                                <td>{{ $it['uploaded'] ? 'Descargado' : 'En cámara' }}</td>
                                <td>
                                    <a class="btn" href="{{ $prepUrl }}">Preparar envío</a>
                                    @if($viewUrl)
                                        <a class="btn" href="{{ $viewUrl }}" target="_blank">Ver</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </details>
            @endforeach
        </details>
    @empty
        <div class="muted">Sin elementos</div>
    @endforelse
@endsection


