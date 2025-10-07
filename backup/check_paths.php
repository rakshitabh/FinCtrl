<?php
// Check PHP include paths and file existence

// Set to display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>PHP Path and File Check</h1>";
echo "<pre>";

// Check include path
echo "PHP Include Path:\n";
echo get_include_path() . "\n\n";

// Check if vendor directory is accessible
echo "Checking vendor directory:\n";
$vendorDir = __DIR__ . '/vendor';
if (is_dir($vendorDir)) {
    echo "✓ Vendor directory exists at: $vendorDir\n";
    
    // Check autoload.php
    $autoloadFile = $vendorDir . '/autoload.php';
    if (file_exists($autoloadFile)) {
        echo "✓ autoload.php exists\n";
    } else {
        echo "✗ autoload.php is missing!\n";
    }
    
    // Check PHPMailer directory
    $phpMailerDir = $vendorDir . '/phpmailer/phpmailer';
    if (is_dir($phpMailerDir)) {
        echo "✓ PHPMailer directory exists\n";
        
        // Check PHPMailer class file
        $phpMailerFile = $phpMailerDir . '/src/PHPMailer.php';
        if (file_exists($phpMailerFile)) {
            echo "✓ PHPMailer.php exists\n";
        } else {
            echo "✗ PHPMailer.php is missing!\n";
        }
        
        // Check SMTP class file
        $smtpFile = $phpMailerDir . '/src/SMTP.php';
        if (file_exists($smtpFile)) {
            echo "✓ SMTP.php exists\n";
        } else {
            echo "✗ SMTP.php is missing!\n";
        }
        
        // Check Exception class file
        $exceptionFile = $phpMailerDir . '/src/Exception.php';
        if (file_exists($exceptionFile)) {
            echo "✓ Exception.php exists\n";
        } else {
            echo "✗ Exception.php is missing!\n";
        }
    } else {
        echo "✗ PHPMailer directory is missing!\n";
    }
} else {
    echo "✗ Vendor directory is missing!\n";
}

echo "\n";

// Try to load PHPMailer classes
echo "Attempting to load PHPMailer classes:\n";
try {
    require_once $vendorDir . '/autoload.php';
    echo "✓ Autoloader included successfully\n";
    
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "✓ PHPMailer class is available\n";
    } else {
        echo "✗ PHPMailer class is not available!\n";
    }
    
    if (class_exists('PHPMailer\\PHPMailer\\SMTP')) {
        echo "✓ SMTP class is available\n";
    } else {
        echo "✗ SMTP class is not available!\n";
    }
    
    if (class_exists('PHPMailer\\PHPMailer\\Exception')) {
        echo "✓ Exception class is available\n";
    } else {
        echo "✗ Exception class is not available!\n";
    }
} catch (Exception $e) {
    echo "✗ Error loading classes: " . $e->getMessage() . "\n";
}

echo "\n";

// Check if the SMTP configuration file exists
echo "Checking SMTP configuration:\n";
$smtpConfigFile = __DIR__ . '/smtp_config.php';
if (file_exists($smtpConfigFile)) {
    echo "✓ smtp_config.php exists\n";
    
    // Load and validate SMTP configuration
    $smtp_config = include $smtpConfigFile;
    
    if (is_array($smtp_config)) {
        echo "✓ SMTP configuration loaded successfully\n";
        
        // Check required settings
        $requiredSettings = [
            'smtp_host', 'smtp_port', 'smtp_secure', 
            'smtp_username', 'smtp_password', 'smtp_from_email'
        ];
        
        $missingSettings = [];
        foreach ($requiredSettings as $setting) {
            if (!isset($smtp_config[$setting]) || empty($smtp_config[$setting])) {
                $missingSettings[] = $setting;
            }
        }
        
        if (empty($missingSettings)) {
            echo "✓ All required SMTP settings are present\n";
        } else {
            echo "✗ Missing required SMTP settings: " . implode(', ', $missingSettings) . "\n";
        }
    } else {
        echo "✗ SMTP configuration is not valid!\n";
    }
} else {
    echo "✗ smtp_config.php is missing!\n";
}

echo "</pre>";

echo "<h2>Next Steps</h2>";
echo "<p>After confirming all paths are correct, try sending a test email:</p>";
echo "<ul>";
echo "<li><a href='direct_email_test.php'>Run direct PHPMailer test</a></li>";
echo "</ul>";
?>