<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #222;">
  <h2>Verify your Library Book Share account</h2>
  <p>Hello <?php echo htmlspecialchars($name); ?>,</p>
  <p>Thank you for registering. Click the button below to activate your account.</p>
  <p>
    <a href="<?php echo htmlspecialchars($verifyUrl); ?>"
       style="background:#2563eb;color:#fff;padding:10px 16px;text-decoration:none;border-radius:4px;">
      Verify Email
    </a>
  </p>
  <p>If the button does not work, open this link:</p>
  <p><?php echo htmlspecialchars($verifyUrl); ?></p>
</body>
</html>

