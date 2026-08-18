<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&display=swap">
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
    <div class="app-menubar no-print">
        <a href="{{ route('dashboard') }}"><u>F</u>ile</a>
        @if(can_module('invoices'))<a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.*') ? 'on' : '' }}"><u>I</u>nvoices</a>@endif
        @if(can_module('receipts'))<a href="{{ route('receipts.index') }}" class="{{ request()->routeIs('receipts.*') ? 'on' : '' }}"><u>R</u>eceipts</a>@endif
        @if(can_module('cashbook'))<a href="{{ route('cashbook.index') }}" class="{{ request()->routeIs('cashbook.*') ? 'on' : '' }}">Cash <u>b</u>ook</a>@endif
        @if(can_module('ledger'))<a href="{{ route('ledger.index') }}" class="{{ request()->routeIs('ledger.*') ? 'on' : '' }}"><u>L</u>edgers</a>@endif
        @if(can_module('reports'))<a href="{{ route('reports.financial') }}" class="{{ request()->routeIs('reports.*') ? 'on' : '' }}">Re<u>p</u>orts</a>@endif
        @if(can_module('settings'))<a href="{{ route('settings.company') }}"><u>T</u>ools</a>@endif
        <a href="{{ route('profile') }}"><u>W</u>indow</a>
    </div>
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
                    @continue(! \Illuminate\Support\Facades\Route::has($item['route']))
                    <a class="nav-link {{ request()->routeIs(...($item['match'] ?? [$item['route']])) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                        <i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}
                        @if(($item['badge'] ?? null) === 'emails' && ($unreadEmails ?? 0) > 0)
                            <span class="badge bg-danger">{{ $unreadEmails }}</span>
                        @endif
                @if(($item['badge'] ?? null) === 'review' && ($pendingMealReviews ?? 0) > 0)
                            <span class="badge bg-warning text-dark">{{ $pendingMealReviews }}</span>
                        @endif
                        @if(($item['badge'] ?? null) === 'requisitions' && ($pendingRequisitions ?? 0) > 0)
                            <span class="badge bg-warning text-dark">{{ $pendingRequisitions }}</span>
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
                <h1><span class="win-dot"></span> @yield('title', 'Dashboard')</h1>
                <div class="page-bar-actions">@yield('actions')</div>
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
    <footer class="app-statusbar no-print">
        <span class="sb-cell">Ready</span>
        <span class="sb-cell">{{ $company?->name ?? config('app.name') }}</span>
        <span class="sb-cell">{{ auth()->user()->name }} · {{ auth()->user()->role }}</span>
        <span class="sb-cell">Period {{ now()->format('F Y') }}</span>
        <span class="sb-cell sb-end" id="appClock">{{ now()->format('D d M Y H:i') }}</span>
    </footer>
</div>
<div id="appConfirm" class="app-confirm no-print" hidden>
    <div class="app-confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="appConfirmTitle" aria-describedby="appConfirmMsg">
        <div class="app-confirm-title" id="appConfirmTitle">Confirm</div>
        <div class="app-confirm-body" id="appConfirmMsg"></div>
        <div class="app-confirm-actions">
            <button type="button" class="btn btn-primary" id="appConfirmOk">Yes</button>
            <button type="button" class="btn btn-outline-secondary" id="appConfirmCancel">No</button>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.getElementById('appSidebar')?.classList.toggle('open');
});
(function () {
    const overlay = document.getElementById('appConfirm');
    const msgEl = document.getElementById('appConfirmMsg');
    const okBtn = document.getElementById('appConfirmOk');
    const cancelBtn = document.getElementById('appConfirmCancel');
    let pending = null;

    function close(result) {
        overlay.hidden = true;
        const resolve = pending;
        pending = null;
        if (resolve) resolve(result);
    }

    function ask(message) {
        return new Promise((resolve) => {
            pending = resolve;
            msgEl.textContent = message;
            overlay.hidden = false;
            cancelBtn.focus();
        });
    }

    okBtn?.addEventListener('click', () => close(true));
    cancelBtn?.addEventListener('click', () => close(false));
    overlay?.addEventListener('click', (e) => { if (e.target === overlay) close(false); });
    document.addEventListener('keydown', (e) => {
        if (!overlay || overlay.hidden) return;
        if (e.key === 'Escape') { e.preventDefault(); close(false); }
        if (e.key === 'Enter' && document.activeElement !== okBtn) { e.preventDefault(); close(false); }
    });

    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.confirmed === '1') return;
        const method = (form.querySelector('input[name="_method"]')?.value || form.getAttribute('method') || 'get').toUpperCase();
        const custom = form.getAttribute('data-confirm');
        if (!custom && method !== 'DELETE') return;
        if (!form.checkValidity()) return;
        e.preventDefault();
        const ok = await ask(custom || 'Delete this record? This cannot be undone.');
        if (!ok) return;
        form.dataset.confirmed = '1';
        HTMLFormElement.prototype.submit.call(form);
    });
})();
(function () {
    const el = document.getElementById('appClock');
    if (!el) return;
    setInterval(() => {
        const d = new Date();
        el.textContent = d.toLocaleString('en-GB', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }, 30000);
})();
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
