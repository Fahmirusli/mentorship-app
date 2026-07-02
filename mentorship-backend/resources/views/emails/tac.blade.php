<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subjectString }}</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #0b0f19;
            margin: 0;
            padding: 0;
            color: #ffffff;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            background-color: #0b0f19;
            width: 100%;
            table-layout: fixed;
            padding: 40px 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #111827;
            border-radius: 24px;
            border: 1px solid #1f2937;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .header-bg {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 40px 20px;
            text-align: center;
        }
        .logo-text {
            font-size: 28px;
            font-weight: 900;
            color: #ffffff;
            margin: 0;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .content {
            padding: 40px 30px;
            text-align: center;
        }
        .title {
            font-size: 22px;
            font-weight: 700;
            color: #f9fafb;
            margin: 0 0 15px 0;
        }
        .text {
            font-size: 15px;
            color: #d1d5db;
            line-height: 1.7;
            margin: 0 0 30px 0;
        }
        .tac-box {
            background-color: #1f2937;
            border: 2px dashed #4f46e5;
            border-radius: 16px;
            padding: 25px;
            margin: 0 auto 30px auto;
            max-width: 320px;
        }
        .tac-code {
            font-size: 48px;
            font-weight: 800;
            letter-spacing: 12px;
            color: #818cf8;
            margin: 0;
            margin-right: -12px;
            text-align: center;
            font-family: 'SF Mono', ui-monospace, Menlo, Monaco, Consolas, monospace;
        }
        .warning-text {
            font-size: 13px;
            color: #9ca3af;
            line-height: 1.5;
            margin: 0;
        }
        .highlight {
            color: #a78bfa;
            font-weight: 600;
        }
        .footer {
            background-color: #0b0f19;
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid #1f2937;
        }
        .footer-text {
            font-size: 12px;
            color: #6b7280;
            margin: 0 0 8px 0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <table class="container" width="100%" cellpadding="0" cellspacing="0" border="0" align="center">
            <tr>
                <td class="header-bg">
                    <h1 class="logo-text">UPLIFTS</h1>
                </td>
            </tr>
            <tr>
                <td class="content">
                    <h2 class="title">{{ $subjectString }}</h2>
                    <p class="text">
                        You're almost there! Use the secure authorization code below to complete your request.
                    </p>
                    
                    <div class="tac-box">
                        <p class="tac-code">{{ $tac }}</p>
                    </div>
                    
                    <p class="warning-text">
                        This code will expire in <span class="highlight">10 minutes</span>.<br>
                        If you didn't request this, you can safely ignore this email.
                    </p>
                </td>
            </tr>
            <tr>
                <td class="footer">
                    <p class="footer-text">&copy; {{ date('Y') }} Uplifts Mentorship. All rights reserved.</p>
                    <p class="footer-text">Securely sent from the Uplifts platform.</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
