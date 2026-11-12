<aside class="app-sidebar bg-white border-end shadow-sm" data-bs-theme="light">
    <div class="sidebar-brand border-bottom">
        <a href="{{ route('dashboard') }}" class="brand-link">
            <img src="{{ asset('amoleck-logo.png') }}" alt="{{ config('app.name') }}" class="brand-image app-logo-sidebar">
        </a>
    </div>

    <div class="sidebar-wrapper">
        <div class="px-3 pt-2 sidebar-search-wrap">
            <input type="text" id="sidebarSearch" class="form-control form-control-sm" placeholder="Filter menu..." autocomplete="off">
        </div>
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="true">
                @php($isAdmin = Auth::user()->isAdmin())

                <li class="nav-header text-uppercase small text-muted">{{ __('Overview') }}</li>
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon hgi-stroke hgi-dashboard-square-01"></i>
                        <p>{{ __('Dashboard') }}</p>
                    </a>
                </li>

                <li class="nav-header text-uppercase small text-muted mt-1">{{ __('Clinic Operations') }}</li>
                <li class="nav-item">
                    <a href="{{ route('patients.index') }}" class="nav-link {{ request()->routeIs('patients.*') ? 'active' : '' }}">
                        <i class="nav-icon hgi-stroke hgi-patient"></i>
                        <p>{{ __('Patients') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('visits.index') }}" class="nav-link {{ request()->routeIs('visits.*') ? 'active' : '' }}">
                        <i class="nav-icon hgi-stroke hgi-stethoscope-02"></i>
                        <p>{{ __('Visits & Treatment') }}</p>
                    </a>
                </li>
                @if (\App\Support\Settings::bool('appointments_enabled', true))
                    <li class="nav-item">
                        <a href="{{ route('appointments.index') }}" class="nav-link {{ request()->routeIs('appointments.*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-calendar-01"></i>
                            <p>{{ __('Appointments') }}</p>
                        </a>
                    </li>
                @endif
                <li class="nav-item">
                    <a href="{{ route('follow-ups.index') }}" class="nav-link {{ request()->routeIs('follow-ups.*') ? 'active' : '' }}">
                        <i class="nav-icon hgi-stroke hgi-rotate-01"></i>
                        <p>{{ __('Follow-ups') }}</p>
                    </a>
                </li>
                @if ($isAdmin)
                    <li class="nav-item">
                        <a href="{{ route('admin.services.index') }}" class="nav-link {{ request()->routeIs('admin.services.*', 'admin.service-categories.*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-briefcase-01"></i>
                            <p>{{ __('Services & Pricing') }}</p>
                        </a>
                    </li>
                @endif

                @if ($isAdmin || \App\Support\Settings::bool('staff_can_pos'))
                    <li class="nav-header text-uppercase small text-muted mt-1">{{ __('Medical Supplies') }}</li>
                    <li class="nav-item">
                        <a href="{{ route('pos.index') }}" class="nav-link {{ request()->routeIs('pos.*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-shopping-cart-02"></i>
                            <p>{{ __('Point of Sale') }}</p>
                        </a>
                    </li>
                    @if ($isAdmin)
                        <li class="nav-item">
                            <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.*', 'admin.product-categories.*') ? 'active' : '' }}">
                                <i class="nav-icon hgi-stroke hgi-package"></i>
                                <p>{{ __('Products') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.inventory.index') }}" class="nav-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">
                                <i class="nav-icon hgi-stroke hgi-archive"></i>
                                <p>{{ __('Inventory') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.sales.index') }}" class="nav-link {{ request()->routeIs('admin.sales.*') ? 'active' : '' }}">
                                <i class="nav-icon hgi-stroke hgi-invoice-01"></i>
                                <p>{{ __('Sales') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.deliveries.index') }}" class="nav-link {{ request()->routeIs('admin.deliveries.*') ? 'active' : '' }}">
                                <i class="nav-icon hgi-stroke hgi-maps-location-01"></i>
                                <p>{{ __('Deliveries') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.customers.index') }}" class="nav-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                                <i class="nav-icon hgi-stroke hgi-user-group"></i>
                                <p>{{ __('Customers') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.purchases.index') }}" class="nav-link {{ request()->routeIs('admin.purchases.*', 'admin.suppliers.*') ? 'active' : '' }}">
                                <i class="nav-icon hgi-stroke hgi-delivery-truck-01"></i>
                                <p>{{ __('Purchases') }}</p>
                            </a>
                        </li>
                    @endif
                @endif

                <li class="nav-header text-uppercase small text-muted mt-1">{{ __('Financial') }}</li>
                @if ($isAdmin)
                    <li class="nav-item">
                        <a href="{{ route('admin.financials.index') }}" class="nav-link {{ request()->routeIs('admin.financials.*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-analytics-02"></i>
                            <p>{{ __('Financial Overview') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.invoices.index') }}" class="nav-link {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-invoice-01"></i>
                            <p>{{ __('Invoices') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.payments.index') }}" class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-coins-01"></i>
                            <p>{{ __('Payments') }}</p>
                        </a>
                    </li>
                @endif
                <li class="nav-item">
                    <a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') && ! request()->routeIs('admin.*') ? 'active' : '' }}">
                        <i class="nav-icon hgi-stroke hgi-money-02"></i>
                        <p>{{ __('Expenses') }}</p>
                    </a>
                </li>
                @if ($isAdmin)
                    <li class="nav-item">
                        <a href="{{ route('admin.expense-approvals.index') }}" class="nav-link {{ request()->routeIs('admin.expense-approvals.*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-checkmark-badge-01"></i>
                            <p>{{ __('Expense Approvals') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.refunds.index') }}" class="nav-link {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-reverse-withdrawal-01"></i>
                            <p>{{ __('Refunds') }}</p>
                        </a>
                    </li>
                @endif

                @if ($isAdmin)
                    <li class="nav-header text-uppercase small text-muted mt-1">{{ __('Communication') }}</li>
                    <li class="nav-item">
                        <a href="{{ route('admin.communication.index') }}" class="nav-link {{ request()->routeIs('admin.communication.index') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-message-01"></i>
                            <p>{{ __('Comm. Center') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.communication.messages') }}" class="nav-link {{ request()->routeIs('admin.communication.messages*', 'admin.communication.campaigns', 'admin.communication.templates') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-sent"></i>
                            <p>{{ __('Messages') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.communication.rules') }}" class="nav-link {{ request()->routeIs('admin.communication.rules', 'admin.communication.gateway') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-settings-01"></i>
                            <p>{{ __('Messaging Setup') }}</p>
                        </a>
                    </li>
                @endif

                <li class="nav-item">
                    <a href="{{ route('my.workplace') }}" class="nav-link {{ request()->routeIs('my.*') ? 'active' : '' }}">
                        <i class="nav-icon hgi-stroke hgi-user-circle"></i>
                        <p>{{ __('My Workplace') }}</p>
                    </a>
                </li>
                @if (auth()->user()->hasPermission('exec.dashboard'))
                    <li class="nav-item">
                        <a href="{{ route('management.dashboard') }}" class="nav-link {{ request()->routeIs('management.*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-analytics-01"></i>
                            <p>{{ __('Executive') }}</p>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('accounting.dashboard'))
                    <li class="nav-item">
                        <a href="{{ route('accounting.dashboard') }}" class="nav-link {{ request()->routeIs('accounting.*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-calculator-01"></i>
                            <p>{{ __('Accounting') }}</p>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('reception.queue') || auth()->user()->hasPermission('patients.view'))
                    <li class="nav-item">
                        <a href="{{ route('reception.dashboard') }}" class="nav-link {{ request()->routeIs('reception.*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-desk"></i>
                            <p>{{ __('Front Desk') }}</p>
                        </a>
                    </li>
                @endif

                @if ($isAdmin)
                    <li class="nav-header text-uppercase small text-muted mt-1">{{ __('Operations') }}</li>
                    <li class="nav-item">
                        <a href="{{ route('admin.ops.dashboard') }}" class="nav-link {{ request()->routeIs('admin.ops.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-briefcase-01"></i>
                            <p>{{ __('Ops Overview') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.employees.index') }}" class="nav-link {{ request()->routeIs('admin.employees*', 'admin.attendance*', 'admin.leave*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-user-group"></i>
                            <p>{{ __('HR / Employees') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.payroll.index') }}" class="nav-link {{ request()->routeIs('admin.payroll*', 'admin.loans*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-money-03"></i>
                            <p>{{ __('Payroll') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.assets.index') }}" class="nav-link {{ request()->routeIs('admin.assets*', 'admin.asset-categories*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-archive"></i>
                            <p>{{ __('Assets') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.branches.index') }}" class="nav-link {{ request()->routeIs('admin.branches*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-hierarchy"></i>
                            <p>{{ __('Branches') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.batches.index') }}" class="nav-link {{ request()->routeIs('admin.batches*', 'admin.budgets*', 'admin.pettycash*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-package"></i>
                            <p>{{ __('Batches & Budgets') }}</p>
                        </a>
                    </li>
                @endif

                <li class="nav-header text-uppercase small text-muted mt-1">{{ __('Management') }}</li>
                @if ($isAdmin)
                    <li class="nav-item">
                        <a href="{{ route('admin.staff.index') }}" class="nav-link {{ request()->routeIs('admin.staff.*', 'admin.users.*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-user-multiple-02"></i>
                            <p>{{ __('Staff') }}</p>
                        </a>
                    </li>
                @endif
                <li class="nav-item">
                    <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        <i class="nav-icon hgi-stroke hgi-chart"></i>
                        <p>{{ __('Reports & Analytics') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                        <i class="nav-icon hgi-stroke hgi-notification-01"></i>
                        <p>{{ __('Notifications') }}</p>
                    </a>
                </li>
                @if ($isAdmin)
                    <li class="nav-item">
                        <a href="{{ route('admin.access.index') }}" class="nav-link {{ request()->routeIs('admin.access.*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-shield-user"></i>
                            <p>{{ __('Staff Access') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.audit.index') }}" class="nav-link {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-shield-01"></i>
                            <p>{{ __('Audit Logs') }}</p>
                        </a>
                    </li>
                @endif

                @if ($isAdmin)
                    <li class="nav-header text-uppercase small text-muted mt-1">{{ __('Configuration') }}</li>
                    <li class="nav-item">
                        <a href="{{ route('admin.settings.edit') }}" class="nav-link {{ request()->routeIs('admin.settings.*', 'admin.expense-categories.*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-setting-07"></i>
                            <p>{{ __('Settings') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <i class="nav-icon hgi-stroke hgi-user-lock-01"></i>
                            <p>{{ __('User Accounts') }}</p>
                        </a>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
</aside>
