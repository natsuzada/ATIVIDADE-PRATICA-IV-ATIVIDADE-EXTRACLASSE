<?php

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'todo_app');


$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);


if($link === false){

    die("ERRO: Não foi possível conectar ao banco de dados. " . mysqli_connect_error());
}

/**
 * @param string
 * @param string
 * @param array
 * @return mysqli_result|bool
 */
function execute_query($sql, $types, $params, $link) {
    if ($stmt = mysqli_prepare($link, $sql)) {
        if (!empty($types) && !empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        if (mysqli_stmt_execute($stmt)) {
            if (strtoupper(substr($sql, 0, 6)) === 'SELECT' || strtoupper(substr($sql, 0, 4)) === 'CALL') {
                $result = mysqli_stmt_get_result($stmt);
                mysqli_stmt_close($stmt);
                return $result;
            } else {
                $affected_rows = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);
                return $affected_rows !== -1;
            }
        } else {
            error_log("ERRO ao executar a query: " . mysqli_error($link));
            mysqli_stmt_close($stmt);
            return false;
        }
    } else {
        error_log("ERRO ao preparar a query: " . mysqli_error($link));
        return false;
    }
}
?>