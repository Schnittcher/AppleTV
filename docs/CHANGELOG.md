# Changelog

## 19.10.2026 - Version 2.0

### Neu
- Neue Kachel für die Kachel-Visualisierung: Player mit Titel, Artist, Album, Fortschrittsbalken, Wiedergabesteuerung, Lautstärke, Standby und einer einblendbaren Fernbedienung.
- Neue Variable "Position", die Variable "Dauer" wird jetzt gefüllt.
- Das Modul benötigt jetzt IP-Symcon 9.0 und ist auf die aktuelle Modulschnittstelle umgestellt.
- Die Instanz zeigt den Status "Inaktiv", solange kein MQTT Topic eingetragen ist.

### Fixes
- Ungültige Steuerbefehle und fehlerhafte MQTT-Nachrichten werden ignoriert, statt Fehler zu erzeugen.
- Die Steuerung meldet keinen Fehler mehr, wenn der MQTT-Parent nicht aktiv ist.
- Der Befehl "Suspend" heißt in der Auswahl wieder richtig (bei bestehenden Installationen erst nach Neuanlegen des Profils).
