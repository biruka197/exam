<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam System - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-start: #667eea;
            --primary-end: #764ba2;
            --primary-gradient: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; color: #333; }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: var(--primary-gradient); color: white; position: fixed; height: 100%; overflow-y: auto; z-index: 1000; transition: transform 0.3s ease; }
        .sidebar-header { padding: 2rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { font-size: 1.3rem; margin-bottom: 0.5rem; }
        .sidebar-header p { font-size: 0.9rem; opacity: 0.8; }
        .sidebar-nav { padding: 1rem 0; }
        .nav-link { display: flex; align-items: center; padding: 1rem 1.5rem; color: white; text-decoration: none; transition: all 0.3s ease; }
        .nav-link:hover { background: rgba(255,255,255,0.1); padding-left: 2rem; }
        .nav-link.active { background: rgba(255,255,255,0.15); border-right: 4px solid white; }
        .nav-link i { margin-right: 1rem; width: 20px; text-align: center; }
        .sidebar-footer { position: absolute; bottom: 0; width: 100%; padding: 1rem 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .logout-btn { display: flex; align-items: center; width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.1); color: white; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; transition: background 0.3s ease; }
        .logout-btn:hover { background: rgba(255,255,255,0.2); }
        .main-content { flex: 1; margin-left: 280px; padding: 2rem; }
        .page-header { background: white; padding: 1.5rem 2rem; border-radius: 12px; box-shadow: 0 2px 20px rgba(0,0,0,0.08); margin-bottom: 2rem; }
        h1, h2, h3 { color: #2d3748; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 20px rgba(0,0,0,0.08); position: relative; overflow: hidden; border-left: 4px solid var(--primary-start); }
        .stat-card h3 { font-size: 2.5rem; color: var(--primary-start); margin-bottom: 0.5rem; }
        .stat-card p { color: #718096; font-weight: 500; }
        .content-section { background: white; border-radius: 12px; box-shadow: 0 2px 20px rgba(0,0,0,0.08); margin-bottom: 2rem; overflow: hidden; }
        .section-header { background: var(--primary-gradient); color: white; padding: 1.5rem 2rem; font-size: 1.1rem; font-weight: 600; }
        .section-content { padding: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; transition: border-color 0.3s ease; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--primary-start); box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .btn { padding: 1rem 1.5rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: var(--primary-gradient); color: white; }
        .btn-danger { background: #e53e3e; color: white; }
        .btn-success { background: #38a169; color: white; }
        .table-container { overflow-x: auto; margin-top: 1.5rem; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .table th { background: #f7fafc; font-weight: 600; }
        .alert { padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid; }
        .alert-success { background: #f0fff4; color: #22543d; border-color: #38a169; }
        .alert-error { background: #fed7d7; color: #742a2a; border-color: #e53e3e; }
        .course-category h2 { font-size: 1.5rem; margin-top: 2rem; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0; }
        
        #drop-area { border: 2px dashed #ccc; border-radius: 8px; padding: 2rem; text-align: center; transition: all 0.3s ease; background-color: #f8f9fa; }
        #drop-area.highlight { border-color: var(--primary-start); background-color: #e9eafc; }
        #drop-area p { color: #666; }
        #file-input { display: none; }
        #file-name { margin-top: 1rem; font-weight: bold; color: var(--primary-start); }

        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
        .modal-content { background-color: white; margin: 5% auto; padding: 2rem; border-radius: 12px; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; margin-bottom: 1rem; }
        .close-btn { font-size: 2rem; cursor: pointer; color: #718096; }

        .mobile-toggle { display: none; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1rem; }
            .mobile-toggle { display: block; position: fixed; top: 1rem; left: 1rem; z-index: 1001; background: var(--primary-start); color: white; border: none; padding: 0.8rem; border-radius: 8px; }
        }
    </style>
</head>
<body>