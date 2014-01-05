<?php
    function logout() {
        setcookie('bouffeatulm_staySignedIn', FALSE, 0, WEB_PATH);

        if(isset($_COOKIE['bouffeatulm_login']))
            setcookie('bouffeatulm_login', $_COOKIE['bouffeatulm_login'], 0, WEB_PATH);

        session_destroy();
    }

    function getNotice() {
        if(!file_exists('data/notice')) {
            file_put_contents('data/notice');
        }

        return file_get_contents('data/notice');
    }

    function setNotice($notice) {
        return file_put_contents('data/notice', $notice);
    }

    function secureDisplay($unsecured) {
        $return = NULL;
        if(is_array($unsecured)) {
            $return = array();
            foreach($unsecured as $key=>$unsecured_item) {
                $return[$key] = secureDisplay($unsecured_item);
            }
        }
        elseif(is_object($unsecured)) {
            $return = $unsecured->secureDisplay();
        }
        elseif(is_numeric($unsecured)) {
            if(intval($unsecured) == floatval($unsecured))
                $return = (int) $unsecured;
            else
                $return = (float) $unsecured;
        }
        elseif(is_bool($unsecured)) {
            $return = (bool) $unsecured;
        }
        else {
            $return = htmlspecialchars($unsecured);
        }

        return $return;
    }

    function ampm2int($date) {
        if($date == 'am')
            return 0;
        else
            return 1;
    }

    function int2ampm($hour) {
        if($hour == 0)
            return 6;
        else
            return 18;
    }

    function listTemplates($dir) {
        if(strrpos($dir, '/') !== strlen($dir) - 1) {
            $dir .= '/';
        }

        $return = array();

        if ($handle = opendir($dir)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != ".." && $entry != 'json' && is_dir($dir.$entry)) {
                    $return[] = array('value'=>$entry, 'option'=>str_replace(array('_en', '_fr'), array(' (English)', ' (French)'), $entry));
                }
            }
            closedir($handle);
        }
        return $return;
    }

    function TwoDArrayToOneD($array, $key) {
        $return = array();

        foreach($array as $value) {
            $return[] = $value[$key];
        }

        return $return;
    }

    // Sendmail function by Bronco
    function sendmail($to, $subject = '[Bouffe@Ulm]', $msg, $from = null, $format = 'text/plain') {
        $r = "\r\n";
        $header = '';
        $msg = wordwrap ($msg, 70, $r);
        if ($format != 'text/plain') {
            $msg = htmlspecialchars ($msg);
        }
        if (!empty ($from)) {
            $header .= 'From: '.$from.$r;
        }
        $header =
            'Content-Type: text/plain; charset="utf-8"'.$r.
            'Content-Transfer-Encoding: 8bit'.$r.$header;

        return mail ($to, $subject, $msg, $header);
    }

    // Function to sort an array by abs desc
    function sort_array_abs($a, $b) {
        if(abs($a) == abs($b))
            return 0;

        return (abs($a) < abs($b)) ? 1 : -1;
    }
