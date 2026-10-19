# AppleTV
Die Instanz bildet einen Apple TV in IP-Symcon ab. Sie empfängt Status und Wiedergabedaten der pyatv-mqtt-bridge per MQTT, legt dafür Statusvariablen an und sendet Steuerbefehle zurück an die Bridge. Zusätzlich zeigt sie in der Kachel-Visualisierung einen Player mit Fernbedienung.

## Inhaltsverzeichnis
- [AppleTV](#appletv)
  - [Inhaltsverzeichnis](#inhaltsverzeichnis)
  - [1. Konfiguration](#1-konfiguration)
  - [2. Variablen](#2-variablen)
  - [3. Kachel-Visualisierung](#3-kachel-visualisierung)
  - [4. Funktionen](#4-funktionen)
  - [5. Spenden](#5-spenden)
  - [6. Lizenz](#6-lizenz)

## 1. Konfiguration

Feld | Beschreibung
------------ | ----------------
MQTT Topic | Topic des Apple TV, wie es in der Konfiguration der pyatv-mqtt-bridge steht (zum Beispiel `home/wohnzimmer/appletv`). Ohne Topic steht die Instanz auf "Inaktiv" und empfängt keine Daten.

Als Gateway wird der interne MQTT-Server von IP-Symcon verwendet. Mit einem externen Broker muss der MQTT Client von Hand angelegt und in der Instanz unter "Gateway ändern" ausgewählt werden.

## 2. Variablen
Die Variablen werden automatisch angelegt. Das Löschen einzelner Variablen kann zu Fehlfunktionen führen.

Variable | Ident | Typ | Beschreibung
------------ | ------------ | ------------ | ----------------
Name | `Name` | Text | Name des Apple TV
IP-Adresse | `IP` | Text | IP-Adresse des Apple TV
Gerätestatus | `DeviceState` | Text | Status der Wiedergabe (zum Beispiel `playing`, `paused`, `idle`)
Power Status | `PowerState` | Text | `on` oder `off`
Steuerung | `Controls` | Text | Steuerbar. Sendet den gewählten Befehl an den Apple TV (Profil `ATV.Control`)
Dauer | `Duration` | Ganzzahl (Sekunden) | Dauer der aktuellen Wiedergabe, Topic `totalTime` der Bridge
Position | `Position` | Ganzzahl (Sekunden) | Aktuelle Position der Wiedergabe, Topic `position` der Bridge
Artist | `Artist` | Text | Künstler der aktuellen Wiedergabe
Titel | `Title` | Text | Titel der aktuellen Wiedergabe
Album | `Album` | Text | Album der aktuellen Wiedergabe
App | `AppDisplayName` | Text | Name der aktuellen App
AppBundleIdentifier | `AppBundleIdentifier` | Text | Kennung der aktuellen App

Dauer und Position erscheinen nur, wenn die pyatv-mqtt-bridge sie liefert.

Das Profil `ATV.Control` enthält die Befehle `down`, `home`, `homeHold`, `left`, `menu`, `next`, `pause`, `play`, `playPause`, `previous`, `right`, `select`, `skipBackward`, `skipForward`, `stop`, `suspend`, `topMenu`, `up`, `volumeDown`, `volumeUp`, `wakeup` und `turnOn`.

## 3. Kachel-Visualisierung
Die Instanz zeigt in der Kachel-Visualisierung einen Player:

* Gerätename, App und Status (Wiedergabe, Pausiert, Standby)
* Titel, Artist und Album der aktuellen Wiedergabe
* Fortschrittsbalken mit Position und Dauer, der zwischen den Meldungen des Apple TV weiterläuft
* Zurück, Rückwärts springen, Wiedergabe/Pause, Vorwärts springen, Weiter
* Lautstärke, Aufwecken und Standby
* Umschaltbare Fernbedienung mit Steuerkreuz, Auswahl, Zurück, Home und Home halten

Die Bedienung läuft über die Variable "Steuerung". Die maximierte Ansicht der Kachel gibt es ab IP-Symcon 9.1, unter 9.0 bleibt sie leer.

## 4. Funktionen

`RequestAction(integer $VariablenID, string $Wert);`
Sendet einen Befehl an den Apple TV. Die Variable ist die Variable "Steuerung" (`Controls`), der Wert einer der Befehle des Profils `ATV.Control`. Unbekannte Werte werden ignoriert.

Beispiel, um den Apple TV aus dem Standby zu wecken:

`RequestAction(12345, 'wakeup');`

## 5. Spenden
Dieses Modul ist für die nicht kommerzielle Nutzung kostenlos, Schenkungen als Unterstützung für den Autor werden hier akzeptiert:

[![PayPal](https://img.shields.io/badge/PayPal-Spenden-00457C?logo=paypal&logoColor=white&style=for-the-badge)](https://www.paypal.com/donate?hosted_button_id=EK4JRP87XLSHW) [![Amazon Wunschzettel](https://img.shields.io/badge/Amazon-Wunschzettel-FF9900?logo=amazon&logoColor=white&style=for-the-badge)](https://www.amazon.de/hz/wishlist/ls/3JVWED9SZMDPK?ref_=wl_share)

## 6. Lizenz

[CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/)
