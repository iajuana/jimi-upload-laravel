@extends('layouts.app')
@section('title','Enviar comandos')
@section('content')
    <h2>Enviar comandos</h2>
    <div class="card">
        <form method="post" action="#" onsubmit="alert('Envío simulado. Integraremos SMS/HTTP más tarde.'); return false;">
            @csrf
            <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <label>Teléfono
                    <input type="text" name="phone" id="phone" value="{{ $phone }}" style="padding:6px; border:1px solid #e0e0e0;">
                </label>
                <label>Comando
                    <input type="text" name="command" id="command" value="{{ $command }}" style="width:520px; padding:6px; border:1px solid #e0e0e0;">
                </label>
                <button class="btn" type="submit">Enviar</button>
            </div>
        </form>
    </div>
    <div class="card">
        <strong>Presets</strong>
        <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
            <button class="btn" onclick="preset('FILELIST')">FILELIST</button>
            <button class="btn" onclick="preset('STATUS')">STATUS</button>
        </div>
    </div>
    <script>
        function preset(cmd){ document.getElementById('command').value = cmd; }
    </script>
@endsection


