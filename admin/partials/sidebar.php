<button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-graduation-cap"></i> Exam System</h2>
        <p>Admin Dashboard</p>
    </div>

    <nav class="sidebar-nav">
        <a href="?page=dashboard" class="nav-link <?php if ($current_page == 'dashboard')
            echo 'active'; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="?page=ai_assistant" class="nav-link <?php if ($current_page == 'ai_assistant')
            echo 'active'; ?>"><i class="fas fa-robot"></i> AI Assistant</a>
        <a href="?page=courses" class="nav-link <?php if ($current_page == 'courses')
            echo 'active'; ?>"><i class="fas fa-book"></i> Courses</a>
        <a href="?page=manage_study_modules" class="nav-link <?php if ($current_page == 'manage_study_modules')
            echo 'active'; ?>"><i class="fas fa-book-reader"></i> Study Modules</a>
        <a href="?page=admins" class="nav-link <?php if ($current_page == 'admins')
            echo 'active'; ?>"><i class="fas fa-users-cog"></i> Admin Users</a>
        <a href="?page=reports" class="nav-link <?php if ($current_page == 'reports')
            echo 'active'; ?>"><i class="fas fa-exclamation-triangle"></i> Error Reports</a>
        <a href="?page=online_users" class="nav-link <?php if ($current_page == 'online_users')
            echo 'active'; ?>"><i class="fas fa-users"></i> Online Users</a>

    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>