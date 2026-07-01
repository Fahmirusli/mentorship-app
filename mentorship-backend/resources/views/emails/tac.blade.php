<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subjectString }}</title>
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f111a;
            margin: 0;
            padding: 0;
            color: #ffffff;
        }
        .wrapper {
            background-color: #0f111a;
            background-image: linear-gradient(135deg, #1e1b4b 0%, #0f111a 100%);
            width: 100%;
            table-layout: fixed;
            padding: 60px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #1a1c23;
            border-radius: 20px;
            border: 1px solid #374151;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            overflow: hidden;
        }
        .header {
            text-align: center;
            padding: 40px 40px 20px;
        }
        .logo-box {
            display: inline-block;
            background-color: #4f46e5;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }
        .logo-text {
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            letter-spacing: 1px;
        }
        .content {
            padding: 20px 40px 40px;
            text-align: center;
        }
        .title {
            font-size: 24px;
            font-weight: 700;
            color: #f3f4f6;
            margin-bottom: 15px;
            margin-top: 0;
        }
        .text {
            font-size: 16px;
            color: #9ca3af;
            line-height: 1.6;
            margin-bottom: 35px;
        }
        .tac-container {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border-radius: 16px;
            padding: 25px;
            margin: 0 auto 35px;
            max-width: 300px;
            box-shadow: 0 10px 25px rgba(124, 58, 237, 0.3);
        }
        .tac-code {
            font-size: 42px;
            font-weight: 900;
            letter-spacing: 14px;
            color: #ffffff;
            margin: 0;
            margin-right: -14px;
            text-align: center;
            font-family: 'Courier New', Courier, monospace;
        }
        .footer {
            background-color: #111318;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #374151;
        }
        .footer-text {
            font-size: 13px;
            color: #6b7280;
            margin: 0 0 10px;
        }
        .highlight {
            color: #a78bfa;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <div class="logo-box">
                    <h1 class="logo-text">UPLIFTS</h1>
                </div>
            </div>
            <div class="content">
                <h2 class="title">{{ $subjectString }}</h2>
                <p class="text">
                    Here is your secure 6-digit authorization code. Please enter it in the application to continue with your request.
                </p>
                
                <div class="tac-container">
                    <p class="tac-code">{{ $tac }}</p>
                </div>
                
                <p class="text" style="font-size: 14px; margin-bottom: 0;">
                    This code will expire in <span class="highlight">10 minutes</span>.<br>
                    If you did not initiate this request, please securely ignore this email.
                </p>
            </div>
            <div class="footer">
                <p class="footer-text">
                    &copy; {{ date('Y') }} Uplifts Mentorship. All rights reserved.
                </p>
                <p class="footer-text" style="margin-bottom: 0;">
                    You are receiving this email because a request was made on your account.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
