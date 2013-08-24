<?php
    define('DATA_DIR', 'data'); // Data subdirectory
    define('IPBANS_FILENAME', DATADIR.'/ipbans.php'); // File storage for failures and bans.
    define('BAN_AFTER', 5); // Ban IP after this many failures.
    define('BAN_DURATION', 1800); // Ban duration for IP address after login failures (in seconds) (1800 sec. = 30 minutes)
    if (!is_dir(DATADIR)) { mkdir(DATADIR,0705); chmod(DATADIR,0705); }
    if (!is_file(DATADIR.'/.htaccess')) { file_put_contents(DATADIR.'/.htaccess',"Allow from none\nDeny from all\n"); } // Protect data files.

    function logm($message)
    {
        $t = strval(date('Y/m/d_H:i:s')).' - '.$_SERVER["REMOTE_ADDR"].' - '.strval($message)."\n";
        file_put_contents(DATADIR.'/log.txt',$t,FILE_APPEND);
    }


    // ------------------------------------------------------------------------------------------
    // Brute force protection system
    // Several consecutive failed logins will ban the IP address for 30 minutes.
    if (!is_file(IPBANS_FILENAME)) file_put_contents(IPBANS_FILENAME, "<?php\n\$GLOBALS['IPBANS']=".var_export(array('FAILURES'=>array(),'BANS'=>array()),true).";\n?>");
    include IPBANS_FILENAME;
    // Signal a failed login. Will ban the IP if too many failures:
    function ban_loginFailed()
    {
        $ip=$_SERVER["REMOTE_ADDR"]; $gb=$GLOBALS['IPBANS'];
        if (!isset($gb['FAILURES'][$ip])) $gb['FAILURES'][$ip]=0;
        $gb['FAILURES'][$ip]++;
        if ($gb['FAILURES'][$ip]>(BAN_AFTER-1))
        {
            $gb['BANS'][$ip]=time()+BAN_DURATION;
            logm('IP address banned from login');
        }
        $GLOBALS['IPBANS'] = $gb;
        file_put_contents(IPBANS_FILENAME, "<?php\n\$GLOBALS['IPBANS']=".var_export($gb,true).";\n?>");
    }

    // Signals a successful login. Resets failed login counter.
    function ban_loginOk()
    {
        $ip=$_SERVER["REMOTE_ADDR"]; $gb=$GLOBALS['IPBANS'];
        unset($gb['FAILURES'][$ip]); unset($gb['BANS'][$ip]);
        $GLOBALS['IPBANS'] = $gb;
        file_put_contents(IPBANS_FILENAME, "<?php\n\$GLOBALS['IPBANS']=".var_export($gb,true).";\n?>");
        logm('Login ok.');
    }

    // Checks if the user CAN login. If 'true', the user can try to login.
    function ban_canLogin()
    {
        $ip=$_SERVER["REMOTE_ADDR"]; $gb=$GLOBALS['IPBANS'];
        if (isset($gb['BANS'][$ip]))
        {
            // User is banned. Check if the ban has expired:
            if ($gb['BANS'][$ip]<=time())
            { // Ban expired, user can try to login again.
                logm('Ban lifted.');
                unset($gb['FAILURES'][$ip]); unset($gb['BANS'][$ip]);
                file_put_contents(IPBANS_FILENAME, "<?php\n\$GLOBALS['IPBANS']=".var_export($gb,true).";\n?>");
                return true; // Ban has expired, user can login.
            }
            return false; // User is banned.
        }
        return true; // User is not banned.
    }

    // Returns user IP
    function user_IPs()
    {
        $ip = $_SERVER["REMOTE_ADDR"];
        // Then we use more HTTP headers to prevent session hijacking from users behind the same proxy.
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) { $ip=$ip.'_'.$_SERVER['HTTP_X_FORWARDED_FOR']; }
        if (isset($_SERVER['HTTP_CLIENT_IP'])) { $ip=$ip.'_'.$_SERVER['HTTP_CLIENT_IP']; }
        return $ip;
    }
