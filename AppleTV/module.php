<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/vendor/SymconModulHelper/VariableProfileHelper.php';

    class AppleTV extends IPSModule
    {
        use ATVHelper;
        public function Create()
        {
            //Never delete this line!
            parent::Create();

            $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
            $this->RegisterPropertyString('MQTTTopic', '');

            $this->RegisterVariableString('Name', $this->Translate('Name'), '', 0);
            $this->RegisterVariableString('IP', $this->Translate('IP Address'), '', 1);
            $this->RegisterVariableString('PlayBackState', $this->Translate('State'), '', 2);

            if (!IPS_VariableProfileExists('ATV.Control')) {
                $Associations = [];
                $Associations[] = [1, $this->Translate('Up'), '', -1];
                $Associations[] = [2, $this->Translate('Down'), '', -1];
                $Associations[] = [3, $this->Translate('Left'), '', -1];
                $Associations[] = [4, $this->Translate('Right'), '', -1];
                $Associations[] = [5, $this->Translate('Menue'), '', -1];
                $Associations[] = [6, $this->Translate('Play'), '', -1];
                $Associations[] = [7, $this->Translate('Pause'), '', -1];
                $Associations[] = [8, $this->Translate('Next'), '', -1];
                $Associations[] = [9, $this->Translate('Previous'), '', -1];
                $Associations[] = [10, $this->Translate('Suspend'), '', -1];
                $Associations[] = [11, $this->Translate('Select'), '', -1];
                $Associations[] = [12, $this->Translate('Long TV'), '', -1];
                $Associations[] = [13, $this->Translate('TV'), '', -1];

                $this->RegisterProfileIntegerEx('ATV.Controls', 'Databse', '', '', $Associations);
                $this->RegisterVariableInteger('Controls', $this->Translate('Controls'), 'ATV.Controls', 3);
                $this->EnableAction('Controls');
            }
            $this->RegisterVariableInteger('Duration', $this->Translate('Duration'), '', 4);
            $this->RegisterVariableInteger('ElapsedTime', $this->Translate('Elapsed Time'), '', 5);
            $this->RegisterVariableString('Artist', $this->Translate('Artist'), '', 6);
            $this->RegisterVariableString('Title', $this->Translate('Title'), '', 7);
            $this->RegisterVariableString('Album', $this->Translate('Album'), '', 8);
            $this->RegisterVariableString('AppDisplayName', $this->Translate('App'), '', 9);
            $this->RegisterVariableString('AppBundleIdentifier', $this->Translate('AppBundleIdentifier'), '', 10);
            $this->RegisterVariableInteger('Timestamp', $this->Translate('Timestamp'), '', 11);
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
                    switch ($Value) {
                        case 1:
                            $this->sendMQTT($this->ReadPropertyString('MQTTTopic') . '/up', '');
                            break;
                        case 2:
                            $this->sendMQTT($this->ReadPropertyString('MQTTTopic') . '/down', '');
                            break;
                        case 3:
                            $this->sendMQTT($this->ReadPropertyString('MQTTTopic') . '/left', '');
                            break;
                        case 4:
                            $this->sendMQTT($this->ReadPropertyString('MQTTTopic') . '/right', '');
                            break;
                        case 5:
                            $this->sendMQTT($this->ReadPropertyString('MQTTTopic') . '/menu', '');
                            break;
                        case 6:
                            $this->sendMQTT($this->ReadPropertyString('MQTTTopic') . '/play', '');
                            break;
                        case 7:
                            $this->sendMQTT($this->ReadPropertyString('MQTTTopic') . '/pause', '');
                            break;
                        case 8:
                            $this->sendMQTT($this->ReadPropertyString('MQTTTopic') . '/next', '');
                            break;
                        case 9:
                            $this->sendMQTT($this->ReadPropertyString('MQTTTopic') . '/previous', '');
                            break;
                        case 10:
                            $this->sendMQTT($this->ReadPropertyString('MQTTTopic') . '/suspend', '');
                            break;
                        case 11:
                            $this->sendMQTT($this->ReadPropertyString('MQTTTopic') . '/select', '');
                            break;
                        case 12:
                            $this->sendMQTT($this->ReadPropertyString('MQTTTopic') . '/LongTv', '');
                            break;
                        case 13:
                            $this->sendMQTT($this->ReadPropertyString('MQTTTopic') . '/Tv', '');
                            break;

                    }
            }
        }

        public function ReceiveData($JSONString)
        {
            $MQTTTopic = $this->ReadPropertyString('MQTTTopic');
            $this->SendDebug('JSON', $JSONString, 0);
            if (!empty($this->ReadPropertyString('MQTTTopic'))) {
                $data = json_decode($JSONString);

                switch ($data->DataID) {
                    case '{7F7632D9-FA40-4F38-8DEA-C83CD4325A32}': // MQTT Server
                        $Buffer = $data;
                        break;
                    case '{DBDA9DF7-5D04-F49D-370A-2B9153D00D9B}': //MQTT Client
                        $Buffer = json_decode($data->Buffer);
                        break;
                    default:
                        $this->LogMessage('Invalid Parent', KL_ERROR);
                        return;
                }

                $this->SendDebug('MQTT Topic', $Buffer->Topic, 0);
                $this->SendDebug('MQTT Payload', $Buffer->Payload, 0);

                switch ($Buffer->Topic) {
                    case $MQTTTopic . '/address':
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
                    case $MQTTTopic . '/appDisplayName':
                        $this->SetValue('AppDisplayName', $Buffer->Payload);
                        break;
                    case $MQTTTopic . '/appBundleIdentifier':
                        $this->SetValue('AppBundleIdentifier', $Buffer->Payload);
                        break;
                    case $MQTTTopic . '/playbackState':
                        $this->SetValue('PlayBackState', $Buffer->Payload);
                        break;
                }
            }
        }

        private function sendMQTT($Topic, $Payload)
        {
            $resultServer = true;
            $resultClient = true;
            //MQTT Server
            $Server['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
            $Server['PacketType'] = 3;
            $Server['QualityOfService'] = 0;
            $Server['Retain'] = false;
            $Server['Topic'] = $Topic;
            $Server['Payload'] = $Payload;
            $ServerJSON = json_encode($Server, JSON_UNESCAPED_SLASHES);
            $this->SendDebug(__FUNCTION__ . 'MQTT Server', $ServerJSON, 0);
            $resultServer = @$this->SendDataToParent($ServerJSON);

            //MQTT Client
            $Buffer['PacketType'] = 3;
            $Buffer['QualityOfService'] = 0;
            $Buffer['Retain'] = false;
            $Buffer['Topic'] = $Topic;
            $Buffer['Payload'] = $Payload;
            $BufferJSON = json_encode($Buffer, JSON_UNESCAPED_SLASHES);

            $Client['DataID'] = '{97475B04-67C3-A74D-C970-E9409B0EFA1D}';
            $Client['Buffer'] = $BufferJSON;

            $ClientJSON = json_encode($Client);
            $this->SendDebug(__FUNCTION__ . 'MQTT Client', $ClientJSON, 0);
            $resultClient = @$this->SendDataToParent($ClientJSON);

            if ($resultServer === false && $resultClient === false) {
                $last_error = error_get_last();
                echo $last_error['message'];
            }
        }
    }
