<?php

use Illuminate\Support\Facades\{Auth, Route, Artisan};
use App\Http\Controllers\Admin\{
    DashboardController,
    AccountsController,
    ExpensesController,
    ReportsController,
    UsersController,
    RolesController,
    PermissionsController,
    CurrenciesController,
    TransactionsController,
    JournalController,
    SettingsController,
    AccountCategoriesController,
    AccountSubCategoriesController,
    ExpenseCategoryController,
    AuditLogController,
    ExchangeController,
    ExchangeRateController,
    RemittanceController,
    RemittanceModalController,
    SafeController
};

// --- Redirect Logic ---
Route::get('/', fn() => Auth::check() ? redirect()->route('admin.dashboard') : redirect()->route('login'));

// --- Authenticated Admin Routes ---
Route::middleware(['auth'])->prefix('admin')->as('admin.')->group(function () {

    Route::prefix('reports/aed')->as('reports.aed.')
        ->middleware('permission.feedback:view customers reports')
        ->controller(\App\Http\Controllers\Admin\AedReportController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/pdf', 'pdf')->name('pdf');
            Route::post('/', 'store')->name('store');
            Route::patch('/{entry}', 'update')->name('update');
            Route::delete('/{entry}', 'destroy')->name('destroy');
        });

    // System Utilities
    Route::get('/recalculate-balances', [ReportsController::class, 'recalculateBalances'])
        ->name('reports.recalculate_balances');

    // Dashboard
    Route::controller(DashboardController::class)->prefix('dashboard')->group(function () {
        Route::get('/', 'index')->name('dashboard')->middleware('permission.feedback:view dashboard');
        Route::post('/refresh-balances', 'refreshBalances')->name('dashboard.refresh-balances');
        Route::get('/transactions', 'transactionChartData');
        Route::get('/profit-chart', 'profitChartData');
    });

    // Accounts Management
    Route::prefix('accounts')->as('accounts.')->controller(AccountsController::class)->group(function () {
        // Core CRUD & View Logic
        Route::get('/', 'index')->name('index')->middleware('permission.feedback:view customers');
        Route::post('/', 'store')->name('store')->middleware('permission.feedback:create customers');
        Route::get('/fetch-accounts', 'fetchAccounts')->name('fetch-accounts');
        Route::get('/update-accounts-stats', 'updateStats')->name('update-stats');
        Route::get('/check-code', 'checkCode')->name('checkCode');
        Route::get('/bulk-import/template', 'downloadImportTemplate')->name('bulk-import.template')->middleware('permission.feedback:create customers');
        Route::post('/bulk-import', 'bulkImport')->name('bulk-import')->middleware('permission.feedback:create customers');

        // Specific Account Actions (Order matters: static paths first)
        Route::get('/subcategories/{categoryId}', 'getSubcategories')->name('subcategories');
        Route::get('/category-prefix/{id}', 'getCategoryPrefix')->name('category-prefix');
        Route::post('/create-user', 'createUserForAccount')->name('create-user');

        // Instance Routes
        Route::prefix('{id}')->group(function () {
            Route::get('/', 'show')->name('show');
            Route::get('/edit', 'edit')->name('edit');
            Route::put('/', 'update')->name('update');
            Route::delete('/', 'destroy')->name('destroy');

            // Export & Utility
            Route::get('/print', 'print')->name('print');
            Route::get('/export/pdf', 'exportPdf2')->name('export.pdf');
            Route::get('/export-pdf', 'exportPdf')->name('exportPdf');
            Route::get('/export/image', 'exportImage')->name('export.image');
            Route::get('/export/view', 'exportView')->name('export.view');
            Route::get('/send-whatsapp', 'sendWhatsApp')->name('send-whatsapp');
            Route::get('/info', 'getAccountInfo')->name('info');
            Route::get('/details', 'showAccount')->name('showAccount');
            Route::get('/balances', 'getBalancesByAccount')->name('getBalances');
            Route::get('/transactions/{type}', 'transactionData')->name('transactionData');
            Route::get('/transactions-actual/{type}', 'transactionActualData')->name('transactionActualData');
            Route::get('/balance/{currency}', 'getCurrencyBalance')->name('currencyBalance');
            Route::get('/summary/filter', 'filteredSummary')->name('summary.filter');
        })->middleware('permission.feedback:view customers');

        Route::get('/{account}/whatsapp-balance', [AccountsController::class, 'whatsappBalance'])->name('whatsappBalance');
    });

    // Expenses
    Route::prefix('expenses')->as('expenses.')->controller(ExpensesController::class)->group(function () {
        Route::get('/data', 'data')->name('data');
        Route::get('/summary', 'summary')->name('summary');
        Route::get('/export', 'export')->name('export');
        Route::resource('/', ExpensesController::class)->parameters(['' => 'expense'])->except(['create', 'show']);
    })->middleware('permission.feedback:view expenses');

    // Safe Management
    Route::prefix('safe')->as('safe.')->controller(SafeController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/data', 'data')->name('data');
        Route::get('/summary', 'summary')->name('summary');
        Route::get('/daily-report', 'dailyReport')->name('daily-report');
        Route::get('/ledger', 'ledger')->name('ledger');
        Route::get('/ledger/print', 'printLedger')->name('ledger.print');
    });

    // Reports
    Route::prefix('reports')->as('reports.')->controller(ReportsController::class)->group(function () {

        Route::get('/customers', 'customers')->name('customers');
        Route::get('/customers/data', 'customersData')->name('customers.data');
        Route::get('/customers/print', [ReportsController::class, 'customersPrint'])->name('customers.print');
        Route::get('/customers-by-sub-category', 'getCustomersBySubCategory')->name('getCustomersBySubCategory');

        Route::get('/creditors', 'creditors')->name('creditors');
        Route::get('/creditors/data', 'creditorsData')->name('creditors.data');
        Route::get('/creditors/export/pdf', 'creditorsPdf')->name('creditors.export.pdf');

        Route::get('/debtors', 'debtors')->name('debtors');
        Route::get('/debtors/data', 'debtorsData')->name('debtors.data');
        Route::get('/debtors/export/pdf', 'debtorsPdf')->name('debtors.export.pdf');

        Route::get('/currency', 'currency')->name('currency');
        Route::get('/currency/data', 'currencyData')->name('currency.data');
        Route::get('/currency/export/pdf', 'currencyPdf')->name('currency.export.pdf');

        Route::get('/profit-loss', 'profitLoss')->name('profit-loss');
        Route::get('/profit-loss/data', 'profitLossData')->name('profit-loss.data');

        Route::get('/transactions', 'transactions')->name('transactions');
        Route::get('/transactions/data', 'transactionsData')->name('transactions.data');
        Route::get('/transactions/export/excel', 'transactionsExcel')->name('transactions.export.excel');

        Route::get('/balance', [ReportsController::class, 'balance'])->name('balance');
        Route::get('/balance/data', [ReportsController::class, 'balanceData'])->name('balance.data');
        Route::get('/balance/rates', [ReportsController::class, 'balanceRates'])->name('balance.rates');
        Route::post('/balance/print', [ReportsController::class, 'balancePrint'])->name('balance.print');

        Route::post('balance/close', [ReportsController::class, 'closeDay'])->name('balance.close');

        Route::get('/customer-exchange-report', [ReportsController::class, 'customerExchangeReport'])->name('customer-exchange-report');
        Route::get('/customer-exchange-report/data', [ReportsController::class, 'customerExchangeReportData'])->name('customer-exchange-report.data');
        Route::get('/customer-exchange-report/print', [ReportsController::class, 'customerExchangeReportPrint'])->name('customer-exchange-report.print');
        Route::get('/currency-exchange-report', [ReportsController::class, 'currencyExchangeReport'])->name('currency-exchange-report');
        Route::get('/currency-exchange-report/data', [ReportsController::class, 'currencyExchangeReportData'])->name('currency-exchange-report.data');
        Route::get('/currency-exchange-report/print', [ReportsController::class, 'currencyExchangeReportPrint'])->name('currency-exchange-report.print');
    });

    // User & Access Management
    Route::prefix('users')->as('users.')->controller(UsersController::class)->group(function () {
        Route::get('/fetch', 'fetchUsers')->name('fetch-users');
        Route::resource('/', UsersController::class)->parameters(['' => 'id']);
        Route::get('/{id}/update-status', 'updateStatus')->name('update-status');
        Route::put('/{id}/update-password', 'updatePassword')->name('update-password');
        Route::post('/{id}/assign-permissions', 'assignPermissions')->name('assign-permissions');
        Route::post('/{id}/update-permissions', 'updatePermissions')->name('update-permissions');
    });

    Route::resource('roles', RolesController::class);
    Route::get('roles/{role}/permissions-json', [PermissionsController::class, 'rolePermissionsJson']);

    Route::prefix('permissions')->as('permissions.')->group(function () {
        Route::get('/data', [PermissionsController::class, 'data'])->name('data');
        Route::post('/assign', [PermissionsController::class, 'assign'])->name('assign');
        Route::resource('/', PermissionsController::class);
    });

    // Currencies
    Route::prefix('currencies')->as('currencies.')->controller(CurrenciesController::class)->group(function () {
        Route::get('/fetch', 'fetch')->name('fetch');
        Route::post('/{currency}/toggle', 'toggleActive')->name('toggle');
        Route::resource('/', CurrenciesController::class)->parameters(['' => 'currency']);
    });

    // Transactions
    Route::prefix('transactions')->as('transactions.')->controller(TransactionsController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/data', 'data')->name('data');
        Route::get('/summary', 'summary')->name('summary');
        Route::get('/export', 'export')->name('export');
        Route::get('/account-balances', 'getAccountBalances')->name('account-balances');
        Route::patch('/bulk-meta', 'bulkMeta')->name('bulk-meta')->middleware('permission.feedback:update transactions');
        Route::get('/get-accounts-by-sub-category', 'getAccountsBySubCategory')->name('getAccountById');
        Route::get('/fetch/{id}', 'fetch')->name('fetch');
        Route::get('/{transaction}/print', 'print')->name('print');
        Route::post('/', 'store')->name('store');
        Route::put('/{linkedId}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');

        Route::post('/modal-store', [TransactionsController::class, 'modalStore'])->name('modal.store');
    });

    Route::get('/transactions/source-edit/{transaction}', [AccountsController::class, 'sourceEdit'])
        ->name('transactions.source-edit');

    Route::put('/modal-remittances/{remittance}', [RemittanceModalController::class, 'update'])
        ->name('modal-remittances.update');

    Route::put('/exchanges/modal-update/{exchange}', [ExchangeController::class, 'modalUpdate'])
        ->name('exchanges.modal-update');

    // Journal & Logs
    Route::prefix('journal')->as('journal.')->controller(JournalController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/data', 'data')->name('data');
        Route::get('/summary', 'summary')->name('summary');
        Route::get('/print', 'print')->name('print');
        Route::get('/{transaction}/send-whatsapp', 'sendWhatsApp')->name('send-whatsapp');

        Route::delete('/{transaction}', 'destroy')->name('destroy');
    });

    Route::prefix('remittances')->as('remittances.')->controller(RemittanceController::class)->group(function () {
        Route::get('/next-hawala', 'nextHawala')->name('nextHawala');
        Route::get('/create-new', 'createNew')->name('createNew');
        Route::get('/outgoing', 'outgoing')->name('outgoing');
        Route::get('/incoming-paid', 'incomingPaid')->name('incomingPaid');
        Route::get('/pending-incoming', 'pendingIncoming')->name('pendingIncoming');

        Route::get('/data', 'data')->name('data');
        Route::get('/account-balances', 'getAccountBalances')->name('account-balances');

        Route::post('/mark-paid/{id}', 'markPaid')->name('markPaid');
        Route::post('/reject/{id}', 'reject')->name('reject');

        Route::get('/{remittance}/payment', 'payment')->name('payment');
        Route::get('/{remittance}/payment-receipt', 'paymentReceipt')->name('payment-receipt');
        Route::get('/{remittance}/payment-evidence/{kind}', 'paymentEvidence')->name('payment-evidence');

        Route::get('/{remittance}/send-whatsapp', 'sendWhatsApp')->name('send-whatsapp');
        Route::get('/{remittance}/print', 'print')->name('print');

        Route::post('/', 'store')->name('store');
        Route::delete('/{remittance}', 'destroy')->name('destroy');

        Route::get('/account-report/{accountId}', 'accountReport')->name('account-report');
        Route::post('/modal-store', 'modalStore')->name('modal.store');
    });

    Route::prefix('modal-remittances')->as('modal-remittances.')->controller(RemittanceModalController::class)->group(function () {
        Route::get('/next-hawala', 'nextHawala')->name('nextHawala');
        Route::get('/create-new', 'createNew')->name('createNew');
        Route::get('/outgoing', 'outgoing')->name('outgoing');
        Route::get('/incoming-paid', 'incomingPaid')->name('incomingPaid');
        Route::get('/pending-incoming', 'pendingIncoming')->name('pendingIncoming');

        Route::get('/data', 'data')->name('data');
        Route::get('/account-balances', 'getAccountBalances')->name('account-balances');

        Route::post('/mark-paid/{id}', 'markPaid')->name('markPaid');
        Route::post('/reject/{id}', 'reject')->name('reject');

        Route::get('/{remittance}/print', 'print')->name('print');

        Route::post('/', 'store')->name('store');
        Route::delete('/{remittance}', 'destroy')->name('destroy');

        Route::get('/account-report/{accountId}', 'accountReport')->name('account-report');
    });

    Route::get('/exchanges/rate', [ExchangeController::class, 'getSmartRate'])->name('exchanges.rate');

    Route::prefix('exchange-rates')->as('exchange-rates.')->controller(ExchangeRateController::class)->group(function () {
        Route::get('/list', 'list')->name('list');
        Route::post('/store', 'store')->name('store');
        Route::delete('/{id}', 'destroy')->name('destroy');

        Route::get('/', 'index')->name('index');
    });

    Route::prefix('exchanges')->as('exchanges.')->controller(ExchangeController::class)->group(function () {
        Route::get('/create', 'create')->name('create');
        Route::get('/data', 'data')->name('data');
        Route::post('/store', 'store')->name('store');
        Route::get('/{exchange}/edit', 'edit')->name('edit');
        Route::put('/{exchange}', 'update')->name('update');
        Route::delete('/{exchange}', 'destroy')->name('destroy');

        // fetch account balances
        Route::get('/balances', 'balances')->name('balances');
        Route::get('/market-rate', 'marketRate')->name('market-rate');
        Route::get('/smart-rate', 'getSmartRate')->name('smart-rate');
        Route::get('/', 'index')->name('index');
        Route::get('/{exchange}', 'show')->name('show');

        Route::post('/modal-store', 'modalStore')->name('modal-store');
    });


    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('/audit-logs/data', [AuditLogController::class, 'data'])->name('audit.data');

    // System Settings
    Route::prefix('settings')->as('settings.')->controller(SettingsController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'update')->name('update'); // This creates 'admin.settings.update'
    })->middleware('permission.feedback:view settings');
    // Account Categories
    Route::prefix('account-categories')->as('account-categories.')->group(function () {
        Route::get('/data', [AccountCategoriesController::class, 'data'])->name('data');
        Route::resource('/', AccountCategoriesController::class)->parameters(['' => 'account_category']);
    });

    // Account Sub Categories
    Route::prefix('account-sub-categories')->as('account-sub-categories.')->group(function () {
        Route::get('/data', [AccountSubCategoriesController::class, 'data'])->name('data');
        Route::resource('/', AccountSubCategoriesController::class)->parameters(['' => 'account_sub_category']);
    });

    // Expense Categories
    Route::prefix('expense-categories')->as('expense-categories.')->group(function () {
        Route::get('/data', [ExpenseCategoryController::class, 'data'])->name('data');
        Route::resource('/', ExpenseCategoryController::class)->parameters(['' => 'expense_category']);
    });
});
