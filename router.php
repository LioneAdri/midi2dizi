<?php

session_start();

if (isset($_POST)) {

    if (isset($_POST['check']) && $_POST['check']) {
        echo checkMidi();
        return;
    }

    if (isset($_POST['demo'])) {
        $file = "demo/".$_POST['demo'].".mid";
    }  else {
        $file = (isset($_FILES['midi']) && $_FILES['midi']['tmp_name'] != '') ? $_FILES['midi']['tmp_name'] : '';
    }

    if ($file != '') {
        require('classes/dizi.class.php');

        $fn = isset($_POST['demo']) ? $_POST['demo'] : $_FILES['midi']['name'];
        $bn = strtok($fn, '.');

        $midi = new Dizi();
        try {
            $midi->importMid($file);
        } catch (Exception $e) {
            echo json_encode(array ('success' => false, 'alert' =>  $e->getMessage()));
            return;
        }
        echo $midi->getNotes($bn, $_POST['baseNote'], $_POST['flute']);
        return;
    }


    echo json_encode(array ('success' => false, 'alert' => "File not found ".$file));
    return;
}

function checkMidi () {
    $file = (isset($_FILES['midi']) && $_FILES['midi']['tmp_name'] != '') ? $_FILES['midi']['tmp_name'] : '';
    require('classes/dizi.class.php');

    $fn = isset($_POST['demo']) ? $_POST['demo'] : $_FILES['midi']['name'];
    $bn = strtok($fn, '.');

    $midi = new Dizi();
    try {
        $midi->importMid($file);
        $midiData = array(
            "tracks" => ($midi->getTrackCount() - 1),
            "transposeMin"=>-24, // soon
            "transposeMax"=>24,
            'success' => true
        );
        return json_encode($midiData);

    } catch (Exception $e) {
        return json_encode(array ('success' => false, 'alert' =>  $e->getMessage()));
    }
}

echo "404";