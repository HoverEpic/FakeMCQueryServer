<?php

class QueryServer {

    private $sock;
    public $gameData = array(
        'hostname' => 'A Minecraft Server',
        'gametype' => 'SMP',
        'game_id' => 'MINCERAFT',
        'version' => '1.2.5',
        'plugins' => 'FakeQueryServer',
        'map' => 'world',
        'numplayers' => '1',
        'maxplayers' => '20',
        'hostport' => '25565',
        'hostip' => '127.0.0.1',
    );
    public $onlinePlayers = array("Notch");

    private function sendPacket($outPacket, $peer) {
        echo "\nSending Packet to " . $peer . ":\n";
        var_dump(implode(" ", str_split(bin2hex($outPacket), 2)));
        stream_socket_sendto($this->sock, $outPacket, 0, $peer);
    }

    private function handlePacket($data, $peer) {

        echo "\n\nRecieved Packet:   ";
        var_dump($data);
        var_dump(implode(" ", str_split(bin2hex($data), 2)));

        if (ord($data[0]) == 0xFE && ord($data[1]) == 0xFD) {

            switch (ord($data[2])) {
                case 0x09:
                    $this->handleChallenge($data, $peer);
                    break;
                case 0x00:
                    $this->handleQuery($data, $peer);
                    break;
                default:
                    echo "Unknown Packet Type :";
                    var_dump(implode(" ", str_split(bin2hex($data), 2)));
            }
        }
    }

    private function handleChallenge($data, $peer) {
        $idToken = substr($data, 3, 4);
        if ($idToken == false) {
            $idToken = chr(0x00) . chr(0x00) . chr(0x00) . chr(0x00);
        }

        $challengeToken = $this->getChallengeToken($peer);

        $outPacket = chr(0x09) . $idToken . $this->parseCTokenAsString($challengeToken);

        $this->sendPacket($outPacket, $peer);
    }

    private function handleQuery($data, $peer) {
        $idToken = substr($data, 3, 4);
        $challengeToken = substr($data, 7, 4);

        if ($challengeToken == $this->parseCTokenAsHex($this->getChallengeToken($peer))) {

            if (substr($data, -4, 4) == "\x00\x00\x00\x00") {
                $stats = $this->getLargeStats();
            } else {
                $stats = $this->getSmallStats();
            }

            $outPacket = chr(0x00) . $idToken . $stats;
            $this->sendPacket($outPacket, $peer);
        }
    }

    private function getSmallStats() {
        $stats = $this->gameData['hostname'] . "\x00";
        $stats .= $this->gameData['gametype'] . "\x00";
        $stats .= $this->gameData['map'] . "\x00";
        $stats .= $this->gameData['numplayers'] . "\x00";
        $stats .= $this->gameData['maxplayers'] . "\x00";
        $stats .= $this->gameData['hostport'] . "\x00";
        $stats .= $this->gameData['hostip'] . "\x00";
        $stats .= "\0\0\0";
        return $stats;
    }

    private function getLargeStats() {
        $stats = "\x46\x61\x6b\x65\x46\x61\x6b\x65\x00\x80\x00"; // Padding

        foreach ($this->gameData as $key => $value) {
            $stats .= "$key\0$value\0";
        }

        $stats .= "\0";
        $stats .= "\x46\x61\x6b\x65\x46\x61\x6b\x65\x00\x00"; // More padding

        foreach ($this->onlinePlayers as $player) {
            $stats .= "$player\0";
        }

        $stats .= "\0\0\0";

        return $stats;
    }

    private $tokens = array();

    private function getChallengeToken($peer) {
        if (isset($this->tokens[$peer])) {
            return $this->tokens[$peer];
        } else {
            $this->tokens[$peer] = rand(0, 2147483647);
            return $this->tokens[$peer];
        }
    }

    private function parseCTokenAsString($token) {
        return strval($token) . "\0";
    }

    private function parseCTokenAsHex($token) {
        return hex2bin(dechex($token));
    }

    function __construct($gameData, $playerList, $port = null) {
        echo "FakeQueryServer by tschrock\n";
        
        $this->gameData = array_merge($this->gameData, $gameData);
        $this->onlinePlayers = $playerList;
        
        $port = ($port == null) ? $this->gameData['hostport'] : $port;
        
        $this->sock = stream_socket_server("udp://0.0.0.0:" . $port, $errno, $errstr, STREAM_SERVER_BIND)
                or die("[Error] Couldn't create socket on port $port, exiting. $errno: $errstr\n");

    }

    function start() {
        $this->running = true;
        while ($this->running) {
            $data = stream_socket_recvfrom($this->sock, 1500, 0, $peer);
            if ($data != "") {
                $this->handlePacket($data, $peer);
            }
        }
    }

}
