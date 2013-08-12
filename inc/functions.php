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
