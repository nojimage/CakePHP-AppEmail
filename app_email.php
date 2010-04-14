<?php
/**
 *
 * AppEmailComponent (for Japanese)
 *
 * Copyright 2010, nojimage (http://php-tips.com/)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @package     app
 * @subpackage  app.controller.components
 * @author    nojimage <nojimage at gmail.com>
 * @copyright 2010 nojimage
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link    　http://php-tips.com/
 *
 */
App::import('Core', 'Multibyte');
App::import('Component', 'Email');
class AppEmailComponent extends EmailComponent {
    /**
     * Startup component
     *
     * @param object $controller Instantiating controller
     * @access public
     */
    function startup(&$controller) {

        $this->Controller =& $controller;

        mb_language('Japanese');

         
        // 設定読み出し
        $this->load($this->Controller->name);

    }

    /**
     * 設定ファイルから値読み込み
     *
     * @param $config
     * @return unknown_type
     */
    function load($config = 'default') {

        Configure::load('email');
        $params = Configure::read('Email');

        $keys = array('to', 'cc', 'bcc', 'from', 'replayTo', 'subject', 'lineLength', 'xMailer', 'delivery', 'sendAs', 'smtpOptions', 'layout', 'template', '_debug');
         
        foreach ($keys as $key) {
            if (!empty($params[$config][$key])) {
                $this->{$key} = $params[$config][$key];
            } else if (!empty($params['default'][$key])) {
                $this->{$key} = $params['default'][$key];
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see cake/libs/controller/components/EmailComponent#reset()
     */
    function reset() {
        parent::reset();
        $this->load($this->Controller->name);
    }

    /**
     * Wrap the message using EmailComponent::$lineLength
     * 日本語対応
     *
     * @param string $message Message to wrap
     * @return array Wrapped message
     * @access private
     */
    function _wrap($message) {
        $encoding = strtolower(Configure::read('App.encoding'));
        $message = $this->_strip($message, true);
        $message = str_replace(array("\r\n","\r"), "\n", $message);
        $lines = explode("\n", $message);
        $formatted = array();

        // 行頭禁則文字
        $wordwrap_not_head = '、。．）«\]}｝〕〉》」』】〙〗〟»ヽヾ';
        // 行末禁則文字
        $wordwrap_not_foot = '（\[{｛〔〈《「『【〘〖‘“«';

        if ($this->_lineLength !== null) {
            trigger_error(__('_lineLength cannot be accessed please use lineLength', true), E_USER_WARNING);
            $this->lineLength = $this->_lineLength;
        }

        foreach ($lines as $line) {

            if(substr($line, 0, 1) == '.') {
                $line = '.' . $line;
            }

            // 正規表現を使用するためutf-8エンコード
            if ($encoding != 'UTF-8') {
                $line = mb_convert_encoding($line, 'UTF-8', $encoding);
            }

            if (preg_match('!https?://|ftp://!', $line)) {
                // httpを含む行は折り返しを行わない
                $formatted[] = $line;
                continue;
            }

            // $this->lineLengthで折り返す
            $tmp_line = '';
            $parts = array();

            while (mb_strwidth($tmp_line . $line, 'UTF-8') > $this->lineLength ) {
                // 1字切り出し
                $chr  = mb_substr($line, 0, 1, 'UTF-8');
                $line = mb_substr($line, 1, mb_strlen($line), 'UTF-8');
                $tmp_line .= $chr;

                if (mb_strwidth($tmp_line, 'UTF-8') >= $this->lineLength) {
                    $next_chr = mb_substr($line, 0, 1, 'UTF-8');
                    if (mb_strpos($wordwrap_not_foot, $chr, 0, 'UTF-8') !== false
                    || (strlen($next_chr) > 0 && mb_strpos($wordwrap_not_head, $next_chr, 0, 'UTF-8') !== false)) {
                        // 行末禁則文字に切り出し文字が該当する場合 or 行頭禁則文字に次の文字が該当する場合
                        continue;
                    }
                    $parts[] = $tmp_line;
                    $tmp_line = '';
                }
            }

            $parts[] = $tmp_line . $line;

            $formatted = array_merge($formatted, $parts);

        }
        $formatted[] = '';

        // charset指定エンコードへ変換
        for ($i = 0; $i < count($formatted); $i++) {
            $formatted[$i] = mb_convert_encoding($formatted[$i], $this->charset, 'UTF-8');
        }

        return $formatted;
    }
}