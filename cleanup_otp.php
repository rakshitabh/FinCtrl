<?php
/**
 * OTP Cleanup Script
 * 
 * This script removes expired and verified OTP records from the database.
 * It should be run periodically via cron job.
 * 
 * Example cron job (runs every hour):
 * 0 * * * * /usr/bin/php /path/to/Finance/cleanup_otp.php
 */

// Include database connection
require_once __DIR__ . '/includes/database.php';

try {
    // Get database instance
    $db = Database::getInstance();
    
    // Start transaction
    $db->beginTransaction();
    
    // Delete expired OTPs
    $expired = $db->delete(
        'otp_verifications',
        'expires_at < NOW()',
        []
    );
    
    // Delete verified OTPs that are older than 24 hours
    $verified = $db->delete(
        'otp_verifications',
        'verified = TRUE AND created_at < NOW() - INTERVAL \'24 hours\'',
        []
    );
    
    // Commit transaction
    $db->commit();
    
    echo "Cleanup complete: Removed $expired expired and $verified verified OTP records.\n";
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollback();
    }
    
    echo "Error during cleanup: " . $e->getMessage() . "\n";
    exit(1);
}
?>