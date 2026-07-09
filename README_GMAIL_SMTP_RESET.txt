AquaIntelX Gmail SMTP Password Reset Setup
==========================================

Files to replace/add:
- login.html
- login.php
- reset_password.html
- reset_password.php
- smtp_mailer.php
- aquaintelx_password_reset_smtp_update.sql

Railway Variables to add:
- SMTP_HOST=smtp.gmail.com
- SMTP_PORT=587
- SMTP_SECURE=tls
- SMTP_USERNAME=yourgmail@gmail.com
- SMTP_PASSWORD=your-gmail-app-password
- SMTP_FROM_EMAIL=yourgmail@gmail.com
- SMTP_FROM_NAME=AquaIntelX
- APP_URL=https://your-railway-app.up.railway.app

Important:
- Use a Gmail App Password, not your normal Gmail password.
- Your Gmail account usually needs 2-Step Verification before App Passwords are available.
- Run aquaintelx_password_reset_smtp_update.sql once in Railway MySQL.

Reset flow:
1. User clicks Forgot password?
2. User enters registered email.
3. System sends a 6-digit verification code through Gmail SMTP.
4. User enters code + new password.
5. Password changes only if the code is valid and not expired.

Code expiry: 10 minutes.
