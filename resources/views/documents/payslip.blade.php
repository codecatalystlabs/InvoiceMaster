<html>
<head>
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
h1 { font-size: 18px; margin: 0 0 8px; }
table { width: 100%; border-collapse: collapse; margin-top: 12px; }
td { padding: 4px 0; }
.muted { color: #555; }
.total { font-size: 16px; font-weight: bold; }
.money { font-family: "Courier New", Courier, monospace; font-weight: 600; }
</style>
</head>
<body>
<h1>{{ $company->name }}</h1>
<div class="muted">Payslip · {{ $item->run->periodLabel() }} · {{ $item->employee->number }}</div>
<p><strong>{{ $item->employee->name }}</strong><br>{{ $item->employee->job_title }}</p>
<table>
<tr><td>Basic</td><td style="text-align:right;">{{ money($item->basic, $company) }}</td></tr>
<tr><td>Allowances</td><td style="text-align:right;">{{ money($item->allowances, $company) }}</td></tr>
<tr><td>Gross</td><td style="text-align:right;">{{ money($item->gross, $company) }}</td></tr>
<tr><td>PAYE</td><td style="text-align:right;">({{ money($item->paye, $company) }})</td></tr>
<tr><td>NSSF (employee 5%)</td><td style="text-align:right;">({{ money($item->nssf_employee, $company) }})</td></tr>
<tr><td>Local Service Tax</td><td style="text-align:right;">({{ money($item->lst, $company) }})</td></tr>
<tr><td>Canteen recovery</td><td style="text-align:right;">({{ money($item->canteen, $company) }})</td></tr>
<tr><td class="total">Net pay</td><td class="total" style="text-align:right;">{{ money($item->net, $company) }}</td></tr>
</table>
<p class="muted">Employer NSSF (10%): {{ money($item->nssf_employer, $company) }} · Paid via {{ $item->employee->pay_method }} {{ $item->employee->pay_account }}</p>
</body>
</html>
