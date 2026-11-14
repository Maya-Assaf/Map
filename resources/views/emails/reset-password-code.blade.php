<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Code</title>
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
            background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
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
        .code-box {
            margin: 28px 0;
            padding: 20px;
            background-color: #fef2f2;
            border: 2px dashed #ef4444;
            border-radius: 10px;
            text-align: center;
        }
        .code-title {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #991b1b;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .code-value {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #b91c1c;
            font-family: 'Courier New', Courier, monospace;
        }
        .notice {
            font-size: 13px;
            color: #64748b;
            background-color: #f8fafc;
            border-left: 4px solid #ef4444;
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
            <div class="greeting">Hello{{ !empty($userName) ? ' ' . e($userName) : '' }},</div>
            <p>You have requested to reset your password. Use the verification code below to verify your request and reset your password:</p>
            
            <div class="code-box">
                <div class="code-title">Reset Password Code</div>
                <div class="code-value">{{ $code }}</div>
            </div>

            <p>Enter this 6-digit code in the app to proceed with resetting your password.</p>

            <div class="notice">
                <strong>Important:</strong> This reset code will expire in <strong>15 minutes</strong>. If you did not request a password reset, please ignore this email or contact support immediately.
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Reparametrize. All rights reserved.
        </div>
    </div>
</body>
</html>
