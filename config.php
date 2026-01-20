<?php
/**
 * Configuration file for Kakaweti Lucky Spin
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'kakaweti_spin');
define('DB_USER', 'kakaweti_user');
define('DB_PASS', 'YourSecurePassword123!');

// Email configuration
define('FROM_EMAIL', 'no-reply@fezalogistics.com');
define('FROM_NAME', 'Kakaweti Lucky Spin');
define('ADMIN_EMAIL', 'joseph@fezalogistics.com'); // All emails CC'd/BCC'd to admin
define('COMPANY_NAME', 'Feza Logistics');
define('COMPANY_WEBSITE', 'https://fezalogistics.com');
define('COMPANY_LOGO_URL', 'https://fezalogistics.com/logo.png');

// App configuration
define('APP_TITLE', 'Kakaweti Lucky Spin');

// Default participants fallback list
define('DEFAULT_PARTICIPANTS', ["Peter", "Joseph", "Peace", "Cartine", "Chantal", "Annet", "Lydia", "Steve", "Elyse", "Safari", "Sam", "Abuba", "Philippe", "Veronique", "Gorette", "Anthony", "Arlette", "Jambo"]);

// Colors for the wheel
$wheelColors = [
    "#e74c3c", "#2ecc71", "#3498db", "#9b59b6",
    "#f1c40f", "#1abc9c", "#e67e22", "#34495e"
];

/**
 * Database connection function
 */
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all active participants
 */
function getParticipants() {
    $pdo = getDBConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $stmt = $pdo->query("SELECT name FROM participants WHERE is_active = 1 ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error fetching participants: " . $e->getMessage());
        return [];
    }
}

/**
 * Get only available (unselected) participants
 */
function getAvailableParticipants() {
    $pdo = getDBConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $stmt = $pdo->query("SELECT name FROM participants WHERE is_active = 1 AND is_selected = 0 ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error fetching participants: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all participants except the specified name (for wheel display)
 * Does NOT filter by selection status - keeps the secret!
 */
function getAllParticipantsExcept($excludeName) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT name FROM participants WHERE is_active = 1 AND name != ? ORDER BY id");
        $stmt->execute([$excludeName]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error fetching participants: " . $e->getMessage());
        return [];
    }
}

/**
 * Get the next available (unselected) participant starting from a given name
 * Used for silent backend skip logic
 * @param string $startingName The name where the wheel landed (already selected)
 * @param string $currentSelector The person making the selection (to exclude from being selected)
 * @return string|null Next available participant name or null if all selected
 */
function getNextAvailableParticipant($startingName, $currentSelector) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        // Get all participants in order
        $stmt = $pdo->query("SELECT name FROM participants WHERE is_active = 1 ORDER BY id");
        $allParticipants = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get already selected names
        $selectedStmt = $pdo->query("SELECT selected_name FROM selections");
        $selectedNames = $selectedStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Add current selector to excluded list (cannot select themselves)
        $selectedNames[] = $currentSelector;
        
        // Find starting index
        $startIndex = array_search($startingName, $allParticipants);
        if ($startIndex === false) {
            $startIndex = 0;
        }
        
        // Search from NEXT position after starting (skip the starting position itself)
        // This is because this function is called when startingName is already selected
        $count = count($allParticipants);
        // Loop through remaining positions (start at 1 to skip landing position, avoid off-by-one with $i < $count)
        for ($i = 1; $i < $count; $i++) {
            $index = ($startIndex + $i) % $count;
            $candidate = $allParticipants[$index];
            
            if (!in_array($candidate, $selectedNames)) {
                return $candidate; // Found available person
            }
        }
        
        return null; // Everyone is selected
    } catch (PDOException $e) {
        error_log("Error finding next available participant: " . $e->getMessage());
        return null;
    }
}

/**
 * Extract first name from full name
 * @param string $fullName
 * @return string First name
 */
function extractFirstName($fullName) {
    $trimmed = trim($fullName);
    if (empty($trimmed)) {
        return '';
    }
    $parts = explode(' ', $trimmed);
    return $parts[0];
}

/**
 * Calculate secret letter for a first name (3rd character, lowercase)
 * @param string $firstName
 * @return string|null Secret letter or null if name is too short
 */
function getSecretLetter($firstName) {
    $trimmed = trim($firstName);
    if (strlen($trimmed) < 3) {
        return null; // Name must have at least 3 characters
    }
    return strtolower($trimmed[2]); // 3rd character (0-indexed position 2)
}

/**
 * Generate device fingerprint from IP and User Agent
 * @param string $ipAddress
 * @param string $userAgent
 * @return string Device fingerprint hash
 */
function generateDeviceFingerprint($ipAddress, $userAgent) {
    return hash('sha256', $ipAddress . '|' . $userAgent);
}

/**
 * Get list of already selected participant names
 * @return array List of selected names
 */
function getAlreadySelectedParticipants() {
    $pdo = getDBConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        // Check selections table first
        $stmt = $pdo->query("SELECT selected_name FROM selections ORDER BY id");
        $selections = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Also get from game_pairings for compatibility
        $stmt = $pdo->query("SELECT selected_name FROM game_pairings ORDER BY id");
        $pairings = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Merge and deduplicate
        return array_unique(array_merge($selections, $pairings));
    } catch (PDOException $e) {
        error_log("Error fetching selected participants: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if user or device already made a selection
 * Returns selection based on either first name OR device fingerprint
 * @param string $firstName
 * @param string $deviceFingerprint
 * @return array|null Returns selection data if exists, null otherwise
 */
function checkUserAlreadySelected($firstName, $deviceFingerprint) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        // Check selections table first (primary source of truth)
        $stmt = $pdo->prepare("
            SELECT selected_name, created_at 
            FROM selections 
            WHERE selector_first_name = ? OR device_fingerprint = ?
            LIMIT 1
        ");
        $stmt->execute([$firstName, $deviceFingerprint]);
        $result = $stmt->fetch();
        
        if ($result) {
            return [
                'selected_name' => $result['selected_name'],
                'selected_at' => $result['created_at']
            ];
        }
        
        // Fallback: Check game_pairings table for legacy compatibility
        $stmt = $pdo->prepare("
            SELECT selected_name, selected_at 
            FROM game_pairings 
            WHERE selector_name = ?
            LIMIT 1
        ");
        $stmt->execute([$firstName]);
        $result = $stmt->fetch();
        
        if ($result) {
            return $result;
        }
        
        // Also check by device fingerprint in game_pairings
        $stmt = $pdo->prepare("
            SELECT gp.selected_name, gp.selected_at
            FROM game_pairings gp
            WHERE gp.device_fingerprint = ?
            LIMIT 1
        ");
        $stmt->execute([$deviceFingerprint]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error checking user selection: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if a first name has been selected by someone else
 * @param string $firstName
 * @return array|null Returns selector info if the name has been selected, null otherwise
 */
function checkIfUserHasBeenSelected($firstName) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        // Check selections table first
        $stmt = $pdo->prepare("
            SELECT selector_first_name as selector_name, created_at as selected_at 
            FROM selections 
            WHERE selected_name = ?
            LIMIT 1
        ");
        $stmt->execute([$firstName]);
        $result = $stmt->fetch();
        
        if ($result) {
            return $result;
        }
        
        // Fallback: Check game_pairings for legacy compatibility
        $stmt = $pdo->prepare("
            SELECT selector_name, selected_at 
            FROM game_pairings 
            WHERE selected_name = ?
            LIMIT 1
        ");
        $stmt->execute([$firstName]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error checking if user has been selected: " . $e->getMessage());
        return null;
    }
}

/**
 * Validate that full name starts with a valid participant name
 * @param string $fullName
 * @return string|null Returns the matching participant name if valid, null otherwise
 */
function validateNameStartsWithParticipant($fullName) {
    $pdo = getDBConnection();
    if (!$pdo) {
        // Fallback to default participants if database unavailable
        $participants = DEFAULT_PARTICIPANTS;
    } else {
        try {
            $stmt = $pdo->query("SELECT name FROM participants WHERE is_active = 1");
            $participants = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (empty($participants)) {
                $participants = DEFAULT_PARTICIPANTS;
            }
        } catch (PDOException $e) {
            error_log("Error fetching participants for validation: " . $e->getMessage());
            $participants = DEFAULT_PARTICIPANTS;
        }
    }
    
    $trimmedFullName = trim($fullName);
    
    // Sort participants by length in descending order to prioritize longer matches
    // This prevents "Sam" from matching before "Samuel"
    usort($participants, function($a, $b) {
        return strlen($b) - strlen($a);
    });
    
    // Check if the full name starts with any of the participant names
    foreach ($participants as $participant) {
        // Check if fullName starts with participant name (case-insensitive)
        // Use word boundary to ensure it's at the start
        if (stripos($trimmedFullName, $participant) === 0) {
            // Verify it's either the exact name or followed by a space
            if (strlen($trimmedFullName) === strlen($participant) || 
                substr($trimmedFullName, strlen($participant), 1) === ' ') {
                return $participant;
            }
        }
    }
    
    return null;
}

/**
 * Check if a device is locked (has already made a selection)
 * @param string $deviceFingerprint
 * @return array|null Returns lock info if device is locked, null otherwise
 */
function checkDeviceLock($deviceFingerprint) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT first_name, selected_name, locked_at 
            FROM device_locks 
            WHERE device_fingerprint = ?
            LIMIT 1
        ");
        $stmt->execute([$deviceFingerprint]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error checking device lock: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if first name exists in participants list
 * @param string $firstName
 * @return bool
 */
function isValidParticipant($firstName) {
    $pdo = getDBConnection();
    if (!$pdo) {
        // Fallback to default participants if database unavailable
        return in_array($firstName, DEFAULT_PARTICIPANTS);
    }
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM participants WHERE name = ? AND is_active = 1");
        $stmt->execute([$firstName]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking participant: " . $e->getMessage());
        // Fallback to default participants
        return in_array($firstName, DEFAULT_PARTICIPANTS);
    }
}

/**
 * Get user by first name
 * @param string $firstName
 * @return array|null Returns user data if found, null otherwise
 */
function getUserByFirstName($firstName) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, full_name, first_name, email, password_hash, device_fingerprint FROM users WHERE first_name = ? LIMIT 1");
        $stmt->execute([$firstName]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting user by first name: " . $e->getMessage());
        return null;
    }
}

/**
 * Verify if user credentials and device match
 * @param string $firstName
 * @param string $email
 * @param string $password
 * @param string $deviceFingerprint
 * @return array Result with 'valid', 'message', 'user', and 'reason' keys
 */
function verifyUserDevice($firstName, $email, $password, $deviceFingerprint) {
    $user = getUserByFirstName($firstName);
    
    if (!$user) {
        // User doesn't exist - this is a new registration
        return [
            'valid' => true,
            'is_new' => true,
            'message' => 'New user registration'
        ];
    }
    
    // User exists - verify credentials
    $passwordMatch = password_verify($password, $user['password_hash']);
    $emailMatch = ($email === $user['email']);
    $deviceMatch = ($deviceFingerprint === $user['device_fingerprint']);
    
    if ($passwordMatch && $emailMatch && $deviceMatch) {
        // Same user, same device - allow login
        return [
            'valid' => true,
            'is_returning' => true,
            'user' => $user,
            'message' => 'Welcome back'
        ];
    } elseif ($passwordMatch && $emailMatch && !$deviceMatch) {
        // Same user, different device - block
        return [
            'valid' => false,
            'reason' => 'different_device',
            'message' => 'You must use your original device to access this platform'
        ];
    } else {
        // Invalid credentials
        return [
            'valid' => false,
            'reason' => 'invalid_credentials',
            'message' => 'Invalid credentials'
        ];
    }
}
?>