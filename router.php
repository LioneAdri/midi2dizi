<?php

session_start();

if (isset($_POST)) {

    if (isset($_POST['check']) && $_POST['check']) {
        echo checkMidi();
        return;
    }

    if (isset($_POST['demo'])) {
        $file = "demo/".$_POST['demo'].".mid";
        $tn = -1;
        $bn = $_POST['demo'];
    }  else {
        $file = (isset($_FILES['midi']) && $_FILES['midi']['tmp_name'] != '') ? $_FILES['midi']['tmp_name'] : '';
        $tn = ($_POST['track'] != '' ? $_POST['track'] : 0);
        $fn = isset($_POST['demo']) ? $_POST['demo'] : $_FILES['midi']['name'];
        $bn = strtok($fn, '.');
    }

    if ($file != '') {
        require('classes/dizi.class.php');
        $midi = new Dizi();
        try {
            $midi->importMid($file);
        } catch (Exception $e) {
            echo json_encode(array ('success' => false, 'alert' =>  $e->getMessage()));
            return;
        }

        $tr = ($_POST['transpose'] != '' ? $_POST['transpose'] : 0);

        echo $midi->getNotes($bn, $_POST['baseNote'], $_POST['flute'], $tn, $tr);
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
            "tracks_" => $midi->tracks,
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