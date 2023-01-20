<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/vendor/SymconModulHelper/VariableProfileHelper.php';

    class AppleTV extends IPSModule
    {
        use VariableProfileHelper;
        public function Create()
        {
            //Never delete this line!
            parent::Create();

            $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
            $this->RegisterPropertyString('MQTTTopic', '');

            $this->RegisterVariableString('Name', $this->Translate('Name'), '', 0);
            $this->RegisterVariableString('IP', $this->Translate('IP Address'), '', 1);
            $this->RegisterVariableString('DeviceState', $this->Translate('Device State'), '', 2);
            $this->RegisterVariableString('PowerState', $this->Translate('Power State'), '', 3);

            if (!IPS_VariableProfileExists('ATV.Control')) {
                $this->RegisterProfileStringEx('ATV.Control', 'Menu', '', '', [
                    ['down', $this->Translate('Down'), '', 0xFFFFFF],
                    ['home', $this->Translate('Home'), '', 0x0000FF],
                    ['homeHold', $this->Translate('Home Hold'), '', 0x0000FF],
                    ['left', $this->Translate('Left'), '', 0x0000FF],
                    ['menu', $this->Translate('Menu'), '', 0x0000FF],
                    ['next', $this->Translate('Next'), '', 0x0000FF],
                    ['pause', $this->Translate('Pause'), '', 0x0000FF],
                    ['play', $this->Translate('Play'), '', 0x0000FF],
                    ['playPause', $this->Translate('Play / Pause'), '', 0x0000FF],
                    ['previous', $this->Translate('Previous'), '', 0x0000FF],
                    ['right', $this->Translate('Right'), '', 0x0000FF],
                    ['select', $this->Translate('Select'), '', 0x0000FF],
                    ['skipBackward', $this->Translate('Skip Backward'), '', 0x0000FF],
                    ['skipForward', $this->Translate('Skip Forward'), '', 0x0000FF],
                    ['stop', $this->Translate('Stop'), '', 0x0000FF],
                    ['suspend', $this->Translate('Supsend'), '', 0x0000FF],
                    ['topMenu', $this->Translate('Top Menu'), '', 0x0000FF],
                    ['up', $this->Translate('Up'), '', 0x0000FF],
                    ['volumeDown', $this->Translate('Volume Down'), '', 0x0000FF],
                    ['volumeUp', $this->Translate('Volume Up'), '', 0x0000FF],
                    ['wakeup', $this->Translate('Wakeup'), '', 0x0000FF],
                    //['turnOff', $this->Translate('Turn off'), '', 0x0000FF], //Funktioniert nicht
                    ['turnOn', $this->Translate('Turn On'), '', 0x0000FF]
                ]);
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

        public function Destroy()
        {
            //Never delete this line!
            parent::Destroy();
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();
            $MQTTTopic = $this->ReadPropertyString('MQTTTopic');
            $this->SetReceiveDataFilter('.*' . $MQTTTopic . '.*');
        }

        public function RequestAction($Ident, $Value)
        {
            switch ($Ident) {
                case 'Controls':
                    $this->sendMQTT($this->ReadPropertyString('MQTTTopic') . '/' . $Value, '');
                    break;
                default:
                    $this->SendDebug('RequestAction :: No Ident', $Ident, 0);
                    break;
            }
        }

        public function ReceiveData($JSONString)
        {
            $MQTTTopic = $this->ReadPropertyString('MQTTTopic');
            $this->SendDebug('JSON', $JSONString, 0);
            if (!empty($this->ReadPropertyString('MQTTTopic'))) {
                $Buffer = json_decode($JSONString);

                //Für MQTT Fix in IPS Version 6.3
                if (IPS_GetKernelDate() > 1670886000) {
                    $Buffer->Payload = utf8_decode($Buffer->Payload);
                }

                $this->SendDebug('MQTT Topic', $Buffer->Topic, 0);
                $this->SendDebug('MQTT Payload', $Buffer->Payload, 0);

                switch ($Buffer->Topic) {
                    case $MQTTTopic . '/host':
                        $this->SetValue('IP', $Buffer->Payload);
                        break;
                    case $MQTTTopic . '/name':
                        $this->SetValue('Name', $Buffer->Payload);
                        break;
                    case $MQTTTopic . '/title':
                        $this->SetValue('Title', $Buffer->Payload);
                        break;
                    case $MQTTTopic . '/artist':
                        $this->SetValue('Artist', $Buffer->Payload);
                        break;
                    case $MQTTTopic . '/album':
                        $this->SetValue('Album', $Buffer->Payload);
                        break;
                    case $MQTTTopic . '/app':
                        $this->SetValue('AppDisplayName', $Buffer->Payload);
                        break;
                    case $MQTTTopic . '/appId':
                        $this->SetValue('AppBundleIdentifier', $Buffer->Payload);
                        break;
                    case $MQTTTopic . '/deviceState':
                        $this->SetValue('DeviceState', $Buffer->Payload);
                        break;
                    case $MQTTTopic . '/powerState':
                        $this->SetValue('PowerState', $Buffer->Payload);
                        break;
                    }
            }
        }

        private function sendMQTT($Topic, $Payload)
        {
            $resultServer = true;
            $Server['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
            $Server['PacketType'] = 3;
            $Server['QualityOfService'] = 0;
            $Server['Retain'] = false;
            $Server['Topic'] = $Topic;
            $Server['Payload'] = $Payload;
            $ServerJSON = json_encode($Server, JSON_UNESCAPED_SLASHES);
            $this->SendDebug(__FUNCTION__ . 'MQTT Server', $ServerJSON, 0);
            $resultServer = @$this->SendDataToParent($ServerJSON);

            if ($resultServer === false) {
                $last_error = error_get_last();
                echo $last_error['message'];
            }
        }
    }
