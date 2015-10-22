<?php

    function dump($variable) {
        echo "<pre style=\"border: 1px solid #000; margin: 0.5em;\">";
        var_dump($variable);
        echo "</pre>\n";
    }

    function ordinalSuffix($num) {
        $suffixes = array("st", "nd", "rd");
        $lastDigit = $num % 10;

        if(($num > 9 && $num < 20) || $lastDigit == 0 || $lastDigit > 3) return "th";

        return $suffixes[$lastDigit - 1];
    }

    function isDevelopment() {
        return preg_match(
            '/localhost/',
            "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"
        );
    }

    function logger($message, $priority, $userId = 0) {
        $dev = isDevelopment() ? 'dev' : 'prd';
        $sql = "INSERT INTO tb_log (
                message,
                priority,
                datetime,
                environment,
                fk_pk_user_id
            ) VALUES (
                '$message',
                '$priority',
                NOW(),
                '$dev',
                $userId
            );";
        ModelFactory::execute($sql);
    }
