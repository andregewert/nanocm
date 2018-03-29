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

# Fremdkomponenten

NanoCM versucht bewusst, auf Abhängigkeiten von fremden Komponenten zu verzichten.
Ein kompletter Verzicht ist trotzdem nicht möglich, und so werden folgende Fremdkomponenten
verwendet:

- [Fat Cow Icons](http://www.fatcow.com/free-icons) (für Symbole im Administrationsbereich)
- [Bootstrap](https://getbootstrap.com/) (für das Standard-Seitentemplate)

# Mitmachen

Der Autor freut sich über jede Person, die zu diesem Projekt beitragen möchte.
Dafür ist das Code-Repository frei zugänglich; jede(r) kann also das Projekt
klonen und Pull Request einreichen. Gerne kann vorab auch Kontakt zum Autor
hergestellt werden, um Ideen, offene Baustellen usw. zu besprechen.

Als Entwicklungsumgebung wurde vom Autor ursprünglich Netbeans verwendet.
Im weiteren Projektverlauf wurde vollständig auf JetBrains PHPStorm gewechselt.
Die Entwicklung ist jedenfalls mit beiden IDEs gut möglich.

// TODO Link auf das öffentliche Repo bereitstellen

# Dokumentation

Für die Dokumentation wurden einerseits Markdown-formatierte Textdateien
und andererseits MindMaps verwendet. Für das Markdown-Format existieren
zahlreiche Standalone-Tools und IDE-Plugins. Die MindMaps sind entstanden
mit dem [Netbeans MindMaps-Plugin](http://www.igormaznitsa.com/netbeans-mmd-plugin/)
von Igor Maznitsa, das auch als Plugin für die JetBrains-IDEs sowie als
alleinstehende Anwendung verfügbar ist.

# Autor

André Gewert  
agewert@gmail.com  
www.ubergeek.de
