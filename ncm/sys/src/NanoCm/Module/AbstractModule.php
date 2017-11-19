<?php

/* 
 * Copyright (C) 2017 André Gewert <agewert@ubergeek.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Ubergeek\NanoCm\Module;

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
 * @created 2017-11-11
 */
abstract class AbstractModule implements
    \Ubergeek\Controller\ControllerInterface,
    \Ubergeek\Log\LoggerInterface {

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
     * @var array
     */
    public $templateOptions = array();
    
    /**
     * Gibt den relativen Pfad zu den Templatedateien an
     * @var string
     */
    public $templateDir = 'tpl';

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
    }

    
    // <editor-fold desc="Grundlegende Ausgabe-Funktionalität">

    public function getAction() {
        return $this->getParam('action');
    }
    
    /**
     * Gibt die BaseURL für die NCM-Installation zurück
     * @return string
     */
    public function getBaseUrl() {
        return $this->ncm->relativeBaseUrl;
    }

    /**
     * Gibt den Standardtitel der Site zurück.
     * @return string Seitentitel
     */
    public function getSiteTitle() : string {
        return $this->orm->getSiteTitle();
    }

    /**
     * Setzt eine Template-Option auf den angegebenen Wert
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
     * @param string $string
     * @return HTML-kodierter String
     */
    public function htmlEncode($string) : string {
        //return $this->ncm->htmlEncode($string);
        return \Ubergeek\NanoCm\Util::htmlEncode($string);
    }
    
    /**
     * Bindet an Ort und Stelle ein Template ein
     * @param string $file Relativer Pfad zum betreffenden Template
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
        
        if ($this->allowUserTemplates) {
            $fname = $this->ncm->createPath(array(
                $this->ncm->pubdir,
                $this->templateDir,
                $file
            ));
        }
        
        if (!$this->allowUserTemplates || !file_exists($fname)) {
            $fname = $this->ncm->createPath(array(
                $this->ncm->sysdir,
                $this->templateDir,
                $file
            ));
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

    // </editor-fold>
    
    
    // <editor-fold desc="ControllerInterface">
    
    public function execute() {
        // Eigentlicher Inhalt
        try {
            $this->init();
            $this->run();
        } catch (\Ubergeek\NanoCm\Exception\AuthorizationException $ex) {
            $this->exception = $ex;
            $this->setContent($this->renderUserTemplate('exception-authorization.phtml'));
        } catch (\Exception $ex) {
            $this->exception = $ex;
            $this->setContent($this->renderUserTemplate('exception.phtml'));
        }
        
        // Wenn kein Modul einen Inhalt generiert hat, Fehler 404 anzeigen
        if (strlen($this->getContent()) == 0) {
            $this->setTitle($this->getSiteTitle() . ' - Seite nicht gefunden!');
            http_response_code(404);
            $this->setContent($this->renderUserTemplate('error-404.phtml'));
        }

        // Äußeres Template rendern
        if ($this->pageTemplate !== self::PAGE_NONE) {
            $this->setContent(
                $this->renderUserTemplate($this->pageTemplate)
            );
        }
    }
    
    public function addContent(string $content, string $area = 'default'): string {
        $this->frontController->addContent($content, $area);
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

    public function getParams(): array {
        return $this->frontController->getParams();
    }

    public function getVar(string $key, $default = null) {
        return $this->frontController->getVar($key, $default);
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