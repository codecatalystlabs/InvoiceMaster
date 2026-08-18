<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\CashBookController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CanteenController;
use App\Http\Controllers\CanteenMonthController;
use App\Http\Controllers\CanteenReviewController;
use App\Http\Controllers\ChangeRequestController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EfrisController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PettyCashController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PublicPaymentController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RequisitionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TrailController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VatController;
use Illuminate\Support\Facades\Route;

Route::bind('cashbook', fn ($value) => \App\Models\CashBookEntry::findOrFail($value));
Route::bind('budget', fn ($value) => \App\Models\AnnualBudget::findOrFail($value));
Route::bind('allocation', fn ($value) => \App\Models\BudgetAllocation::findOrFail($value));
Route::bind('payroll', fn ($value) => \App\Models\PayrollRun::findOrFail($value));
Route::bind('bank', fn ($value) => \App\Models\BankAccount::findOrFail($value));

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/invite/{token}', [AuthController::class, 'showAcceptInvite'])->name('invite.accept');
    Route::post('/invite/{token}', [AuthController::class, 'acceptInvite']);
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::post('/pay/webhook/{provider}', [PublicPaymentController::class, 'webhook'])->name('pay.webhook');
Route::get('/pay/{token}/thanks', [PublicPaymentController::class, 'thanks'])->name('pay.thanks');
Route::get('/pay/{token}/wait', [PublicPaymentController::class, 'wait'])->name('pay.wait');
Route::get('/pay/{token}/status', [PublicPaymentController::class, 'status'])->name('pay.status');
Route::get('/pay/{token}', [PublicPaymentController::class, 'show'])->name('pay.show');
Route::post('/pay/{token}', [PublicPaymentController::class, 'store'])->name('pay.store');
Route::get('/portal/{token}', [PortalController::class, 'show'])->name('portal.show');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    Route::middleware('module:canteen')->group(function () {
        Route::get('/canteen/today', [CanteenController::class, 'today'])->name('canteen.today');
        Route::post('/canteen/today', [CanteenController::class, 'store'])->name('canteen.store');
        Route::get('/canteen', [CanteenController::class, 'index'])->name('canteen.index');
        Route::get('/canteen/{meal}', [CanteenController::class, 'show'])->name('canteen.show');
        Route::get('/canteen/{meal}/request', [CanteenController::class, 'requestForm'])->name('canteen.request');
        Route::post('/canteen/{meal}/request', [CanteenController::class, 'requestStore'])->name('canteen.request.store');
    });

    Route::middleware('module:canteen.review')->group(function () {
        Route::get('/canteen-review', [CanteenReviewController::class, 'index'])->name('canteen.review');
        Route::post('/canteen/{meal}/approve', [CanteenReviewController::class, 'approve'])->name('canteen.approve');
        Route::post('/canteen/{meal}/refuse', [CanteenReviewController::class, 'refuse'])->name('canteen.refuse');
        Route::post('/canteen-review/bulk', [CanteenReviewController::class, 'bulkApprove'])->name('canteen.bulk');
    });

    Route::middleware('module:canteen.catalog')->group(function () {
        Route::get('/canteen-catalog', [CanteenReviewController::class, 'catalog'])->name('canteen.catalog');
        Route::post('/canteen-catalog', [CanteenReviewController::class, 'storeItem'])->name('canteen.items.store');
        Route::put('/canteen-catalog/{canteen_item}', [CanteenReviewController::class, 'updateItem'])->name('canteen.items.update');
        Route::delete('/canteen-catalog/{canteen_item}', [CanteenReviewController::class, 'destroyItem'])->name('canteen.items.destroy');
    });

    Route::middleware('module:canteen.close')->group(function () {
        Route::get('/canteen-month', [CanteenMonthController::class, 'show'])->name('canteen.month');
        Route::post('/canteen-month', [CanteenMonthController::class, 'close'])->name('canteen.month.close');
    });

    Route::middleware('module:requests')->group(function () {
        Route::get('/requests', [ChangeRequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/{change_request}', [ChangeRequestController::class, 'show'])->name('requests.show');
        Route::post('/requests/{change_request}/approve', [ChangeRequestController::class, 'approve'])->name('requests.approve');
        Route::post('/requests/{change_request}/refuse', [ChangeRequestController::class, 'refuse'])->name('requests.refuse');
    });

    Route::middleware('module:clients')->group(function () {
        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
        Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
    });

    Route::middleware('module:quotations')->group(function () {
        Route::resource('quotations', QuotationController::class);
        Route::post('/quotations/{quotation}/convert', [QuotationController::class, 'convert'])->name('quotations.convert');
        Route::get('/quotations/{quotation}/pdf', [QuotationController::class, 'pdf'])->name('quotations.pdf');
        Route::get('/quotations/{quotation}/email', [QuotationController::class, 'emailForm'])->name('quotations.email');
        Route::post('/quotations/{quotation}/email', [QuotationController::class, 'sendEmail'])->name('quotations.email.send');
    });

    Route::middleware('module:invoices')->group(function () {
        Route::resource('invoices', InvoiceController::class);
        Route::post('/invoices/{invoice}/paid', [InvoiceController::class, 'markPaid'])->name('invoices.paid');
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::get('/invoices/{invoice}/docx', [InvoiceController::class, 'docx'])->name('invoices.docx');
        Route::get('/invoices/{invoice}/email', [InvoiceController::class, 'emailForm'])->name('invoices.email');
        Route::post('/invoices/{invoice}/email', [InvoiceController::class, 'sendEmail'])->name('invoices.email.send');
    });

    Route::middleware('module:receipts')->group(function () {
        Route::resource('receipts', ReceiptController::class);
        Route::get('/receipts/{receipt}/pdf', [ReceiptController::class, 'pdf'])->name('receipts.pdf');
        Route::get('/receipts/{receipt}/docx', [ReceiptController::class, 'docx'])->name('receipts.docx');
        Route::get('/receipts/{receipt}/email', [ReceiptController::class, 'emailForm'])->name('receipts.email');
        Route::post('/receipts/{receipt}/email', [ReceiptController::class, 'sendEmail'])->name('receipts.email.send');
    });

    Route::middleware('module:emails')->group(function () {
        Route::get('/emails', [EmailController::class, 'index'])->name('emails.index');
        Route::get('/emails/compose', [EmailController::class, 'compose'])->name('emails.compose');
        Route::post('/emails/compose', [EmailController::class, 'send'])->name('emails.send');
        Route::post('/emails/sync', [EmailController::class, 'sync'])->name('emails.sync');
        Route::get('/emails/{emailMessage}/attachments/{attachment}', [EmailController::class, 'attachment'])->name('emails.attachment');
        Route::get('/emails/{emailMessage}', [EmailController::class, 'show'])->name('emails.show');
    });

    Route::middleware('module:cashbook')->group(function () {
        Route::resource('cashbook', CashBookController::class)->parameters(['cashbook' => 'cashbook']);
        Route::get('/cashbook/{cashbook}/pdf', [CashBookController::class, 'pdf'])->name('cashbook.pdf');
    });

    Route::middleware('module:expenses')->resource('expenses', ExpenseController::class);
    Route::middleware('module:assets')->group(function () {
        Route::resource('assets', AssetController::class);
        Route::post('/assets/{asset}/value', [AssetController::class, 'value'])->name('assets.value');
    });
    Route::middleware('module:services')->group(function () {
        Route::resource('services', ServiceController::class);
        Route::post('/services/{service}/pay', [ServiceController::class, 'pay'])->name('services.pay');
    });
    Route::middleware('module:accounts')->group(function () {
        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::put('/accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
        Route::delete('/accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');
    });
    Route::middleware('module:ledger')->group(function () {
        Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
        Route::get('/ledger/preview', [LedgerController::class, 'preview'])->name('ledger.preview');
        Route::get('/ledger/pdf', [LedgerController::class, 'pdf'])->name('ledger.pdf');
        Route::post('/ledger/rebuild', [LedgerController::class, 'rebuild'])->name('ledger.rebuild');
    });
    Route::middleware('module:receivables')->group(function () {
        Route::get('/receivables', [ReceivableController::class, 'index'])->name('receivables.index');
        Route::post('/receivables/{invoice}/remind', [ReceivableController::class, 'remind'])->name('receivables.remind');
    });
    Route::middleware('module:employees')->resource('employees', EmployeeController::class)->except(['show']);
    Route::middleware('module:payroll')->group(function () {
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/create', [PayrollController::class, 'create'])->name('payroll.create');
        Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
        Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::post('/payroll/{payroll}/post', [PayrollController::class, 'post'])->name('payroll.post');
        Route::get('/payroll/{payroll}/bulk-pay', [PayrollController::class, 'bulkPay'])->name('payroll.bulk');
        Route::get('/payslips/{item}', [PayrollController::class, 'payslip'])->name('payroll.payslip');
    });
    Route::middleware('module:banks')->group(function () {
        Route::get('/banks', [BankAccountController::class, 'index'])->name('banks.index');
        Route::post('/banks', [BankAccountController::class, 'store'])->name('banks.store');
        Route::get('/banks/{bank}/statements', [BankAccountController::class, 'statements'])->name('banks.statements');
        Route::post('/banks/{bank}/statements', [BankAccountController::class, 'import'])->name('banks.import');
    });
    Route::middleware('module:vat')->get('/vat', [VatController::class, 'index'])->name('vat.index');
    Route::middleware('module:bills')->group(function () {
        Route::get('/bills', [BillController::class, 'index'])->name('bills.index');
        Route::get('/bills/create', [BillController::class, 'create'])->name('bills.create');
        Route::post('/bills', [BillController::class, 'store'])->name('bills.store');
        Route::get('/bills/{bill}', [BillController::class, 'show'])->name('bills.show');
        Route::post('/bills/{bill}/pay', [BillController::class, 'pay'])->name('bills.pay');
    });
    Route::middleware('module:projects')->group(function () {
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    });
    Route::middleware('module:efris')->group(function () {
        Route::get('/efris', [EfrisController::class, 'index'])->name('efris.index');
        Route::post('/invoices/{invoice}/efris', [EfrisController::class, 'queue'])->name('invoices.efris');
    });
    Route::middleware('module:analytics')->get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::middleware('module:reports')->get('/reports/financial', [ReportController::class, 'financial'])->name('reports.financial');
    Route::middleware('module:exports')->group(function () {
        Route::get('/exports', [ExportController::class, 'index'])->name('exports.index');
        Route::get('/exports/{type}', [ExportController::class, 'download'])->name('exports.download');
    });
    Route::middleware('module:departments')->group(function () {
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    });

    Route::middleware('module:budgets')->group(function () {
        Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
        Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
        Route::get('/budgets/{budget}', [BudgetController::class, 'show'])->name('budgets.show');
        Route::post('/budgets/{budget}/approve', [BudgetController::class, 'approve'])->name('budgets.approve');
        Route::post('/budgets/{budget}/allocate', [BudgetController::class, 'allocate'])->name('budgets.allocate');
        Route::delete('/budget-allocations/{allocation}', [BudgetController::class, 'destroyAllocation'])->name('budgets.allocations.destroy');
    });

    Route::middleware('module:requisitions')->group(function () {
        Route::get('/requisitions', [RequisitionController::class, 'index'])->name('requisitions.index');
        Route::get('/requisitions/create', [RequisitionController::class, 'create'])->name('requisitions.create');
        Route::post('/requisitions', [RequisitionController::class, 'store'])->name('requisitions.store');
        Route::get('/requisitions/{requisition}', [RequisitionController::class, 'show'])->name('requisitions.show');
        Route::post('/requisitions/{requisition}/initiate', [RequisitionController::class, 'initiate'])->name('requisitions.initiate');
        Route::post('/requisitions/{requisition}/approve', [RequisitionController::class, 'approve'])->name('requisitions.approve');
        Route::post('/requisitions/{requisition}/reject', [RequisitionController::class, 'reject'])->name('requisitions.reject');
        Route::post('/requisitions/{requisition}/disburse', [RequisitionController::class, 'disburse'])->name('requisitions.disburse');
        Route::post('/requisitions/{requisition}/account', [RequisitionController::class, 'account'])->name('requisitions.account');
        Route::post('/requisitions/{requisition}/close', [RequisitionController::class, 'close'])->name('requisitions.close');
    });

    Route::middleware('module:petty-cash')->group(function () {
        Route::get('/petty-cash', [PettyCashController::class, 'index'])->name('petty-cash.index');
        Route::get('/petty-cash/create', [PettyCashController::class, 'createForm'])->name('petty-cash.create');
        Route::post('/petty-cash', [PettyCashController::class, 'store'])->name('petty-cash.store');
        Route::get('/petty-cash/{petty_cash_fund}', [PettyCashController::class, 'show'])->name('petty-cash.show');
        Route::post('/petty-cash/{petty_cash_fund}/topup', [PettyCashController::class, 'topup'])->name('petty-cash.topup');
    });

    Route::middleware('module:trail')->get('/trail', [TrailController::class, 'index'])->name('trail.index');

    Route::middleware('module:settings')->group(function () {
        Route::get('/settings', [SettingsController::class, 'company'])->name('settings.company');
        Route::put('/settings', [SettingsController::class, 'updateCompany'])->name('settings.update');
        Route::post('/settings/invite', [SettingsController::class, 'invite'])->name('settings.invite');
    });
    Route::middleware('module:users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
    Route::middleware('module:audit')->get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
});
