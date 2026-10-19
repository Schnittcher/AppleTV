<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AppleTVTest extends TestCase
{
    private const MODULE_ID = '{C41CDD09-60C6-A8F9-9808-E875B3B1096A}';
    private const DATA_ID = '{7F7632D9-FA40-4F38-8DEA-C83CD4325A32}';
    private const TOPIC = 'test/appletv';

    // Idents von Version 1.x: öffentlicher Vertrag (Archiv, Skripte, Visualisierung), bleiben erhalten
    private const STRING_IDENTS = ['Name', 'IP', 'DeviceState', 'PowerState', 'Controls', 'Artist', 'Title', 'Album', 'AppDisplayName', 'AppBundleIdentifier'];

    // Steuerbefehle der Bridge, zugleich MQTT-Topic-Suffix und Wert der Variable "Controls"
    private const CONTROLS = [
        'down', 'home', 'homeHold', 'left', 'menu', 'next', 'pause', 'play', 'playPause', 'previous', 'right', 'select',
        'skipBackward', 'skipForward', 'stop', 'suspend', 'topMenu', 'up', 'volumeDown', 'volumeUp', 'wakeup', 'turnOn'
    ];

    protected function setUp(): void
    {
        // Jeder Test startet mit einem leeren Kernel und lädt die Bibliothek neu
        IPS\Kernel::reset();
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/fixtures/MQTTServerStub/library.json');
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/../library.json');
        parent::setUp();
    }

    public function testExistingIdentsAndTypesAreKept(): void
    {
        $instanceID = $this->createInstance(self::TOPIC);

        foreach (self::STRING_IDENTS as $ident) {
            $variableID = $this->variableID($instanceID, $ident);
            $this->assertNotFalse($variableID, 'Ident fehlt: ' . $ident);
            $this->assertSame(VARIABLETYPE_STRING, IPS_GetVariable($variableID)['VariableType'], $ident);
        }
        $this->assertSame(VARIABLETYPE_INTEGER, IPS_GetVariable($this->variableID($instanceID, 'Duration'))['VariableType']);
    }

    public function testOnlyControlsIsActionVariable(): void
    {
        $instanceID = $this->createInstance(self::TOPIC);

        foreach (IPS_GetChildrenIDs($instanceID) as $childID) {
            $ident = IPS_GetObject($childID)['ObjectIdent'];
            $hasAction = IPS_GetVariable($childID)['VariableAction'] !== 0;
            $this->assertSame($ident === 'Controls', $hasAction, $ident);
        }
    }

    public function testControlProfileContainsAllControlsWithStableIdents(): void
    {
        $this->createInstance(self::TOPIC);

        $values = array_column(IPS_GetVariableProfile('ATV.Control')['Associations'], 'Value');
        $this->assertEqualsCanonicalizing(self::CONTROLS, $values);
    }

    public function testStatusIsInactiveWithoutTopic(): void
    {
        $instanceID = $this->createInstance('');

        $this->assertSame(104, IPS_GetInstance($instanceID)['InstanceStatus']);
    }

    public function testStatusIsActiveWithTopic(): void
    {
        $instanceID = $this->createInstance(self::TOPIC);

        $this->assertSame(102, IPS_GetInstance($instanceID)['InstanceStatus']);
    }

    public function testStatusFollowsTopicChangeAndApplyChangesIsRepeatable(): void
    {
        $instanceID = $this->createInstance(self::TOPIC);
        $count = count(IPS_GetChildrenIDs($instanceID));

        IPS_SetConfiguration($instanceID, json_encode(['MQTTTopic' => '']));
        IPS_ApplyChanges($instanceID);
        $this->assertSame(104, IPS_GetInstance($instanceID)['InstanceStatus']);

        IPS_SetConfiguration($instanceID, json_encode(['MQTTTopic' => self::TOPIC]));
        IPS_ApplyChanges($instanceID);
        IPS_ApplyChanges($instanceID);
        $this->assertSame(102, IPS_GetInstance($instanceID)['InstanceStatus']);
        $this->assertCount($count, IPS_GetChildrenIDs($instanceID));
    }

    // Ein Fall je Topic der Bridge
    #[DataProvider('topicProvider')]
    public function testReceivedTopicsAreWrittenToVariables(string $suffix, string $ident): void
    {
        $instanceID = $this->createInstance(self::TOPIC);

        $this->receive($instanceID, self::TOPIC . '/' . $suffix, 'Wert ' . $suffix);

        $this->assertSame('Wert ' . $suffix, GetValue($this->variableID($instanceID, $ident)));
    }

    public static function topicProvider(): array
    {
        return [
            'host'        => ['host', 'IP'],
            'name'        => ['name', 'Name'],
            'title'       => ['title', 'Title'],
            'artist'      => ['artist', 'Artist'],
            'album'       => ['album', 'Album'],
            'app'         => ['app', 'AppDisplayName'],
            'appId'       => ['appId', 'AppBundleIdentifier'],
            'deviceState' => ['deviceState', 'DeviceState'],
            'powerState'  => ['powerState', 'PowerState']
        ];
    }

    public function testHexPayloadWithUmlautsIsDecodedToUtf8(): void
    {
        $instanceID = $this->createInstance(self::TOPIC);

        $this->receive($instanceID, self::TOPIC . '/artist', 'Beyoncé Über');

        $this->assertSame('Beyoncé Über', GetValue($this->variableID($instanceID, 'Artist')));
    }

    public function testTimesAreStoredAsIntegerSeconds(): void
    {
        $instanceID = $this->createInstance(self::TOPIC);

        $this->receive($instanceID, self::TOPIC . '/totalTime', '2711');
        $this->receive($instanceID, self::TOPIC . '/position', '63.6');

        $this->assertSame(2711, GetValue($this->variableID($instanceID, 'Duration')));
        $this->assertSame(64, GetValue($this->variableID($instanceID, 'Position')));
    }

    public function testEmptyOrInvalidTimeBecomesZero(): void
    {
        $instanceID = $this->createInstance(self::TOPIC);
        $this->receive($instanceID, self::TOPIC . '/position', '120');

        $this->receive($instanceID, self::TOPIC . '/position', '');
        $this->assertSame(0, GetValue($this->variableID($instanceID, 'Position')));

        $this->receive($instanceID, self::TOPIC . '/totalTime', 'abc');
        $this->assertSame(0, GetValue($this->variableID($instanceID, 'Duration')));
    }

    public function testTileContainsMarkupAndStartData(): void
    {
        $instanceID = $this->createInstance(self::TOPIC);
        $this->receive($instanceID, self::TOPIC . '/title', 'Song "A"');
        $this->receive($instanceID, self::TOPIC . '/deviceState', 'playing');

        $html = IPS\InstanceManager::getInstanceInterface($instanceID)->GetVisualizationTile();

        $this->assertStringContainsString('id="atv"', $html);
        $this->assertStringContainsString("requestAction('Controls'", $html);
        $this->assertSame(1, preg_match('/handleMessage\((".*")\);<\/script>\s*$/s', $html, $match));
        $data = json_decode(json_decode($match[1]), true);
        $this->assertSame('Song "A"', $data['title']);
        $this->assertSame('playing', $data['deviceState']);
        $this->assertTrue($data['active']);
        $this->assertArrayHasKey('Nothing playing', $data['texts']);
    }

    public function testTileIsInactiveWithoutTopic(): void
    {
        $instanceID = $this->createInstance('');

        $html = IPS\InstanceManager::getInstanceInterface($instanceID)->GetVisualizationTile();

        $this->assertSame(1, preg_match('/handleMessage\((".*")\);<\/script>\s*$/s', $html, $match));
        $this->assertFalse(json_decode(json_decode($match[1]), true)['active']);
    }

    public function testEmptyPayloadClearsVariable(): void
    {
        $instanceID = $this->createInstance(self::TOPIC);
        $this->receive($instanceID, self::TOPIC . '/title', 'Song');

        $this->receive($instanceID, self::TOPIC . '/title', '');

        $this->assertSame('', GetValue($this->variableID($instanceID, 'Title')));
    }

    public function testOtherTopicsAreIgnored(): void
    {
        $instanceID = $this->createInstance(self::TOPIC);

        $this->receive($instanceID, 'other/appletv/title', 'Fremd');
        $this->receive($instanceID, self::TOPIC . 'x/title', 'Fremd');
        $this->receive($instanceID, self::TOPIC . '/unknown', 'Fremd');

        $this->assertSame('', GetValue($this->variableID($instanceID, 'Title')));
    }

    public function testInvalidPayloadsAreIgnored(): void
    {
        $instanceID = $this->createInstance(self::TOPIC);
        $interface = IPS\InstanceManager::getInstanceInterface($instanceID);

        $interface->ReceiveData('kein json');
        $interface->ReceiveData(json_encode(['DataID' => self::DATA_ID]));
        $interface->ReceiveData(json_encode(['Topic' => self::TOPIC . '/title']));
        $interface->ReceiveData(json_encode(['Topic' => self::TOPIC . '/title', 'Payload' => 'kein hex']));
        $interface->ReceiveData(json_encode(['Topic' => self::TOPIC . '/title', 'Payload' => 'ABC']));

        $this->assertSame('', GetValue($this->variableID($instanceID, 'Title')));
    }

    public function testNothingIsWrittenWithoutTopic(): void
    {
        $instanceID = $this->createInstance('');

        $this->receive($instanceID, '/title', 'Song');

        $this->assertSame('', GetValue($this->variableID($instanceID, 'Title')));
    }

    public function testControlIsPublishedAsTopicSuffixWithEmptyPayload(): void
    {
        $instanceID = $this->createInstance(self::TOPIC);

        RequestAction($this->variableID($instanceID, 'Controls'), 'wakeup');

        $this->assertCount(1, MQTTServerStub::$packets);
        $packet = MQTTServerStub::$packets[0];
        $this->assertSame('{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}', $packet['DataID']);
        $this->assertSame(3, $packet['PacketType']);
        $this->assertSame(self::TOPIC . '/wakeup', $packet['Topic']);
        $this->assertSame('', $packet['Payload']);
    }

    public function testEveryKnownControlIsPublished(): void
    {
        $instanceID = $this->createInstance(self::TOPIC);

        foreach (self::CONTROLS as $control) {
            RequestAction($this->variableID($instanceID, 'Controls'), $control);
        }

        $this->assertSame(
            array_map(fn (string $control): string => self::TOPIC . '/' . $control, self::CONTROLS),
            array_column(MQTTServerStub::$packets, 'Topic')
        );
    }

    public function testUnknownControlsAreNotPublished(): void
    {
        $instanceID = $this->createInstance(self::TOPIC);
        $controls = $this->variableID($instanceID, 'Controls');

        RequestAction($controls, 'nonsense');
        RequestAction($controls, '');
        RequestAction($controls, '../other/topic');

        $this->assertSame([], MQTTServerStub::$packets);
    }

    private function createInstance(string $topic): int
    {
        $instanceID = IPS_CreateInstance(self::MODULE_ID);
        MQTTServerStub::$packets = [];
        IPS_SetConfiguration($instanceID, json_encode(['MQTTTopic' => $topic]));
        IPS_ApplyChanges($instanceID);
        return $instanceID;
    }

    private function variableID(int $instanceID, string $ident): int|false
    {
        return @IPS_GetObjectIDByIdent($ident, $instanceID);
    }

    private function receive(int $instanceID, string $topic, string $payload): void
    {
        IPS\InstanceManager::getInstanceInterface($instanceID)->ReceiveData(json_encode([
            'DataID'  => self::DATA_ID,
            'Topic'   => $topic,
            'Payload' => bin2hex($payload)
        ]));
    }
}
