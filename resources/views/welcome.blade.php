<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API GO</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo/logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/logo/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/logo/logo.png') }}">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* Light Mode */
            --primary-color: #05DF72;
            --primary-dark: #04b85e;
            --primary-light: #3ee88d;
            --accent-blue: #3b82f6;
            --accent-purple: #8b5cf6;
            --accent-orange: #f97316;
            --accent-cyan: #06b6d4;
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --bg-accent: #f0fdf4;
            --text-primary: #111827;
            --text-secondary: #4b5563;
            --text-muted: #9ca3af;
            --border-color: #e5e7eb;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        [data-theme="dark"] {
            /* Dark Mode */
            --primary-color: #05DF72;
            --primary-dark: #04b85e;
            --primary-light: #3ee88d;
            --accent-blue: #60a5fa;
            --accent-purple: #a78bfa;
            --accent-orange: #fb923c;
            --accent-cyan: #22d3ee;
            --bg-primary: #111827;
            --bg-secondary: #1f2937;
            --bg-accent: #0f2e1e;
            --text-primary: #f9fafb;
            --text-secondary: #d1d5db;
            --text-muted: #9ca3af;
            --border-color: #374151;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.4), 0 4px 6px -2px rgba(0, 0, 0, 0.3);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.4);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            transition: background 0.3s ease, color 0.3s ease;
            overflow-x: hidden;
        }

        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            width: 56px;
            height: 56px;
            background: var(--primary-color);
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-xl);
        }

        .theme-toggle i {
            font-size: 22px;
            color: white;
        }

        /* Container */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Header */
        .header {
            padding: 100px 0 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -10%;
            width: 120%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(5, 223, 114, 0.05) 0%, transparent 70%);
            animation: pulse 8s ease-in-out infinite;
            pointer-events: none;
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .logo-container {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 50px;
            width: 100%;
        }

        .logo {
            max-width: 180px;
            height: auto;
            position: relative;
            z-index: 2;
            animation: fadeInDown 0.8s ease, float 6s ease-in-out infinite;
            filter: drop-shadow(0 10px 25px rgba(5, 223, 114, 0.2));
        }

        .logo-ring {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 220px;
            height: 220px;
            border: 2px solid var(--primary-color);
            border-radius: 50%;
            opacity: 0.3;
            animation: rotate 20s linear infinite;
        }

        .logo-ring::before {
            content: '';
            position: absolute;
            top: 10px;
            right: 10px;
            width: 12px;
            height: 12px;
            background: var(--primary-color);
            border-radius: 50%;
            box-shadow: 0 0 20px var(--primary-color);
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 900;
            color: var(--text-primary);
            margin-bottom: 24px;
            letter-spacing: -0.03em;
            line-height: 1.1;
            animation: fadeInUp 0.8s ease 0.2s both;
            position: relative;
            display: inline-block;
        }

        .hero-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 2px;
            animation: expandWidth 1s ease 0.8s both;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            color: var(--text-secondary);
            max-width: 750px;
            margin: 0 auto 40px;
            font-weight: 400;
            line-height: 1.7;
            animation: fadeInUp 0.8s ease 0.4s both;
        }

        .document-types {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-bottom: 40px;
            animation: fadeInUp 0.8s ease 0.5s both;
        }

        .doc-type {
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .doc-type:nth-child(1) i {
            color: var(--primary-color);
        }

        .doc-type:nth-child(2) i {
            color: var(--accent-blue);
        }

        .doc-type:nth-child(3) i {
            color: var(--accent-orange);
        }

        .doc-type:nth-child(4) i {
            color: var(--accent-purple);
        }

        .doc-type:nth-child(5) i {
            color: var(--accent-cyan);
        }

        .doc-type i {
            font-size: 14px;
        }

        .doc-type:hover {
            border-color: var(--primary-color);
            transform: translateY(-3px);
            background: var(--bg-primary);
            box-shadow: 0 8px 20px rgba(5, 223, 114, 0.15);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: var(--primary-color);
            color: white;
            padding: 16px 32px;
            border-radius: 100px;
            font-weight: 600;
            font-size: 1.05rem;
            box-shadow: 0 8px 25px rgba(5, 223, 114, 0.4);
            animation: fadeInUp 0.8s ease 0.6s both, pulse 3s ease-in-out infinite;
            position: relative;
            overflow: hidden;
        }

        .status-badge::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .status-badge:hover::before {
            width: 300px;
            height: 300px;
        }

        .status-badge i {
            font-size: 22px;
            animation: checkPulse 2s ease-in-out infinite;
            position: relative;
            z-index: 1;
        }

        .status-badge span {
            position: relative;
            z-index: 1;
        }

        /* Stats Section */
        .stats-section {
            background: var(--bg-secondary);
            padding: 40px 0;
            margin: 60px 0;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            text-align: center;
        }

        .stat-item {
            padding: 20px;
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .stat-item:nth-child(1) .stat-icon {
            background: var(--accent-orange);
        }

        .stat-item:nth-child(2) .stat-icon {
            background: var(--accent-blue);
        }

        .stat-item:nth-child(3) .stat-icon {
            background: var(--accent-purple);
        }

        .stat-item:nth-child(4) .stat-icon {
            background: var(--primary-color);
        }

        .stat-icon i {
            font-size: 28px;
            color: white;
        }

        .stat-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        /* Features Section */
        .section {
            padding: 80px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 16px;
            letter-spacing: -0.02em;
        }

        .section-description {
            font-size: 1.125rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 32px;
        }

        .feature-card {
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 20px;
            padding: 36px 28px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-color);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            border-color: var(--primary-color);
            box-shadow: var(--shadow-xl);
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .feature-card:nth-child(1) .feature-icon {
            background: var(--primary-color);
        }

        .feature-card:nth-child(2) .feature-icon {
            background: var(--accent-blue);
        }

        .feature-card:nth-child(3) .feature-icon {
            background: var(--accent-purple);
        }

        .feature-card:nth-child(4) .feature-icon {
            background: var(--accent-cyan);
        }

        .feature-card:nth-child(5) .feature-icon {
            background: var(--accent-orange);
        }

        .feature-card:nth-child(6) .feature-icon {
            background: var(--primary-color);
        }

        .feature-icon i {
            font-size: 28px;
            color: white;
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
        }

        .feature-description {
            font-size: 1rem;
            color: var(--text-secondary);
            line-height: 1.7;
        }

        /* Action Cards */
        .action-section {
            background: var(--bg-secondary);
            padding: 80px 0;
        }

        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 32px;
        }

        .action-card {
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 24px;
            padding: 40px 32px;
            transition: all 0.3s ease;
            text-align: left;
        }

        .action-card:hover {
            transform: translateY(-6px);
            border-color: var(--primary-color);
            box-shadow: var(--shadow-xl);
        }

        .action-card-icon {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }

        .action-card:nth-child(1) .action-card-icon {
            background: var(--accent-cyan);
        }

        .action-card:nth-child(2) .action-card-icon {
            background: var(--accent-orange);
        }

        .action-card:nth-child(3) .action-card-icon {
            background: var(--accent-purple);
        }

        .action-card-icon i {
            font-size: 32px;
            color: white;
        }

        .action-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
        }

        .action-card p {
            font-size: 1rem;
            color: var(--text-secondary);
            margin-bottom: 24px;
            line-height: 1.7;
        }

        .action-card ul {
            list-style: none;
            margin-bottom: 28px;
        }

        .action-card ul li {
            padding: 8px 0;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .action-card ul li i {
            color: var(--primary-color);
            font-size: 16px;
            flex-shrink: 0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: var(--primary-color);
            color: white;
            padding: 14px 28px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(5, 223, 114, 0.3);
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(5, 223, 114, 0.4);
        }

        .btn i {
            font-size: 18px;
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            box-shadow: none;
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Video Section */
        .video-section {
            padding: 80px 0;
        }

        .video-container {
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 24px;
            padding: 48px;
            max-width: 900px;
            margin: 0 auto;
        }

        .video-container h3 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
            text-align: center;
        }

        .video-container p {
            color: var(--text-secondary);
            text-align: center;
            margin-bottom: 32px;
            font-size: 1.05rem;
        }

        .video-wrapper {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 16px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-lg);
        }

        .video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }

        .video-actions {
            display: flex;
            justify-content: center;
            gap: 16px;
        }

        /* Steps Section */
        .steps-section {
            background: var(--bg-secondary);
            padding: 80px 0;
        }

        .steps-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .step-item {
            display: flex;
            gap: 24px;
            margin-bottom: 32px;
            padding: 28px;
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .step-item:hover {
            border-color: var(--primary-color);
            transform: translateX(8px);
            box-shadow: var(--shadow-lg);
        }

        .step-number {
            flex-shrink: 0;
            width: 48px;
            height: 48px;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .step-item:nth-child(1) .step-number,
        .step-item:nth-child(4) .step-number {
            background: var(--primary-color);
        }

        .step-item:nth-child(2) .step-number,
        .step-item:nth-child(5) .step-number {
            background: var(--accent-blue);
        }

        .step-item:nth-child(3) .step-number,
        .step-item:nth-child(6) .step-number {
            background: var(--accent-purple);
        }

        .step-content h4 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .step-content p {
            color: var(--text-secondary);
            line-height: 1.7;
        }

        .step-content code {
            background: var(--bg-secondary);
            padding: 4px 8px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            color: var(--primary-color);
            font-size: 0.9rem;
        }

        .step-content ul {
            margin-top: 12px;
            padding-left: 24px;
        }

        .step-content ul li {
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        /* Footer */
        .footer {
            background: var(--bg-primary);
            border-top: 1px solid var(--border-color);
            padding: 40px 0;
            text-align: center;
        }

        .footer-content {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 20px;
        }

        .footer-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s ease;
        }

        .footer-links a:hover {
            opacity: 0.7;
        }

        /* Scroll to Top */
        .scroll-top {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 56px;
            height: 56px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 999;
        }

        .scroll-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .scroll-top:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }

        .scroll-top i {
            font-size: 20px;
            color: white;
        }

        /* WhatsApp Floating Button */
        .whatsapp-float {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #25d366, #128c7e);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
            z-index: 999;
            text-decoration: none;
            transition: all 0.3s ease;
            animation: whatsapp-pulse 2s ease-in-out infinite;
        }

        .whatsapp-float:hover {
            transform: scale(1.1) translateY(-3px);
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.6);
        }

        .whatsapp-float i {
            font-size: 32px;
            color: white;
        }

        /* WhatsApp Badge */
        .whatsapp-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ff3b30;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            border: 3px solid var(--bg-primary);
            box-shadow: 0 2px 8px rgba(255, 59, 48, 0.5);
            animation: badge-bounce 2s ease-in-out infinite;
        }

        @keyframes badge-bounce {
            0%, 100% {
                transform: scale(1);
            }
            10%, 30% {
                transform: scale(1.2);
            }
            20%, 40% {
                transform: scale(1);
            }
        }

        @keyframes whatsapp-pulse {
            0%, 100% {
                box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
            }
            50% {
                box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4), 0 0 0 12px rgba(37, 211, 102, 0.15);
            }
        }

        /* WhatsApp tooltip */
        .whatsapp-float::before {
            content: '¿Necesitas ayuda?';
            position: absolute;
            left: calc(100% + 12px);
            background: var(--text-primary);
            color: var(--bg-primary);
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            box-shadow: var(--shadow-lg);
            transform: translateX(-100%);
            left: -12px;
        }

        .whatsapp-float::after {
            content: '';
            position: absolute;
            left: calc(100% + 4px);
            top: 50%;
            transform: translateY(-50%) translateX(-100%);
            border: 8px solid transparent;
            border-right-color: var(--text-primary);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            left: -4px;
        }

        .whatsapp-float:hover::before,
        .whatsapp-float:hover::after {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .whatsapp-float {
                width: 56px;
                height: 56px;
                bottom: 20px;
                right: 20px;
            }

            .whatsapp-float i {
                font-size: 28px;
            }

            .whatsapp-float::before {
                display: none;
            }

            .whatsapp-float::after {
                display: none;
            }
        }

        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 0.6;
                transform: scale(1);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes rotate {
            from {
                transform: translate(-50%, -50%) rotate(0deg);
            }
            to {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        @keyframes expandWidth {
            from {
                width: 0;
            }
            to {
                width: 80px;
            }
        }

        @keyframes checkPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.15);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .section-title {
                font-size: 1.875rem;
            }

            .features-grid,
            .action-cards {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 24px;
            }

            .video-container {
                padding: 32px 24px;
            }

            .step-item {
                flex-direction: column;
                text-align: center;
            }

            .step-number {
                margin: 0 auto;
            }

            .logo {
                max-width: 140px;
            }

            .logo-ring {
                width: 180px;
                height: 180px;
            }

            .document-types {
                gap: 8px;
            }

            .doc-type {
                font-size: 0.85rem;
                padding: 6px 14px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 16px;
            }

            .hero-title {
                font-size: 1.85rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .logo {
                max-width: 120px;
            }

            .logo-ring {
                width: 150px;
                height: 150px;
            }

            .status-badge {
                padding: 12px 24px;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <!-- Theme Toggle -->
    <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle theme">
        <i class="fas fa-moon" id="theme-icon"></i>
    </button>

    <!-- Scroll to Top -->
    <div class="scroll-top" onclick="scrollToTop()">
        <i class="fas fa-arrow-up"></i>
    </div>

    <!-- WhatsApp Floating Button -->
    <a href="https://wa.link/z50dwk" target="_blank" class="whatsapp-float" aria-label="Contactar por WhatsApp">
        <i class="fab fa-whatsapp"></i>
        <span class="whatsapp-badge">1</span>
    </a>

    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo-container">
                    <div class="logo-ring"></div>
                    <img src="{{ asset('assets/logo/logo.png') }}" alt="Logo" class="logo">
                </div>
                <h1 class="hero-title">API GO Facturación Electrónica</h1>
                <p class="hero-subtitle">
                    Integración directa con SUNAT para emisión de comprobantes electrónicos
                </p>
                <div class="document-types">
                    <span class="doc-type">
                        <i class="fas fa-file-invoice"></i>
                        Facturas
                    </span>
                    <span class="doc-type">
                        <i class="fas fa-receipt"></i>
                        Boletas
                    </span>
                    <span class="doc-type">
                        <i class="fas fa-file-circle-minus"></i>
                        Notas de Crédito
                    </span>
                    <span class="doc-type">
                        <i class="fas fa-file-circle-plus"></i>
                        Notas de Débito
                    </span>
                    <span class="doc-type">
                        <i class="fas fa-truck"></i>
                        Guías de Remisión
                    </span>
                </div>
                <div class="status-badge">
                    <i class="fas fa-circle-check"></i>
                    <span>En Línea</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fab fa-laravel"></i>
                    </div>
                    <div class="stat-value">Laravel 12</div>
                    <div class="stat-label">Framework</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fab fa-php"></i>
                    </div>
                    <div class="stat-value">PHP 8.2+</div>
                    <div class="stat-label">Versión</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-code"></i>
                    </div>
                    <div class="stat-value">Greenter 5.1</div>
                    <div class="stat-label">Librería SUNAT</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-value">{{ date('Y') }}</div>
                    <div class="stat-label">Actualizado</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Versions Section -->
    <section class="section" style="padding: 80px 0; background: var(--bg-secondary); position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at top right, rgba(5, 223, 114, 0.05), transparent 50%), radial-gradient(circle at bottom left, rgba(59, 130, 246, 0.05), transparent 50%); pointer-events: none;"></div>
        <div class="container" style="position: relative; z-index: 1;">
            <div class="section-header">
                <h2 class="section-title">Planes disponibles</h2>
                <p class="section-description">
                    Empieza gratis o desbloquea el poder de la IA
                </p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px; max-width: 800px; margin: 0 auto;">
                <!-- Basic Plan -->
                <div style="background: var(--bg-primary); border: 2px solid var(--border-color); border-radius: 20px; padding: 28px 24px; transition: all 0.4s ease; position: relative;">
                    <div style="text-align: center; margin-bottom: 24px;">
                        <div style="width: 64px; height: 64px; background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(59, 130, 246, 0.05)); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; border: 2px solid var(--accent-blue);">
                            <i class="fas fa-rocket" style="font-size: 28px; color: var(--accent-blue);"></i>
                        </div>
                        <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary); margin-bottom: 6px;">Básica</h3>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Para iniciar</p>
                    </div>

                    <div style="text-align: center; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--border-color);">
                        <div style="font-size: 2.5rem; font-weight: 900; color: var(--accent-blue); line-height: 1;">Gratis</div>
                        <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 6px;">Sin costo</div>
                    </div>

                    <ul style="list-style: none; padding: 0; margin-bottom: 24px;">
                        <li style="padding: 8px 0; color: var(--text-secondary); display: flex; align-items: center; gap: 10px; font-size: 0.9rem;">
                            <i class="fas fa-check-circle" style="color: var(--accent-blue); font-size: 16px;"></i>
                            <span>Facturas y Boletas</span>
                        </li>
                        <li style="padding: 8px 0; color: var(--text-secondary); display: flex; align-items: center; gap: 10px; font-size: 0.9rem;">
                            <i class="fas fa-check-circle" style="color: var(--accent-blue); font-size: 16px;"></i>
                            <span>Notas Crédito/Débito</span>
                        </li>
                        <li style="padding: 8px 0; color: var(--text-secondary); display: flex; align-items: center; gap: 10px; font-size: 0.9rem;">
                            <i class="fas fa-check-circle" style="color: var(--accent-blue); font-size: 16px;"></i>
                            <span>Guías de Remisión</span>
                        </li>
                        <li style="padding: 8px 0; color: var(--text-secondary); display: flex; align-items: center; gap: 10px; font-size: 0.9rem;">
                            <i class="fas fa-check-circle" style="color: var(--accent-blue); font-size: 16px;"></i>
                            <span>XML, PDF y CDR (Pdf básico)</span>
                        </li>
                    </ul>

                    <a href="https://github.com/yorchavez9/Api-de-facturacion-electronica-sunat-Peru" target="_blank" style="display: flex; align-items: center; justify-content: center; gap: 8px; background: var(--accent-blue); color: white; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 0.95rem; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                        <i class="fab fa-github" style="font-size: 18px;"></i>
                        Descargar
                    </a>
                </div>

                <!-- Pro Plan -->
                <div style="background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-accent) 100%); border: 3px solid var(--primary-color); border-radius: 20px; padding: 28px 24px; transition: all 0.4s ease; position: relative; box-shadow: 0 20px 40px rgba(5, 223, 114, 0.15); transform: scale(1.05);">
                    <div style="position: absolute; top: -14px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; padding: 6px 20px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px; box-shadow: 0 6px 12px rgba(5, 223, 114, 0.3);">
                        ⚡ POPULAR
                    </div>

                    <div style="text-align: center; margin-bottom: 24px; margin-top: 8px;">
                        <div style="width: 64px; height: 64px; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; box-shadow: 0 8px 20px rgba(5, 223, 114, 0.3); animation: pulse 3s ease-in-out infinite;">
                            <i class="fas fa-crown" style="font-size: 28px; color: white;"></i>
                        </div>
                        <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary); margin-bottom: 6px;">Pro con IA</h3>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Versión completa</p>
                    </div>

                    <div style="text-align: center; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--border-color);">
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; align-items: baseline; justify-content: center; gap: 6px;">
                                <span style="font-size: 2rem; font-weight: 900; color: var(--primary-color); line-height: 1;">S/ 300.00</span>
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.9rem;">o USD 95</div>
                        </div>
                        <div style="color: var(--text-muted); font-size: 0.8rem; margin-top: 8px;">Código fuente completo</div>
                    </div>

                    <div style="background: rgba(5, 223, 114, 0.08); border: 1px solid var(--primary-color); border-radius: 12px; padding: 10px; margin-bottom: 20px; text-align: center;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 6px; color: var(--primary-color); font-weight: 700; font-size: 0.95rem;">
                            <i class="fas fa-sparkles" style="animation: checkPulse 2s ease-in-out infinite;"></i>
                            Básica +
                        </div>
                    </div>

                    <ul style="list-style: none; padding: 0; margin-bottom: 20px;">
                        <li style="padding: 8px 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px; font-weight: 500; font-size: 0.9rem;">
                            <i class="fas fa-brain" style="color: var(--primary-color); font-size: 16px;"></i>
                            <span><strong>IA</strong> inteligente</span>
                        </li>
                        <li style="padding: 8px 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px; font-weight: 500; font-size: 0.9rem;">
                            <i class="fas fa-building" style="color: var(--primary-color); font-size: 16px;"></i>
                            <span><strong>Multi-empresa</strong></span>
                        </li>
                        <li style="padding: 8px 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px; font-weight: 500; font-size: 0.9rem;">
                            <i class="fas fa-chart-pie" style="color: var(--primary-color); font-size: 16px;"></i>
                            <span><strong>Reportes</strong> avanzados</span>
                        </li>
                        <li style="padding: 8px 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px; font-weight: 500; font-size: 0.9rem;">
                            <i class="fas fa-headset" style="color: var(--primary-color); font-size: 16px;"></i>
                            <span><strong>Soporte</strong> 24/7</span>
                        </li>
                        <li style="padding: 8px 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px; font-weight: 500; font-size: 0.9rem;">
                            <i class="fas fa-code" style="color: var(--primary-color); font-size: 16px;"></i>
                            <span><strong>Código fuente</strong> incluido</span>
                        </li>
                        <li style="padding: 8px 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px; font-weight: 500; font-size: 0.9rem;">
                            <i class="fas fa-file-archive" style="color: var(--primary-color); font-size: 16px;"></i>
                            <span><strong>Ejemplos</strong> completos</span>
                        </li>
                    </ul>

                    <a href="https://wa.link/z50dwk" target="_blank" style="display: flex; align-items: center; justify-content: center; gap: 8px; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 0.95rem; transition: all 0.3s ease; box-shadow: 0 8px 24px rgba(5, 223, 114, 0.4);">
                        <i class="fab fa-whatsapp" style="font-size: 18px;"></i>
                        Comprar ahora
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">¿Qué incluye?</h2>
                <p class="section-description">
                    Solución completa para facturación electrónica
                </p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h3 class="feature-title">Comprobantes</h3>
                    <p class="feature-description">
                        Emite facturas y boletas con firma digital válida ante SUNAT
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-truck-fast"></i>
                    </div>
                    <h3 class="feature-title">Guías Electrónicas</h3>
                    <p class="feature-description">
                        Genera GRE con validación automática y respuesta inmediata
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Resúmenes</h3>
                    <p class="feature-description">
                        Envía resúmenes diarios y anulaciones de forma automática
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <h3 class="feature-title">Validación</h3>
                    <p class="feature-description">
                        Verifica estados y obtén CDR directamente desde SUNAT
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3 class="feature-title">Seguridad</h3>
                    <p class="feature-description">
                        Conexión certificada SSL para ambientes beta y producción
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <h3 class="feature-title">Archivos</h3>
                    <p class="feature-description">
                        Crea XML firmado y PDF con diseño personalizable
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Action Cards Section -->
    <section class="action-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Comienza ahora</h2>
                <p class="section-description">
                    Descarga recursos y configura tu entorno
                </p>
            </div>
            <div class="action-cards">
                <div class="action-card">
                    <div class="action-card-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <h3>Pack de ejemplos</h3>
                    <p>Descarga colección Postman lista para usar con casos reales.</p>
                    <ul>
                        <li><i class="fas fa-check-circle"></i> Colección Postman</li>
                        <li><i class="fas fa-check-circle"></i> JSON de ejemplo</li>
                        <li><i class="fas fa-check-circle"></i> Variables configuradas</li>
                        <li><i class="fas fa-check-circle"></i> Guía rápida</li>
                    </ul>
                    <a href="{{ asset('assets/ejemplos/EJEMPLOS-API-GO-SUNAT.zip') }}" download class="btn">
                        <i class="fas fa-download"></i>
                        Descargar
                    </a>
                </div>

                <div class="action-card">
                    <div class="action-card-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>Obtener Postman</h3>
                    <p>Instala la herramienta para probar endpoints fácilmente.</p>
                    <ul>
                        <li><i class="fas fa-check-circle"></i> Windows / Mac / Linux</li>
                        <li><i class="fas fa-check-circle"></i> Interfaz simple</li>
                        <li><i class="fas fa-check-circle"></i> Pruebas rápidas</li>
                        <li><i class="fas fa-check-circle"></i> Gratis</li>
                    </ul>
                    <a href="https://www.postman.com/downloads/" target="_blank" class="btn">
                        <i class="fas fa-external-link-alt"></i>
                        Descargar Postman
                    </a>
                </div>

                <div class="action-card">
                    <div class="action-card-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3>Ver documentación</h3>
                    <p>Consulta la guía completa de endpoints y configuración.</p>
                    <ul>
                        <li><i class="fas fa-check-circle"></i> Guías paso a paso</li>
                        <li><i class="fas fa-check-circle"></i> Lista de endpoints</li>
                        <li><i class="fas fa-check-circle"></i> Códigos de error</li>
                        <li><i class="fas fa-check-circle"></i> Casos prácticos</li>
                    </ul>
                    <a href="https://apigo.apuuraydev.com/" target="_blank" class="btn">
                        <i class="fas fa-book-open"></i>
                        Abrir docs
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Video Section -->
    <section class="video-section">
        <div class="container">
            <div class="video-container">
                <h3>Video tutorial</h3>
                <p>Mira cómo importar la colección en Postman en menos de 5 minutos.</p>
                <div class="video-wrapper">
                    <iframe
                        src="https://www.youtube.com/embed/vJ6Ah70Oq4s"
                        title="Tutorial Postman"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>
                <div class="video-actions">
                    <a href="https://www.youtube.com/watch?v=vJ6Ah70Oq4s" target="_blank" class="btn btn-outline">
                        <i class="fab fa-youtube"></i>
                        Abrir en YouTube
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Steps Section -->
    <section class="steps-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Configuración rápida</h2>
                <p class="section-description">
                    Configura Postman en 6 pasos
                </p>
            </div>
            <div class="steps-container">
                <div class="step-item">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4>Descarga el ZIP</h4>
                        <p>Obtén el paquete de ejemplos desde arriba.</p>
                    </div>
                </div>

                <div class="step-item">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4>Extrae archivos</h4>
                        <p>Descomprime el ZIP en cualquier carpeta.</p>
                    </div>
                </div>

                <div class="step-item">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4>Abre Postman</h4>
                        <p>Ejecuta la app de Postman.</p>
                    </div>
                </div>

                <div class="step-item">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h4>Importa colección</h4>
                        <p>Click en "Import" y selecciona el <code>.json</code></p>
                    </div>
                </div>

                <div class="step-item">
                    <div class="step-number">5</div>
                    <div class="step-content">
                        <h4>Ajusta variables</h4>
                        <p>Configura tu URL base:</p>
                        <ul>
                            <li><strong>Local:</strong> <code>http://localhost:8000</code></li>
                            <li><strong>Demo:</strong> <code>https://apigov1.apuuraydev.com</code></li>
                            <li><code>token</code>: Tu token de acceso</li>
                        </ul>
                    </div>
                </div>

                <div class="step-item">
                    <div class="step-number">6</div>
                    <div class="step-content">
                        <h4>Prueba endpoints</h4>
                        <p>Ya puedes hacer tus primeras peticiones.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <p>© {{ date('Y') }} API GO - Laravel 12 + Greenter 5.1</p>
            </div>
            <div class="footer-links">
                <a href="https://apigo.apuuraydev.com/" target="_blank">Docs</a>
                <a href="https://github.com/yorchavez9/Api-de-facturacion-electronica-sunat-Peru" target="_blank">GitHub</a>
                <a href="https://wa.link/z50dwk" target="_blank">Contacto</a>
            </div>
        </div>
    </footer>

    <script>
        // Theme Toggle
        function toggleTheme() {
            const html = document.documentElement;
            const icon = document.getElementById('theme-icon');
            const isDark = html.getAttribute('data-theme') === 'dark';

            if (isDark) {
                html.removeAttribute('data-theme');
                icon.className = 'fas fa-moon';
                localStorage.setItem('theme', 'light');
            } else {
                html.setAttribute('data-theme', 'dark');
                icon.className = 'fas fa-sun';
                localStorage.setItem('theme', 'dark');
            }
        }

        // Load saved theme
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme');
            const icon = document.getElementById('theme-icon');

            if (savedTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                icon.className = 'fas fa-sun';
            }
        });

        // Scroll to Top
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Show/Hide Scroll Button
        window.addEventListener('scroll', () => {
            const scrollBtn = document.querySelector('.scroll-top');
            if (window.pageYOffset > 300) {
                scrollBtn.classList.add('visible');
            } else {
                scrollBtn.classList.remove('visible');
            }
        });
    </script>
</body>
</html>
