<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password Link</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #333333;
        }
        .container {
            max-width: 580px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .header {
            background: linear-gradient(135deg, #1e293b 0%, #475569 100%);
            padding: 30px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 36px 30px;
            line-height: 1.6;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .button-box {
            margin: 30px 0;
            text-align: center;
        }
        .btn-reset {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            padding: 14px 28px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);
        }
        .url-text {
            word-break: break-all;
            font-size: 12px;
            color: #64748b;
            margin-top: 20px;
        }
        .notice {
            font-size: 13px;
            color: #64748b;
            background-color: #f8fafc;
            border-left: 4px solid #64748b;
            padding: 12px 16px;
            border-radius: 4px;
            margin-top: 24px;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Reparametrize</h1>
        </div>
        <div class="content">
            <div class="greeting">Hello,</div>
            <p>You are receiving this email because we received a password reset request for your account.</p>
            
            <div class="button-box">
                <a href="{{ $resetUrl }}" class="btn-reset" target="_blank">Reset Password</a>
            </div>

            <div class="notice">
                This password reset link will expire in <strong>60 minutes</strong>. If you did not request a password reset, no further action is required.
            </div>

            <div class="url-text">
                If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:<br>
                <a href="{{ $resetUrl }}" style="color: #2563eb;">{{ $resetUrl }}</a>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Reparametrize. All rights reserved.
        </div>
    </div>
</body>
</html>
