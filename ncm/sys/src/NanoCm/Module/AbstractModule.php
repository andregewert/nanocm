<?php

/**
 * NanoCM
 * Copyright (C) 2017 - 2018 André Gewert <agewert@ubergeek.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace Ubergeek\NanoCm\Module;

use Ubergeek\Controller\ControllerInterface;
use Ubergeek\Log\LoggerInterface;
use Ubergeek\NanoCm\Article;
use Ubergeek\NanoCm\Constants;
use Ubergeek\NanoCm\ContentConverter\HtmlConverter;
use Ubergeek\NanoCm\Exception\AuthorizationException;
use Ubergeek\NanoCm\Exception\ContentNotFoundException;
use Ubergeek\NanoCm\Medium;
use Ubergeek\NanoCm\UserMessage;
use Ubergeek\NanoCm\Util;

/**
 * Die Basisklasse für NCM-Module versteckt einige Implementierungsdetails und
 * stellt ableitenden Klassen genau die Referenzen und Methoden sowie einige
 * Shortcut-Methoden bereit, die häufig genutzt werden.
 * 
 * Über die referenzierten Objekte ist es den Implementierungen, also konkreten
 * Modulen, immer noch möglich, auf alle Teile des Software-Systems (bspw. den
 * ausführenden FrontController) zuzugreifen. Die Modul-Klasse selbst implementiert
 * außerdem das ControllerInterface.
 * 
 * Konkrete Module müssen dementsprechend die zentrale run()-Methode
 * implementieren. Außerdem kann die init()-Methode überschrieben werden.
 * Die Klasse AbstractModule besitzt eine einfache Implementierung der
 * execute()-Methode, die den über run() generieren Inhalt in ein
 * Seiten-Template einbindet. Soll kein Seiten-Template genutzt werden, so kann
 * mit setPageTemplate(self::PAGE_NONE) dieses Verhalten abgeschaltet werden.
 * 
 * Jedes Modul hat jedoch auch die Möglichkeit, die execute()-Methode mit
 * spezifischen Verhalten zu überschreiben.
 * 
 * AbstractModule implementiert außerdem das LoggerInterface und leitet
 * entsprechende Methodenaufrufe an die Logger-Instanz weiter, die die NanoCM-
 * Instanz erstellt und konfiguriert. Jedes Modul hat die Möglich, dieses
 * Verhalten zu ändern und beispielsweise einen eigenen Logger zu konfigurieren.
 * 
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2017-11-11
 */
abstract class AbstractModule implements
    ControllerInterface,
    LoggerInterface {

    // <editor-fold desc="Constants">
    
    /**
     * Bezeichnet das Standard-Seiten-Template
     * @var string
     */
    const PAGE_STANDARD = 'page-standard.phtml';
    
    /**
     * Vereinfachtes Template für das Setup
     * @var string
     */
    const PAGE_SETUP = 'page-setup.phtml';
    
    /**
     * Kann genutzt werden, wenn der Seiteninhalt nicht in ein Seiten-Template
     * eingebunden werden soll
     * @var string
     */
    const PAGE_NONE = 'none';
    
    // </editor-fold>
    
    
    // <editor-fold desc="Properties">
    
    /**
     * Referenz auf den ausführenden FrontController.
     * 
     * Über diese Referenz sind auch alle anderen benötigten Ressourcen
     * zugänglich. Dazu gehören bspw. Datenbank-Mapping und Medien-Manager.
     * (Außerdem Konfiguration der zu verwendenden Pfade.)
     * 
     * @var \Ubergeek\NanoCm\FrontController
     */
    public $frontController = null;

    /**
     * Referenz auf die NanoCM-Instanz
     * @var \Ubergeek\NanoCm\NanoCm
     */
    public $ncm = null;
    
    /**
     * Referenz auf den OR-Mapper
     * @var \Ubergeek\NanoCm\Orm
     */
    public $orm = null;
    
    /**
     * Referenz auf eine Logger-Instanz
     * @var \Ubergeek\Log\Logger
     */
    public $log = null;
    
    /**
     * Enthält eine Referenz auf die zuletzt aufgefangenge Exception.
     * Kann im Template für die Fehlerausgabe verwendet werden.
     * @var \Exception
     */
    public $exception;
    
    /**
     * Zu renderndes Seiten-Template
     * @var string
     */
    public $pageTemplate = 'page-standard.phtml';
    
    /**
     * Enthält optionale Optionen, die von den Templates (insbesondere den
     * Seiten-Templates) ausgewertet werden können.
     *
     * @var array
     */
    public $templateOptions = array();

    /**
     * Gibt den relativen Pfad zu den Templatedateien an
     *
     * @var string
     */
    public $templateDir = null;

    /**
     * Gibt an, ob die jeweiligen Templates (die im Systemverzeichnis abgelegt
     * sind) auch durch installationsspezifische Versionen (die überhalb des
     * NCM-Systemverzeichnisses abgelegt sind) überschrieben werden können.
     * 
     * Bei den Templates für den Administrationsbereich ist das bspw. nicht
     * der Fall; hier sollen immer ausschließlich die vordefinierten
     * Systemdateien verwendet werden.
     * 
     * @var bool
     */
    public $allowUserTemplates = true;

    /**
     * Hinweismeldungen für den Anwender
     *
     * @var UserMessage[]
     */
    public $userMessages = array();

    /**
     * Gibt das Ausgabeformat an (HTML oder XHTML)
     *
     * Im Standardfall sollte das TinyCM HTML5 ausgeben. Bei der Erstellung von
     * EPub-Inhalten wird diese Option jedoch genutzt, um den Templates zu signalisieren,
     * dass XHTML erzeugt werden muss.
     *
     * @var string
     */
    public $targetFormat = Constants::FORMAT_HTML;
    
    // </editor-fold>
    
    
    /**
     * Dem Konstruktor wird eine Referenz auf den ausführenden FrontController
     * übergeben. Hierüber sind alle weiteren Ressourcen des NanoCM erreichbar.
     * 
     * @param \Ubergeek\NanoCm\FrontController $frontController Referenz auf den
     *      ausführenden Controller.
     */
    public function __construct(\Ubergeek\NanoCm\FrontController $frontController) {
        $this->frontController = $frontController;
        $this->ncm = $frontController->ncm;
        $this->orm = $frontController->ncm->orm;
        $this->log = $frontController->ncm->log;
        $this->templateDir = $frontController->ncm->tpldir;

        $this->replaceMeta('Cache-control', 'private, max-age=10800, must-revalidate');
    }

    
    // <editor-fold desc="Grundlegende Ausgabe-Funktionalität">

    public function getAction() {
        return $this->getParam('action');
    }

    /**
     * Gibt die URL für einen bestimmten Artikel zurück
     *
     * Die zurückgegebene URL ist relativ zum NanoCM-Installationsverzeichnis, muss also in der Regel bei der Ausgabe im
     * Template noch mit convUrl() behandelt werden.
     *
     * @param Article $article Der betreffende Artikel
     * @return string Die URL zum angegebenen Artikel
     */
    public function getArticleUrl(Article $article) {
        return $article->getArticleUrl();
    }

    /**
     * Gibt die URL für ein auf das angegebene Format skalierte Vorschaubild für eine bestimmte Mediendatei zurück
     *
     * Die zurückgegebene URL ist relativ zum NanoCM-Installationsverzeichnis, muss also in der Regel bei der Ausgabe im
     * Template noch mit convUrl() behandelt werden.
     *
     * @param Medium $medium Die betreffende Mediendatei
     * @param string $formatKey Schlüssel des gewünschten Ausgabeformats
     * @return string URL zum skalierten Bild
     */
    public function getImageUrl(Medium $medium, string $formatKey) {
        return $medium->getImageUrl($formatKey);
    }

    /**
     * Gibt den Download-Link für eine über die Medienverwaltung verwaltete Datei zurück
     *
     * Die zurückgegebene URL ist relativ zum NanoCM-Installationsverzeichnis, muss also in der Regel bei der Ausgabe im
     * Template noch mit convUrl() behandelt werden.
     *
     * @param Medium $medium Das betreffende Medium
     * @return string Die Download-URL für diese Mediendatei
     */
    public function getDownloadUrl(Medium $medium) {
        return '/media/' . $medium->hash . '/download/';
    }

    /**
     * Gibt die URL für ein Youtube-Thumbnail in dem angegebenen Bildformat zurück
     *
     * Die zurückgegebene URL ist relativ zum NanoCM-Installationsverzeichnis, muss also in der Regel bei der Ausgabe im
     * Template noch mit convUrl() behandelt werden.
     *
     * @param string $youtubeId Die Youtube-Video-ID
     * @param string $formatKey Schlüssel für das gewünschte Bildformat
     * @return string Die URL zum Thumbnail
     */
    public function getYoutubeThumbnailUrl(string $youtubeId, string $formatKey) {
        return "/media/$youtubeId/yt/$formatKey";
    }

    /**
     * Gibt die BaseURL für die NCM-Installation zurück
     *
     * Hierbei handelt sich "lediglich" um denjenigen Teil-Pfad, der zwischen DOCUMENT_ROOT und NanoCM-Installation liegt.
     * Ist das NanoCM direkt im Document Root installiert, so beinhaltet die BaseUrl den String "/"; ist NanoCM bspw. im
     * Unterverzeichnis "nanocm" installiert, so enthält die BaseUrl den Wert "nanocm/". Wichtig für den gesamten
     * Mechanismus ist, dass das Document Root in der Webserver-Konfiguration korrekt hinterlegt ist. Bei manchen
     * virtualisierten bzw. chroot-Umgebungen ist dies nicht immer der Fall.
     *
     * @return string
     */
    public function getBaseUrl() {
        return $this->ncm->relativeBaseUrl;
    }

    /**
     * Erstellt aus einer relativen URL, die sich auf das Installationsverzeichnis von NanoCM bezieht, eine
     * server-absolute URL
     *
     * Beispiel: Soll auf die Startseite der NanoCM-Installation verwiesen werden ($relativeURL ist "/index.html"), und ist NanoCM
     * im Verzeichnis "user1/nanocm" unterhalb des Document Root installiert, so liefert diese Methode die URL
     * "user1/nanocm/index.html" zurück.
     *
     * @param $relativeUrl Auf das Installationsverzeichnis von NanoCM bezogene relative URL
     * @return string Server-absolute URL
     */
    public function convUrl($relativeUrl) {
        if (preg_match('/^[a-z]+\:/i', $relativeUrl)) return $relativeUrl;
        $url = $this->ncm->relativeBaseUrl;
        if (substr($relativeUrl, 0, 1) == '/') {
            $relativeUrl = substr($relativeUrl, 1);
        }
        return $url . $relativeUrl;
    }

    public function convUrlToAbsolute($relativeUrl) {
        return $this->frontController->createAbsoluteSiteLink($relativeUrl);
    }

    /**
     * Wandelt eine URL genau so um wie convUrl, kodiert jedoch zusätzlich HTML-Sonderzeichen, so dass der Rückggabewert
     * direkt bzw. ohne weiteren Funktionsaufruf in HTML-Templates ausgegeben werden kann
     *
     * @param $relativeUrl string Auf das Installationsverzeichnis von NanoCM bezogene relative URL
     * @return string Server-absolute und HTML-kodierte URL
     */
    public function htmlConvUrl(string $relativeUrl) : string {
        return $this->htmlEncode($this->convUrl($relativeUrl));
    }

    /**
     * Gibt den Standardtitel der Site zurück.
     *
     * @return string Seitentitel
     */
    public function getSiteTitle() : string {
        return $this->orm->getSiteTitle();
    }

    /**
     * Setzt eine Template-Option auf den angegebenen Wert
     *
     * @param string $key Name der Option
     * @param mixed $value Neuer Wert der Option
     */
    public function setTemplateOption(string $key, $value) {
        if (!is_array($this->templateOptions)) {
            $this->templateOptions = array();
        }
        $this->templateOptions[$key] = $value;
    }
    
    /**
     * Gibt - falls vorhanden - den Wert der genannten Template-Option oder
     * andernfalls einen bestimmten Standardwert zurück
     * @param string $key Name der Option
     * @param mixed $default Zurück zu gebender Standardwert, falls Options nicht gesetzt
     * @return mixed Angeforderte Template-Option oder Standardwert
     */
    public function getTemplateOption(string $key, $default = null) {
        if (!is_array($this->templateOptions) || !array_key_exists($key, $this->templateOptions)) {
            return $default;
        }
        return $this->templateOptions[$key];
    }
    
    /**
     * Setzt das zu nutzende Seiten-Template.
     * Diese Klasse enthält entsprechende Konstanten mit den vordefinierten
     * Templates, die als Parameter genutzt werden können.
     * 
     * @param string $pageTemplate
     */
    public function setPageTemplate(string $pageTemplate) {
        $this->pageTemplate = $pageTemplate;
    }

    /**
     * Shortcut-Methode zum Setzen des HTTP-Headers für den auszugebenden 
     * Content-Type.
     * @param string $contentType
     */
    public function setContentType(string $contentType) {
        $this->replaceMeta('content-type', $contentType);
    }

    /**
     * Kodiert einen String für die HTML-Ausgabe.
     * Der Eingabestring muss UTF8-kodiert sein.
     *
     * @param string $string
     * @param string $overrideTargetFormat Zielformat angeben, um dieses zu erzwingen
     * @return string HTML-kodierter String
     */
    public function htmlEncode($string, $overrideTargetFormat = null) : string {
        $format = ($overrideTargetFormat == null)? $this->targetFormat : $overrideTargetFormat;
        return Util::htmlEncode($string, $format);
    }

    /**
     * Formatiert eine Ganzzahl für die Ausgabe (mit Tausender-Trennzeichen)
     * @param $int Ganzzahliger Wert
     * @return string Für die Ausgabe formatierter Wert
     * @todo Konvertierung muss lokalisierbar sein!
     */
    public function formatInt($int) : string {
        return number_format(intval($int), 0, ',', '.');
    }

    /**
     * Formatiert einen Dezimalbruch für die Ausgabe (mit Tausender- und Dezimaltrennzeichen)
     * @param $float Dezimalbruch
     * @param int $decimals Anzahl Nachkommastellen
     * @return string Für die Ausgabe formatierter Wert
     * @todo Konvertierung muss lokalisierbar sein!
     */
    public function formatFloat($float, $decimals = 2) : string {
        return number_format(floatval($float), $decimals, ',', '.');
    }

    /**
     * Bindet an Ort und Stelle ein Template ein
     * @param string $file Relativer Pfad zum betreffenden Template
     * @throws \Exception Exceptions, die vom Template geworfen werden, werden von dieser Methode weitergeworfen
     */
    public function includeUserTemplate(string $file) {
        echo $this->renderUserTemplate($file);
    }
    
    /**
     * Rendert ein Template, das installations-spezifisch überschrieben werden
     * kann.
     * @param string $file Das zu rendernde Template (ohne Pfadangabe)
     * @return string Inhalt des gerenderten Templates
     * @throws \Exception Exceptions, die bei der Ausführung des Templates
     *      geworfen werden, werden weitergeworfen
     * @todo Möglichkeit, ein spezifisches Template-Verzeichnis zu konfigurieren
     */
    public function renderUserTemplate(string $file) : string {
        $c = '';
        $fname = Util::createPath($this->templateDir, $file);

        if ($this->allowUserTemplates && !file_exists($fname)) {
            $fname = Util::createPath(array($this->ncm->pubdir, 'tpl', 'default'));
        }

        if (!file_exists($fname)) {
            throw new \Exception("Template file not found: $file");
        }
        
        // Ermitteltes Template einbinden
        ob_start();
        try {
            include($fname);
            $c = ob_get_contents();
        } catch (\Exception $ex) {
            throw $ex;
        } finally {
            ob_end_clean();
        }
        
        return $c;
    }

    /**
     * Gibt den zur NanoCM-Installation relativen Teil der
     * angeforderten URL zurück
     * @return string
     */
    public function getRelativeUrl() : string {
        return $this->frontController->getRelativeUrl();
    }
    
    /**
     * Gibt die relativen (in Bezug nur NCM-Installation) URL-Bestandteile
     * zurück
     * @return string[]
     */
    public function getRelativeUrlParts() : array {
        return $this->frontController->getRelativeUrlParts();
    }
    
    public function getRelativeUrlPart(int $idx) : string {
        $parts = $this->getRelativeUrlParts();
        if (count($parts) > $idx) {
            return $parts[$idx];
        }
        return '';
    }

    /**
     * Ermittelt einen Wert aus den HTTP-Parametern oder aus der NanoCM-Session, falls nicht im Request gesetzt.
     *
     * Der Wert wird anschließend in die Session zurückgeschrieben. So können Variablen bequem aus der Session
     * ausgelesen und gegebenenfalls mit einem HTTP-Parameter überschrieben werden. Dieser Mechanismus sollte nur bei
     * unkritischen Dingen - etwa der Übergabe von Such-Parametern - genutzt werden.
     *
     * @param string $name Name des Parameters
     * @param $default Standardwert, falls Schlüssel weder im Request noch in der Session gesetzt
     * @return mixed
     */
    public function getOrOverrideSessionVarWithParam(string $name, $default = null) {
        $value = $default;
        if ($this->isParamExisting($name)) {
            $value = $this->getParam($name, $default);
            if ($value == '') $value = null;
        } elseif ($this->ncm->session != null && $this->ncm->session->isVarExisting($name)) {
            $value = $this->ncm->session->getVar($name, $default);
            if ($value == '') $value = null;
        }

        if ($this->ncm->session != null) {
            $this->ncm->session->setVar($name, $value);
        }

        return $value;
    }

    /**
     * Fügt eine Hinweismeldung für den Endanwender hinzu
     *
     * @param string|null $message Der eigentliche Hinweistext
     * @param string|null $title Überschrift für die Meldung
     * @param string $type Der Nachrichtentyp
     * @return void
     */
    public function addUserMessage($message, $title = null, $type = UserMessage::TYPE_INFO) {
        if (!is_array($this->userMessages)) {
            $this->userMessages = array();
        }
        array_push($this->userMessages, new UserMessage($message, $title, $type));
    }

    // </editor-fold>


    // <editor-fold desc="Erweiterte Formatierungsfunktionen">

    /**
     * Ersetzt Zeilenumbrüche im übergebenen Eingabe-String durch <br>-Tags
     *
     * @param string $string Eingabe-String
     * @return string Text mit durch <br>-Tag ersetzten Zeilenumbrüchen
     */
    public function nl2br(string $string) : string {
        $string = preg_replace('/(\n|\r\n|\n\r)/i', "<br>", $string);
        return($string);
    }

    /**
     * Konvertiert einen Eingabestring mit Formatierungs-Auszeichnungen in das
     * angegebene Zielformat
     *
     * Die Konvertierung soll modular aufgebaut und konfigurierbar sein.
     * Das Eingabeformat orientiert sich an Markdown, weicht aber in einigen
     * Punkten davon ab. So ist beispielsweise kein eingebetteter HTML-Code
     * erlaubt.
     *
     * @param string $input Eingabestring
     * @return string Der ins Ausgabeformat konvertierte String
     */
    public function convertTextWithFullMarkup(string $input) : string {
        switch ($this->targetFormat) {
            case Constants::FORMAT_HTML:
            case Constants::FORMAT_XHTML:
                $converter = new HtmlConverter($this);
                if ($this->targetFormat == Constants::FORMAT_XHTML) {
                    $converter->generateXhtml = true;
                }
                $output = $converter->convertFormattedText($input);
                break;

            default:
                throw new \InvalidArgumentException("Unsupported target format: $this->targetFormat");
        }

        return $output;
    }

    /**
     * Wandelt einen Kommentartext bzw. einen Text mit simplen Formatierungsoptionen um in das aktuelle
     * Zielformat
     * @param string $input Der Eingabestring
     * @return string Ins Zielformat umgewandelter Text
     */
    public function convertTextWithBasicMarkup(string $input) : string {
        switch ($this->targetFormat) {
            case Constants::FORMAT_HTML:
            case Constants::FORMAT_XHTML:
                if ($this->targetFormat == Constants::FORMAT_XHTML) {
                    $output = htmlentities($input, ENT_COMPAT | ENT_XHTML | ENT_SUBSTITUTE, 'UTF-8', false);
                } else {
                    $output = htmlentities($input, ENT_COMPAT | ENT_HTML5, 'UTF-8', false);
                }
                $output = preg_replace('/(https?:\/\/([^\s]+))/i', '<a href="$1">$2</a>', $output);
                $output = $this->nl2br($output);
                $output = str_replace("'", '&rsquo;', $output);
                $output = str_replace(' ...', '&nbsp;&hellip;', $output);
                $output = str_replace('...', '&hellip;', $output);
                $output = str_replace(' -- ', '&nbsp;&ndash; ', $output);
                $output = preg_replace('/&quot;(.+?)&quot;/i', '&bdquo;$1&ldquo;', $output);
                $output = preg_replace('/\_(.+?)\_/i', '<em>$1</em>', $output);
                $output = preg_replace('/\*(.+?)\*/i', '<strong>$1</strong>', $output);
                $output = preg_replace('/\(c\)/i', '&copy;', $output);
                $output = trim($output);
                break;

            default:
                throw new \InvalidArgumentException("Unsupported target format: $this->targetFormat");
        }

        return $output;
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    private function render404Content() {
        http_response_code(404);
        $this->setContentType('text/html');
        $this->replaceMeta('content-disposition', 'inline');
        $this->targetFormat = Constants::FORMAT_HTML;
        $this->setPageTemplate(self::PAGE_STANDARD);
        $this->setTitle($this->getSiteTitle() . ' - Seite nicht gefunden!');
        $this->setContent($this->renderUserTemplate('error-404.phtml'));
    }

    private function renderExceptionContent() {
        $this->setContentType('text/html');
        $this->replaceMeta('content-disposition', 'inline');
        $this->targetFormat = Constants::FORMAT_HTML;
        $this->setPageTemplate(self::PAGE_STANDARD);
        $this->setContent($this->renderUserTemplate('exception.phtml'));
    }

    private function renderAuthorizationExceptionContent() {
        $this->setContentType('text/html');
        $this->replaceMeta('content-disposition', 'inline');
        $this->targetFormat = Constants::FORMAT_HTML;
        $this->setPageTemplate(self::PAGE_STANDARD);
        $this->setContent($this->renderUserTemplate('exception-authorization.phtml'));
    }

    // </editor-fold>
    
    // <editor-fold desc="ControllerInterface">
    
    public function execute() {
        // Eigentlicher Inhalt
        try {
            $this->init();
            $this->run();
        } catch (AuthorizationException $ex) {
            $this->exception = $ex;
            $this->renderAuthorizationExceptionContent();
        } catch (ContentNotFoundException $ex) {
            $this->exception = $ex;
            $this->render404Content();
        } catch (\Exception $ex) {
            $this->exception = $ex;
            $this->renderExceptionContent();
        }
        
        // Wenn kein Modul einen Inhalt generiert hat, Fehler 404 anzeigen
        if (strlen($this->getContent()) == 0) {
            $this->render404Content();
        }

        // Äußeres Template rendern
        if ($this->pageTemplate !== self::PAGE_NONE) {
            $this->setContent(
                $this->renderUserTemplate($this->pageTemplate)
            );
        }
    }
    
    public function addContent(string $content, string $area = 'default'): string {
        return $this->frontController->addContent($content, $area);
    }

    public function addMeta(string $key, string $value) {
        $this->frontController->addMeta($key, $value);
    }

    public function getContent(string $area = 'default'): string {
        return $this->frontController->getContent($area);
    }

    public function getMeta(string $key): array {
        return $this->frontController->getMeta($key);
    }

    public function getMetaData(): array {
        return $this->frontController->getMetaData();
    }

    public function getParam(string $key, $default = null) {
        return $this->frontController->getParam($key, $default);
    }

    public function isParamExisting(string $key): bool {
        return $this->frontController->isParamExisting($key);
    }

    public function getParams(): array {
        return $this->frontController->getParams();
    }

    public function getVar(string $key, $default = null) {
        return $this->frontController->getVar($key, $default);
    }

    public function isVarExisting(string $key): bool {
        return $this->frontController->isVarExisting($key);
    }

    public function getVars(): array {
        return $this->frontController->getVars();
    }
    
    public function getTitle() : string {
        return $this->frontController->getTitle();
    }
    
    public function setTitle(string $title) {
        $this->frontController->setTitle($title);
    }

    public function init() {
        // Leere Default-Implementierung kann bei Bedarf überschrieben werden
    }

    public function replaceMeta(string $key, string $value) {
        $this->frontController->replaceMeta($key, $value);
    }

    public function setContent(string $content, string $area = 'default') {
        $this->frontController->setContent($content, $area);
    }

    public function setParam(string $key, $value) {
        $this->frontController->setParam($key, $value);
    }

    public function setVar(string $key, $value) {
        $this->frontController->setVar($key, $value);
    }

    // </editor-fold>
    
    
    // <editor-fold desc="LoggerInterface">
    
    public function alert($data, \Exception $ex = null, array $backtrace = null, string $line = '') {
        $this->log->alert($data, $ex, $backtrace, $line);
    }

    public function crit($data, \Exception $ex = null, array $backtrace = null, string $line = '') {
        $this->log->crit($data, $ex, $backtrace, $line);
    }

    public function debug($data, \Exception $ex = null, array $backtrace = null, string $line = '') {
        $this->log->debug($data, $ex, $backtrace, $line);
    }

    public function emerg($data, \Exception $ex = null, array $backtrace = null, string $line = '') {
        $this->log->emerg($data, $ex, $backtrace, $line);
    }

    public function err($data, \Exception $ex = null, array $backtrace = null, string $line = '') {
        $this->log->err($data, $ex, $backtrace, $line);
    }

    public function notice($data, \Exception $ex = null, array $backtrace = null, string $line = '') {
        $this->log->notice($data, $ex, $backtrace, $line);
    }

    public function warn($data, \Exception $ex = null, array $backtrace = null, string $line = '') {
        $this->log->warn($data, $ex, $backtrace, $line);
    }
    
    // </editor-fold>
}