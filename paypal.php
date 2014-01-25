<?php

/**
 * @author     Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link       http://www.z-index.net
 * @copyright  (c) 2014 Branko Wilhelm
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

class plgQuickiconPayPal extends JPlugin
{

    private $url = 'https://api-3t.paypal.com/nvp';

    public function onGetIcons()
    {
        $balance = '';

        if ($this->params->get('balance') && $this->params->get('apiuser') && $this->params->get('apipw') && $this->params->get('apisig')) {
            $balance = $this->getBalance();
        }

        if (version_compare(JVERSION, '3', '>=')) {
            $image = 'paypal';
            JFactory::getDocument()->addStyleDeclaration('.icon-paypal:before{content: url("' . JUri::root() . 'media/paypal/paypal-icon.png");}');
        } else {
            $image = JUri::root() . 'media/paypal/paypal-logo.png';
        }

        return array(
            array(
                'link' => 'http://www.paypal.com',
                'image' => $image,
                'text' => JText::sprintf('PayPal%s', $balance),
                'id' => 'plg_quickicon_paypal'
            )
        );
    }

    private function getBalance()
    {
        $cache = JFactory::getCache('paypal', 'output');
        $cache->setCaching(1);
        $cache->setLifeTime($this->params->get('cache', 60));

        $key = md5($this->params->toString());

        if (!$result = $cache->get($key)) {
            try {
                $http = JHttpFactory::getHttp();

                $data = array(
                    'USER' => $this->params->get('apiuser'),
                    'PWD' => $this->params->get('apipw'),
                    'SIGNATURE' => $this->params->get('apisig'),
                    'VERSION' => '51.0',
                    'METHOD' => 'GetBalance'
                );

                $result = $http->post($this->url, $data);
            } catch (Exception $e) {
                return $e->getMessage();
            }

            $cache->store($result, $key);
        }

        if ($result->code != 200) {
            $msg = __CLASS__ . ' HTTP-Status ' . JHtml::_('link', 'http://wikipedia.org/wiki/List_of_HTTP_status_codes#' . $result->code, $result->code, array('target' => '_blank'));
            JFactory::getApplication()->enqueueMessage($msg, 'error');
            return;
        }

        parse_str($result->body, $result->body);

        if (!isset($result->body['ACK']) || $result->body['ACK'] != 'Success') {
            return $result->body['L_SHORTMESSAGE0'];
        }

        if (version_compare(JVERSION, '3', '>=')) {
            return ' <span class="label label-success">' . $result->body['L_AMT0'] . ' ' . $result->body['L_CURRENCYCODE0'] . '</span>';
        } else {
            return ' <small class="success">' . $result->body['L_AMT0'] . ' ' . $result->body['L_CURRENCYCODE0'] . '</small>';
        }
    }
}