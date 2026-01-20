<?php
require_once 'config.php';

// Get participants from database
$participants = getParticipants();

// Fallback if database is empty
if (empty($participants)) {
    $participants = [
        "Peter", "Joseph", "Peace", "Cartine", "Chantal", "Annet", "Lydia",
        "Steve", "Elyse", "Safari", "Sam", "Abuba", "Philippe", "Veronique",
        "Gorette", "Anthony", "Arlette", "Jambo"
    ];
}

$participantsJson = json_encode($participants);
$colorsJson = json_encode($wheelColors);
$appTitle = APP_TITLE;
$companyLogoUrl = COMPANY_LOGO_URL;
$companyName = COMPANY_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($appTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($appTitle); ?> - A fun way to randomly select participants">
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
        }
        
        h1 {
            margin-top: 25px;
            font-size: 2em;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .container {
            max-width: 540px;
            margin: auto;
            padding: 20px;
        }
        
        .registration-overlay {
            position:  fixed;
            top: 0;
            left: 0;
            width:  100%;
            height: 100%;
            background: radial-gradient(circle at center, #0f2027, #203a43, #2c5364);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index:  1000;
            overflow: hidden;
        }
        
        .registration-overlay::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(2px 2px at 20% 30%, gold, transparent),
                radial-gradient(2px 2px at 60% 70%, gold, transparent),
                radial-gradient(1px 1px at 50% 50%, gold, transparent),
                radial-gradient(1px 1px at 80% 10%, gold, transparent),
                radial-gradient(2px 2px at 90% 60%, gold, transparent),
                radial-gradient(1px 1px at 33% 80%, gold, transparent),
                radial-gradient(2px 2px at 15% 90%, gold, transparent);
            background-size: 200% 200%;
            animation: particles 20s linear infinite;
            opacity: 0.4;
        }
        
        @keyframes particles {
            0% { transform: translateY(0); }
            100% { transform: translateY(-100px); }
        }
        
        .registration-form {
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.9), rgba(22, 33, 62, 0.9));
            backdrop-filter: blur(10px);
            padding: 45px;
            border-radius: 25px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6), 0 0 0 2px rgba(255, 215, 0, 0.3);
            max-width: 480px;
            width: 90%;
            border: 2px solid rgba(255, 215, 0, 0.4);
            position: relative;
            animation: formEntrance 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 10;
        }
        
        @keyframes formEntrance {
            0% { 
                opacity: 0;
                transform: scale(0.8) translateY(30px);
            }
            100% { 
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        .registration-form::before {
            content: '🎊';
            position: absolute;
            font-size: 60px;
            top: -30px;
            left: 20px;
            animation: float 3s ease-in-out infinite;
        }
        
        .registration-form::after {
            content: '🏆';
            position: absolute;
            font-size: 60px;
            top: -30px;
            right: 20px;
            animation: float 3s ease-in-out infinite 0.5s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(10deg); }
        }
        
        .registration-form h2 {
            margin:  0 0 10px 0;
            color: gold;
            font-size: 30px;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.6), 0 0 40px rgba(255, 215, 0, 0.3);
            animation: glow 2s ease-in-out infinite;
        }
        
        @keyframes glow {
            0%, 100% { text-shadow: 0 0 20px rgba(255, 215, 0, 0.6), 0 0 40px rgba(255, 215, 0, 0.3); }
            50% { text-shadow: 0 0 30px rgba(255, 215, 0, 0.8), 0 0 60px rgba(255, 215, 0, 0.5); }
        }
        
        .registration-form p {
            margin:  0 0 30px 0;
            color: rgba(255, 255, 255, 0.85);
            font-size: 16px;
            font-weight: 500;
        }
        
        .floating-emoji {
            position: absolute;
            font-size: 40px;
            pointer-events: none;
            opacity: 0.6;
            z-index: 5;
        }
        
        .floating-emoji:nth-child(1) {
            top: 15%;
            left: 10%;
            animation: floatEmoji 4s ease-in-out infinite;
        }
        
        .floating-emoji:nth-child(2) {
            top: 25%;
            right: 8%;
            animation: floatEmoji 5s ease-in-out infinite 0.5s;
        }
        
        .floating-emoji:nth-child(3) {
            bottom: 20%;
            left: 12%;
            animation: floatEmoji 4.5s ease-in-out infinite 1s;
        }
        
        .floating-emoji:nth-child(4) {
            bottom: 30%;
            right: 15%;
            animation: floatEmoji 5.5s ease-in-out infinite 1.5s;
        }
        
        @keyframes floatEmoji {
            0%, 100% { transform: translateY(0) rotate(0deg) scale(1); }
            25% { transform: translateY(-20px) rotate(5deg) scale(1.1); }
            50% { transform: translateY(-10px) rotate(-5deg) scale(0.9); }
            75% { transform: translateY(-25px) rotate(3deg) scale(1.05); }
        }
        
        .form-group {
            margin-bottom:  20px;
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
            color:  white;
            font-size: 16px;
            transition:  all 0.3s ease;
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
        
        .form-group . hint {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 5px;
        }
        
        .error-message {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #e74c3c;
            padding:  10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }
        
        button {
            padding: 15px 30px;
            margin:  10px;
            font-size: 18px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, gold, orange);
            color:  black;
            cursor:  pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            min-width: 120px;
            font-weight: bold;
        }
        
        button:hover:not([disabled]) {
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.6);
        }
        
        button[disabled] {
            background: gray;
            cursor:  not-allowed;
        }
        
        .btn-enter {
            width: 100%;
            margin:  25px 0 0 0;
            padding: 20px;
            font-size: 22px;
            border-radius: 50px;
            background: linear-gradient(135deg, #f1c40f, #e67e22, #e74c3c);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite, pulse 2s ease-in-out infinite;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 900;
            box-shadow: 0 5px 25px rgba(241, 196, 15, 0.4);
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes pulse {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 0 5px 25px rgba(241, 196, 15, 0.4);
            }
            50% { 
                transform: scale(1.02);
                box-shadow: 0 8px 35px rgba(241, 196, 15, 0.6);
            }
        }
        
        .spinner-section {
            display: none;
        }
        
        .welcome-message {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.2), rgba(26, 188, 156, 0.2));
            border: 1px solid rgba(46, 204, 113, 0.5);
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .welcome-message strong {
            color:  #2ecc71;
        }
        
        .spinner-container {
            position: relative;
            width:  420px;
            height: 420px;
            margin: 30px auto;
        }
        
        canvas {
            border-radius: 50%;
            background: white;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.5);
        }

        .pointer {
            position:  absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.4));
            transition: top 0.8s cubic-bezier(0.34, 1.56, 0.64, 1), 
                        transform 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .pointer.pointing {
            top:  120px;
            transform: translateX(-50%) scale(1.2);
        }

        .pointer.bounce {
            animation:  pointerBounce 0.5s ease-in-out infinite;
        }

        .pointer.celebrate {
            animation:  pointerCelebrate 0.6s ease-in-out 3;
        }

        .pointer.tick {
            animation:  tickSound 0.05s ease-out;
        }

        .pointer.pulse {
            animation:  pointerPulse 0.8s ease-in-out infinite;
        }

        @keyframes pointerBounce {
            0%, 100% { transform: translateX(-50%) translateY(0) rotate(0deg); }
            50% { transform: translateX(-50%) translateY(8px) rotate(0deg); }
        }

        @keyframes pointerCelebrate {
            0%, 100% { transform: translateX(-50%) scale(1.2) translateY(0) rotate(0deg); }
            25% { transform: translateX(-50%) scale(1.3) translateY(5px) rotate(-5deg); }
            50% { transform:  translateX(-50%) scale(1.4) translateY(10px) rotate(0deg); }
            75% { transform:  translateX(-50%) scale(1.3) translateY(5px) rotate(5deg); }
        }

        @keyframes tickSound {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(3px); }
        }

        @keyframes pointerPulse {
            0%, 100% {
                transform: translateX(-50%) scale(1.2);
                filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.4)) drop-shadow(0 0 10px rgba(255, 215, 0, 0.5));
            }
            50% {
                transform: translateX(-50%) scale(1.35);
                filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.4)) drop-shadow(0 0 25px rgba(255, 215, 0, 0.9));
            }
        }

        .pointer-arrow {
            width: 0;
            height:  0;
            border-left: 22px solid transparent;
            border-right:  22px solid transparent;
            border-top: 45px solid rgba(220, 20, 60, 0.9);
            position: relative;
        }

        .pointer-arrow::before {
            content: '';
            position: absolute;
            top: -42px;
            left: -15px;
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right:  15px solid transparent;
            border-top: 32px solid rgba(255, 80, 100, 0.7);
        }

        .pointer-arrow::after {
            content: '';
            position: absolute;
            top: -48px;
            left: 50%;
            transform:  translateX(-50%);
            width:  20px;
            height: 20px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.9), rgba(220, 20, 60, 0.8));
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        .pointer-base {
            position: absolute;
            top: 35px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 8px;
            background: linear-gradient(to bottom, rgba(180, 20, 50, 0.9), rgba(120, 10, 30, 0.9));
            border-radius:  0 0 5px 5px;
        }

        .highlight-ring {
            position:  absolute;
            top: 50%;
            left:  50%;
            width: 0;
            height:  0;
            border-radius: 50%;
            border: 4px solid gold;
            transform: translate(-50%, -50%);
            opacity: 0;
            pointer-events: none;
            box-shadow: 0 0 20px gold, inset 0 0 20px rgba(255, 215, 0, 0.3);
            transition: all 0.5s ease-out;
        }

        .highlight-ring.show {
            width:  200px;
            height: 200px;
            opacity: 1;
            animation: ringPulse 1s ease-in-out infinite;
        }

        @keyframes ringPulse {
            0%, 100% { box-shadow: 0 0 20px gold, inset 0 0 20px rgba(255, 215, 0, 0.3); }
            50% { box-shadow: 0 0 40px gold, inset 0 0 30px rgba(255, 215, 0, 0.5); }
        }

        #result {
            margin-top: 20px;
            font-size: 22px;
            font-weight: bold;
            color: gold;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
            min-height: 30px;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s, transform 0.5s;
        }

        #result.show {
            opacity: 1;
            transform:  translateY(0);
        }

        #result span {
            color: #fff;
            background: linear-gradient(135deg, #e74c3c, #9b59b6);
            padding: 5px 15px;
            border-radius: 20px;
            margin-left: 10px;
            display: inline-block;
            animation: resultPop 0.5s ease-out;
        }

        @keyframes resultPop {
            0% { transform:  scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            top: -10px;
            opacity: 0;
            pointer-events: none;
        }

        .confetti.animate {
            animation:  confettiFall 3s ease-out forwards;
        }

        @keyframes confettiFall {
            0% { opacity: 1; top: -10px; transform: rotate(0deg) translateX(0); }
            100% { opacity: 0; top: 100vh; transform: rotate(720deg) translateX(100px); }
        }

        .winner-float {
            position:  absolute;
            top:  50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 28px;
            font-weight: bold;
            color: gold;
            text-shadow: 0 0 20px rgba(0, 0, 0, 0.8), 0 0 40px gold;
            opacity: 0;
            pointer-events: none;
            z-index: 20;
        }

        .winner-float.show {
            animation: winnerAppear 2s ease-out forwards;
        }

        @keyframes winnerAppear {
            0% { opacity: 0; transform: translate(-50%, -50%) scale(0.5); }
            20% { opacity: 1; transform: translate(-50%, -50%) scale(1.3); }
            40% { transform: translate(-50%, -50%) scale(1); }
            100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }

        .email-status {
            margin-top: 15px;
            padding: 15px;
            border-radius: 10px;
            font-size: 14px;
            display: none;
        }

        .email-status.sending {
            display: block;
            background: rgba(52, 152, 219, 0.2);
            border: 1px solid #3498db;
            color: #3498db;
        }

        .email-status.success {
            display: block;
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid #2ecc71;
            color: #2ecc71;
        }

        .email-status.error {
            display:  block;
            background: rgba(231, 76, 60, 0.2);
            border:  1px solid #e74c3c;
            color:  #e74c3c;
        }

        .footer {
            margin-top: 30px;
            padding: 20px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
        }

        .participant-count {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 10px;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border:  3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color:  #fff;
            animation:  spin 1s ease-in-out infinite;
            margin-right: 10px;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .container { padding: 10px; }
            h1 { font-size: 1.5em; }
            .spinner-container { width: 100%; max-width: 350px; height: auto; }
            canvas { width: 100%; height: auto; }
        }

        @media (max-width: 480px) {
            .spinner-container { max-width: 300px; }
            h1 { font-size: 1.3em; }
            .registration-form { padding: 25px; width: 95%; }
            .form-group input { font-size: 16px; } /* Prevents iOS zoom */
            button { padding: 12px 20px; font-size: 16px; }
        }

        @media (max-width: 360px) {
            .spinner-container { max-width: 280px; }
            h1 { font-size: 1.2em; }
        }
        
        /* Glowing pointer at final stop */
        .pointer.winner-glow {
            filter: drop-shadow(0 0 20px gold) drop-shadow(0 0 40px gold) drop-shadow(0 0 60px rgba(255, 215, 0, 0.8));
            animation: pointerGlow 0.5s ease-in-out infinite alternate;
        }

        @keyframes pointerGlow {
            from { filter: drop-shadow(0 0 20px gold) drop-shadow(0 0 40px gold); }
            to { filter: drop-shadow(0 0 30px gold) drop-shadow(0 0 60px gold) drop-shadow(0 0 80px rgba(255, 215, 0, 0.8)); }
        }
        
        /* Enhanced winner name animation */
        .winner-float.show {
            animation: winnerReveal 1s ease-out forwards;
        }

        @keyframes winnerReveal {
            0% { opacity: 0; transform: translate(-50%, -50%) scale(0.3); }
            50% { opacity: 1; transform: translate(-50%, -50%) scale(1.2); }
            70% { transform: translate(-50%, -50%) scale(0.95); }
            100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }
        
        /* Company logo */
        .logo-container {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .company-logo {
            max-width: 200px;
            height: auto;
        }
        
        /* Fairness notice */
        .fairness-notice {
            margin-top: 15px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
    </style>
</head>

<body>
    <div class="registration-overlay" id="registrationOverlay">
        <div class="floating-emoji">🎊</div>
        <div class="floating-emoji">🏆</div>
        <div class="floating-emoji">⭐</div>
        <div class="floating-emoji">🎁</div>
        <div class="registration-form">
            <h2>🎉 Welcome to Kakaweti Lucky Spin! 🎉</h2>
            <p>Enter your details for a chance to be selected!</p>
            
            <div class="error-message" id="errorMessage"></div>
            
            <form id="registrationForm">
                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="fullName" placeholder="Enter your full name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                    <div class="hint">We'll send the congratulations to this email</div>
                </div>
                
                <div class="form-group">
                    <label for="secretLetter">Secret Letter</label>
                    <input type="text" id="secretLetter" name="secretLetter" placeholder="Enter the 3rd letter of your first name" maxlength="1" required>
                    <div class="hint">Hint: The 3rd letter of your first name (e.g., "Peter" → "t")</div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a password" minlength="6" required>
                    <div class="hint">Choose a password to check your selection later</div>
                </div>
                
                <button type="submit" class="btn-enter" id="enterBtn">🎯 YOU'RE ABOUT TO WIN!</button>
            </form>
        </div>
    </div>

    <div class="container">
        <div class="spinner-section" id="spinnerSection">
            <div class="logo-container">
                <img src="<?php echo htmlspecialchars($companyLogoUrl); ?>" alt="<?php echo htmlspecialchars($companyName); ?>" class="company-logo" onerror="this.style.display='none'">
            </div>
            <h1>&#127881; <?php echo htmlspecialchars($appTitle); ?> &#127881;</h1>
            
            <div class="welcome-message" id="welcomeMessage">
                Welcome, <strong id="displayName"></strong>! 
            </div>
            
            <div class="participant-count">
                Total Participants: <strong id="participantCount"><?php echo count($participants); ?></strong>
            </div>

            <div class="spinner-container">
                <div class="pointer" id="pointer">
                    <div class="pointer-arrow"></div>
                    <div class="pointer-base"></div>
                </div>
                <div class="highlight-ring" id="highlightRing"></div>
                <div class="winner-float" id="winnerFloat"></div>
                <canvas id="wheel" width="420" height="420"></canvas>
            </div>

            <button id="spinBtn">START</button>
            
            <div class="fairness-notice">
                <span>&#128274;</span> Each person is selected only once • Fair & Random Selection
            </div>
            
            <div class="links" style="margin-top: 15px; font-size: 14px;">
                <a href="check_selection.php" style="color: gold; text-decoration: none;">🔍 Check My Selection</a>
            </div>
            
            <div id="result"></div>
            <div class="email-status" id="emailStatus"></div>
            
            <div class="footer">
                &#169; <?php echo date('Y'); ?> <?php echo htmlspecialchars($appTitle); ?> | Powered by <?php echo htmlspecialchars(COMPANY_NAME); ?>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var names = <?php echo $participantsJson; ?>;
        var colors = <?php echo $colorsJson; ?>;
        var SPIN_SPEED = 0.15;
        var DECELERATION = 0.985;
        var CANVAS_MAX_WIDTH = 420;
        var CANVAS_MARGIN = 40;
        
        // Ceremony timing constants (in milliseconds)
        var DRAMATIC_PAUSE_MS = 1500;
        var POINTER_DOWN_DELAY = 200;
        var POINTER_GLOW_DELAY = 500;
        var FANFARE_DELAY = 1000;
        var WINNER_REVEAL_DELAY = 1500;
        var CELEBRATE_DELAY = 2000;
        var CONFETTI_DELAY = 2500;
        var EMAIL_DELAY = 3000;
        var PULSE_DELAY = 4000;

        var userData = {
            fullName: '',
            email: '',
            userId: null,
            deviceFingerprint: null
        };

        // Device Fingerprinting Functions
        function getCanvasFingerprint() {
            try {
                var canvas = document.createElement('canvas');
                var ctx = canvas.getContext('2d');
                canvas.width = 200;
                canvas.height = 50;
                ctx.textBaseline = 'top';
                ctx.font = '14px Arial';
                ctx.textBaseline = 'alphabetic';
                ctx.fillStyle = '#f60';
                ctx.fillRect(125, 1, 62, 20);
                ctx.fillStyle = '#069';
                ctx.fillText('Kakaweti 🎉', 2, 15);
                ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
                ctx.fillText('Kakaweti 🎉', 4, 17);
                return canvas.toDataURL().substring(0, 100);
            } catch (e) {
                return 'canvas-error';
            }
        }

        function getWebGLFingerprint() {
            try {
                var canvas = document.createElement('canvas');
                var gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
                if (!gl) return 'no-webgl';
                
                var debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                if (debugInfo) {
                    var vendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL);
                    var renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
                    return vendor + '|' + renderer;
                }
                return 'webgl-no-debug';
            } catch (e) {
                return 'webgl-error';
            }
        }

        function hashString(str) {
            // Simple hash for client-side fingerprinting (not for security)
            // Note: Server-side uses SHA-256 for actual storage
            var hash = 0;
            if (str.length === 0) return '0';
            for (var i = 0; i < str.length; i++) {
                var char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash; // Convert to 32bit integer
            }
            return Math.abs(hash).toString(16);
        }

        function generateDeviceFingerprint() {
            var components = [
                navigator.userAgent || 'unknown',
                navigator.language || 'unknown',
                screen.width + 'x' + screen.height,
                screen.colorDepth || 'unknown',
                new Date().getTimezoneOffset(),
                navigator.platform || 'unknown',
                navigator.hardwareConcurrency || 'unknown',
                getCanvasFingerprint(),
                getWebGLFingerprint()
            ];
            return hashString(components.join('|||'));
        }

        function collectDeviceData() {
            return {
                fingerprint: generateDeviceFingerprint(),
                screenResolution: screen.width + 'x' + screen.height,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'unknown',
                language: navigator.language || 'unknown',
                canvasFingerprint: hashString(getCanvasFingerprint()),
                webglFingerprint: hashString(getWebGLFingerprint())
            };
        }

        var deviceData = collectDeviceData();
        userData.deviceFingerprint = deviceData.fingerprint;

        // Sound Manager
        var SoundManager = {
            audioContext: null,
            supported: true,
            
            init: function() {
                try {
                    this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                } catch (e) {
                    console.warn('Web Audio API not supported');
                    this.supported = false;
                }
            },
            
            playTick: function() {
                if (!this.supported) return;
                if (!this.audioContext) this.init();
                if (!this.supported) return;
                try {
                    var osc = this.audioContext.createOscillator();
                    var gain = this.audioContext.createGain();
                    osc.connect(gain);
                    gain.connect(this.audioContext.destination);
                    osc.frequency.value = 800;
                    gain.gain.value = 0.1;
                    osc.start();
                    gain.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.05);
                    osc.stop(this.audioContext.currentTime + 0.05);
                } catch (e) {
                    console.warn('Error playing tick sound:', e);
                }
            },
            
            playFanfare: function() {
                if (!this.supported) return;
                if (!this.audioContext) this.init();
                if (!this.supported) return;
                try {
                    var frequencies = [523, 659, 784, 1047]; // C, E, G, C (major chord)
                    frequencies.forEach(function(freq, i) {
                        setTimeout(function() {
                            var osc = SoundManager.audioContext.createOscillator();
                            var gain = SoundManager.audioContext.createGain();
                            osc.connect(gain);
                            gain.connect(SoundManager.audioContext.destination);
                            osc.frequency.value = freq;
                            osc.type = 'sine';
                            gain.gain.value = 0.15;
                            osc.start();
                            gain.gain.exponentialRampToValueAtTime(0.01, SoundManager.audioContext.currentTime + 0.5);
                            osc.stop(SoundManager.audioContext.currentTime + 0.5);
                        }, i * 150);
                    });
                } catch (e) {
                    console.warn('Error playing fanfare:', e);
                }
            },
            
            playApplause: function() {
                if (!this.supported) return;
                if (!this.audioContext) this.init();
                if (!this.supported) return;
                try {
                    // White noise filtered to sound like applause
                    var bufferSize = this.audioContext.sampleRate * 2;
                    var buffer = this.audioContext.createBuffer(1, bufferSize, this.audioContext.sampleRate);
                    var data = buffer.getChannelData(0);
                    for (var i = 0; i < bufferSize; i++) {
                        data[i] = Math.random() * 2 - 1;
                    }
                    var noise = this.audioContext.createBufferSource();
                    noise.buffer = buffer;
                    var filter = this.audioContext.createBiquadFilter();
                    filter.type = 'bandpass';
                    filter.frequency.value = 1000;
                    var gain = this.audioContext.createGain();
                    gain.gain.value = 0.3;
                    gain.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 2);
                    noise.connect(filter);
                    filter.connect(gain);
                    gain.connect(this.audioContext.destination);
                    noise.start();
                    noise.stop(this.audioContext.currentTime + 2);
                } catch (e) {
                    console.warn('Error playing applause:', e);
                }
            }
        };

        var registrationOverlay = document.getElementById('registrationOverlay');
        var registrationForm = document.getElementById('registrationForm');
        var errorMessage = document.getElementById('errorMessage');
        var spinnerSection = document.getElementById('spinnerSection');
        var displayName = document.getElementById('displayName');
        var participantCount = document.getElementById('participantCount');

        var canvas = document.getElementById('wheel');
        var ctx = canvas.getContext('2d');
        var center = canvas.width / 2;
        var totalSlices = names.length;
        var sliceAngle = (2 * Math.PI) / totalSlices;
        var pointer = document.getElementById('pointer');
        var spinBtn = document.getElementById('spinBtn');
        var resultDiv = document.getElementById('result');
        var highlightRing = document.getElementById('highlightRing');
        var winnerFloat = document.getElementById('winnerFloat');
        var emailStatus = document.getElementById('emailStatus');

        if (participantCount) {
            participantCount.innerText = names.length;
        }

        var currentRotation = 0;
        var spinning = false;
        var stopRequested = false;
        var animationId;
        var velocity = SPIN_SPEED;
        var lastSliceIndex = -1;
        
        // Dynamic canvas sizing for mobile
        function resizeCanvas() {
            var containerWidth = Math.min(window.innerWidth - CANVAS_MARGIN, CANVAS_MAX_WIDTH);
            canvas.width = containerWidth;
            canvas.height = containerWidth;
            center = canvas.width / 2;
            if (totalSlices > 0) {
                drawWheel(currentRotation);
            }
        }
        window.addEventListener('resize', resizeCanvas);

        registrationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var fullName = document.getElementById('fullName').value.trim();
            var email = document.getElementById('email').value.trim();
            var secretLetter = document.getElementById('secretLetter').value.trim().toLowerCase();
            var password = document.getElementById('password').value.trim();
            var enterBtn = document.getElementById('enterBtn');
            
            if (!fullName || !email || !secretLetter || !password) {
                showError('Please fill in all fields');
                return;
            }
            
            if (password.length < 6) {
                showError('Password must be at least 6 characters long');
                return;
            }
            
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showError('Please enter a valid email address');
                return;
            }
            
            // Disable button and show loading
            enterBtn.disabled = true;
            enterBtn.innerHTML = '<span class="loading-spinner"></span> Processing...';
            
            // Send registration request with device data
            var formData = new FormData();
            formData.append('full_name', fullName);
            formData.append('email', email);
            formData.append('secret_letter', secretLetter);
            formData.append('password', password);
            formData.append('device_fingerprint', deviceData.fingerprint);
            formData.append('screen_resolution', deviceData.screenResolution);
            formData.append('timezone', deviceData.timezone);
            formData.append('language', deviceData.language);
            formData.append('canvas_fingerprint', deviceData.canvasFingerprint);
            formData.append('webgl_fingerprint', deviceData.webglFingerprint);
            
            fetch('register_user.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    userData.fullName = fullName;
                    userData.email = email;
                    userData.userId = data.user_id;
                    
                    // Update names with available participants
                    if (data.participants && data.participants.length > 0) {
                        names = data.participants;
                        totalSlices = names.length;
                        sliceAngle = (2 * Math.PI) / totalSlices;
                        
                        if (participantCount) {
                            participantCount.innerText = names.length;
                        }
                    }
                    
                    // Check if all participants are selected
                    if (names.length === 0) {
                        showError('All participants have been selected! Please try again later.');
                        enterBtn.disabled = false;
                        enterBtn.innerHTML = '🎯 YOU\'RE ABOUT TO WIN!';
                        return;
                    }
                    
                    registrationOverlay.style.display = 'none';
                    spinnerSection.style.display = 'block';
                    displayName.innerText = fullName;
                    
                    resizeCanvas();
                    drawWheel(0);
                } else {
                    // Check if this is the "already selected" case
                    if (data.already_selected && data.selected_name) {
                        // User has already made a selection - show wheel but lock button
                        userData.fullName = fullName;
                        userData.email = email;
                        userData.userId = 0;
                        userData.alreadySelected = true;
                        userData.selectedName = data.selected_name;
                        
                        registrationOverlay.style.display = 'none';
                        spinnerSection.style.display = 'block';
                        displayName.innerText = fullName;
                        
                        // Show all participants on wheel (for visual purposes)
                        resizeCanvas();
                        drawWheel(0);
                        
                        // Lock the button permanently
                        spinBtn.disabled = true;
                        spinBtn.innerText = 'ALREADY SELECTED';
                        spinBtn.style.background = 'gray';
                        
                        // Show warning message
                        resultDiv.innerHTML = '⚠️ Sorry, your kakawetee is: <span>' + data.selected_name + '</span>';
                        resultDiv.classList.add('show');
                    } else if (data.already_been_selected) {
                        // User has been selected by someone else - show wheel but lock button
                        userData.fullName = fullName;
                        userData.email = email;
                        userData.userId = 0;
                        userData.alreadyBeenSelected = true;
                        
                        registrationOverlay.style.display = 'none';
                        spinnerSection.style.display = 'block';
                        displayName.innerText = fullName;
                        
                        // Show all participants on wheel (for visual purposes)
                        resizeCanvas();
                        drawWheel(0);
                        
                        // Lock the button permanently
                        spinBtn.disabled = true;
                        spinBtn.innerText = 'YOU\'VE BEEN SELECTED';
                        spinBtn.style.background = 'gray';
                        
                        // Show message
                        resultDiv.innerHTML = '🎉 You have already been selected by someone! Your kakawetee is waiting for you.';
                        resultDiv.classList.add('show');
                    } else {
                        showError(data.message || 'Registration failed. Please try again.');
                        enterBtn.disabled = false;
                        enterBtn.innerHTML = '🎯 YOU\'RE ABOUT TO WIN!';
                    }
                }
            })
            .catch(function(error) {
                showError('An error occurred. Please try again.');
                enterBtn.disabled = false;
                enterBtn.innerHTML = '🎯 YOU\'RE ABOUT TO WIN!';
            });
        });

        function showError(message) {
            errorMessage.innerText = message;
            errorMessage.style.display = 'block';
            setTimeout(function() {
                errorMessage.style.display = 'none';
            }, 5000);
        }

        function drawWheel(rotation) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.save();
            ctx.translate(center, center);
            ctx.rotate(rotation);
            ctx.translate(-center, -center);

            for (var i = 0; i < totalSlices; i++) {
                var startAngle = -Math.PI / 2 + i * sliceAngle;
                var endAngle = startAngle + sliceAngle;

                ctx.beginPath();
                ctx.moveTo(center, center);
                ctx.arc(center, center, center - 2, startAngle, endAngle);
                ctx.closePath();
                ctx.fillStyle = colors[i % colors.length];
                ctx.fill();

                ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
                ctx.lineWidth = 1;
                ctx.stroke();

                ctx.save();
                ctx.translate(center, center);
                ctx.rotate(startAngle + sliceAngle / 2);
                ctx.textAlign = 'right';
                ctx.fillStyle = 'white';
                ctx.font = 'bold 14px Arial';
                ctx.shadowColor = 'rgba(0, 0, 0, 0.5)';
                ctx.shadowBlur = 3;
                ctx.shadowOffsetX = 1;
                ctx.shadowOffsetY = 1;
                ctx.fillText(names[i], center - 14, 6);
                ctx.restore();
            }

            ctx.beginPath();
            ctx.arc(center, center, 25, 0, 2 * Math.PI);
            ctx.fillStyle = '#fff';
            ctx.fill();
            ctx.strokeStyle = '#ddd';
            ctx.lineWidth = 3;
            ctx.stroke();

            ctx.beginPath();
            ctx.arc(center, center, 15, 0, 2 * Math.PI);
            ctx.fillStyle = 'gold';
            ctx.fill();

            ctx.restore();
        }

        function getCurrentSliceIndex() {
            var normalizedRotation = ((currentRotation % (2 * Math.PI)) + 2 * Math.PI) % (2 * Math.PI);
            var pointerPosition = (2 * Math.PI - normalizedRotation) % (2 * Math.PI);
            var index = Math.floor(pointerPosition / sliceAngle);
            return ((index % totalSlices) + totalSlices) % totalSlices;
        }

        function tickPointer() {
            var currentSlice = getCurrentSliceIndex();
            if (currentSlice !== lastSliceIndex) {
                lastSliceIndex = currentSlice;
                pointer.classList.remove('tick');
                void pointer.offsetWidth;
                pointer.classList.add('tick');
            }
        }

        function spinLoop() {
            currentRotation += velocity;
            drawWheel(currentRotation);
            
            // Basic tick animation during fast spin
            var currentSlice = getCurrentSliceIndex();
            if (currentSlice !== lastSliceIndex) {
                lastSliceIndex = currentSlice;
                pointer.classList.remove('tick');
                void pointer.offsetWidth;
                pointer.classList.add('tick');
            }

            if (!stopRequested) {
                animationId = requestAnimationFrame(spinLoop);
            } else {
                decelerateLoop();
            }
        }

        function decelerateLoop() {
            currentRotation += velocity;
            velocity *= DECELERATION;
            
            drawWheel(currentRotation);
            
            // Play tick sound on each slice pass
            var currentSlice = getCurrentSliceIndex();
            if (currentSlice !== lastSliceIndex) {
                lastSliceIndex = currentSlice;
                SoundManager.playTick();
                pointer.classList.remove('tick');
                void pointer.offsetWidth;
                pointer.classList.add('tick');
            }

            if (velocity > 0.002) {
                animationId = requestAnimationFrame(decelerateLoop);
            } else {
                // DRAMATIC PAUSE before reveal
                setTimeout(function() {
                    finalizeStop();
                }, DRAMATIC_PAUSE_MS);
            }
        }

        function resetPointer() {
            pointer.classList.remove('pointing', 'celebrate', 'bounce', 'pulse', 'winner-glow');
            pointer.style.top = '';
            pointer.style.transform = '';
            highlightRing.classList.remove('show');
            winnerFloat.classList.remove('show');
            winnerFloat.innerHTML = '';
            emailStatus.className = 'email-status';
            emailStatus.innerHTML = '';
        }

        function startSpin() {
            // Check if user already made a selection
            if (userData.alreadySelected) {
                spinBtn.disabled = true;
                spinBtn.innerText = 'ALREADY SELECTED';
                spinBtn.style.background = 'gray';
                resultDiv.innerHTML = '⚠️ Sorry, your kakawetee is: <span>' + userData.selectedName + '</span>';
                resultDiv.classList.add('show');
                return;
            }
            
            // Check if user has been selected by someone
            if (userData.alreadyBeenSelected) {
                spinBtn.disabled = true;
                spinBtn.innerText = 'YOU\'VE BEEN SELECTED';
                spinBtn.style.background = 'gray';
                resultDiv.innerHTML = '🎉 You have already been selected by someone! Your kakawetee is waiting for you.';
                resultDiv.classList.add('show');
                return;
            }
            
            // Check if all participants selected
            if (names.length === 0) {
                spinBtn.disabled = true;
                spinBtn.innerText = 'ALL SELECTED';
                resultDiv.innerHTML = '&#127942; All participants have been selected! &#127942;';
                resultDiv.classList.add('show');
                return;
            }
            
            spinning = true;
            stopRequested = false;
            velocity = SPIN_SPEED;
            lastSliceIndex = -1;
            resultDiv.classList.remove('show');
            resultDiv.innerHTML = '';
            resetPointer();
            spinBtn.innerText = 'STOP';
            spinLoop();
        }

        function stopSpin() {
            stopRequested = true;
            spinBtn.innerText = 'STOPPING...';
            spinBtn.disabled = true;
        }

        spinBtn.addEventListener('click', function() {
            if (!spinning) {
                startSpin();
            } else if (!stopRequested) {
                stopSpin();
            }
        });

        function createConfetti() {
            // Brand colors - gold, orange, red, purple
            var confettiColors = ['#FFD700', '#FFA500', '#e74c3c', '#9b59b6', '#f1c40f', '#e67e22', '#FF6347', '#DAA520'];
            
            for (var i = 0; i < 100; i++) { // More particles
                var confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.backgroundColor = confettiColors[Math.floor(Math.random() * confettiColors.length)];
                confetti.style.width = (Math.random() * 12 + 6) + 'px';
                confetti.style.height = (Math.random() * 12 + 6) + 'px';
                confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
                confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
                confetti.style.animationDelay = (Math.random() * 0.5) + 's';
                document.body.appendChild(confetti);
                
                (function(el) {
                    setTimeout(function() { el.classList.add('animate'); }, 10);
                    setTimeout(function() { el.remove(); }, 6000);
                })(confetti);
            }
        }

        function sendCongratulationsEmail(selectedName) {
            emailStatus.className = 'email-status sending';
            emailStatus.innerHTML = '<span class="loading-spinner"></span> Sending congratulations email...';
            
            var formData = new FormData();
            formData.append('full_name', userData.fullName);
            formData.append('email', userData.email);
            formData.append('selected_name', selectedName);
            formData.append('user_id', userData.userId);
            formData.append('device_fingerprint', deviceData.fingerprint);
            formData.append('screen_resolution', deviceData.screenResolution);
            formData.append('timezone', deviceData.timezone);
            formData.append('language', deviceData.language);
            formData.append('canvas_fingerprint', deviceData.canvasFingerprint);
            formData.append('webgl_fingerprint', deviceData.webglFingerprint);
            
            fetch('send_email.php', {
                method: 'POST',
                body:  formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    // Use the ACTUAL selected name returned from backend (may differ from wheel landing due to silent skip)
                    var actualSelectedName = data.selected_name || selectedName;
                    
                    // Update the UI to show the ACTUAL selected person (this is the ONLY place we show the name)
                    winnerFloat.innerHTML = '&#127881; ' + actualSelectedName + ' &#127881;';
                    resultDiv.innerHTML = '&#127881; Selected: <span>' + actualSelectedName + '</span>';
                    resultDiv.classList.add('show');
                    
                    emailStatus.className = 'email-status success';
                    emailStatus.innerHTML = '&#10004; ' + data.message + ' Check your inbox at ' + userData.email;
                    
                    // PERMANENTLY LOCK THE BUTTON after successful selection
                    spinBtn.disabled = true;
                    spinBtn.innerText = 'ALREADY SELECTED';
                    spinBtn.style.background = 'gray';
                    
                    // Store in userData to prevent re-selection
                    userData.alreadySelected = true;
                    userData.selectedName = actualSelectedName;
                } else {
                    emailStatus.className = 'email-status error';
                    emailStatus.innerHTML = '&#10008; ' + data.message;
                    
                    // Show error in result div too
                    winnerFloat.innerHTML = '&#10008; Error';
                    resultDiv.innerHTML = '&#10008; ' + data.message;
                    resultDiv.classList.add('show');
                }
            })
            .catch(function(error) {
                emailStatus.className = 'email-status error';
                emailStatus.innerHTML = '&#10008; Failed to send email. Please contact support.';
                
                winnerFloat.innerHTML = '&#10008; Error';
                resultDiv.innerHTML = '&#10008; Failed to process selection. Please contact support.';
                resultDiv.classList.add('show');
            });
        }

        function finalizeStop() {
            cancelAnimationFrame(animationId);
            
            var index = getCurrentSliceIndex();
            var selectedName = names[index];

            // Step 1: Pointer moves down
            setTimeout(function() {
                pointer.classList.add('pointing');
            }, POINTER_DOWN_DELAY);

            // Step 2: Pointer glows
            setTimeout(function() {
                pointer.classList.add('winner-glow');
            }, POINTER_GLOW_DELAY);

            // Step 3: Fanfare sound
            setTimeout(function() {
                SoundManager.playFanfare();
            }, FANFARE_DELAY);

            // Step 4: Show winner name with animation - BUT wait for backend to confirm
            setTimeout(function() {
                highlightRing.classList.add('show');
                // Show "Verifying selection..." message instead of name
                winnerFloat.innerHTML = '&#8987; Verifying selection...';
                winnerFloat.classList.add('show');
            }, WINNER_REVEAL_DELAY);

            // Step 5: Pointer celebrates
            setTimeout(function() {
                pointer.classList.add('celebrate');
            }, CELEBRATE_DELAY);

            // Step 6: Applause + Confetti + Result - BUT don't show name yet
            setTimeout(function() {
                SoundManager.playApplause();
                createConfetti();
                // Don't show result yet - wait for backend confirmation
            }, CONFETTI_DELAY);

            // Step 7: Send email (this will get the ACTUAL selected name from backend)
            setTimeout(function() {
                sendCongratulationsEmail(selectedName);
            }, EMAIL_DELAY);

            // Step 8: Continuous pulse
            setTimeout(function() {
                pointer.classList.remove('celebrate');
                pointer.classList.add('pulse');
            }, PULSE_DELAY);

            spinning = false;
            stopRequested = false;
            velocity = SPIN_SPEED;
            // Button remains disabled - it will be permanently locked after email is sent
        }
    })();
    </script>
</body>
</html>