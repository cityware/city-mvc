<?php

namespace Cityware\Mvc\Controller\Plugins;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\I18n\Translator\Translator;

class TranslatePlugin extends AbstractPlugin {

    /** @var  Translator $translator */
    private $globalConfig;

    /**
     * TranslatePlugin constructor.
     * @param Translator $translator
     */
    public function __construct() {
        //$this->globalConfig = \Zend\Config\Factory::fromFile(GLOBAL_CONFIG_PATH . 'global.php');
    }

    /**
     * @param $str
     * @param pt_BR $locale
     * @return string
     */
    public function __invoke($str, $locale = 'pt_BR', $typeTranslate = 'PhpArray', $pathFileTranslate = null) {
        $translator = new Translator();

        if (!empty($pathFileTranslate)) {
            $translator->addTranslationFile($typeTranslate, $pathFileTranslate, 'default', $locale);
        }
        return $translator->translate($str, $locale);
    }

}
