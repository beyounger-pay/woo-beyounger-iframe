<?php

class SHA256Util {
    public static function sign($signature) {
        $signature = hash('sha256', $signature);
        return $signature;
    }

}
