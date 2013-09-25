<?php
    // Generates a token against CSRF
    // ==============================
    function generate_token($name = '') {
        if(session_id() == '')
            session_start();

        $token = uniqid(rand(), true);

        $_SESSION[$name.'_token'] = $token;
        $_SESSION[$name.'_token_time'] = time();

        return $token;
    }

    // Checks that the anti-CSRF token is correct
    // ==========================================
    function check_token($time, $name = '') {
        if(session_id() == '')
            session_start();

        if(isset($_SESSION[$name.'_token']) && isset($_SESSION[$name.'_token_time']) && (isset($_POST['token']) || isset($_GET['token']))) {
            if(!empty($_POST['token']))
                $token = $_POST['token'];
            else
                $token = $_GET['token'];

            if($_SESSION[$name.'_token'] == $token) {
                if($_SESSION[$name.'_token_time'] >= (time() - (int) $time))
                    return true;
            }
        }
        return false;
    }
