<?php

/**
 *
 * AppEmailComponent (for Japanese)
 *
 * Copyright 2011, nojimage (http://php-tips.com/)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @package     app
 * @subpackage  app.controller.components
 * @author    nojimage <nojimage at gmail.com>
 * @copyright 2011 nojimage
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link    　http://php-tips.com/
 *
 * Thank you:
 *   mon_sat https://gist.github.com/365550
 *   ZiSTA   http://blog.zista.jp/docs/id/0000000126
 */
App::import('Core', 'Multibyte');
App::import('Component', 'Email');

class AppEmailComponent extends EmailComponent {

    var $params;

    /**
     * Startup component
     *
     * @param object $controller Instantiating controller
     * @access public
     */
    function startup(&$controller) {

        $this->Controller = & $controller;

        mb_language('Japanese');

        // 設定読み出し
        Configure::load('email');
        $this->params = Configure::read('Email');
        $this->load($this->Controller->name);
    }

    /**
     * 設定ファイルから値読み込み
     *
     * @param $config
     * @return unknown_type
     */
    function load($config = 'default') {

        $keys = array('to', 'cc', 'bcc', 'from', 'replayTo', 'subject', 'lineLength', 'xMailer', 'delivery', 'sendAs', 'smtpOptions', 'layout', 'template', '_debug');

        foreach ($keys as $key) {
            if (!empty($this->params[$config][$key])) {
                $this->{$key} = $this->params[$config][$key];
            } else if (!empty($this->params['default'][$key])) {
                $this->{$key} = $this->params['default'][$key];
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see cake/libs/controller/components/EmailComponent#reset()
     * @param $config
     * @return unknown_type
     */
    function reset($config = '') {
        parent::reset();
        if (empty($config)) {
            $config = $this->Controller->name;
        }
        $this->load($config);
    }

    /**
     * Wrap the message using EmailComponent::$lineLength
     * 日本語対応
     *
     * @param string $message Message to wrap
     * @param integer $lineLength Max length of line
     * @return array Wrapped message
     * @access private
     */
    function _wrap($message, $lineLength = null) {
        $encoding = strtoupper(Configure::read('App.encoding'));
        $message = $this->_strip($message, true);
        $message = str_replace(array("\r\n", "\r"), "\n", $message);
        $lines = explode("\n", $message);
        $formatted = array();

        // 行頭禁則文字
        $wordwrap_not_head = '，）］｝、〕〉》」』】〙〗〟’”｠»'
                . 'ヽヾーァィゥェォッャュョヮヵヶぁぃぅぇぉっゃゅょゎゕゖㇰㇱㇲㇳㇴㇵㇶㇷㇸㇹㇺㇻㇼㇽㇾㇿ々〻'
                . '‐–〜？！‼⁇⁈⁉' . '・：；' . '。．';
        // 行末禁則文字
        $wordwrap_not_foot = '（［｛〔〈《「『【〘〖〝‘“｟«';

        if ($this->_lineLength !== null) {
            trigger_error(__('_lineLength cannot be accessed please use lineLength', true), E_USER_WARNING);
            $this->lineLength = $this->_lineLength;
        }

        if (!$lineLength) {
            $lineLength = $this->lineLength;
        }

        foreach ($lines as $line) {

            if (substr($line, 0, 1) === '.') {
                $line = '.' . $line;
            }

            // 正規表現を使用するためutf-8エンコード
            if ($encoding != 'UTF-8') {
                $line = mb_convert_encoding($line, 'UTF-8', $encoding);
            }

            if (!strlen($line) || preg_match('!(?:https?|ftp)://!', $line)) {
                // 空行またはhttpを含む行は折り返しを行わない
                $formatted[] = $line;
                continue;
            }

            if (preg_match('/\A[\x20-\x7E]+\z/u', $line)) {
                // 英字のみの行
                $parts = parent::_wrap($line, $lineLength);
                array_pop($parts);
                $formatted = array_merge($formatted, $parts);
                continue;
            }

            // $lineLengthで折り返す
            $chr = $tmp_line = '';
            $matches = $parts = array();

            while (mb_strwidth($tmp_line . $line, 'UTF-8') > $lineLength) {
                if (preg_match('/^[\x20-\x7E]+/iu', $line, $matches)) {
                    // 英字の処理
                    $line = mb_substr($line, mb_strlen($matches[0]), mb_strlen($line), 'UTF-8');
                    if (mb_strwidth($tmp_line . $matches[0], 'UTF-8') >= $lineLength) {
                        $parts[] = trim($tmp_line);
                        $tmp_line = '';
                    }
                    $tmp_line .= $matches[0];
                    if (mb_strwidth($tmp_line, 'UTF-8') >= $lineLength) {
                        $parts = array_merge($parts, explode("\n", wordwrap($tmp_line, $lineLength, "\n", true)));
                        $tmp_line = '';
                    }
                } else {
                    // 1字切り出し
                    $chr = mb_substr($line, 0, 1, 'UTF-8');
                    $line = mb_substr($line, 1, mb_strlen($line), 'UTF-8');
                    $tmp_line .= $chr;

                    if (mb_strwidth($tmp_line, 'UTF-8') >= $lineLength) {
                        $next_chr = mb_substr($line, 0, 1, 'UTF-8');
                        if ((strlen($chr) > 0 && mb_strpos($wordwrap_not_foot, $chr, 0, 'UTF-8') !== false)
                                || (strlen($next_chr) > 0 && mb_strpos($wordwrap_not_head, $next_chr, 0, 'UTF-8') !== false)) {
                            // 行末禁則文字に切り出し文字が該当する場合 or 行頭禁則文字に次の文字が該当する場合
                            continue;
                        }
                        $parts[] = trim($tmp_line);
                        $tmp_line = '';
                    }
                }
            }

            if (!empty($line)) {
                $parts[] = trim($tmp_line . $line);
            }
            $formatted = array_merge($formatted, $parts);
        }
        $formatted[] = '';

        // charset指定エンコードへ変換
        for ($i = 0; $i < count($formatted); $i++) {
            $formatted[$i] = mb_convert_encoding($formatted[$i], $this->charset, 'UTF-8');
        }

        return $formatted;
    }

    /**
     * Encode the specified string using the current charset
     *
     * @param string $subject String to encode
     * @return string Encoded string
     * @access private
     * @see http://blog.zista.jp/docs/id/0000000126
     */
    function _encode($subject) {
        $subject = $this->_strip($subject);

        $nl = "\r\n";
        if ($this->delivery == 'mail') {
            $nl = '';
        }
        $return = mb_encode_mimeheader($subject, $this->charset, 'B', $nl);
        return $return;
    }

}