# NanoCm

Ein einfaches, kleines Content Management System und Blogging-Plattform.

# Mission Statement

Oberstes Ziel bei der Entwicklung des NanoCM war es, eine Zero-Conf-Installation
zu ermöglichen, bei der weder Datenbank-Konfigurationen noch externe
Abhängigkeiten konfiguriert werden müssen. NanoCM verwendet aus diesem Grunde
eine eingebettete SQLite-Datenbank und verzichtet auf die Verwendung von
externen PHP-Bibliotheken und auf die Verwendung von Composer.

Weitere Ziele bei der Entwicklung von NanoCM waren und sind:

- Einfache Textbearbeitung, angelehnt an die Markdown-Syntax, aber mit größerer Flexibilität
- Erweiterbarkeit über Plugins, insbesondere in Hinblick auf die Textverarbeitung
- Flexibles Einbinden von statischen Seiten und bestehenden Anwendungen
- Service-orientierte Architektur, die eine einfache Anbindung zusätzlicher Clients ermöglicht
- Clean Code: Die PHP-Quellen sollen klar strukturiert und einfach wartbar sein

# Besondere Funktionen

- Integrierte ePub-Generierung
- Virtuelles Emoji-Keyboard
- Unterstützung von austauschbaren Themes bzw. Templates

# Fremdkomponenten

NanoCM versucht bewusst, auf Abhängigkeiten von fremden Komponenten zu verzichten.
Ein kompletter Verzicht ist trotzdem nicht möglich, und so werden folgende Fremdkomponenten
verwendet:

- [jQuery](https://jquery.com/) (weil es einem so furchtbar viel Arbeit abnimmt)
- [Fat Cow Icons](http://www.fatcow.com/free-icons) (für Symbole im Administrationsbereich)
- [highlight.js](https://highlightjs.org/) (für das Syntax-Highlighting bei Code-Beispielen)
- [Lightbox](https://lokeshdhakar.com/projects/lightbox2/) (die Original-Lightbox von Lokesh Dhakar für die Präsentation von Bildern)
- [Bootstrap](https://getbootstrap.com/) (für das Standard-Seitentemplate - also nicht für ubergeek.de)
- Source Sans Pro: Copyright 2010, 2012, 2014 Adobe Systems Incorporated (http://www.adobe.com/), with Reserved Font Name Source.
- Oswald: Copyright 2016 The Oswald Project Authors (https://github.com/googlefonts/OswaldFont)

# Mitmachen

Der Autor freut sich über jede Person, die zu diesem Projekt beitragen möchte.
Dafür ist das Code-Repository frei zugänglich; jede/r kann also das Projekt
klonen und Pull Request einreichen. Gerne kann vorab auch Kontakt zum Autor
hergestellt werden, um Ideen, offene Baustellen usw. zu besprechen.

Als Entwicklungsumgebung wurde vom Autor ursprünglich Netbeans verwendet.
Im weiteren Projektverlauf wurde vollständig auf JetBrains PHPStorm gewechselt.
Die Entwicklung ist jedenfalls mit beiden IDEs gut möglich, auch wenn das Netbeans-Projekt
leider halbtot ist.

Das öffentlichte GIT-Repository ist über Github verfügbar:

https://github.com/ubergewert/nanocm

# Dokumentation

Für die Dokumentation wurden einerseits Markdown-formatierte Textdateien
und andererseits MindMaps verwendet. Für das Markdown-Format existieren
zahlreiche Standalone-Tools und IDE-Plugins. Die MindMaps sind entstanden
mit dem [Netbeans MindMaps-Plugin](http://www.igormaznitsa.com/netbeans-mmd-plugin/)
von Igor Maznitsa, das auch als Plugin für die JetBrains-IDEs sowie als
alleinstehende Anwendung verfügbar ist.

# Voraussetzungen

- PHP 7.3  
Anmerkung: Ich habe erst spät im Laufe der Entwicklung den "language level" auf Version
7.3 und noch später auf PHP 8 gehoben.
- PHP-Extension pdo_sqlite
- PHP-Extension curl
- Apache mit mod_rewrite

# TODO

- PSR-kompatibles Logging implementieren
- Zusätzliche Order für Root-Namespace hinzufügen

# Autor

André Gewert  
agewert@ubergeek.de  
www.ubergeek.de
