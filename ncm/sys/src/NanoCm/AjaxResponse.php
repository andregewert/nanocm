<?php
namespace Ubergeek\NanoCm;

/**
 * Simple PDO to keep response information for ajax requests
 *
 * @package Ubergeek\NanoCm
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2020-07-31
 */
class AjaxResponse {

    // <editor-fold desc="Constants">

    /**
     * Status code "ok"
     * @var integer
     */
    public const STATUS_OK = 0;

    /**
     * Status code "error"
     * @var integer
     */
    public const STATUS_ERROR = 500;

    // </editor-fold>


    // <editor-fold desc="Public properties">

    /**
     * Status code
     * 0 = OK
     * > 500 Error
     * @var integer
     */
    public $status;

    /**
     * Human readable message corresponding to the status code
     * @var string
     */
    public $message;

    /**
     * Additional info depending on the return status
     * @var string
     */
    public $info;

    // </editor-fold>


    // <editor-fold desc="Constructor">

    public function __construct($status = self::STATUS_OK, $message = '', $info = null) {
        $this->status = $status;
        $this->message = $message;
        $this->info = $info;
    }

    // </editor-fold>

}
