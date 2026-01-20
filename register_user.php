<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$secretLetter = strtolower(trim($_POST['secret_letter'] ?? ''));
$password = trim($_POST['password'] ?? '');

// Get device fingerprint data
$deviceFingerprint = trim($_POST['device_fingerprint'] ?? '');
$screenResolution = trim($_POST['screen_resolution'] ?? '');
$timezone = trim($_POST['timezone'] ?? '');
$language = trim($_POST['language'] ?? '');
$canvasFingerprint = trim($_POST['canvas_fingerprint'] ?? '');
$webglFingerprint = trim($_POST['webgl_fingerprint'] ?? '');

// Validate
if (empty($fullName) || empty($email) || empty($secretLetter) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// STRICT validation: Full name MUST start with a valid participant name
$matchedParticipantName = validateNameStartsWithParticipant($fullName);
if (!$matchedParticipantName) {
    echo json_encode([
        'success' => false, 
        'message' => 'Sorry, your first name must be one of the participants. Please enter your name starting with your participant name.'
    ]);
    exit;
}

// Use the matched participant name as the first name
$firstName = $matchedParticipantName;

// Calculate the expected secret letter from the first name (3rd character)
$expectedSecretLetter = getSecretLetter($firstName);

// Handle edge case: names with less than 3 characters
if ($expectedSecretLetter === null) {
    echo json_encode([
        'success' => false, 
        'message' => 'Your first name must have at least 3 characters to participate.'
    ]);
    exit;
}

// Validate the secret letter matches the 3rd character of their first name
if ($secretLetter !== $expectedSecretLetter) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid secret letter. The secret letter for "' . htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') . '" should be the 3rd letter of your first name.'
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}

// Generate device fingerprint
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// Use frontend-generated fingerprint if available, otherwise generate from IP+UA
if (empty($deviceFingerprint)) {
    $deviceFingerprint = generateDeviceFingerprint($ipAddress, $userAgent);
}

// Check if user exists and verify credentials + device
$verification = verifyUserDevice($firstName, $email, $password, $deviceFingerprint);

if (!$verification['valid']) {
    // User exists but credentials/device don't match
    echo json_encode([
        'success' => false,
        'message' => $verification['message']
    ]);
    exit;
}

// If this is a returning user (same person, same device)
if (isset($verification['is_returning']) && $verification['is_returning']) {
    $user = $verification['user'];
    
    // Check if they already made a selection
    $existingSelection = checkUserAlreadySelected($firstName, $deviceFingerprint);
    
    if ($existingSelection) {
        // Return success with their existing selection - show dashboard with locked button
        $participants = getAllParticipantsExcept($firstName);
        
        if (empty($participants)) {
            $participants = array_values(array_filter(DEFAULT_PARTICIPANTS, function($name) use ($firstName) {
                return $name !== $firstName;
            }));
        }
        
        echo json_encode([
            'success' => true,
            'is_returning' => true,
            'user_id' => $user['id'],
            'first_name' => $firstName,
            'participants' => $participants,
            'already_selected' => true,
            'selected_name' => $existingSelection['selected_name'],
            'message' => 'Welcome back, ' . $firstName . '!'
        ]);
        exit;
    } else {
        // Returning user but hasn't made a selection yet - allow them to play
        $participants = getAllParticipantsExcept($firstName);
        
        if (empty($participants)) {
            $participants = array_values(array_filter(DEFAULT_PARTICIPANTS, function($name) use ($firstName) {
                return $name !== $firstName;
            }));
        }
        
        echo json_encode([
            'success' => true,
            'is_returning' => true,
            'user_id' => $user['id'],
            'first_name' => $firstName,
            'participants' => $participants,
            'message' => 'Welcome back, ' . $firstName . '!'
        ]);
        exit;
    }
}

// This is a new user - check if device is already locked
$deviceLock = checkDeviceLock($deviceFingerprint);
if ($deviceLock) {
    echo json_encode([
        'success' => false,
        'message' => 'This device has already been used for selection by ' . $deviceLock['first_name'],
        'device_locked' => true
    ]);
    exit;
}

// NOTE: We do NOT block if this person has been selected by someone else
// They can still spin (if they haven't selected yet), but they cannot be selected again
// The backend (send_email.php) will handle skipping them if wheel lands on them

$pdo = getDBConnection();
if (!$pdo) {
    // If database fails, still allow the game to work with fallback participants
    // But remove user's own name
    $participants = array_values(array_filter(DEFAULT_PARTICIPANTS, function($name) use ($firstName) {
        return $name !== $firstName;
    }));
    
    echo json_encode([
        'success' => true,
        'user_id' => 0,
        'first_name' => $firstName,
        'participants' => $participants,
        'message' => 'Registration successful'
    ]);
    exit;
}

try {
    // For new users, check if first name is already registered
    // (Returning users are already handled above)
    if (!isset($verification['is_returning'])) {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE first_name = ? LIMIT 1");
        $checkStmt->execute([$firstName]);
        if ($checkStmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'Sorry, "' . htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') . '" has already been registered. Only one person with this first name can participate.'
            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            exit;
        }
    }
    
    // Hash the password before storing (only for new users)
    if (!isset($verification['is_returning'])) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Save user with password, first_name and device_fingerprint
        $insertStmt = $pdo->prepare("INSERT INTO users (full_name, first_name, email, password_hash, ip_address, user_agent, device_fingerprint) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->execute([$fullName, $firstName, $email, $passwordHash, $ipAddress, $userAgent, $deviceFingerprint]);
        $userId = $pdo->lastInsertId();

        // Create login session
        try {
            $sessionStmt = $pdo->prepare("INSERT INTO login_sessions (user_id, first_name, device_fingerprint, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
            $sessionStmt->execute([$userId, $firstName, $deviceFingerprint, $ipAddress, $userAgent]);
        } catch (PDOException $e) {
            // Log but don't fail if login_sessions table doesn't exist yet
            error_log("Login session insert failed: " . $e->getMessage());
        }
    } else {
        // Returning user - use their existing ID
        $userId = $verification['user']['id'];
        
        // Update login session for returning user
        try {
            $sessionStmt = $pdo->prepare("INSERT INTO login_sessions (user_id, first_name, device_fingerprint, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
            $sessionStmt->execute([$userId, $firstName, $deviceFingerprint, $ipAddress, $userAgent]);
        } catch (PDOException $e) {
            error_log("Login session insert failed: " . $e->getMessage());
        }
    }

    // Get ALL participants except the logged-in user (maintains secret - no filtering by selection status)
    $participants = getAllParticipantsExcept($firstName);
    
    // If no participants from DB, use default and remove user's own name
    if (empty($participants)) {
        $participants = array_values(array_filter(DEFAULT_PARTICIPANTS, function($name) use ($firstName) {
            return $name !== $firstName;
        }));
    }

    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'first_name' => $firstName,
        'participants' => $participants,
        'message' => 'Registration successful'
    ]);

} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    
    // Still allow the game to work even if database has issues
    // But remove user's own name
    $participants = array_values(array_filter(DEFAULT_PARTICIPANTS, function($name) use ($firstName) {
        return $name !== $firstName;
    }));
    
    echo json_encode([
        'success' => true,
        'user_id' => 0,
        'first_name' => $firstName,
        'participants' => $participants,
        'message' => 'Registration successful'
    ]);
}
?>