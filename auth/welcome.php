<?php
/**
 * Employee Management System
 * صفحة الترحيب بعد تسجيل الدخول
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// التحقق من أن المستخدم سجل دخول للتو
if (!isset($_SESSION['just_logged_in'])) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

// إزالة العلامة بعد العرض
unset($_SESSION['just_logged_in']);

$username = $_SESSION['username'] ?? 'المستخدم';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مرحباً بك - نظام إدارة الموظفين</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    
    <!-- Typed.js -->
    <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
    
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            overflow: hidden;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #4facfe 75%, #00f2fe 100%);
            background-size: 400% 400%;
            animation: gradientShift 20s ease infinite;
            position: relative;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .welcome-container {
            position: relative;
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 2;
            color: white;
        }
        
        .welcome-content {
            text-align: center;
            padding: 30px 20px;
            max-width: 700px;
        }
        
        .welcome-icon {
            font-size: 120px;
            margin-bottom: 30px;
            animation: bounce 2s infinite, rotate 10s linear infinite;
            filter: drop-shadow(0 15px 30px rgba(0,0,0,0.3));
            position: relative;
        }
        
        .welcome-icon::before {
            content: '';
            position: absolute;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: pulse-ring 2s infinite;
        }
        
        @keyframes pulse-ring {
            0% {
                transform: translate(-50%, -50%) scale(0.8);
                opacity: 1;
            }
            100% {
                transform: translate(-50%, -50%) scale(1.5);
                opacity: 0;
            }
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0) rotate(0deg) scale(1); }
            25% { transform: translateY(-20px) rotate(-10deg) scale(1.05); }
            75% { transform: translateY(-20px) rotate(10deg) scale(1.05); }
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .welcome-title {
            font-size: 42px;
            font-weight: 900;
            margin-bottom: 20px;
            text-shadow: 0 8px 20px rgba(0,0,0,0.4), 0 0 30px rgba(255,255,255,0.3);
            color: white;
            letter-spacing: -2px;
            animation: title-glow 3s ease-in-out infinite;
        }
        
        @keyframes title-glow {
            0%, 100% { text-shadow: 0 8px 20px rgba(0,0,0,0.4), 0 0 30px rgba(255,255,255,0.3); }
            50% { text-shadow: 0 8px 30px rgba(0,0,0,0.5), 0 0 50px rgba(255,255,255,0.5); }
        }
        
        .welcome-subtitle {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 35px;
            opacity: 0.98;
            color: white;
            text-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .typed-text {
            font-size: 24px;
            font-weight: 600;
            color: #fff;
            text-shadow: 0 3px 10px rgba(0,0,0,0.3);
            min-height: 40px;
            margin-bottom: 15px;
        }
        
        .typed-text .typed-cursor {
            color: #fff;
            font-weight: 300;
        }
        
        .enter-button {
            margin-top: 40px;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        
        .enter-text {
            font-size: 16px;
            font-weight: 600;
            color: white;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            margin-bottom: 8px;
        }
        
        .arrow-down {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(15px);
            border: 3px solid white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            animation: float 2s infinite;
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
            text-decoration: none;
            position: relative;
        }
        
        .arrow-down::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px solid white;
            animation: ripple 2s infinite;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .arrow-down:hover {
            background: rgba(255, 255, 255, 0.5);
            transform: scale(1.2) translateY(-10px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
            border-width: 5px;
        }
        
        .arrow-down:active {
            transform: scale(1.1) translateY(-5px);
        }
        
        .arrow-down i {
            font-size: 28px;
            color: white;
            animation: arrow-bounce 1.5s infinite;
            position: relative;
            z-index: 1;
        }
        
        @keyframes arrow-bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(6px); }
        }
        
        .enter-hint {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 8px;
            animation: fadeInOut 2s infinite;
        }
        
        @keyframes fadeInOut {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }
        
        .particles-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
        }
        
        .particle {
            position: absolute;
            width: 6px;
            height: 6px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: particle-float 15s infinite;
        }
        
        @keyframes particle-float {
            0% {
                transform: translateY(100vh) translateX(0) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 0.6;
            }
            90% {
                opacity: 0.6;
            }
            100% {
                transform: translateY(-100px) translateX(100px) rotate(360deg);
                opacity: 0;
            }
        }
        
        /* Music Control */
        .music-control {
            position: fixed;
            bottom: 30px;
            left: 30px;
            z-index: 10;
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 3px solid white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        .music-control:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: scale(1.1) rotate(15deg);
        }
        
        .music-control i {
            color: white;
            font-size: 24px;
        }
        
        .music-control.playing {
            animation: music-pulse 1s infinite;
        }
        
        @keyframes music-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Background Circles */
        .bg-circles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
        }
        
        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: circle-float 25s infinite ease-in-out;
        }
        
        .bg-circle:nth-child(1) {
            width: 400px;
            height: 400px;
            top: -150px;
            left: -150px;
            animation-delay: 0s;
        }
        
        .bg-circle:nth-child(2) {
            width: 300px;
            height: 300px;
            top: 50%;
            right: -100px;
            animation-delay: 5s;
        }
        
        .bg-circle:nth-child(3) {
            width: 350px;
            height: 350px;
            bottom: -100px;
            left: 20%;
            animation-delay: 10s;
        }
        
        .bg-circle:nth-child(4) {
            width: 250px;
            height: 250px;
            top: 20%;
            left: 50%;
            animation-delay: 15s;
        }
        
        @keyframes circle-float {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            33% {
                transform: translate(50px, -50px) scale(1.1);
            }
            66% {
                transform: translate(-30px, 30px) scale(0.9);
            }
        }
        
        @media (max-width: 768px) {
            .welcome-content {
                padding: 20px 15px;
            }
            
            .welcome-title {
                font-size: 28px;
            }
            
            .welcome-subtitle {
                font-size: 18px;
            }
            
            .typed-text {
                font-size: 18px;
            }
            
            .welcome-icon {
                font-size: 70px;
                margin-bottom: 20px;
            }
            
            .enter-button {
                margin-top: 30px;
            }
            
            .arrow-down {
                width: 60px;
                height: 60px;
            }
            
            .arrow-down i {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Background Circles -->
    <div class="bg-circles">
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
    </div>
    
    <!-- Particles Background -->
    <div class="particles-bg" id="particles"></div>
    
    <!-- Music Control -->
    <div class="music-control" id="musicControl" title="تشغيل/إيقاف الموسيقى">
        <i class="fas fa-music"></i>
    </div>
    
    <!-- Audio Element -->
    <audio id="welcomeMusic" loop preload="auto" autoplay>
        <!-- موسيقى خلفية من مصدر خارجي -->
        <source src="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3" type="audio/mpeg">
    </audio>
    
    <div class="welcome-container">
        <div class="welcome-content">
            <div class="welcome-icon">
                <i class="fas fa-hand-sparkles"></i>
            </div>
            
            <h1 class="welcome-title">مرحباً بك</h1>
            <h2 class="welcome-subtitle"><?php echo htmlspecialchars($username); ?></h2>
            
            <div class="typed-text" id="typed"></div>
            
            <div class="enter-button">
                <div class="enter-text">اضغط للدخول إلى النظام</div>
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="arrow-down" title="الدخول إلى النظام" id="enterBtn">
                    <i class="fas fa-arrow-down"></i>
                </a>
                <div class="enter-hint">↓ اضغط هنا ↓</div>
            </div>
        </div>
    </div>
    
    <script>
        // Typed.js Animation
        const typed = new Typed('#typed', {
            strings: [
                'في نظام إدارة الموظفين',
                'نظام متكامل لإدارة شؤون الموظفين',
                'سهل الاستخدام وقوي الأداء',
                'مرحباً بك في عالم الإدارة الذكية'
            ],
            typeSpeed: 60,
            backSpeed: 40,
            backDelay: 2000,
            loop: true,
            showCursor: true,
            cursorChar: '|'
        });
        
        // GSAP Animations
        gsap.from('.welcome-icon', {
            duration: 1.2,
            scale: 0,
            rotation: 360,
            ease: 'back.out(1.7)'
        });
        
        gsap.from('.welcome-title', {
            duration: 1,
            y: -50,
            opacity: 0,
            delay: 0.3,
            ease: 'power3.out'
        });
        
        gsap.from('.welcome-subtitle', {
            duration: 1,
            y: -30,
            opacity: 0,
            delay: 0.5,
            ease: 'power3.out'
        });
        
        gsap.from('.typed-text', {
            duration: 1,
            opacity: 0,
            delay: 0.8,
            ease: 'power2.out'
        });
        
        gsap.from('.arrow-down', {
            duration: 1.2,
            scale: 0,
            delay: 1.5,
            ease: 'elastic.out(1, 0.5)'
        });
        
        // Create Particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 60;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                particle.style.width = (Math.random() * 4 + 4) + 'px';
                particle.style.height = particle.style.width;
                particlesContainer.appendChild(particle);
            }
        }
        
        createParticles();
        
        // Music Control
        const musicControl = document.getElementById('musicControl');
        const welcomeMusic = document.getElementById('welcomeMusic');
        let isPlaying = false;
        
        // محاولة تشغيل الموسيقى تلقائياً
        welcomeMusic.volume = 0.3; // خفض الصوت قليلاً
        
        welcomeMusic.play().then(() => {
            isPlaying = true;
            musicControl.innerHTML = '<i class="fas fa-pause"></i>';
            musicControl.classList.add('playing');
        }).catch(e => {
            console.log('Auto-play blocked, user interaction required');
            isPlaying = false;
        });
        
        musicControl.addEventListener('click', function() {
            if (isPlaying) {
                welcomeMusic.pause();
                musicControl.innerHTML = '<i class="fas fa-music"></i>';
                musicControl.classList.remove('playing');
                isPlaying = false;
            } else {
                welcomeMusic.play().catch(e => {
                    console.log('Music play failed:', e);
                });
                musicControl.innerHTML = '<i class="fas fa-pause"></i>';
                musicControl.classList.add('playing');
                isPlaying = true;
            }
        });
        
        // Click anywhere to play music (if blocked)
        document.addEventListener('click', function() {
            if (!isPlaying && welcomeMusic.paused) {
                welcomeMusic.play().then(() => {
                    isPlaying = true;
                    musicControl.innerHTML = '<i class="fas fa-pause"></i>';
                    musicControl.classList.add('playing');
                }).catch(e => {
                    console.log('Music play failed:', e);
                });
            }
        }, { once: true });
        
        // Auto scroll arrow animation
        setInterval(() => {
            const arrow = document.querySelector('.arrow-down i');
            if (arrow) {
                gsap.to(arrow, {
                    duration: 0.5,
                    y: 8,
                    yoyo: true,
                    repeat: 1,
                    ease: 'power2.inOut'
                });
            }
        }, 2000);
        
        // Add click effect to enter button
        const enterBtn = document.getElementById('enterBtn');
        enterBtn.addEventListener('click', function(e) {
            // Add loading effect
            this.style.opacity = '0.7';
            this.style.pointerEvents = 'none';
            
            // Small delay for visual feedback
            setTimeout(() => {
                window.location.href = this.href;
            }, 200);
        });
    </script>
</body>
</html>

