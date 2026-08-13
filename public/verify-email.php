<?php
header('Location: ../api/index.php?path=auth/verify-email&token=' . urlencode($_GET['token'] ?? ''));
exit;
