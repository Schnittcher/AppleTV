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

### 2. Vorraussetzungen

- IP-Symcon ab Version 6.0

### 3. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' ist das 'AppleTV'-Modul unter dem Hersteller 'Apple' aufgeführt.

__Konfigurationsseite__:

Name     | Beschreibung
-------- | ------------------
MQTT Topic | Topic des ATV2MQTT Moduls, in der Konfigurations Datei von ATV2MQTT zu finden

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
Dauer|Integer| Dauer der aktuellen Wiedergabe
Artist|String| Aktueller Künstler der Wiedergabe
Titel|String| Aktueller Title der Wiedergabe
Album|String| Aktuelles Album der Wiedergabe
App|String| Aktuelle App
AppBundleIdentifier|String| AppBundleIdentifier

#### Profile

Name   | Typ
------ | -------
ATV.Controls|String

### 5. WebFront

Anzeige und Steuerung des Apple TVs.

### 6. PHP-Befehlsreferenz

`RequestAction(integer $VariablenID, $Value);`
Schalten der Variable.

Beispiel:
 Variable Steuerung = 12345
 AppleTV in Standby aufwecken
`RequestAction(12345, 'wakeup');`