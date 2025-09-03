<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Admin Dashboard - CRM</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            line-height: 1.6;
        }
        
        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #333;
        }
        
        .header .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
            transition: background 0.3s;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .card p {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .quick-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .action-btn {
            flex: 1;
            min-width: 120px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .header {
                padding: 1rem;
            }
            
            .header .user-menu {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CRM Dashboard</h1>
        <div class="user-menu">
            <span>Welcome, Admin</span>
            <a href="/admin/awais-mehnga/logout" class="btn btn-danger">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="dashboard-grid">
            <!-- Overview Card -->
            <div class="card">
                <h3>Overview</h3>
                <div class="stats">
                    <div class="stat-item">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Total Customers</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Active Leads</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Closed Deals</div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions Card -->
            <div class="card">
                <h3>Quick Actions</h3>
                <p>Manage your CRM efficiently with these shortcuts</p>
                <div class="quick-actions">
                    <a href="#" class="btn btn-primary action-btn">Add Customer</a>
                    <a href="#" class="btn btn-primary action-btn">New Lead</a>
                    <a href="#" class="btn btn-primary action-btn">View Reports</a>
                </div>
            </div>
            
            <!-- System Info Card -->
            <div class="card">
                <h3>System Information</h3>
                <p><strong>Environment:</strong> <?= env('APP_ENV', 'local') ?></p>
                <p><strong>Application:</strong> <?= env('APP_NAME', 'CRM') ?></p>
                <p><strong>Version:</strong> 1.0.0</p>
                <p><strong>Last Login:</strong> <?= date('Y-m-d H:i:s') ?></p>
            </div>
            
            <!-- Getting Started Card -->
            <div class="card">
                <h3>Getting Started</h3>
                <p>Welcome to your minimal CRM system. You can now start building your customer management features.</p>
                <ul style="margin: 1rem 0; padding-left: 1.5rem; color: #666;">
                    <li>Create customer management routes</li>
                    <li>Build lead tracking system</li>
                    <li>Add reporting features</li>
                    <li>Implement email integration</li>
                </ul>
            </div>
        </div>
        
        <!-- Recent Activity Section -->
        <div class="card">
            <h3>Recent Activity</h3>
            <p style="color: #666; font-style: italic;">No recent activity to display. Start using your CRM to see activity here.</p>
        </div>
    </div>
</body>
</html>
