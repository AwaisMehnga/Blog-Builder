<nav>
    <div class="nav-container">
        <a href="/" class="logo">BilloCraft</a>
        <ul class="nav-links">
            <li><a href="/">Home</a></li>
            <li><a href="/a">About</a></li>
            <?php if (auth()->check()): ?>
                <li><a href="/dashboard">Dashboard</a></li>
                <li><a href="/logout">Logout</a></li>
            <?php else: ?>
                <li><a href="/auth">Login</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
