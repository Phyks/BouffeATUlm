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
