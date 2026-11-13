<nav style="display:flex;gap:16px;margin-bottom:20px;font-size:14px">
    <a href="{{ route('admin.users.index') }}" style="color:{{ request()->routeIs('admin.users.*') ? '#e8eaf0' : '#4f7ef8' }};text-decoration:none;font-weight:600">Usuarios</a>
    <a href="{{ route('admin.reports.create') }}" style="color:{{ request()->routeIs('admin.reports.*') ? '#e8eaf0' : '#4f7ef8' }};text-decoration:none;font-weight:600">Poner pin</a>
</nav>
