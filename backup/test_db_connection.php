<?php
// Include the database class
require_once __DIR__ . '/includes/database.php';

// Handle form submission for testing connection
$message = '';
$status = '';
$showForm = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get database instance
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Check connection by running a test query
        $stmt = $conn->query("SELECT version()");
        $version = $stmt->fetchColumn();
        
        $message = "Connection successful! PostgreSQL version: " . $version;
        $status = 'success';
        
        // Test the tables
        $tables = ['users', 'categories', 'accounts', 'transactions'];
        $tableResults = [];
        
        foreach ($tables as $table) {
            try {
                $count = $db->fetchOne("SELECT COUNT(*) as count FROM $table");
                $tableResults[$table] = [
                    'exists' => true,
                    'count' => $count['count'] ?? 0
                ];
            } catch (PDOException $e) {
                $tableResults[$table] = [
                    'exists' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
    } catch (PDOException $e) {
        $message = "Connection failed: " . $e->getMessage();
        $status = 'error';
    }
    $showForm = false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinCtrl Database Test</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-color);
            text-decoration: none;
        }
        .logo-img {
            height: 40px;
            margin-right: 10px;
        }
        .result-box {
            margin: 30px 0;
            padding: 20px;
            border-radius: 5px;
            background-color: #f8f9fa;
            border-left: 5px solid;
        }
        .success {
            border-color: var(--success-color);
            background-color: rgba(76, 175, 80, 0.1);
        }
        .error {
            border-color: var(--danger-color);
            background-color: rgba(244, 67, 54, 0.1);
        }
        .next-steps {
            margin-top: 30px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
        }
        .next-steps h3 {
            margin-top: 0;
        }
        .action-links {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .config-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-family: monospace;
        }
        .icon-success {
            color: var(--success-color);
        }
        .icon-error {
            color: var(--danger-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="index.html" class="logo">
                <img src="assets/images/logo.svg" alt="FinCtrl Logo" class="logo-img">
                FinCtrl
            </a>
            <h1>Database Connection Test</h1>
            <p>Test your PostgreSQL database connection</p>
        </div>
        
        <?php if ($showForm): ?>
            <div class="config-details">
                <?php 
                $config = include __DIR__ . '/db_config.php';
                echo "<strong>Host:</strong> {$config['host']}<br>";
                echo "<strong>Port:</strong> {$config['port']}<br>";
                echo "<strong>Database:</strong> {$config['database']}<br>";
                echo "<strong>Username:</strong> {$config['username']}<br>";
                echo "<strong>Schema:</strong> {$config['schema']}<br>";
                ?>
            </div>
            
            <form method="post" action="test_db_connection.php">
                <p>Click the button below to test the database connection with the above settings.</p>
                <button type="submit" class="btn btn-primary">Test Connection</button>
            </form>
        <?php else: ?>
            <div class="result-box <?php echo $status; ?>">
                <h3>
                    <?php if ($status === 'success'): ?>
                        <i class="fas fa-check-circle icon-success"></i> Success!
                    <?php else: ?>
                        <i class="fas fa-times-circle icon-error"></i> Error
                    <?php endif; ?>
                </h3>
                <p><?php echo $message; ?></p>
            </div>
            
            <?php if ($status === 'success' && isset($tableResults)): ?>
                <h3>Table Status:</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Status</th>
                            <th>Records</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tableResults as $table => $result): ?>
                            <tr>
                                <td><?php echo $table; ?></td>
                                <td>
                                    <?php if ($result['exists']): ?>
                                        <i class="fas fa-check-circle icon-success"></i> Exists
                                    <?php else: ?>
                                        <i class="fas fa-times-circle icon-error"></i> Missing
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($result['exists']) {
                                        echo $result['count'];
                                    } else {
                                        echo '<span class="icon-error">Error: ' . htmlspecialchars(substr($result['error'], 0, 50)) . '...</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="next-steps">
                    <h3>Next Steps</h3>
                    <ol>
                        <li>Update the OTP and Password Reset functionality to use the database instead of sessions</li>
                        <li>Implement the user registration/login system to store user data in the database</li>
                        <li>Set up the database repositories for financial data management</li>
                    </ol>
                </div>
            <?php endif; ?>
            
            <div class="action-links">
                <a href="test_db_connection.php" class="btn">Test Again</a>
                <a href="db_setup_guide.md" class="btn">Setup Guide</a>
                <a href="index.html" class="btn btn-primary">Back to Home</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>