<?php
// Hapus cookie sm_uid
setcookie('sm_uid', '', time() - 3600, '/', '', false, true);
header("Location: login.php");
exit();
