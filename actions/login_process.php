<?php
include '../../../init.php';
include '../../../Actions/module_login_security.php';

mlsValidateCsrf();

$clientIP  = filter_var($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', FILTER_VALIDATE_IP) ?: '0.0.0.0';
$rateCheck = mlsCheckRateLimit($clientIP);
if ($rateCheck['blocked']) {
    $wait = (int) $rateCheck['wait'];
    echo "<script>app_alert(\"Too Many Attempts\",\"Too many failed login attempts. Please try again in {$wait} minute(s).\",\"warning\",\"Ok\",\"\",\"\");</script>";
    exit();
}

if (!isset($_POST['mode'])) {
    echo '<script>app_alert("Warning","The Mode you are trying to pass does not exist","warning","Ok","","no");</script>';
    exit();
}

$mode = $_POST['mode'];
$db   = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if ($mode == 'c10cc6b684e1417e8ffa924de1e58373') {

    $uname    = trim($_POST['username'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $user_app = "Warehouse Management";
    $mods     = str_replace(" ", "_", $user_app);
    $password = $encpass->encryptedPassword($pass, $db);

    $stmt = $db->prepare(
        "SELECT void_access, company, idcode, username, level, role, firstname, lastname
         FROM tbl_system_user WHERE username = ? AND password = ?"
    );
    if (!$stmt) {
        echo '<script>app_alert("Error","A system error occurred. Please try again.","warning","Ok","","");</script>';
        $db->close(); exit();
    }
    $stmt->bind_param("ss", $uname, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $listrow    = $result->fetch_assoc();
        $stmt->close();
        $void       = $listrow['void_access'];
        $company    = $listrow['company'];
        $idcode     = $listrow['idcode'];
        $username   = $listrow['username'];
        $user_level = $listrow['level'];
        $user_role  = $listrow['role'];
        $employee   = $listrow['firstname'] . ' ' . $listrow['lastname'];

        if ($user_role != 'Administrator' && $user_level < 80) {
            if (mlsCheckPolicy($username, $user_app, $db) == 0) {
                mlsRecordFailedAttempt($clientIP);
                $db->close();
                echo '<script>app_alert("Access Denied","You have no access to this application","warning","Ok","","");</script>';
                exit();
            }
        }
        if ($void == 0) {
            $recipient = 'WAREHOUSE';
            session_regenerate_id(true);
            $_SESSION['csrf_token']          = bin2hex(random_bytes(32));
            if ($recipient != '') $_SESSION['wms_user_recipient'] = $recipient;
            $_SESSION['wms_username']    = $username;
            $_SESSION['wms_company']     = $company;
            $_SESSION['wms_appnameuser'] = $employee;
            $_SESSION['wms_userlevel']   = $user_level;
            $_SESSION['wms_userrole']    = $user_role;
            $_SESSION['wms_application'] = $user_app;
            mlsClearFailedAttempts($clientIP);
            $db->close();
            $safeCompany  = htmlspecialchars($company,  ENT_QUOTES, 'UTF-8');
            $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
            $safeMods     = htmlspecialchars($mods,     ENT_QUOTES, 'UTF-8');
            echo "<script>
                \$(\"#formmodal\").hide();
                sessionStorage.setItem('company',  '{$safeCompany}');
                sessionStorage.setItem('username', '{$safeUsername}');
                sessionStorage.setItem('module',   '{$safeMods}');
                app_alert('Sign In Success','You have successfully signed in','success','Ok','','appyes');
            </script>";
            exit();
        } else {
            $db->close();
            echo '<script>app_alert("System Message","Your account is locked. Please contact the system Administrator.","warning","Ok","","");</script>';
            exit();
        }
    } else {
        $stmt->close();
        mlsRecordFailedAttempt($clientIP);
        $db->close();
        echo '<script>$("#upass").val(""); app_alert("Login Error","Invalid Username or Password","warning","Ok","","");</script>';
        exit();
    }
}

