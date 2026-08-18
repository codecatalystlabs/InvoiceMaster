<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('head')
</head>
<body>
@php
    $company = auth()->user()->company;
    $logo = $company?->logoUrl() ?? asset('images/logo.png');
@endphp
<div class="app-shell">
    <header class="app-header">
        <div class="d-flex align-items-center gap-2 min-w-0">
            <button type="button" class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebarToggle" aria-label="Menu">
                <i class="bi bi-list"></i>
            </button>
            <a class="app-brand" href="{{ route('dashboard') }}">
                <img src="{{ $logo }}" alt="{{ $company?->name ?? 'logo' }}">
                <span class="app-brand-text">
                    <strong>{{ config('app.name') }}</strong>
                    <small>{{ $company?->name ?? 'Code Catalyst Labs' }}</small>
                </span>
            </a>
        </div>
        <div class="app-header-actions">
            <a href="{{ url()->previous() }}" class="btn btn-skin">Back</a>
            <div class="dropdown">
                <a class="user-chip dropdown-toggle" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-person-fill"></i> {{ auth()->user()->name }}
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text text-muted small">{{ auth()->user()->role }}</span></li>
                    <li><a class="dropdown-item" href="{{ route('profile') }}">Profile</a></li>
                    @if(can_module('settings'))
                        <li><a class="dropdown-item" href="{{ route('settings.company') }}">Settings</a></li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="dropdown-item">Log out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>
    <div class="app-body">
        <aside class="sidebar" id="appSidebar">
            <div class="sidebar-solo">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid"></i> Dashboard</a>
                @if(can_module('canteen'))
                    <a class="nav-link {{ request()->routeIs('canteen.today') ? 'active' : '' }}" href="{{ route('canteen.today') }}"><i class="bi bi-cup-hot"></i> Log a meal</a>
                @endif
            </div>
            @foreach(config('modules.nav') as $group)
                @php
                    $visible = collect($group['items'])->filter(fn ($item) => can_module($item['module']));
                @endphp
                @continue($visible->isEmpty())
            <details class="nav-group" open>
                <summary>{{ $group['label'] }}</summary>
                @foreach($visible as $item)
                    <a class="nav-link {{ request()->routeIs(...($item['match'] ?? [$item['route']])) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                        <i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}
                        @if(($item['badge'] ?? null) === 'emails' && ($unreadEmails ?? 0) > 0)
                            <span class="badge bg-danger">{{ $unreadEmails }}</span>
                        @endif
                @if(($item['badge'] ?? null) === 'review' && ($pendingMealReviews ?? 0) > 0)
                            <span class="badge bg-warning text-dark">{{ $pendingMealReviews }}</span>
                        @endif
                        @if($item['module'] === 'requests' && ($pendingChangeRequests ?? 0) > 0 && can_module('canteen.review'))
                            <span class="badge bg-warning text-dark">{{ $pendingChangeRequests }}</span>
                        @endif
                    </a>
                @endforeach
            </details>
            @endforeach
        </aside>
        <div class="main">
            <div class="page-bar">
                <h1>@yield('title', 'Dashboard')</h1>
                <div>@yield('actions')</div>
            </div>
            <main class="content">
                @if(($pendingMealReviews ?? 0) > 0 && can_module('canteen.review') && !request()->routeIs('canteen.review'))
                    <div class="alert alert-warning">{{ $pendingMealReviews }} canteen {{ $pendingMealReviews === 1 ? 'entry' : 'entries' }} waiting for review. <a href="{{ route('canteen.review') }}">Open queue</a></div>
                @endif
                @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
                @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
                @if($errors->any()) <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div> @endif
                @yield('content')
            </main>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.getElementById('appSidebar')?.classList.toggle('open');
});
function addItemRow(tableId) {
    const tbody = document.querySelector('#' + tableId + ' tbody');
    const i = tbody.querySelectorAll('tr').length;
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><input name="items[${i}][item_name]" class="form-control" required></td>
        <td><input type="number" step="0.01" name="items[${i}][qty]" class="form-control qty" value="1"></td>
        <td><input type="number" step="0.01" name="items[${i}][unit_price]" class="form-control price"></td>
        <td class="line-total">0</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove();calcTotals()">×</button></td>`;
    tbody.appendChild(tr);
}
function calcTotals() {
    let sub = 0;
    document.querySelectorAll('#itemsTable tbody tr').forEach(tr => {
        const q = parseFloat(tr.querySelector('.qty')?.value || 0);
        const p = parseFloat(tr.querySelector('.price')?.value || 0);
        const t = q * p;
        sub += t;
        const cell = tr.querySelector('.line-total');
        if (cell) cell.textContent = t.toLocaleString();
    });
    const taxRate = parseFloat(document.getElementById('tax_rate')?.value || 0);
    const disc = parseFloat(document.getElementById('discount')?.value || 0);
    const tax = sub * taxRate / 100;
    const total = sub + tax - disc;
    function totalLabel(v) { return Number(v).toLocaleString(); }
    if (document.getElementById('sumSub')) document.getElementById('sumSub').textContent = totalLabel(sub);
    if (document.getElementById('sumTax')) document.getElementById('sumTax').textContent = totalLabel(tax);
    if (document.getElementById('sumTotal')) document.getElementById('sumTotal').textContent = totalLabel(total);
}
document.addEventListener('input', e => { if (e.target.closest('#itemsTable') || e.target.id === 'tax_rate' || e.target.id === 'discount') calcTotals(); });
</script>
@stack('scripts')
</body>
</html>
