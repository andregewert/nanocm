# Benutzerdefinierte Templates

In diesem Verzeichnis werden Templates für die öffentliche Ausgabeseite (also alles außer dem Administrationsbereich)
abgelegt.

NanoCM unterstützt die gleichzeitige Installation mehrerer Ausgabe-Templates, zwischen denen umgeschaltet werden kann.
Jedes zusammengehörige Template wird in einem separaten Verzeichnis abgelegt. Wird in den konfigurierten Verzeichnis
ein bestimmte Datei nicht gefunden, so versucht das System, die entsprechende Datei aus dem Ordner "default" zu
verwenden, weil davon ausgegangen wird, dass das Default-Template immer zusammen mit der Anwendung aktualisiert wird und
somit vollständig ist.

Jedes Template kann und soll für sich eigene Unterverzeichnisse für Javascript-Dateien, Images, Fonts usw. mitbringen.

## Offene Punkte

- Das Default-Template wird erst (vollständig) entwickelt, wenn die Vorlage für ubergeek.de fertig ist
- Das ubergeek-Template ist und bleibt Bestandteil des Quellcode-Verwaltung
- Das Default-Template soll schlicht und generisch bleiben und möglichst auf Bootstrap basieren
