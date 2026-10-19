<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/vendor/SymconModulHelper/VariableProfileHelper.php';

class AppleTV extends IPSModuleStrict
{
    use VariableProfileHelper;

    private const MQTT_DATA_ID = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
    private const MQTT_PARENT_ID = '{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}';

    // Topic-Suffix der Bridge => Ident der Statusvariable
    private const TOPIC_TO_IDENT = [
        'host'        => 'IP',
        'name'        => 'Name',
        'title'       => 'Title',
        'artist'      => 'Artist',
        'album'       => 'Album',
        'app'         => 'AppDisplayName',
        'appId'       => 'AppBundleIdentifier',
        'deviceState' => 'DeviceState',
        'powerState'  => 'PowerState'
    ];

    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent(self::MQTT_PARENT_ID);
        $this->RegisterPropertyString('MQTTTopic', '');

        $this->RegisterVariableString('Name', $this->Translate('Name'), '', 0);
        $this->RegisterVariableString('IP', $this->Translate('IP Address'), '', 1);
        $this->RegisterVariableString('DeviceState', $this->Translate('Device State'), '', 2);
        $this->RegisterVariableString('PowerState', $this->Translate('Power State'), '', 3);

        if (!IPS_VariableProfileExists('ATV.Control')) {
            $associations = [];
            foreach ($this->getControls() as $ident => $caption) {
                $associations[] = [$ident, $this->Translate($caption), '', $ident === 'down' ? 0xFFFFFF : 0x0000FF];
            }
            $this->RegisterProfileStringEx('ATV.Control', 'Menu', '', '', $associations);
        }
        $this->RegisterVariableString('Controls', $this->Translate('Controls'), 'ATV.Control', 4);
        $this->EnableAction('Controls');
        $this->RegisterVariableInteger('Duration', $this->Translate('Duration'), '', 5);
        $this->RegisterVariableString('Artist', $this->Translate('Artist'), '', 6);
        $this->RegisterVariableString('Title', $this->Translate('Title'), '', 7);
        $this->RegisterVariableString('Album', $this->Translate('Album'), '', 8);
        $this->RegisterVariableString('AppDisplayName', $this->Translate('App'), '', 90);
        $this->RegisterVariableString('AppBundleIdentifier', $this->Translate('AppBundleIdentifier'), '', 10);
    }

    public function Destroy(): void
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges(): void
    {
        //Never delete this line!
        parent::ApplyChanges();

        $topic = trim($this->ReadPropertyString('MQTTTopic'));
        if ($topic === '') {
            $this->SetReceiveDataFilter('.*NOTHING.*');
            $this->SetStatus(104);
            return;
        }

        $this->SetReceiveDataFilter('.*' . $topic . '.*');
        $this->SetStatus(102);
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        switch ($Ident) {
            case 'Controls':
                if (!array_key_exists((string) $Value, $this->getControls())) {
                    $this->SendDebug(__FUNCTION__, 'Unknown control: ' . (string) $Value, 0);
                    return;
                }
                $this->sendMQTT(trim($this->ReadPropertyString('MQTTTopic')) . '/' . $Value, '');
                break;
            default:
                $this->SendDebug(__FUNCTION__ . ' :: No Ident', $Ident, 0);
                break;
        }
    }

    public function ReceiveData(string $JSONString): string
    {
        $this->SendDebug('JSON', $JSONString, 0);

        $topic = trim($this->ReadPropertyString('MQTTTopic'));
        if ($topic === '') {
            return '';
        }

        $data = json_decode($JSONString, true);
        if (!is_array($data) || !isset($data['Topic']) || !array_key_exists('Payload', $data)) {
            $this->SendDebug(__FUNCTION__, 'Invalid MQTT data', 0);
            return '';
        }

        // Der MQTT-Client reicht den Payload hex-kodiert durch
        $payload = (string) $data['Payload'];
        if ($payload !== '') {
            $decoded = ctype_xdigit($payload) && strlen($payload) % 2 === 0 ? hex2bin($payload) : false;
            if ($decoded === false) {
                $this->SendDebug(__FUNCTION__, 'Payload is not hex encoded', 0);
                return '';
            }
            $payload = $decoded;
        }

        $this->SendDebug('MQTT Topic', $data['Topic'], 0);
        $this->SendDebug('MQTT Payload', $payload, 0);

        $prefix = $topic . '/';
        if (!str_starts_with($data['Topic'], $prefix)) {
            return '';
        }

        $suffix = substr($data['Topic'], strlen($prefix));
        if (isset(self::TOPIC_TO_IDENT[$suffix])) {
            $this->SetValue(self::TOPIC_TO_IDENT[$suffix], $payload);
        }
        return '';
    }

    /**
     * Steuerbefehle der Bridge: Ident (= MQTT-Topic-Suffix) => Bezeichnung.
     */
    private function getControls(): array
    {
        return [
            'down'         => 'Down',
            'home'         => 'Home',
            'homeHold'     => 'Home Hold',
            'left'         => 'Left',
            'menu'         => 'Menu',
            'next'         => 'Next',
            'pause'        => 'Pause',
            'play'         => 'Play',
            'playPause'    => 'Play / Pause',
            'previous'     => 'Previous',
            'right'        => 'Right',
            'select'       => 'Select',
            'skipBackward' => 'Skip Backward',
            'skipForward'  => 'Skip Forward',
            'stop'         => 'Stop',
            'suspend'      => 'Suspend',
            'topMenu'      => 'Top Menu',
            'up'           => 'Up',
            'volumeDown'   => 'Volume Down',
            'volumeUp'     => 'Volume Up',
            'wakeup'       => 'Wakeup',
            'turnOn'       => 'Turn On'
        ];
    }

    private function sendMQTT(string $Topic, string $Payload): void
    {
        if (!$this->HasActiveParent()) {
            $this->SendDebug(__FUNCTION__, 'No active parent', 0);
            return;
        }

        $packet = [
            'DataID'           => self::MQTT_DATA_ID,
            'PacketType'       => 3,
            'QualityOfService' => 0,
            'Retain'           => false,
            'Topic'            => $Topic,
            'Payload'          => $Payload
        ];
        $json = json_encode($packet, JSON_UNESCAPED_SLASHES);
        $this->SendDebug(__FUNCTION__ . ' MQTT Server', $json, 0);
        $this->SendDataToParent($json);
    }
}
