# AppleTV
Mit diesem Modul ist es möglich einen Apple TV mithilfe von pyatv-mqtt-bridge (https://github.com/sebbo2002/pyatv-mqtt-bridge) über MQTT in IP-Symcon zu integrieren.

### Inhaltsverzeichnis

- [AppleTV](#appletv)
    - [Inhaltsverzeichnis](#inhaltsverzeichnis)
    - [1. Funktionsumfang](#1-funktionsumfang)
    - [2. Vorraussetzungen](#2-vorraussetzungen)
    - [3. Einrichten der Instanzen in IP-Symcon](#3-einrichten-der-instanzen-in-ip-symcon)
    - [4. Statusvariablen und Profile](#4-statusvariablen-und-profile)
      - [Statusvariablen](#statusvariablen)
      - [Profile](#profile)
    - [5. WebFront](#5-webfront)
    - [6. PHP-Befehlsreferenz](#6-php-befehlsreferenz)

### 1. Funktionsumfang

* Status aktueller Wiedergabe
* Steuerung des Apple TV
* Kachel für die Kachel-Visualisierung (Player mit Fortschritt, Steuerung und Fernbedienung)

### 2. Vorraussetzungen

- IP-Symcon ab Version 9.0
- pyatv-mqtt-bridge (https://github.com/sebbo2002/pyatv-mqtt-bridge), die den Apple TV über MQTT bereitstellt

### 3. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' ist das 'AppleTV'-Modul unter dem Hersteller 'Apple' aufgeführt.

__Konfigurationsseite__:

Name     | Beschreibung
-------- | ------------------
MQTT Topic | Topic des Apple TV in der pyatv-mqtt-bridge (Topic-Präfix aus der Konfiguration der Bridge)

### 4. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name   | Typ     | Beschreibung
------ | ------- | ------------
Name|String| Name des Apple TVs
IP-Adresse|String| IP-Adresse des Apple TVs
Gerätestatus|String| Staus der Wiedergabe / Standby des Gerätes
Power Status|String| Status, ob das Gerät ein- oder ausgeschaltet ist
Steuerung |String| Variable zur Bedienung des AppleTVs
Dauer|Integer| Dauer der aktuellen Wiedergabe in Sekunden
Position|Integer| Aktuelle Position der Wiedergabe in Sekunden
Artist|String| Aktueller Künstler der Wiedergabe
Titel|String| Aktueller Title der Wiedergabe
Album|String| Aktuelles Album der Wiedergabe
App|String| Aktuelle App
AppBundleIdentifier|String| AppBundleIdentifier

#### Profile

Name   | Typ
------ | -------
ATV.Control|String

### 5. WebFront

Anzeige und Steuerung des Apple TVs.

__Kachel-Visualisierung__:

Die Instanz zeigt in der Kachel-Visualisierung einen Player:

* Gerätename, App und Status (Wiedergabe, Pausiert, Standby)
* Titel, Artist und Album der aktuellen Wiedergabe
* Fortschrittsbalken mit Position und Dauer (läuft zwischen den Meldungen des Apple TV weiter)
* Zurück, Rückwärts springen, Wiedergabe/Pause, Vorwärts springen, Weiter
* Lautstärke, Aufwecken und Standby
* Umschaltbare Fernbedienung mit Steuerkreuz, Auswahl, Zurück, Home und Home halten

Die Bedienung läuft über die Variable "Steuerung". Die maximierte Ansicht der Kachel gibt es ab IP-Symcon 9.1, unter 9.0 bleibt sie leer.

Hinweis: Position und Dauer erscheinen nur, wenn die pyatv-mqtt-bridge sie als `position` und `totalTime` liefert.

### 6. PHP-Befehlsreferenz

`RequestAction(integer $VariablenID, $Value);`
Schalten der Variable.

Beispiel:
 Variable Steuerung = 12345
 AppleTV in Standby aufwecken
`RequestAction(12345, 'wakeup');`

Hinweis: Wird das Topic leer gelassen, steht die Instanz auf "Inaktiv" und empfängt keine Daten.
