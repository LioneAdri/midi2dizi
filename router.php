<?php

session_start();

if (isset($_POST)) {

    $file = (isset($_FILES['midi']) && $_FILES['midi']['tmp_name'] != '') ? $_FILES['midi']['tmp_name'] : '';

    if ($file != '') {
        require('classes/dizi.class.php');

        $fn = $_FILES['midi']['name'];
        $bn = strtok($fn, '.');

        $midi = new Dizi();
        $midi->importMid($file);
        echo $midi->getNotes($bn, $_POST['baseNote'], $_POST['flute']); // $baseNote = "C5", $flute = "dizi", $tn = -1
        return;
    }


    echo json_encode(array ('success' => false, 'alert' => "Midi parse fail"));
    return;
}

echo "404";