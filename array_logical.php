<?php
/* Функция делает логические операции с массивами. Может объединять массивы или находить их пересечение (через eval).
    Для Arr1() || Arr2()  ->     array_merge(Arr1,Arr2)
    Для Arr1() && Arr2()  ->     array_intersect(Arr1,Arr2)
И т.д.
*/
// Процедурная реализация (без ООП)

$a = array();
$a[1] = array(1, 2, 3, 10);
$a[2] = array(3, 4, 5, 2);
$a[3] = array(7, 8, 9, 10);
$a[4] = array(10, 11, 2);
$a[5] = array(14, 15, 16);

// Тестовое выражение
$bool_string = '$a[1] && ($a[2] || ($a[3] && $a[4] || ($a[2] && $a[5] && $a[4] || $a[1] && $a[3])))';
$bool_string = '$a['. (1). ']' .' && '. '($a['. (2).']'.' || '.'($a['.(3). ']'.' && '. '$a['. (4) . ']'.' || '.'($a['. (2). ']'.' && '.'$a['. (5). ']'.' && '.'$a['. (4). ']'.' || '. '$a['. (1). ']'.' && '.'$a['. (3). '])))';
//print_r(array_intersect($a[1],array_merge($a[2],array_merge(array_intersect($a[3],$a[4]),array_merge(array_intersect(array_intersect($a[2],$a[5]),$a[4]),array_intersect($a[1],$a[3]))))));

// Пример тестового запуска:
print_r(array_logical_converter($a, $bool_string));



/******    ГЛАВНАЯ ФУНКЦИЯ    *******************/
function array_logical_converter($a, $bool_string){
    $logical_rezult = array_logical_expression($bool_string, 0);

    $message_to_user = 'Ошибка при оценке логического выражения при помощи функции eval. Вот оно, это выражение: '. $bool_string;
    $rez_Arr = eval_logical_Arr_operations($logical_rezult, $message_to_user, $a);

return $rez_Arr;
}

function array_logical_expression($logical_rezult, $i){
// 1. Вначале надо бы оценить строку, содержащую выражение, при помощи eval (на корректность). Результат оценки пока неважен
    $message_to_user = 'Error|выражение с искомыми словами составлено некорректно, функция eval() не может его оценить.';

    $rez_Arr = eval_logical_Arr_operations((bool)$logical_rezult, $message_to_user);
    if($rez_Arr[0] === -1){
        return $message_to_user;
    }

    $i_max = 20;
    if($i++ > $i_max){
        return 'Error|В функции '. __FUNCTION__  .'() превышено число итераций рекурсии, равное '. $i_max.'.';
    }

// 2. Определяем самые вложенные (внутренние) скобки в строке логического выражения (быть может, те, что еще остались)
    preg_match("~(\([^()]*?\))~", $logical_rezult, $matches);

    if(sizeof($matches) > 0){
        $parenth = parenthesis_operators($matches[0]);
        $logical_rezult = str_replace($matches[0], $parenth, $logical_rezult);

        $logical_rezult = array_logical_expression($logical_rezult, $i);

    }else{ // Если скобок (уже) нет
        $logical_rezult = parenthesis_operators($logical_rezult);
        $logical_rezult = str_replace(array('{', '}'), array('(', ')'), $logical_rezult);
    }

    return $logical_rezult;
}

function parenthesis_operators($parenth){
    $parenth = str_replace(array('(', ')'), ' ', $parenth);
    $parenth = change_logical_operator_to_F($parenth, '&&', 0);
    $parenth = change_logical_operator_to_F($parenth, '||', 0);

return strval($parenth);
}

// Функция преобразовывает строку, содержащую логические операции для массивов, в строку с соответствующими массивными функциями
function change_logical_operator_to_F($parenth, $operator, $i){
/* Для Arr1() && Arr2() :     array_merge(Arr1,Arr2)
   Для Arr1() || Arr2() :     array_intersect(Arr1,Arr2)
*/

$i_max = 20;
if($i++ > $i_max){
    return 'Error|В функции '. __FUNCTION__  .'() превышено число итераций рекурсии, равное '. $i_max.'.';
}
    $operators_Arr = array('&&' => 'array_intersect', "||" => 'array_merge');

    $funct = $operators_Arr[$operator];

    if(!isset($funct)){
        return 'Error|Задан неверный логический оператор. Допускается только: '. "&&, &mid;&mid;";
    }

    if(strpos($parenth, $operator) !== false){
        $replacement_amp = " ". $funct ."{". '$1,$2'."} ";
        $parenth = preg_replace('~\s+([^&\|]+?)'. preg_quote($operator, '|') .'(\s*[^&\|]+?)\s+~', $replacement_amp, $parenth);
        $parenth = preg_replace('~\s+~', '', $parenth);
        $parenth = preg_replace('~&&~', ' && ', $parenth);
        $parenth = ' '. preg_replace('~\|\|~', ' || ', $parenth). ' ';

        $parenth = change_logical_operator_to_F($parenth, $operator, $i);
    }

return strval($parenth);
}


function eval_logical_Arr_operations($bool_string, $message_to_user, $a = null){
// В массиве:
// true, если есть совпадение с выражением для искомых искомых слов; false - если нет.

    $bool_string_REZ = 0;

    $str_code = "\$bool_string_REZ = ". $bool_string;
    @eval($str_code. "|| 1". ";"); // Для проверки корректности выражения $str_code. Если оно верно, результат eval() даст заведомо 1 (true)

    if(!!$bool_string_REZ){
        eval($str_code. ";"); // Если ошибки не было, получаем фактическое значение

        return array(null, $bool_string_REZ);

    }else{ // Значит, возникла ошибка в выражении для eval()
        return array(-1, $message_to_user);
    }
}

