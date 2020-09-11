<?php

session_start();

if (isset($_POST)) {

    if (isset($_POST['demo'])) {
        $file = "demo/".$_POST['demo'].".mid";
    } else {
        $file = (isset($_FILES['midi']) && $_FILES['midi']['tmp_name'] != '') ? $_FILES['midi']['tmp_name'] : '';
    }

    if ($file != '') {
        require('classes/dizi.class.php');

        $fn = isset($_POST['demo']) ? $_POST['demo'] : $_FILES['midi']['name'];
        $bn = strtok($fn, '.');

        $midi = new Dizi();
        $midi->importMid($file);
        echo $midi->getNotes($bn, $_POST['baseNote'], $_POST['flute']);
        return;
    }


    echo json_encode(array ('success' => false, 'alert' => "File not found ".$file));
    return;
}

echo "404";