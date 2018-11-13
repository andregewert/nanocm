# Tools

Hier befinden sich CLI-Tools, die für die Entwicklungsprozess verwendet wurden
bzw. werden, jedoch nicht relavant sind für eine laufende Installation.

## Emoji-Verwaltung: parse_emoji_test.php

Dieses Programm soll die Emoji-Testdatei parsen, um daraus eine PHP-Datenstruktur
zu erstellen, die für das Emoji-Virtual-Keyboard verwendet wird.

## Aktualisierung der Emoji-Definitionen

- Herkunft der emoji-test.txt
- Aufruf von parse_emoji_test.php in der Shell und Umleitung der Ausgabe nach
ncm/sys/src/NanoCm/data/emoji-list.json
- Die Ausschlussliste emoji-blacklist.json wird manuell gepflegt
- Regelmäßig muss auf neue Definitionen und die aktuelle Unterstützung durch
die verschiedenen Betriebssysteme geachtet werden
- TODO Ausformulieren!
