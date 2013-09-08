<?php
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

    function formatUsersIn($users_in, $all_users) {
        global $localized;
        // TODO : Move this function to somewhere else ?
        $return = '';
        $users_in = $users_in->get();

        $i = false;
        foreach($users_in as $user_in=>$guests) {
            if($i) { $return .= '<br/>'; } else { $i = true; }

            $return .= $all_users[$user_in]->getDisplayName();
            if($guests != 0) {
                if($guests > 1)
                    $return .= ' ('.$guests.' '.$localized['guest'][LANG].'s)';
                else 
                    $return .= ' ('.$guests.' '.$localized['guest'][LANG].')';
            }
        }

        return $return;
    }
