<?php

declare(strict_types=1);

/**
 * Minimaler MQTT-Server für Tests: nimmt Pakete der Kind-Instanz an und merkt sie sich.
 */
class MQTTServerStub extends IPSModule
{
    public static array $packets = [];

    public function ForwardData($JSONString)
    {
        self::$packets[] = json_decode($JSONString, true);
        return '';
    }
}
