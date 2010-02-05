<?php
/**
 *
 * Email Config Example
 *
 * Copyright 2010, nojimage (http://php-tips.com/)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @package    app
 * @subpackage app.config
 * @author     nojimage <nojimage at gmail.com>
 * @copyright  2010 nojimage
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://php-tips.com/
 * 
 */
$config['Email'] = array(
    'default' => array(
        'delivery'   => 'mail', // 送信方法 (mail or smtp)
        /* // smtpを利用する場合
        'smtpOptions' => array(
            'host' => '',
            'port' => 587,
            'username' => '',
            'password' => ''),
        */
        'sendAs'     => 'text', // メールタイプ (text or html or both)
        'from'       => 'foo@example.com', // メールの送信元(From:)
        'subject'    => 'メール送信',       // メールの表題(Subject:)
        'lineLength' => 72, // 本文の折り返し文字数(全角は2文字としてカウント)
        'xMailer'    => 'CakePHP AppEmail' /* X-Mailerヘッダ */),
    
    /* コントローラ別の設定 */
    'Users' => array(
        'subject' => 'ユーザ登録完了のお知らせ',
        'template' => 'user_registed')
);