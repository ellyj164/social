<?php
require_once 'config.php';

$appTitle = APP_TITLE;
$companyLogoUrl = COMPANY_LOGO_URL;
$companyName = COMPANY_NAME;

// Handle login form submission
$error = '';
$selectedName = '';
$selectionDate = '';
$firstName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginFirstName = trim($_POST['first_name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($loginFirstName) || empty($password)) {
        $error = 'Please enter both first name and password';
    } else {
        $pdo = getDBConnection();
        if ($pdo) {
            try {
                // Get user by first name
                $stmt = $pdo->prepare("SELECT id, first_name, password_hash FROM users WHERE first_name = ? LIMIT 1");
                $stmt->execute([$loginFirstName]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    // Password correct - check if they have a selection
                    $selStmt = $pdo->prepare("
                        SELECT selected_name, created_at 
                        FROM selections 
                        WHERE selector_first_name = ? 
                        LIMIT 1
                    ");
                    $selStmt->execute([$user['first_name']]);
                    $selection = $selStmt->fetch();
                    
                    if ($selection) {
                        $selectedName = $selection['selected_name'];
                        $selectionDate = date('F j, Y \a\t g:i A', strtotime($selection['created_at']));
                        $firstName = $user['first_name'];
                    } else {
                        $error = 'You have not made a selection yet.';
                    }
                } else {
                    $error = 'Invalid first name or password';
                }
            } catch (PDOException $e) {
                error_log("Check selection error: " . $e->getMessage());
                $error = 'An error occurred. Please try again.';
            }
        } else {
            $error = 'Database connection failed. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Check My Selection - <?php echo htmlspecialchars($appTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: radial-gradient(circle, #0f2027, #203a43, #2c5364);
            color: white;
            text-align: center;
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 540px;
            width: 100%;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .company-logo {
            max-width: 200px;
            height: auto;
        }
        
        h1 {
            font-size: 2em;
            margin-bottom: 30px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .card {
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.9), rgba(22, 33, 62, 0.9));
            backdrop-filter: blur(10px);
            padding: 45px;
            border-radius: 25px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6), 0 0 0 2px rgba(255, 215, 0, 0.3);
            border: 2px solid rgba(255, 215, 0, 0.4);
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 16px;
            border: 2px solid rgba(255, 215, 0, 0.2);
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.4);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: gold;
            background: rgba(0, 0, 0, 0.5);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
            transform: translateY(-2px);
        }
        
        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        
        .error-message {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #e74c3c;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .success-message {
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid #2ecc71;
            color: #2ecc71;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .selection-display {
            background: linear-gradient(135deg, #e74c3c, #9b59b6);
            padding: 30px;
            border-radius: 20px;
            margin: 30px 0;
        }
        
        .selection-display .name {
            font-size: 32px;
            font-weight: bold;
            margin: 20px 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }
        
        .selection-display .date {
            font-size: 14px;
            opacity: 0.9;
        }
        
        button {
            width: 100%;
            padding: 18px;
            font-size: 18px;
            border: none;
            border-radius: 50px;
            background: linear-gradient(135deg, gold, orange);
            color: black;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        button:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.6);
        }
        
        .links {
            margin-top: 30px;
            font-size: 14px;
        }
        
        .links a {
            color: gold;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .links a:hover {
            color: orange;
            text-decoration: underline;
        }
        
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            h1 { 
                font-size: 1.6em; 
            }
            
            .card {
                padding: 30px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            
            h1 { 
                font-size: 1.4em;
                margin-bottom: 20px;
            }
            
            .card {
                padding: 20px;
            }
            
            .form-group {
                margin-bottom: 16px;
            }
            
            .form-group label {
                font-size: 14px;
                margin-bottom: 6px;
            }
            
            .form-group input {
                font-size: 16px; /* Prevents iOS zoom */
                padding: 14px;
            }
            
            button {
                padding: 16px;
                font-size: 16px;
                min-height: 44px; /* Minimum touch target */
            }
            
            .error-message,
            .success-message {
                padding: 12px;
                font-size: 14px;
                margin-bottom: 16px;
            }
            
            .selection-display {
                padding: 20px;
                margin: 20px 0;
            }
            
            .selection-display .name {
                font-size: 26px;
                margin: 15px 0;
            }
            
            .selection-display .date {
                font-size: 13px;
            }
            
            .links {
                margin-top: 20px;
                font-size: 13px;
            }
            
            .company-logo {
                max-width: 150px;
            }
        }
        
        @media (max-width: 360px) {
            body {
                padding: 10px;
            }
            
            h1 { 
                font-size: 1.3em;
            }
            
            .card {
                padding: 16px;
            }
            
            .form-group input {
                padding: 12px;
            }
            
            button {
                padding: 14px;
                font-size: 15px;
            }
            
            .selection-display .name {
                font-size: 22px;
            }
            
            .company-logo {
                max-width: 120px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="<?php echo htmlspecialchars($companyLogoUrl); ?>" alt="<?php echo htmlspecialchars($companyName); ?>" class="company-logo" onerror="this.style.display='none'">
        </div>
        
        <h1>🔍 Check My Selection</h1>
        
        <div class="card">
            <?php if ($selectedName): ?>
                <div class="success-message">
                    Welcome back, <strong><?php echo htmlspecialchars($firstName); ?></strong>!
                </div>
                
                <p style="margin-bottom: 20px; font-size: 18px;">Your Kakawetee is:</p>
                
                <div class="selection-display">
                    <div>🎉</div>
                    <div class="name"><?php echo htmlspecialchars($selectedName); ?></div>
                    <div class="date">Selected on <?php echo htmlspecialchars($selectionDate); ?></div>
                </div>
                
                <p style="font-size: 14px; color: rgba(255, 255, 255, 0.7);">
                    🔒 <strong>Remember:</strong> This is your secret! Only you know who you selected.
                </p>
                
                <div class="links">
                    <a href="index.php">← Back to Main Page</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <p style="margin-bottom: 30px;">Enter your credentials to view your selection</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" placeholder="Enter your first name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    
                    <button type="submit">🔓 Check My Selection</button>
                </form>
                
                <div class="links">
                    <a href="index.php">← Back to Main Page</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            © <?php echo date('Y'); ?> <?php echo htmlspecialchars($appTitle); ?> | Powered by <?php echo htmlspecialchars($companyName); ?>
        </div>
    </div>
</body>
</html>
