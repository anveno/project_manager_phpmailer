<?php

// Aufruf: 
// /?rex-api-call=rex_api_project_manager_phpmailer&func=###&api_key=###

class rex_api_project_manager_phpmailer extends rex_api_function
{
    protected $published = true;

    public function execute()
    {
        rex_response::cleanOutputBuffers();
        $api_key = rex_request('api_key','string');

        $func = 'getData';
        if (!empty(rex_request('func', 'string'))) {
            if (strlen(rex_request('func', 'string')) < 50) {
                $func = trim(strip_tags(rex_request('func', 'string')));
            }
        }
        
        if($api_key == rex_config::get('project_manager/client', 'project_manager_api_key')) {

            if(rex_addon::get('phpmailer')->isAvailable()) {

                if ($func === 'getData') {

                    # REDAXO / SERVER / ALLGEMEIN

                    $params['status'] = 1;
                    $params['phpmailer_version'] = rex_addon::get('phpmailer')->getProperty('version');
                    $params['plugin_version'] = rex_plugin::get('project_manager', 'client_phpmailer')->getProperty('version');

                    # / REDAXO / SERVER / ALLGEMEIN

                    # MAILLOG

                    if (version_compare(rex::getVersion(), '5.9') >= 0) {
                        $log = new rex_log_file(rex_path::log('mail.log'));
                    } else {
                        $log = new rex_log_file(rex_path::coreData('mail.log'));
                    }

                    $i = 0;
                    foreach (new LimitIterator($log, 0, 30) as $entry) {
                        $data = $entry->getData();
                        if (isset($data[0]) && $data[0] === 'ERROR') {
                            $params['maillog'][$i]['timestamp'] = $entry->getTimestamp('%d.%m.%Y %H:%M:%S');
                            $params['maillog'][$i]['maillog_type'] = $data[0];
                            $params['maillog'][$i]['maillog_from'] = (isset($data[1]) ? $data[1] : '');
                            $params['maillog'][$i]['maillog_to'] = (isset($data[2]) ? $data[2] : '');
                            $params['maillog'][$i]['maillog_subject'] = (isset($data[3]) ? $data[3] : '');
                            $params['maillog'][$i]['maillog_error'] = (isset($data[4]) ? $data[4] : '');
                        }
                        $i++;
                    }

                    # / MAILLOG

                    # PHPMAILER CONFIG

                    $addon = rex_addon::get('phpmailer');
                    if ($addon->getConfig()) {
                        $phpmailer_config = $addon->getConfig();
                        unset($phpmailer_config['password']); // remove password
                        $params['phpmailer_config'] = $phpmailer_config;
                    }
                    # / PHPMAILER CONFIG

                }

                if ($func === 'testmail') {

                    # TESTMAIL

                    $addon = rex_addon::get('phpmailer');
                    $content = $mailerDebug = '';
                    $date = new DateTime();
                    if ('' == $addon->getConfig('from') || '' == $addon->getConfig('test_address')) {
                        $content .= rex_view::error($addon->i18n('checkmail_noadress'));
                    } else {
                        $mail = new rex_mailer();
                        $mail->addAddress($addon->getConfig('test_address'));
                        $mail->Subject = 'PHPMailer-Test | ' . rex_escape(rex::getServerName()) . ' | ' . date_format($date, 'Y-m-d H:i:s');

                        $devider = "\n--------------------------------------------------";
                        $securityMode = '';

                        if ('smtp' == $addon->getConfig('mailer')) {
                            $securityMode = $addon->getConfig('security_mode');

                            $host = "\nHost: " . rex_escape($addon->getConfig('host'));
                            $smtpinfo = $host . "\nPort: " . rex_escape($addon->getConfig('port'));
                            $smtpinfo .= $devider;

                            if (false == $securityMode) {
                                $securityMode = 'manual configured ' . $addon->getConfig('smtpsecure');
                                $securityMode = "\n" . $addon->i18n('security_mode') . "\n" . $securityMode . $devider . $smtpinfo;
                            } else {
                                $securityMode = 'Auto';
                                $securityMode = "\n" . $addon->i18n('security_mode') . ": \n" . $securityMode . $devider . $host . $devider;
                            }
                        }

                        $mail->Body = $addon->i18n('checkmail_greeting') . "\n\n" . $addon->i18n('checkmail_text') . ' ' . rex::getServerName();
                        $mail->Body .= "\n\nDomain: " . $_SERVER['HTTP_HOST'];

                        $mail->Body .= "\nMailer: " . $addon->getConfig('mailer') . $devider . $securityMode;
                        $mail->Body .= "\n" . $addon->i18n('checkmail_domain_note') . "\n" . $devider;
                        $mail->Debugoutput = static function ($str) use (&$mailerDebug) {
                            $mailerDebug .= date('Y-m-d H:i:s', time()) . ' ' . nl2br($str);
                        };

                        if (!$mail->send()) {
                            $alert = '<h2>' . $addon->i18n('checkmail_error_headline') . '</h2><hr>';
                            $alert .= $addon->i18n('checkmail_error') . ': ' . $mail->ErrorInfo;
                            $content .= rex_view::error($alert);
                        } else {
                            $success = '<h2>' . $addon->i18n('checkmail_send') . '</h2> ' . rex_escape($addon->getConfig('test_address')) . '<br>' . $addon->i18n('checkmail_info');
                            $success .= '<br><br><strong>' . $addon->i18n('checkmail_info_subject') . '</strong>';
                            $content .= rex_view::success($success);
                        }
                    }

                    $fragment = new rex_fragment();
                    $fragment->setVar('title', $addon->i18n('checkmail_headline'));
                    $fragment->setVar('body', $content.$mailerDebug, false);
                    $out = '<!doctype html><html lang="de"><head><meta charset="utf-8" /><title>'.$addon->i18n('checkmail_headline').'</title></head>';
                    $out .= '<body>'.$fragment->parse('core/page/section.php').'</body></html>';

                    header('Content-Type: text/html; charset=UTF-8');
                    exit($out);

                    # / TESTMAIL

                }

            }

        } else {
            $params['pm_version']    = rex_addon::get('project_manager')->getProperty('version');
            $params['cms']              = "REDAXO";
            $params['status']       = 0;
            $params['message'][]    = "Falscher API-Schlüssel.";
        }

        // TODO: EP, um weitere Parameter einzuhängen
        
        header('Content-Type: application/json; charset=UTF-8');  
        $response = json_encode($params, true);
        echo $response;
        exit();
    }
}

?>