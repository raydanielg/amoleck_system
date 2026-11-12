<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ExpenseApprovalController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\FinancialController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\VisitController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Patients
    Route::resource('patients', PatientController::class)->except(['destroy']);
    Route::get('patients/{patient}/print', [PatientController::class, 'print'])->name('patients.print');

    // Visits
    Route::get('patients/{patient}/visits/create', [VisitController::class, 'create'])->name('patients.visits.create')->middleware('perm:clinical.view');
    Route::post('patients/{patient}/visits', [VisitController::class, 'store'])->name('patients.visits.store')->middleware('perm:clinical.view');
    Route::resource('visits', VisitController::class)->only(['index', 'show', 'edit', 'update'])->middleware('perm:clinical.view');
    Route::post('visits/{visit}/complete', [VisitController::class, 'complete'])->name('visits.complete')->middleware('perm:clinical.view');
    Route::get('visits/{visit}/print', [VisitController::class, 'print'])->name('visits.print')->middleware('perm:clinical.view');

    // Reception / Front Desk (permission-gated)
    Route::middleware('perm:reception.queue,patients.view')->prefix('reception')->name('reception.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ReceptionController::class, 'dashboard'])->name('dashboard');
        Route::post('check-in', [\App\Http\Controllers\ReceptionController::class, 'checkIn'])->name('checkin')->middleware('perm:reception.queue');
        Route::post('queue/{patientQueue}', [\App\Http\Controllers\ReceptionController::class, 'updateQueue'])->name('queue.update')->middleware('perm:reception.queue');
    });

    // Staff Self-Service (any authenticated user; per-action permission checks inside)
    Route::prefix('my')->name('my.')->group(function () {
        Route::get('/', [\App\Http\Controllers\MyWorkplaceController::class, 'index'])->name('workplace');
        Route::post('clock-in', [\App\Http\Controllers\MyWorkplaceController::class, 'clockIn'])->name('clockin');
        Route::post('clock-out', [\App\Http\Controllers\MyWorkplaceController::class, 'clockOut'])->name('clockout');
        Route::post('leave', [\App\Http\Controllers\MyWorkplaceController::class, 'submitLeave'])->name('leave');
        Route::post('requests', [\App\Http\Controllers\MyWorkplaceController::class, 'submitRequest'])->name('requests');
        Route::get('payslips/{payrollItem}', [\App\Http\Controllers\MyWorkplaceController::class, 'payslip'])->name('payslip');
    });

    // Expenses
    Route::resource('expenses', ExpenseController::class)->except(['destroy']);
    Route::get('expenses/{expense}/receipt', [ExpenseController::class, 'receipt'])->name('expenses.receipt');

    // Appointments & follow-ups
    Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::post('appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::put('appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');

    Route::get('follow-ups', [FollowUpController::class, 'index'])->name('follow-ups.index');
    Route::post('follow-ups', [FollowUpController::class, 'store'])->name('follow-ups.store');
    Route::patch('follow-ups/{followUp}/status', [FollowUpController::class, 'updateStatus'])->name('follow-ups.status');

    // POS (admin, or staff when staff_can_pos enabled)
    Route::middleware('pos.access')->group(function () {
        Route::get('pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('pos', [PosController::class, 'checkout'])->name('pos.checkout');
        Route::get('pos/sales/{sale}/receipt', [PosController::class, 'receipt'])->name('pos.receipt');
        Route::post('pos/customers', [\App\Http\Controllers\Admin\CustomerController::class, 'store'])->name('pos.customers.store');
    });

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // Management / Executive Intelligence (permission-gated)
    Route::middleware('perm:exec.dashboard')->prefix('management')->name('management.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ManagementController::class, 'dashboard'])->name('dashboard');
        Route::post('insights/refresh', [\App\Http\Controllers\ManagementController::class, 'refreshInsights'])->name('insights.refresh');
        Route::post('insights/{insight}/acknowledge', [\App\Http\Controllers\ManagementController::class, 'acknowledgeInsight'])->name('insights.ack');
        Route::post('kpis', [\App\Http\Controllers\ManagementController::class, 'storeKpi'])->name('kpis.store');
        Route::get('report.pdf', [\App\Http\Controllers\ManagementController::class, 'reportPdf'])->name('report.pdf');
    });

    // Accounting workspace (permission-gated: accountant = CLINIC_STAFF + accounting.* perms)
    Route::middleware('perm:accounting.dashboard')->prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AccountingController::class, 'dashboard'])->name('dashboard');
        Route::get('journals', [\App\Http\Controllers\AccountingController::class, 'journals'])->name('journals');
        Route::post('journals', [\App\Http\Controllers\AccountingController::class, 'storeJournal'])->name('journals.store');
        Route::post('accounts', [\App\Http\Controllers\AccountingController::class, 'storeAccount'])->name('accounts.store');
        Route::get('reports', [\App\Http\Controllers\AccountingController::class, 'reports'])->name('reports');
        Route::post('periods/close', [\App\Http\Controllers\AccountingController::class, 'closePeriod'])->name('periods.close');
    });
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{type}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('reports/{type}/export/{format}', [ReportController::class, 'export'])->name('reports.export');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    Route::get('password/force-change', [\App\Http\Controllers\Auth\ForcePasswordChangeController::class, 'edit'])->name('password.force.edit');
    Route::put('password/force-change', [\App\Http\Controllers\Auth\ForcePasswordChangeController::class, 'update'])->name('password.force.update');
});

// Admin-only routes
Route::middleware(['auth', 'role:ADMIN'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('patients/{patient}/restore', [PatientController::class, 'restore'])->name('patients.restore');
    Route::post('visits/{visit}/void', [VisitController::class, 'void'])->name('visits.void');
    Route::post('expenses/{expense}/void', [ExpenseController::class, 'void'])->name('expenses.void');
    Route::post('patients/{patient}/archive', [PatientController::class, 'archive'])->name('patients.archive');

    Route::resource('service-categories', ServiceCategoryController::class)->except(['show', 'destroy']);
    Route::resource('services', ServiceController::class)->except(['destroy']);
    Route::post('services/{service}/toggle', [ServiceController::class, 'toggle'])->name('services.toggle');
    Route::resource('expense-categories', ExpenseCategoryController::class)->except(['show', 'destroy']);
    Route::post('expense-categories/{expense_category}/toggle', [ExpenseCategoryController::class, 'toggle'])->name('expense-categories.toggle');

    // Financial management
    Route::get('financials', [FinancialController::class, 'index'])->name('financials.index');
    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::post('visits/{visit}/invoice', [InvoiceController::class, 'storeFromVisit'])->name('invoices.from-visit');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::get('invoices/{invoice}/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::get('invoices/{invoice}/preview/frame', [InvoiceController::class, 'previewFrame'])->name('invoices.preview-frame');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('invoices.payments.store');
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::get('payments/{payment}/print', [PaymentController::class, 'print'])->name('payments.print');
    Route::post('payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
    Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
    Route::get('refunds', [RefundController::class, 'index'])->name('refunds.index');
    Route::post('payments/{payment}/refund', [RefundController::class, 'store'])->name('payments.refund');

    // Expense approval center
    Route::get('expense-approvals', [ExpenseApprovalController::class, 'index'])->name('expense-approvals.index');
    Route::get('expense-approvals/{expense}', [ExpenseApprovalController::class, 'show'])->name('expense-approvals.show');
    Route::post('expense-approvals/{expense}/decide', [ExpenseApprovalController::class, 'decide'])->name('expense-approvals.decide');
    Route::post('expense-approvals/{expense}/comment', [ExpenseApprovalController::class, 'comment'])->name('expense-approvals.comment');

    // Staff management
    Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('staff/{user}', [StaffController::class, 'show'])->name('staff.show');

    Route::resource('users', UserController::class)->except(['show', 'destroy']);
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');

    Route::get('access', [\App\Http\Controllers\Admin\AccessController::class, 'index'])->name('access.index');
    Route::post('access/users', [\App\Http\Controllers\Admin\AccessController::class, 'store'])->name('access.users.store');
    Route::put('access/users/{user}/role', [\App\Http\Controllers\Admin\AccessController::class, 'updateRole'])->name('access.users.role');
    Route::post('access/users/{user}/permission', [\App\Http\Controllers\Admin\AccessController::class, 'setPermission'])->name('access.users.permission');

    // Enterprise Operations (HR, Payroll, Assets, Branches)
    Route::get('operations', [\App\Http\Controllers\Admin\OpsController::class, 'dashboard'])->name('ops.dashboard');
    Route::get('employees', [\App\Http\Controllers\Admin\EmployeeController::class, 'index'])->name('employees.index');
    Route::post('employees', [\App\Http\Controllers\Admin\EmployeeController::class, 'store'])->name('employees.store');
    Route::get('employees/{employee}', [\App\Http\Controllers\Admin\EmployeeController::class, 'show'])->name('employees.show');
    Route::put('employees/{employee}', [\App\Http\Controllers\Admin\EmployeeController::class, 'update'])->name('employees.update');
    Route::post('employees/{employee}/components', [\App\Http\Controllers\Admin\EmployeeController::class, 'storeComponent'])->name('employees.components.store');
    Route::post('employees/{employee}/leave', [\App\Http\Controllers\Admin\EmployeeController::class, 'submitLeave'])->name('employees.leave.store');
    Route::post('attendance', [\App\Http\Controllers\Admin\EmployeeController::class, 'recordAttendance'])->name('attendance.store');
    Route::post('leave-requests/{leaveRequest}/decide', [\App\Http\Controllers\Admin\EmployeeController::class, 'decideLeave'])->name('leave.decide');
    Route::post('departments', [\App\Http\Controllers\Admin\EmployeeController::class, 'storeDepartment'])->name('departments.store');
    Route::post('positions', [\App\Http\Controllers\Admin\EmployeeController::class, 'storePosition'])->name('positions.store');

    Route::get('payroll', [\App\Http\Controllers\Admin\PayrollController::class, 'index'])->name('payroll.index');
    Route::post('payroll/batches', [\App\Http\Controllers\Admin\PayrollController::class, 'createBatch'])->name('payroll.batches.store');
    Route::get('payroll/{payrollBatch}', [\App\Http\Controllers\Admin\PayrollController::class, 'show'])->name('payroll.show');
    Route::post('payroll/{payrollBatch}/approve', [\App\Http\Controllers\Admin\PayrollController::class, 'approve'])->name('payroll.approve');
    Route::post('payroll/{payrollBatch}/close', [\App\Http\Controllers\Admin\PayrollController::class, 'close'])->name('payroll.close');
    Route::post('payroll-items/{payrollItem}/pay', [\App\Http\Controllers\Admin\PayrollController::class, 'pay'])->name('payroll.items.pay');
    Route::get('payroll-items/{payrollItem}/payslip', [\App\Http\Controllers\Admin\PayrollController::class, 'payslip'])->name('payroll.payslip');
    Route::post('payroll-rules', [\App\Http\Controllers\Admin\PayrollController::class, 'storeRule'])->name('payroll.rules.store');
    Route::post('payroll-rules/{payrollRule}/toggle', [\App\Http\Controllers\Admin\PayrollController::class, 'toggleRule'])->name('payroll.rules.toggle');
    Route::post('loans', [\App\Http\Controllers\Admin\PayrollController::class, 'storeLoan'])->name('loans.store');

    Route::get('assets', [\App\Http\Controllers\Admin\AssetController::class, 'index'])->name('assets.index');
    Route::post('assets', [\App\Http\Controllers\Admin\AssetController::class, 'store'])->name('assets.store');
    Route::get('assets/{asset}', [\App\Http\Controllers\Admin\AssetController::class, 'show'])->name('assets.show');
    Route::post('assets/{asset}/assign', [\App\Http\Controllers\Admin\AssetController::class, 'assign'])->name('assets.assign');
    Route::post('assets/{asset}/return', [\App\Http\Controllers\Admin\AssetController::class, 'returnAsset'])->name('assets.return');
    Route::post('assets/{asset}/maintenance', [\App\Http\Controllers\Admin\AssetController::class, 'maintenance'])->name('assets.maintenance');
    Route::post('assets/{asset}/dispose', [\App\Http\Controllers\Admin\AssetController::class, 'dispose'])->name('assets.dispose');
    Route::post('asset-categories', [\App\Http\Controllers\Admin\AssetController::class, 'storeCategory'])->name('asset-categories.store');

    Route::get('branches', [\App\Http\Controllers\Admin\BranchController::class, 'index'])->name('branches.index');
    Route::post('branches', [\App\Http\Controllers\Admin\BranchController::class, 'store'])->name('branches.store');
    Route::get('branches/{branch}', [\App\Http\Controllers\Admin\BranchController::class, 'show'])->name('branches.show');

    Route::get('inventory-batches', [\App\Http\Controllers\Admin\OpsController::class, 'batches'])->name('batches.index');
    Route::post('inventory-batches', [\App\Http\Controllers\Admin\OpsController::class, 'storeBatch'])->name('batches.store');
    Route::get('budgets', [\App\Http\Controllers\Admin\OpsController::class, 'budgets'])->name('budgets.index');
    Route::post('budgets', [\App\Http\Controllers\Admin\OpsController::class, 'storeBudget'])->name('budgets.store');
    Route::get('petty-cash', [\App\Http\Controllers\Admin\OpsController::class, 'pettyCash'])->name('pettycash.index');
    Route::post('petty-cash', [\App\Http\Controllers\Admin\OpsController::class, 'storePettyCash'])->name('pettycash.store');

    // Communication Center
    Route::get('communication', [\App\Http\Controllers\Admin\CommunicationController::class, 'index'])->name('communication.index');
    Route::get('communication/gateway', [\App\Http\Controllers\Admin\GatewaySettingController::class, 'edit'])->name('communication.gateway');
    Route::put('communication/gateway', [\App\Http\Controllers\Admin\GatewaySettingController::class, 'update'])->name('communication.gateway.update');
    Route::post('communication/gateway/test', [\App\Http\Controllers\Admin\GatewaySettingController::class, 'testSms'])->name('communication.gateway.test');
    Route::get('communication/messages', [\App\Http\Controllers\Admin\MessageController::class, 'index'])->name('communication.messages');
    Route::post('communication/messages', [\App\Http\Controllers\Admin\MessageController::class, 'store'])->name('communication.messages.store');
    Route::get('communication/messages/{message}', [\App\Http\Controllers\Admin\MessageController::class, 'show'])->name('communication.messages.show');
    Route::post('communication/messages/{message}/retry', [\App\Http\Controllers\Admin\MessageController::class, 'retry'])->name('communication.messages.retry');
    Route::get('communication/templates', [\App\Http\Controllers\Admin\MessageTemplateController::class, 'index'])->name('communication.templates');
    Route::post('communication/templates', [\App\Http\Controllers\Admin\MessageTemplateController::class, 'store'])->name('communication.templates.store');
    Route::put('communication/templates/{messageTemplate}', [\App\Http\Controllers\Admin\MessageTemplateController::class, 'update'])->name('communication.templates.update');
    Route::post('communication/templates/{messageTemplate}/toggle', [\App\Http\Controllers\Admin\MessageTemplateController::class, 'toggle'])->name('communication.templates.toggle');
    Route::get('communication/campaigns', [\App\Http\Controllers\Admin\CampaignController::class, 'index'])->name('communication.campaigns');
    Route::post('communication/campaigns', [\App\Http\Controllers\Admin\CampaignController::class, 'store'])->name('communication.campaigns.store');
    Route::get('communication/rules', [\App\Http\Controllers\Admin\NotificationRuleController::class, 'index'])->name('communication.rules');
    Route::put('communication/rules/{notificationRule}', [\App\Http\Controllers\Admin\NotificationRuleController::class, 'update'])->name('communication.rules.update');

    // Medical Equipment & Supplies
    Route::resource('product-categories', ProductCategoryController::class)->except(['show', 'destroy']);
    Route::post('product-categories/{product_category}/toggle', [ProductCategoryController::class, 'toggle'])->name('product-categories.toggle');
    Route::resource('products', ProductController::class)->only(['index', 'store', 'update']);
    Route::post('products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
    Route::post('inventory/{product}/receive', [InventoryController::class, 'receive'])->name('inventory.receive');
    Route::post('inventory/{product}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'update']);
    Route::post('suppliers/{supplier}/toggle', [SupplierController::class, 'toggle'])->name('suppliers.toggle');
    Route::resource('customers', CustomerController::class)->only(['index', 'store', 'update']);
    Route::get('purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('purchases', [PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
    Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
    Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
    Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::post('sales/{sale}/pay', [SaleController::class, 'pay'])->name('sales.pay');
    Route::post('sales/{sale}/refund', [SaleController::class, 'refund'])->name('sales.refund');
    Route::post('sales/{sale}/return', [SaleController::class, 'returnItems'])->name('sales.return');
    Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
    Route::get('sales/{sale}/receipt.pdf', [SaleController::class, 'receiptPdf'])->name('sales.receipt-pdf');

    // Fulfilment & delivery tracking
    Route::get('deliveries', [\App\Http\Controllers\Admin\FulfilmentController::class, 'index'])->name('deliveries.index');
    Route::post('fulfilments/service/{fulfilment}/step', [\App\Http\Controllers\Admin\FulfilmentController::class, 'serviceStep'])->name('fulfilments.service-step');
    Route::post('fulfilments/product/{fulfilment}/step', [\App\Http\Controllers\Admin\FulfilmentController::class, 'productStep'])->name('fulfilments.product-step');
    Route::post('fulfilments/correct', [\App\Http\Controllers\Admin\FulfilmentController::class, 'correct'])->name('fulfilments.correct');
});


// External document link (token-authenticated, no session)
Route::get('docs/l/{token}', [\App\Http\Controllers\DocumentController::class, 'linkForm'])->name('documents.link');
Route::post('docs/l/{token}', [\App\Http\Controllers\DocumentController::class, 'linkAccess'])->name('documents.link.access');

// Physiotherapy assessments
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('patients/{patient}/assessments', [\App\Http\Controllers\AssessmentController::class, 'index'])->name('assessments.index');
    Route::get('patients/{patient}/assessments/create', [\App\Http\Controllers\AssessmentController::class, 'create'])->name('assessments.create');
    Route::post('patients/{patient}/assessments', [\App\Http\Controllers\AssessmentController::class, 'store'])->name('assessments.store');
    Route::get('assessments/{assessment}', [\App\Http\Controllers\AssessmentController::class, 'show'])->name('assessments.show');
    Route::get('assessments/{assessment}/edit', [\App\Http\Controllers\AssessmentController::class, 'edit'])->name('assessments.edit');
    Route::patch('assessments/{assessment}', [\App\Http\Controllers\AssessmentController::class, 'update'])->name('assessments.update');
    Route::get('assessments/{assessment}/print', [\App\Http\Controllers\AssessmentController::class, 'print'])->name('assessments.print');
});

// ================= Document Center & Document Board =================
Route::middleware(['auth', 'verified'])->prefix('documents')->name('documents.')->group(function () {
    Route::get('/', [\App\Http\Controllers\DocumentController::class, 'index'])->name('index');
    Route::post('/folders', [\App\Http\Controllers\DocumentController::class, 'storeFolder'])->name('folders.store');
    Route::post('/upload', [\App\Http\Controllers\DocumentController::class, 'upload'])->name('upload');
    Route::get('/board', [\App\Http\Controllers\DocumentController::class, 'board'])->name('board');
    Route::get('/board/{announcement}', [\App\Http\Controllers\DocumentController::class, 'boardShow'])->name('board.show');
    Route::post('/board/{announcement}/acknowledge', [\App\Http\Controllers\DocumentController::class, 'acknowledge'])->name('board.ack');

    Route::get('/{document}', [\App\Http\Controllers\DocumentController::class, 'show'])->name('show');
    Route::get('/{document}/preview', [\App\Http\Controllers\DocumentController::class, 'preview'])->name('preview');
    Route::get('/{document}/download/{version?}', [\App\Http\Controllers\DocumentController::class, 'download'])->name('download');
    Route::patch('/{document}', [\App\Http\Controllers\DocumentController::class, 'update'])->name('update');
    Route::post('/{document}/version', [\App\Http\Controllers\DocumentController::class, 'uploadVersion'])->name('version');
    Route::post('/{document}/star', [\App\Http\Controllers\DocumentController::class, 'star'])->name('star');
    Route::post('/{document}/share', [\App\Http\Controllers\DocumentController::class, 'share'])->name('share');
    Route::post('/{document}/link', [\App\Http\Controllers\DocumentController::class, 'createLink'])->name('link.create');
    Route::post('/{document}/permission', [\App\Http\Controllers\DocumentController::class, 'setPermission'])->name('permission');
    Route::delete('/{document}', [\App\Http\Controllers\DocumentController::class, 'destroy'])->name('destroy');
    Route::post('/trash/{id}/restore', [\App\Http\Controllers\DocumentController::class, 'restore'])->name('restore');
    Route::delete('/trash/{id}', [\App\Http\Controllers\DocumentController::class, 'forceDelete'])->name('force-delete');
    Route::post('/shares/{share}/revoke', [\App\Http\Controllers\DocumentController::class, 'revokeShare'])->name('shares.revoke');

});

// Admin: Document Board management
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('document-board', [\App\Http\Controllers\Admin\DocumentBoardController::class, 'index'])->name('document-board.index');
    Route::post('document-board', [\App\Http\Controllers\Admin\DocumentBoardController::class, 'store'])->name('document-board.store');
    Route::patch('document-board/{announcement}', [\App\Http\Controllers\Admin\DocumentBoardController::class, 'update'])->name('document-board.update');
    Route::post('document-board/{announcement}/publish', [\App\Http\Controllers\Admin\DocumentBoardController::class, 'publish'])->name('document-board.publish');
    Route::post('document-board/{announcement}/archive', [\App\Http\Controllers\Admin\DocumentBoardController::class, 'archive'])->name('document-board.archive');
    Route::post('document-board/{announcement}/remind', [\App\Http\Controllers\Admin\DocumentBoardController::class, 'remind'])->name('document-board.remind');
    Route::get('document-board/{announcement}/receipts', [\App\Http\Controllers\Admin\DocumentBoardController::class, 'receipts'])->name('document-board.receipts');
});

require __DIR__.'/auth.php';

// SMS delivery report webhook (secret-authenticated, no session)
Route::post('webhooks/sms/{secret}', [\App\Http\Controllers\SmsWebhookController::class, 'handle'])->name('webhooks.sms');
