# Ablauf der automatisierten Installation

- Es wird *ein* Template geladen mit den wichtigsten Eingabemöglichkeiten
(Seitenname, Admin-Name und -Mail etc.)
- Es werden die notwendigen Zugriffsrechte innerhalb des Installationsverzeichnisses überprüft
- Bei Bestätigung wird:
    - Das Create-DDL für die höchste Verfügbare Datenbankversion ermittelt und ausgeführt
    - Daraus ergibt sich aktuelle Datenbankversion
    - In die Datenbank wird ein Administrations-User mit dem eingegebenen Passwort eingetragen
    - Grundlegende Daten für vorgegebene Listen (Navigation) etc. sollten in dem Create-Script enthalten sein
    - Anhand der aktuellen Pfade ergibt sich der RelativeBaseUrl
    - Mit Hilfe der RelativeBaseUrl kann die .htaccess erstellt / modifiziert werden
    - Die Datenbankversion wird in die version.json geschrieben
- Anmeldung in der Session
- Redirect auf die Startseite der Verwaltung

