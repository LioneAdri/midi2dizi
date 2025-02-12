<?php
require('midi.class.php');

/**
 * Class Dizi
 * This version of Midi parser is made for basic Midi files.
 * This can make Dizi/Xiao sheet music from only one Track.
 * Minimum note length can be 1/16, it allows single dotted note,
 * and no grace notes, no bends, no vibrato, no tempo changing, etc.
 */

class Dizi extends Midi {

    var $notes = array('C','#C','D','#D','E','F','#F','G','#G','A','#A','B');
    var $defaultDur = 4;
    var $defaultScale = 5;
    var $defaultBpm = 120;
    var $baseNote = "C5";
    var $flute = "dizi";
    var $valid = true;
    // xiao: F4/G4
    // dizi: C5, D5, E5, F5, E5
    /**
     * @var mixed
     */
    private $fluteMapArray;
    /**
     * @var mixed
     */
    private $noteMapArray;

    /****************************************************************************
    *                                                                           *
    *                              Public methods                               *
    *                                                                           *
    ****************************************************************************/

    /**
     * @param string $title
     * @param string $baseNote
     * @param string $flute
     * @param int $tn track number
     * @param int $tr transpose
     * @return string
     */
    public function getNotes($title = '', $baseNote = "C5", $flute = "dizi", $tn = -1, $tr = 0) {

        $this->baseNote = $baseNote;
        $this->flute = $flute;
        $this->_readFluteMap();
        $this->_readNoteMap();
        
        if ($tn <= 0) {
            if ($tr != 0) {
                $this->transpose($tr);
            }
            $track = $this->_findFirstContentTrack();
        } else {
            if ($tr != 0) {
                $this->transposeTrack($tn, $tr);
            }
            $track = $this->getTrack($tn);
        }

        $commands = array();
        $last = 0;
        $dt = 0;
        $cnt = count($track);

        $beat = array();
        $beatStep = 0;
        $maxBeatStep = $this->timeSigValue;

        for ($i = 0; $i < $cnt; $i++){
            $line = $track[$i];
            $msg = explode(' ', $line);

            $title = $this->_findTrackName ($title, $msg);

            if ($msg[1] == 'On' && $msg[4] != 'v=0') {
                $time = $msg[0];

                $pause=$time-$last-$dt;
                if ($pause > 0){
                    list($dot, $quarters) = $this->_checkDotted($pause/$this->timebase);
                    $dur = $quarters / $this->defaultDur;
                    $beat[] = "<span class='note regular'>".'0'.$this->_getTempoMap($dur,$dot,true);
                    $beatStep += $dur;
                    if ($dot == ".") {
                        $beatStep += $dur/2;
                    }
                    if ($beatStep == $maxBeatStep) {
                        $commands[] = implode('&nbsp;', $beat);
                        $beatStep = 0;
                        $beat = [];
                    }
                }

                // find note duration
                $dt = 0;
                for ($j = $i+1; $j  <$cnt; $j++){
                    $msgNext = explode(' ', $track[$j]);
                    if ($msgNext[1] == 'On' || $msgNext[1] == 'Off') {
                        $dt = $msgNext[0] - $msg[0];
                        break;
                    }
                }

                eval("\$".$msg[3].';');
                $note = $this->notes[$n % 12];
                $scale = floor($n/12);
                $noteValueArray = $this->_getFluteMap($note.$scale);
                $noteValue = $noteValueArray['value'];

                if ($dt > 0){
                    list($dot, $quarters) = $this->_checkDotted($dt/$this->timebase);
                    $dur = $quarters / $this->defaultDur;
                    $noteLength = $dur;
                    $rest = 0;
                    if ($dur > $maxBeatStep) {
                        $dur = $maxBeatStep - $beatStep;
                        $rest =  $noteLength - $dur;
                    }
                    $validClass = "regular";
                    if ($noteValueArray["valid"] == 0) {
                        $validClass = "gray";
                    }
                    if ($noteValueArray["valid"] == -1) {
                        $validClass = "red";
                    }
                    $beat[] = "<span class='note $validClass'>".$noteValue . $this->_getTempoMap($dur,$dot) ;
                    $beatStep += $noteLength;

                    if ($dot == ".") {
                        $noteLength += $noteLength/2;
                        $beatStep += $noteLength/2;
                    }
                    if ($beatStep >= $maxBeatStep) {
                        $commands[] = implode('&nbsp;', $beat);
                        $beatStep = 0;
                        $beat = [];
                    }
                    /*if ($beatStep == $maxBeatStep) { // TODO
                        $commands[] = implode(' ', $beat);
                        $beatStep = 0;
                        $beat = [];
                    }
                    while ($beatStep > $maxBeatStep) {
                        if ($rest == 0) {
                            $rest = $beatStep - $maxBeatStep;
                        }
                        $commands[] = implode(' ', $beat);
                        $beatStep = 0;
                        $beat = [];
                        $beat[] = $note.$scale.$dot." ".$rest;
                        $beatStep += $rest;
                        if ($beatStep == $maxBeatStep) {
                            $commands[] = implode(' ', $beat);
                            $beatStep = 0;
                            $beat = [];
                        }
                    }*/
                    $last = $time;
                }
            }

        }// for

        $title = ($title == '') ? 'song' : trim($title);
        //$dizistring = $this->valid." ".$this->timeSig." !=".$this->getBpm()." { " . implode(' | ', $commands)." }";

        $result = str_replace("&nbsp;&nbsp;","&nbsp;",implode('&nbsp;| ', $commands));

        $diziArray = array(
            'success' => true,
            "valid" => $this->valid,
            "alert" => $this->valid ? "" : "You may can't play this song, because it contains notes, that your flute does not have. Change the instrument, or choose an other song.",
            "title" => $title,
            "timeSig" => $this->timeSig,
            "bpm" => " !=".$this->getBpm(),
            "baseNote" => "1=".substr($this->baseNote,0,1),
            "noteString" =>"{&nbsp;". $this->timeSig . "&nbsp;&nbsp;" . $result . "&nbsp;}"
        );

        return json_encode($diziArray);
    }


    /****************************************************************************
    *                                                                           *
    *                              Private methods                              *
    *                                                                           *
    ****************************************************************************/

    /**
     * @param $title
     * @param $msg
     * @return false|string
     */
    private function _parseTrackName ($title, $msg) {
        if ($msg[1] == 'Meta' && $msg[2] == 'TrkName') {
            $title = trim($msg[3]);
            if (count($msg) > 4) {
                for ($i = 4; $i < count($msg); $i++) {
                    $title .= " " . trim($msg[$i]);
                }
            }
            if ($title{0} == '"') {
                $title = substr($title, 1);
            }
            if ($title{strlen($title) - 1} == '"') {
                $title = substr($title, 0, -1);
            }
        }
        return $title;
    }
    /**
     * @param $title
     * @param $msg
     * @return false|string
     */
    private function _findTrackName ($title, $msg) {

        $track = $this->getTrack(0);
        $line = $track[0];
        $msg2 = explode(' ', $line);
        $title = _parseTrackName($title, $msg2);

        if ($title == "") {
            $title = _parseTrackName($title, $msg);
        }

        return $title;
    }

    /**
     * finds first track containing note on events
     * @return false|mixed
     */
    private function _findFirstContentTrack(){
        if ($this->type==0) return $this->tracks[0];
        else {
            foreach ($this->tracks as $track)
                foreach ($track as $line){
                    list(,$event) = explode(' ',$line);
                    if ($event=='On') return $track;
                }
        }
        return false;
    }

    /**
     * handles dotted notes
     * @param $quarters
     * @return array
     */
    private function _checkDotted($quarters){
        $dotted = array(6, 3, 3/2, 3/4, 3/8, 3/16);
        foreach ($dotted as $test)
            // to avoid rounding errors check for +/- 10%
            if (abs($quarters/$test-1)<0.1)   //($this->_compare($quarters,$test))
                return array('.', $quarters*2/3);
        return array('', $quarters);
    }

    /**
     * reads flute map json file
     */
    private function _readFluteMap () {
        $string = file_get_contents("json/fluteMap.json");
        $this->fluteMapArray = json_decode($string, true);
    }

    /**
     * @param $note
     * @return array|mixed
     */
    private function _getFluteMap ($note) {
        $baseNote = $this->baseNote;
        $flute = $this->flute;

        $fluteMapArray = $this->fluteMapArray;

        $fluteMap = $fluteMapArray[$flute][$baseNote];
        if (array_key_exists($note, $fluteMap)) {
            $keyData = $fluteMapArray[$flute][$baseNote][$note];

            if ($keyData['valid'] != 1) {
                $this->valid = false;
            }

            return $keyData;
        } else {
            $this->valid = false;
            return array ("value"=>$note, "valid"=>-1);
        }

    }

    /**
     * reads note map json file
     */
    private function _readNoteMap () {

        $string = file_get_contents("json/noteMap.json");
        $this->noteMapArray = json_decode($string, true);

    }

    /**
     * @param $num
     * @param $dot
     * @param false $break
     * @return string|string[]
     */
    private function _getTempoMap ($num,$dot,$break = false) {
        $string = "</span>";
        switch ($num) {
            case 1:
                $string = "</span>".$dot.($break? " 0 0 0":" - - -");
                break;
            case 0.5:
                $s = ($break? "0":"-");
                $string = "</span>".($dot == "." ? " $s $s" : " $s");
                break;
            case 0.75:
                $string = "</span>".($break? " 0 0":" - -");
                break;
            case 0.25:
                $string = "</span>".$dot." ";
                break;
            case 0.125:
                $string = "_"."</span>".$dot;
                break;
            case 0.0625:
                $string = ","."</span>".$dot;
                break;
        }
        $string = str_replace(" ","&nbsp;",$string);
        return $string;
    }
	
} // END OF CLASS
?>