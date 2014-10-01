<?php
define('REETSEE_PATH', dirname(__FILE__));
function __reetsee_autoload($strClassName) {
    if (substr($strClassName, 0, strlen('Reetsee_')) === 'Reetsee_') {
        require_once REETSEE_PATH . '/' . str_replace('_', '/', $strClassName) . '.php';
    }
}
spl_autoload_register('__reetsee_autoload');
?>
