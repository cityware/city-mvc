<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Cityware\Mvc\Controller;

use Zend\Mvc\Controller\AbstractActionController as ZendAbstractActionController;
use Zend\Session\Container as SessionContainer;
use Zend\Session\Config\SessionConfig;
use Zend\Session\SessionManager;
use Cityware\View\Model\ViewModel;


/**
 * Description of AbstractActionController
 *
 * @author fabricio.xavier
 */
abstract class AbstractActionController extends ZendAbstractActionController {

    private $headCssLink = Array(), $headCssStyle = Array(), $headJsScript = Array(),
            $headLink = Array(), $headJsLink = Array(), $metaName = Array(), $metaProperty = Array(),
            $metaHttpEquiv = Array(), $processHead = Array();
    private $viewModel = null, $sessionAdapter, $globalConfig;
    public $globalRoute, $module, $controller, $action;

    public function __construct() {

        /* Acesso o arquivo de configuração global */
        $this->globalConfig = \Zend\Config\Factory::fromFile(GLOBAL_CONFIG_PATH . 'global.php');

        //$this->getViewModel();

        $this->globalRoute = $_SESSION['globalRoute'];
        
        $this->module = $this->globalRoute->moduleName;
        $this->controller = $this->globalRoute->controllerName;
        $this->action = $this->globalRoute->actionName;
        
        $this->globalRoute->moduleView = MODULES_PATH . ucfirst($this->module) . DS . 'view' . DS . strtolower($this->module) . DS;
        $this->globalRoute->moduleIni = MODULES_PATH . ucfirst($this->module) . DS . 'src' . DS . ucfirst($this->module) . DS . 'ini' . DS;
        $this->globalRoute->moduleTranslate = MODULES_PATH . ucfirst($this->module) . DS . 'src' . DS . ucfirst($this->module) . DS . 'translate' . DS;
        $this->globalRoute->moduleController = MODULES_PATH . ucfirst($this->module) . DS . 'src' . DS . ucfirst($this->module) . DS . 'Controller' . DS;


        //$this->image = $this->globalConfig['image'];

        $this->assign('linkDefault', LINK_DEFAULT);
        $this->assign('linkModule', LINK_DEFAULT . $this->module . '/');
        $this->assign('linkController', LINK_DEFAULT . $this->module . '/' . strtolower($this->controller));
        $this->assign('linkAction', LINK_DEFAULT . $this->module . '/' . strtolower($this->controller) . '/' . strtolower($this->action));
        $this->assign('urlDefault', URL_DEFAULT);
        $this->assign('urlUpload', URL_UPLOAD);
        $this->assign('urlStatic', URL_STATIC);
        $this->assign('publicPath', PUBLIC_PATH);
        $this->assign('baseModule', $this->module);
        $this->assign('baseController', $this->controller);
        $this->assign('baseAction', $this->action);
        $this->assign('langDefault', $this->globalRoute->language);
    }

    public function getRsTableModel($tableName, $tableSchema) {
        $connection = \Cityware\Db\Factory::factory();
        $classTable = "\Orm\\{$tableSchema}\Tables\\{$tableName}Table";
        return new $classTable($connection->getAdapter());
    }
    
    public function newUid($idUsuario, $prefix = 'MKM') {
        $sCod = str_pad($idUsuario, 6, '0', STR_PAD_LEFT);
        $sMTime = str_replace(",", '', str_replace(".", '', microtime(true)));
        $guidText = $prefix . '-' . $sCod . '-' . str_pad(substr($sMTime, 8, 6), 6, '0');
        return $guidText;
    }

    public function notFoundAction() {
        $this->layout('layout/error');
        parent::notFoundAction();
    }

    public function getImageGlobalConfig() {
        return $this->globalConfig['image'];
    }

    public function getGlobalConfig() {
        return $this->globalConfig;
    }

    /**
     * Função de redirecionamento por url
     * @param type $url
     */
    public function urlRedirect($url) {
        header("Location: $url");
        exit;
    }

    /**
     * Função que retorna a instância de conexão ao banco de dados
     * @return object
     */
    public function getConnection() {
        return \Cityware\Db\Factory::factory();
    }

    /**
     * Retorna o ViewModel
     * @param array $variables
     * @param string $template
     * @return object
     */
    public function getViewModel(array $variables = Array()) {
        if ($this->viewModel === null) {
            $viewModel = new ViewModel($variables);
            $this->viewModel = $viewModel;
        }
        $this->viewModel->setOption('lfi_protection', true);
        $_SESSION['processHead'] = $this->processHead;
        return $this->viewModel;
    }

    /**
     * Renderiza o template e retorna o html processado
     * @param string $templateName
     * @param array $variables
     * @return string
     */
    public function render($templateName, array $variables = null) {
        $viewModel = $this->getViewModel();
        $viewModel->setTemplate($templateName); // caminho para o template que será renderizado

        if ($variables != null and is_array($variables)) {
            foreach ($variables as $key => $value) {
                $viewModel->setVariable($key, $value);
            }
        }
        $renderer = $this->getServiceLocator()->get('ViewRenderer');
        return $renderer->render($viewModel);
    }

    /**
     * Função para desabilitar a renderização do layout e da view
     * @return object
     */
    public function noRender() {
        $response = $this->getResponse();
        $response->setContent(null);
        return $response;
    }

    /**
     * Função para desabilitar a renderização do layout e da view retornando JSON para utilização em AJAX
     * @param array $variables
     * @return type
     */
    public function ajaxRender(array $variables = array()) {
        $response = $this->getResponse();
        $response->setContent(\Zend\Json\Json::encode($variables));

        return $response;
    }
    
    /**
     * Função para desabilitar a renderização do layout e da view retornando JSONP para utilização em AJAX
     * @param array $variables
     * @param string $jsonpCallback
     * @return string
     */
    public function ajaxRenderJSONP(array $variables = array(), $jsonpCallback = 'callback') {
        $jsonData = \Zend\Json\Json::encode($variables);
        $response = $this->getResponse();
        $response->setContent($_GET[$jsonpCallback].'('.$jsonData.');');
        return $response;
    }

    /**
     * Define as veriáveis que serão apresentadas no layout renderizado
     * @param  string|array                       $spec
     * @param  mixed                              $value
     * @return \Cityware\Controller\AbstractActionController
     * @throws \Exception
     */
    public function assign($spec, $value = null) {
        // which strategy to use?
        if (is_string($spec)) {
            // assign by name and value
            if ('_' == substr($spec, 0, 1)) {
                throw new \Exception('Setting private or protected class members is not allowed', 500);
            }
            $this->getViewModel()->setVariable($spec, $value);
        } elseif (is_array($spec)) {
            // assign from associative array
            $error = false;
            foreach ($spec as $key => $val) {
                if ('_' == substr($key, 0, 1)) {
                    $error = true;
                    break;
                }
                $this->getViewModel()->setVariable($key, $val);
            }
            if ($error) {
                throw new \Exception('Setting private or protected class members is not allowed', 500);
            }
        } else {
            throw new \Exception('assign() expects a string or array, received ' . gettype($spec), 500);
        }

        return $this;
    }

    /**
     * Retorna o adaptador de sessao
     * @param string $name
     * @return SessionContainer
     */
    public function getSessionAdapter($name = 'Default') {

        if (!isset($_SESSION[$name])) {

            $sessionConfig = new SessionConfig();
            $sessionConfig->setOptions($this->globalConfig['session']);

            $sessionStorage = new \Zend\Session\Storage\SessionArrayStorage();

            $sessionManager = new SessionManager();
            $sessionManager->rememberMe($this->globalConfig['session']['remember_me_seconds']);
            $sessionManager->forgetMe();
            $sessionManager->setConfig($sessionConfig);
            $sessionManager->setStorage($sessionStorage);
            $sessionNamespace = new SessionContainer($name, $sessionManager);
            $sessionNamespace->setExpirationSeconds(3600);
            if (!isset($sessionNamespace->init)) {

                $request = new \Zend\Http\PhpEnvironment\Request();

                $sessionNamespace->init = 1;
                $sessionNamespace->remoteAddr = $request->getServer('REMOTE_ADDR');
                $sessionNamespace->httpUserAgent = $request->getServer('HTTP_USER_AGENT');
                /*
                  $chain = $sessionManager->getValidatorChain();
                  $validatorUserAgent = new \Zend\Session\Validator\HttpUserAgent($sessionNamespace->httpUserAgent);
                  $chain->attach('session.validate', array($validatorUserAgent, 'isValid'));
                  $validatorAddr = new \Zend\Session\Validator\RemoteAddr($sessionNamespace->remoteAddr);
                  $chain->attach('session.validate', array($validatorAddr, 'isValid'));

                  $sessionManager->setValidatorChain($chain);
                 * 
                 */
            }
            $sessionNamespace->setDefaultManager($sessionManager);
        } else {
            $sessionNamespace = new SessionContainer($name);
            $sessionNamespace->setExpirationSeconds(3600);
        }
        $this->sessionAdapter = $sessionNamespace;

        return $sessionNamespace;
    }

    /**
     * Define conteudo de um indice da sessao
     * @param string $index
     * @param string $content
     * @return \Cityware\Controller\AbstractActionController
     */
    public function setSessionIndex($index, $content) {
        $this->sessionAdapter->$index = $content;

        return $this;
    }

    /**
     * Retorna conteudo de um indice da sessao
     * @param  string $index
     * @return object
     */
    public function getSessionIndex($index) {
        return $this->sessionAdapter->$index;
    }

    /**
     * Remove um indice da sessao
     * @param string $index
     * @return \Cityware\Controller\AbstractActionController
     */
    public function removeSessionIndex($index) {
        $this->sessionAdapter->offsetUnset($index);

        return $this;
    }

    /**
     * Retorna um array da sessao
     * @return array
     */
    public function getSessionArray() {
        return $this->sessionAdapter->getIterator()->getArrayCopy();
    }

    /**
     * Apaga conteudo de toda sessao
     */
    public function sessionDestroy() {
        $this->sessionAdapter->getManager()->destroy();
    }

    /**
     * Define o Titulo da página
     * @param  string                                            $title
     * @param  string                                            $type      (SET | APPEND | PREPEND)
     * @param  string                                            $separator
     * @return \Cityware\Controller\AbstractActionController
     */
    public function setHeadTitle($title, $type = 'SET', $separator = ' / ') {
        $this->processHead['headTitle'] = Array('title' => $title, 'type' => $type, 'separator' => $separator);
        return $this;
    }

    /**
     * Define doctype da página
     * @param  string                                            $doctype
     * @return \Cityware\Controller\AbstractActionController
     */
    public function setDoctype($doctype = \Zend\View\Helper\Doctype::XHTML5) {
        $this->processHead['doctype'] = $doctype;
        return $this;
    }

    /**
     * Retorna o doctype da página
     * @return string
     */
    public function getDoctype() {
        if (empty($this->processHead['doctype'])) {
            $this->setDoctype();
        }

        return $this->processHead['doctype'];
    }

    /**
     * Define Content Type da página
     * @param  string                                            $contentType
     * @return \Cityware\Controller\AbstractActionController
     */
    public function setContentType($contentType = "utf-8") {
        $this->processHead['contentType'] = $contentType;
        return $this;
    }

    /**
     * Define Content Language da página
     * @param  type                                              $contentLang
     * @return \Cityware\Controller\AbstractActionController
     */
    public function setContentLanguage($contentLang = "pt-br") {
        $this->processHead['contentLang'] = $contentLang;
        return $this;
    }

    /**
     * Define o Favicon da página
     * @param  string                                            $url
     * @return \Cityware\Controller\AbstractActionController
     */
    public function setFavicon($url) {
        $this->processHead['favicon'] = $url;
        return $this;
    }

    /**
     * Desabilita o cache da página por meio de Metatag
     * @return \Cityware\Controller\AbstractActionController
     */
    public function disableCache() {
        $this->setMetaHttpEquiv('cache', 'NO-CACHE');
        $this->setMetaHttpEquiv('pragma', 'NO-CACHE');

        return $this;
    }

    /**
     * Define os keywords da página
     * @param  string                                            $keywords
     * @return \Cityware\Controller\AbstractActionController
     */
    public function setKeywords($keywords) {
        $this->setMetaName('keywords', $keywords);

        return $this;
    }

    /**
     * Define a Description da página
     * @param  string                                            $description
     * @return \Cityware\Controller\AbstractActionController
     */
    public function setDescription($description) {
        $this->setMetaName('description', $description);
        return $this;
    }

    /**
     * Define Meta Tags do tipo Name
     * @param  string                                            $keyValue
     * @param  string                                            $content
     * @return \Cityware\Controller\AbstractActionController
     */
    public function setMetaName($keyValue, $content) {
        array_push($this->metaName, Array('key' => $keyValue, 'content' => $content));
        $this->processHead['metaName'] = $this->metaName;
        return $this;
    }

    /**
     * Define Meta Tags do tipo Property
     * @param  string                                            $keyValue
     * @param  string                                            $content
     * @return \Cityware\Controller\AbstractActionController
     */
    public function setMetaProperty($keyValue, $content) {
        array_push($this->metaProperty, Array('key' => $keyValue, 'content' => $content));
        $this->processHead['metaProperty'] = $this->metaProperty;
        return $this;
    }

    /**
     * Define Meta Tags do tipo HttpEquiv
     * @param  string                                            $keyValue
     * @param  string                                            $content
     * @return \Cityware\Controller\AbstractActionController
     */
    public function setMetaHttpEquiv($keyValue, $content) {
        array_push($this->metaHttpEquiv, Array('key' => $keyValue, 'content' => $content));
        $this->processHead['metaHttpEquiv'] = $this->metaHttpEquiv;
        return $this;
    }

    /**
     * Define Link de Estilo CSS da página
     * @param  string                                            $url
     * @param  string                                            $media
     * @param  array                                             $conditionalStylesheet
     * @return \Cityware\Controller\AbstractActionController
     */
    public function setHeadCssLink($url, $media = 'all', array $conditionalStylesheet = array()) {
        array_push($this->headCssLink, Array('url' => $url, 'media' => $media, 'conditional' => $conditionalStylesheet, 'typeFile' => 'css'));
        $this->processHead['headCssLink'] = $this->headCssLink;
        return $this;
    }

    /**
     * Define Estilo CSS na página
     * @param  string                                            $css
     * @param  array                                             $conditional
     * @return \Cityware\Controller\AbstractActionController
     */
    public function setHeadCssStyle($css, array $conditional = array()) {
        array_push($this->headCssStyle, Array('css' => $css, 'conditional' => $conditional));
        $this->processHead['headCssStyle'] = $this->headCssStyle;
        return $this;
    }

    /**
     * Define Scripts JS na página
     * @param  string                                            $script
     * @param  string                                            $type
     * @param  array                                             $conditional
     * @return \Cityware\Controller\AbstractActionController
     */
    public function setHeadJsScript($script, array $conditional = array()) {
        array_push($this->headJsScript, Array('script' => $script, 'type' => 'text/javascript', 'conditional' => $conditional));
        $this->processHead['headJsScript'] = $this->headJsScript;
        return $this;
    }

    /**
     * Define Link de Script JS da página
     * @param  string                                            $url
     * @param  string                                            $type
     * @param  array                                             $attrs
     * @return \Cityware\Controller\AbstractActionController
     */
    public function setHeadJsLink($url, array $attrs = array()) {
        array_push($this->headJsLink, Array('url' => $url, 'type' => 'text/javascript', 'attrs' => $attrs));
        $this->processHead['headJsLink'] = $this->headJsLink;
        return $this;
    }

    /**
     * Define Link de Script JS da página
     * @param  string                                            $url
     * @param  string                                            $type
     * @param  array                                             $attrs
     * @return \Cityware\Controller\AbstractActionController
     */
    public function setHeadLink($url, $rel, $type = null, $media = null, $sizes = null) {
        array_push($this->headLink, Array('href' => $url, 'rel' => $rel, 'type' => $type, 'media' => $media, 'sizes' => $sizes));
        $this->processHead['headLink'] = $this->headLink;
        return $this;
    }
}
