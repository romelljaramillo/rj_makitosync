<?php

// function listarArchivos($pathIn, $pathOut)
function listarArchivos($pathIn)
{
    $ignore_dir = array(".", "..", "cambiarnombre.php" ); // directorios a ignorar

    $df = opendir($pathIn);

    while (($file = readdir($df)) !== false) {
        $f = $pathIn . '/' . $file;

        if (is_file($f) && !in_array($file, $ignore_dir)) {

            if (obtenerExtension($f) === 'imax'){
                $fichero = substr($f, 0, -strlen('.imax'));
                $renombre =  'OLD_' . substr($file, 0, -strlen('.imax'));
                rename ($fichero, $renombre);
                $datos = file_get_contents(dirname(__FILE__)."/". $f);
                $data = gzuncompress($datos);
                $textDecode = base64_decode($data);
                $actual = substr($textDecode, 12);
                file_put_contents($fichero, '<?php ' . $actual, FILE_APPEND);
            }
        } elseif (is_dir($f) && !in_array($file, $ignore_dir)) {
            listarArchivos($f);
        }
    }
}

function obtenerExtension($str)
{
    return end(explode(".", $str));
}

listarArchivos(".");