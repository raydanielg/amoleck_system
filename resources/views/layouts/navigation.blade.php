@php $authUser = Auth::user(); @endphp
<nav class="app-header navbar navbar-expand bg-body border-bottom">
    <div class="container-fluid">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                    <i class="hgi-stroke hgi-menu-01 fs-5"></i>
                </a>
            </li>
        </ul>

        <span class="navbar-text d-none d-md-inline fw-semibold text-truncate" style="max-width: 40vw;">
            {{ \App\Support\Settings::get('clinic_name', config('app.name')) }}
        </span>

        <ul class="navbar-nav ms-auto">
            @php
                $unreadCount = $authUser->notifications()->whereNull('read_at')->count();
                $latestNotifications = $authUser->notifications()->limit(6)->get();
            @endphp
            <li class="nav-item dropdown">
                <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-label="Notifications">
                    <i class="hgi-stroke hgi-notification-01 fs-5"></i>
                    @if ($unreadCount > 0)
                        <span class="position-absolute translate-middle badge rounded-pill bg-danger notif-badge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end notif-dropdown">
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <span class="fw-semibold small">{{ __('Notifications') }}</span>
                        @if ($unreadCount > 0)
                            <form method="POST" action="{{ route('notifications.read-all') }}">
                                @csrf
                                <button class="btn btn-link btn-sm p-0 text-decoration-none">{{ __('Mark all read') }}</button>
                            </form>
                        @endif
                    </div>
                    @forelse ($latestNotifications as $notification)
                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                            @csrf
                            <button type="submit" class="dropdown-item py-2 text-wrap {{ $notification->isRead() ? 'text-muted' : 'fw-semibold' }}">
                                <div class="small">{{ $notification->title }}</div>
                                <div class="text-muted text-truncate" style="font-size: 0.75rem;">{{ $notification->message }}</div>
                            </button>
                        </form>
                    @empty
                        <span class="dropdown-item-text small text-muted py-3 d-block text-center">{{ __('No notifications') }}</span>
                    @endforelse
                    <div class="border-top">
                        <a href="{{ route('notifications.index') }}" class="dropdown-item text-center small py-2">{{ __('View all') }}</a>
                    </div>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle user-menu d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                    @if ($authUser->avatarUrl())
                        <img src="{{ $authUser->avatarUrl() }}" alt="{{ $authUser->name }}" class="user-avatar-img">
                    @else
                        <span class="user-avatar">{{ $authUser->initials() }}</span>
                    @endif
                    <span class="d-none d-sm-flex flex-column align-items-start lh-1 me-1">
                        <span class="fw-semibold user-menu-name">{{ Auth::user()->name }}</span>
                        <span class="user-menu-role">{{ Auth::user()->isAdmin() ? 'Administrator' : 'Clinic Staff' }}</span>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end user-dropdown">
                    <li class="dropdown-header px-3 py-2">
                        <div class="fw-semibold">{{ Auth::user()->name }}</div>
                        <div class="small text-muted">{{ Auth::user()->email }}</div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                            <i class="hgi-stroke hgi-user me-2"></i>{{ __('Profile') }}
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="hgi-stroke hgi-logout-01 me-2"></i>{{ __('Log Out') }}
                            </button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
