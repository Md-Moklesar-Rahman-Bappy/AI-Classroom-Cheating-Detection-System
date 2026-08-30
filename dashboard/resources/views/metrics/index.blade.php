@extends("layouts.bootstrap")
@section("title","Metrics")
@section("content")
<h2>Performance Metrics</h2>
<div class="card p-3 mb-3"><canvas id="metricsChart" height="100"></canvas></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Job</th><th>FPS</th><th>Latency</th><th>CPU</th><th>Memory</th></tr></thead><tbody>@foreach($metrics as $m)<tr><td>{{ $m->analysis_job_id }}</td><td>{{ $m->processing_fps }}</td><td>{{ $m->detection_latency_ms }}</td><td>{{ $m->cpu_percent }}</td><td>{{ $m->memory_mb }}</td></tr>@endforeach</tbody></table>{{ $metrics->links() }}</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>const ctx=document.getElementById("metricsChart"); if(ctx){ new Chart(ctx,{type:"bar",data:{labels:["FPS","Latency","CPU"],datasets:[{label:"Avg",data:[2.4,180,75]}]}}); }</script>
@endsection
