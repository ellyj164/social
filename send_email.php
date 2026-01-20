<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get data from POST - support both direct fields and user_id lookup
$userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$selectedName = isset($_POST['selected_name']) ? trim($_POST['selected_name']) : '';

// Get device fingerprint data
$deviceFingerprint = trim($_POST['device_fingerprint'] ?? '');
$screenResolution = trim($_POST['screen_resolution'] ?? '');
$timezone = trim($_POST['timezone'] ?? '');
$language = trim($_POST['language'] ?? '');
$canvasFingerprint = trim($_POST['canvas_fingerprint'] ?? '');
$webglFingerprint = trim($_POST['webgl_fingerprint'] ?? '');

// If user_id is provided, get user data from database
// This allows the email to work even if client-side userData is incomplete
if ($userId > 0 && (empty($fullName) || empty($email))) {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if ($user) {
                // Use database values as source of truth
                $fullName = $user['full_name'];
                $email = $user['email'];
            }
        } catch (PDOException $e) {
            error_log("Error fetching user: " . $e->getMessage());
        }
    }
}

// Validate we have required data
if (empty($selectedName)) {
    echo json_encode(['success' => false, 'message' => 'Missing selected name']);
    exit;
}

if (empty($fullName) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// Use frontend-generated fingerprint if available, otherwise generate from IP+UA
if (empty($deviceFingerprint)) {
    $deviceFingerprint = generateDeviceFingerprint($ipAddress, $userAgent);
}

$pdo = getDBConnection();

// If database fails, still try to send email
$dbSuccess = false;
$spinResultId = null;
if ($pdo) {
    try {
        $pdo->beginTransaction();
        
        // Extract first name and generate device fingerprint
        $firstName = extractFirstName($fullName);
        
        // Get the name the wheel landed on (from frontend)
        $landedOnName = $selectedName;
        
        // Check if landed-on person is already selected - implement silent backend skip
        $actualSelectedName = $landedOnName;
        
        // Use SELECT 1 for existence check (more efficient than SELECT id)
        $checkStmt = $pdo->prepare("SELECT 1 FROM selections WHERE selected_name = ? LIMIT 1");
        $checkStmt->execute([$landedOnName]);
        
        if ($checkStmt->fetch()) {
            // Person already selected - find next available (silent skip)
            $actualSelectedName = getNextAvailableParticipant($landedOnName, $firstName);
            
            if (!$actualSelectedName) {
                // Everyone is selected
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'All participants have been selected']);
                exit;
            }
        }
        
        // Use the ACTUAL selected name (after any silent skips)
        $selectedName = $actualSelectedName;
        
        // CRITICAL: Record in selections table FIRST (primary source of truth)
        // This enforces the unique constraints on selector, selected, and device
        $selectionsStmt = $pdo->prepare("
            INSERT INTO selections (
                selector_first_name, selector_user_id, selected_name, device_fingerprint, 
                ip_address, user_agent, screen_resolution, timezone, language, 
                canvas_fingerprint, webgl_fingerprint
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $selectionsStmt->execute([
            $firstName, $userId, $selectedName, $deviceFingerprint, 
            $ipAddress, $userAgent, $screenResolution, $timezone, $language,
            $canvasFingerprint, $webglFingerprint
        ]);
        
        // Record the selection in game_pairings table (for compatibility)
        $pairingStmt = $pdo->prepare("
            INSERT INTO game_pairings (selector_name, selected_name, selector_user_id, device_fingerprint, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $pairingStmt->execute([$firstName, $selectedName, $userId, $deviceFingerprint, $ipAddress]);
        
        // Mark the selector's participant record as having made a selection
        $markSelectorStmt = $pdo->prepare("UPDATE participants SET has_made_selection = 1 WHERE name = ?");
        $markSelectorStmt->execute([$firstName]);
        
        // Mark selected participant as selected
        $markSelectedStmt = $pdo->prepare("UPDATE participants SET is_selected = 1, selected_at = NOW(), selected_by_user_id = ? WHERE name = ? AND is_selected = 0");
        $markSelectedStmt->execute([$userId, $selectedName]);
        
        // Update selector user record
        $updateSelectorStmt = $pdo->prepare("UPDATE users SET has_selected = 1 WHERE id = ?");
        $updateSelectorStmt->execute([$userId]);
        
        // Update selected user record (if they exist) - only update users who haven't been selected yet
        $updateSelectedUserStmt = $pdo->prepare("UPDATE users SET has_been_selected = 1 WHERE first_name = ? AND has_been_selected = 0");
        $updateSelectedUserStmt->execute([$selectedName]);
        
        // Record in spin_results for history
        $spinStmt = $pdo->prepare("INSERT INTO spin_results (user_id, selected_name, email_sent) VALUES (?, ?, 0)");
        $spinStmt->execute([$userId, $selectedName]);
        $spinResultId = $pdo->lastInsertId();
        
        // Lock the device to prevent future selections
        $lockStmt = $pdo->prepare("
            INSERT INTO device_locks (
                device_fingerprint, user_id, first_name, selected_name, 
                ip_address, user_agent, screen_resolution, timezone, language
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $lockStmt->execute([
            $deviceFingerprint, $userId, $firstName, $selectedName,
            $ipAddress, $userAgent, $screenResolution, $timezone, $language
        ]);
        
        // Don't set dbSuccess yet - wait until commit
    } catch (PDOException $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Database error: " . $e->getMessage());
        
        // Check if this is a duplicate entry error (constraint violation)
        if (strpos($e->getMessage(), 'Duplicate entry') !== false || $e->getCode() == '23000') {
            echo json_encode([
                'success' => false, 
                'message' => 'This selection has already been made. Please refresh the page.'
            ]);
            exit;
        }
        // For other errors, continue to try sending email but mark as DB failure
        echo json_encode([
            'success' => false, 
            'message' => 'Database error occurred. Please try again.'
        ]);
        exit;
    }
}

// COMMIT the transaction BEFORE sending email
if ($pdo && isset($spinResultId)) {
    try {
        $pdo->commit();
        $dbSuccess = true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Failed to commit transaction: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to save selection. Please try again.'
        ]);
        exit;
    }
}

// ONLY send email AFTER successful database commit
if (!$dbSuccess) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database operation failed. Selection not saved.'
    ]);
    exit;
}

// NOW send the email (after commit succeeded)
$subject = "Your Kakawetee Selection - " . APP_TITLE;

$htmlMessage = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Congratulations!</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background-color:#f4f4f4;">
<table width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f4f4;padding:20px 0;">
<tr>
<td align="center">
<table width="600" cellspacing="0" cellpadding="0" border="0" style="background-color:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.1);">
<tr>
<td style="background:linear-gradient(135deg,#0f2027,#203a43,#2c5364);padding:40px 30px;text-align:center;">
<h1 style="color:#ffffff;margin:0;font-size:28px;">&#127881; ' . htmlspecialchars(APP_TITLE) . ' &#127881;</h1>
</td>
</tr>
<tr>
<td style="background:linear-gradient(135deg,#f1c40f,#e67e22);padding:30px;text-align:center;">
<h2 style="color:#ffffff;margin:0;font-size:32px;text-transform:uppercase;letter-spacing:2px;">CONGRATULATIONS!</h2>
</td>
</tr>
<tr>
<td style="padding:40px 30px;">
<p style="font-size:18px;color:#333333;margin:0 0 20px 0;">Dear <strong>' . htmlspecialchars($fullName) . '</strong>,</p>
<p style="font-size:16px;color:#555555;line-height:1.6;margin:0 0 20px 0;">
Congratulations! You have successfully made your selection in the <strong>' . htmlspecialchars(APP_TITLE) . '</strong>!
</p>
<p style="font-size:16px;color:#555555;line-height:1.6;margin:0 0 20px 0;">
<strong>Your Kakawetee is:</strong>
</p>
<table width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:30px 0;">
<tr>
<td align="center">
<table cellspacing="0" cellpadding="0" border="0">
<tr>
<td style="background:linear-gradient(135deg,#e74c3c,#9b59b6);border-radius:50px;padding:20px 40px;text-align:center;">
<span style="color:#ffffff;font-size:24px;font-weight:bold;">&#127942; ' . htmlspecialchars($selectedName) . ' &#127942;</span>
</td>
</tr>
</table>
</td>
</tr>
</table>
<p style="font-size:16px;color:#555555;line-height:1.6;margin:0 0 20px 0;">
&#128274; <strong>This is YOUR SECRET!</strong> Only you received this email. <strong>' . htmlspecialchars($selectedName) . '</strong> does not know they were selected by you.
</p>
<p style="font-size:16px;color:#555555;line-height:1.6;margin:0 0 20px 0;">
You decide when and how to reveal your selection. Keep this email as your confirmation.
</p>
<p style="text-align:center;font-size:30px;margin:30px 0;">&#11088; &#127775; &#10024; &#127775; &#11088;</p>
<p style="font-size:16px;color:#555555;line-height:1.6;margin:0;">
Once again, congratulations on your selection! &#127882;
</p>
</td>
</tr>
<tr>
<td style="padding:0 30px 40px 30px;text-align:center;">
<a href="' . htmlspecialchars(COMPANY_WEBSITE) . '" style="display:inline-block;background:linear-gradient(135deg,#2ecc71,#1abc9c);color:#ffffff;text-decoration:none;padding:15px 40px;border-radius:50px;font-size:16px;font-weight:bold;text-transform:uppercase;">Visit Our Website</a>
</td>
</tr>
<tr>
<td style="background-color:#2c3e50;padding:30px;text-align:center;">
<p style="color:#ecf0f1;margin:0 0 10px 0;font-size:14px;"><strong>' . htmlspecialchars(COMPANY_NAME) . '</strong></p>
<p style="color:#bdc3c7;margin:0 0 15px 0;font-size:12px;">This is an automated message from ' . htmlspecialchars(APP_TITLE) . '</p>
<p style="color:#7f8c8d;margin:0;font-size:11px;">&copy; ' . date('Y') . ' ' . htmlspecialchars(COMPANY_NAME) . '. All rights reserved.</p>
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>';

// Use proper email header format
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";

$emailSent = @mail($email, $subject, $htmlMessage, $headers);

// Update database - mark email as sent
if ($pdo && isset($spinResultId) && $emailSent) {
    try {
        $updateStmt = $pdo->prepare("UPDATE spin_results SET email_sent = 1, email_sent_at = NOW() WHERE id = ?");
        $updateStmt->execute([$spinResultId]);
    } catch (PDOException $e) {
        error_log("Failed to update email status: " . $e->getMessage());
    }
}

if ($emailSent) {
    echo json_encode([
        'success' => true, 
        'message' => 'Congratulations email sent successfully!',
        'selected_name' => $selectedName  // Return the ACTUAL selected person (after any skip)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please contact support.']);
}
?>