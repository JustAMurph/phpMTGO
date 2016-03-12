<?php

/**
 * Created by PhpStorm.
 * User: Eoin
 * Date: 10/03/2016
 * Time: 16:32
 */
class MTGODraftFile
{
    /** @var array Contains the resultset after parsing the MTGO Draft Log File */
    public $results = array();

    /** @var int The number of players in the draft */
    private $draftPlayers = 8;

    /** @var int The number of cards in a MTG pack */
    private $cardsPerPack = 15;

    /** @var array The markers MTGO uses to mark certain things in the log file */
    private $markers = array(
        'currentPick' => '-->',
        'currentPlayer' => '-->',
        'packTitle' => '------',
        'pack' => 'Pack'
    );

    /** @var string The date format you want the resultset date to be in */
    public $dateFormat = 'd/m/Y';

    /** @var string The time format you want the resultset time to be in */
    public $timeFormat = 'H:i';

    /**
     * Opens the file handler, and parses the file.
     *
     * @param string $file A valid path for PHP to read the file.
     * @throws Exception if file is not readable.
     * @throws InvalidArgumentException if the argument is not a string.
     */
    public function __construct($file) {

        if (!is_string($file)) {
            throw new InvalidArgumentException("Argument must be a string");
        }

        if (!is_readable($file)) {
            throw new Exception("File cannot be read.");
        }

        $this->fh = fopen($file, 'r');
        $this->parse();
    }

    /**
     * Parses each section of the log file
     */
    public function parse() {
        $this->parseEventData();
        $this->parseTimeData();
        $this->parsePlayers();
        $this->parsePacks();
    }

    /**
     * Parses the event data in a single line. Event Data is stored in a line similar to:
     * Event #: 9488616
     * Sets the $this->results['event_id'] = The event ID.
     */
    private function parseEventData() {
        $eventLine = $this->getLineClean($this->fh);
        $eventID = 'N/A';
        $explode = explode(':', $eventLine);
        if (is_array($explode) && !empty($explode[1])) {
            $eventID = $this->clean($explode[1]);
        }
        $this->results['event_id'] = $eventID;
    }

    /**
     * Parses the timestamp line in the MTGO log file similar to:
     * Time:    10/03/2016 15:48:46
     */
    private function parseTimeData() {
        $timeLine = $this->getLineClean($this->fh);
        $time = $this->clean(str_replace(array('Time: '), '', $timeLine));
        $dateTime = DateTime::createFromFormat('d/m/Y H:i:s', $time);

        $this->results['date'] = $dateTime->format($this->dateFormat);
        $this->results['time'] = $dateTime->format($this->timeFormat);
    }

    /**
     * Parses the players in the MTGO log file.
     * Appends to the $this->results['players'] array.
     *
     * $this->result['active_player'] is set to the drafting player.
     */
    private function parsePlayers() {
        $playersHeadingLine = $this->getLineClean();
        for($i=0; $i<$this->draftPlayers; $i++){
            $buffer = $this->getLineClean();

            $player = $buffer;
            if ($this->isActivePlayer($buffer)) {
                $player = $this->removeMTGOMarkers($buffer);
                $this->results['active_player'] = $player;
            }
            $this->results['players'][] = $player;
        }
    }

    /**
     * Loops over the lines until a pack title is found. If it's found, parse the picks.
     * Looks for a line similar to: Pack 1 pick 1:
     */
    private function parsePacks() {
        while (($buffer = $this->getLineClean()) !== false) {
            if ($this->isPackTitle($buffer)) {
                $this->parsePicks($buffer);
            }
        }
    }

    /**
     * Parse the packs after the "Pack 1 pick 1:" string. If a pack is starting, then
     * parse the picks available in that pack.
     *
     * @param $packTitleLine String The string containing "Pack 1 pick1:"
     */
    private function parsePicks($packTitleLine){
        $pack = array();
        $pack['set'] = $this->clean(str_replace('------', '', $packTitleLine));
        $pack['picks'] = array();
        while(($buffer = $this->getLineClean()) !== false) {

            if ($this->isPackStart($buffer)) {
                list($packNumber, $pickNumber, $packContents) = $this->parsePack($buffer);
                $pack['picks'][] = $packContents;

                if ($pickNumber == $this->cardsPerPack){
                    break;
                }
            }
        }
        $this->results['packs'][] = $pack;
    }

    /**
     * Parses the current picks from a pack
     *
     * @param $packLine string The Packline like "Pack 1 pick1:"
     * @return array The pack number at index 0, the pick number at index 1, the pack data at index 2
     **/
    private function parsePack($packLine){
        $pack = $this->getPackData($packLine);
        while (($cardLine = $this->getLineClean()) !== FALSE) {

            if($cardLine == '') {
                break;
            }

            if ($this->isPickedCard($cardLine)) {
                $cardLine = $this->removeMTGOMarkers($cardLine);
                $pack['picked_card'] = $cardLine;
            }

            $pack['cards'][] = $cardLine;
        }
        return array($pack['pack'], $pack['pick'], $pack);
    }

    /**
     * Parse the pack / pick line to get the pack details
     *
     * @param $packLine
     * @return array
     */
    private function getPackData($packLine) {
        $explode = explode(' ', $packLine);
        $pack = array(
            'pack' => $explode[1],
            'pick' => str_replace(':', '', $explode[3]),
            'picked_card' => ''
        );
        return $pack;
    }

    /**
     * Determines if the $buffer is a Pack Title. By comparing it to the marker.
     * @param $buffer string
     * @return bool
     */
    private function isPackTitle($buffer){
        return $this->contains($buffer, $this->markers['packTitle']);
    }

    /**
     * Determines if $buffer is the start of a pack. By comparing it to the marker.
     * @param $buffer string
     * @return bool
     */
    private function isPackStart($buffer) {
        return $this->contains($buffer, $this->markers['pack']);
    }

    /**
     * Determines if $buffer is a picked card. By comparing it to the marker.
     * @param $buffer string
     * @return bool
     */
    private function isPickedCard($buffer) {
        return $this->contains($buffer, $this->markers['currentPick']);
    }

    /**
     * Determines if $buffer is the active player. By comparing it to the marker.
     * @param $buffer string
     * @return bool
     */
    public function isActivePlayer($buffer) {
        return $this->contains($buffer, $this->markers['currentPlayer']);
    }

    /**
     * Helper function to quickly see if one string contains another.
     * @param $haystack string
     * @param $needle string
     * @return bool
     */
    private function contains($haystack, $needle) {
        if (strpos($haystack, $needle) !== false) {
            return true;
        }
        return false;
    }

    /**
     * Removes various MTGO markers from a string as declared in $this->markers
     * @param $buffer string
     * @return string
     */
    private function removeMTGOMarkers($buffer) {
        return $this->clean(str_replace($this->markers, '', $buffer));
    }

    /**
     * Cleans a string for using. MTGO contains a lot of whitespace and \r\n characters.
     * @param $string string
     * @return string
     */
    private function clean($string){
        return trim($string);
    }

    /**
     * Gets the next line in the current file handle, cleans it, and returns.
     * @return string
     */
    private function getLineClean(){
        $fgets = fgets($this->fh);

        if ($fgets === false) {
            return $fgets;
        }
        return $this->clean($fgets);
    }

}