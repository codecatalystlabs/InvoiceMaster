@extends('layouts.app')
@section('title', 'Analytics')
@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card"><h6>Revenue</h6><h3>{{ money($kpis['revenue']) }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>Expenses</h6><h3>{{ money($kpis['expenses']) }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>Net</h6><h3>{{ money($kpis['net']) }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>Receipts</h6><h3>{{ money($kpis['receipts']) }}</h3></div></div>
</div>
<div class="card mb-3"><div class="card-header">Revenue, expenses & receipts — 12 months</div><div class="card-body"><canvas id="trend"></canvas></div></div>
<div class="row"><div class="col-md-6"><div class="card mb-3"><div class="card-header">Expenses by category</div><div class="card-body"><canvas id="cats"></canvas></div></div>
<div class="col-md-6"><div class="card mb-3"><div class="card-header">Invoice status</div><div class="card-body"><canvas id="status"></canvas></div></div></div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const d = @json($chart);
new Chart(document.getElementById('trend'), { type:'line', data:{ labels:d.labels, datasets:[
    {label:'Revenue', data:d.revenue, borderColor:'#198754'},
    {label:'Expenses', data:d.expenses, borderColor:'#dc3545'},
    {label:'Receipts', data:d.receipts, borderColor:'#1a73e8'}
]}, options:{responsive:true}});
new Chart(document.getElementById('cats'), { type:'doughnut', data:{ labels:d.catLabels, datasets:[{data:d.catValues}] }});
new Chart(document.getElementById('status'), { type:'pie', data:{ labels:d.statusLabels, datasets:[{data:d.statusCounts}] }});
</script>
@endpush
@endsection
