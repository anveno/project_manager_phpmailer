<?php 
$domain = rex_request('domain', 'string', "");

if($domain) {
  $output = '';
  
  $query = 'SELECT * FROM `rex_project_manager_domain_phpmailer` AS H
              INNER JOIN `rex_project_manager_domain` as D
              ON D.domain = H.domain
              WHERE H.domain = ? 
              LIMIT 1';
  
  $result = rex_sql::factory()->setDebug(0)->getArray($query, [$domain]);
  
  if (count($result) > 0) {
    
    $item = $result[0];
    $raw = json_decode($item['raw'], true);
  
    if(is_array($raw)) {

        $output = '<table class="table table-striped"><thead><tr><th>'.$this->i18n('phpmailer_version').'</th><th>'.$this->i18n('status').'</th><th>'.$this->i18n('plugin_version').'</th></tr></thead><tbody>';
        $output .= '<tr>';
        $output .= '<td>'.(isset($raw['phpmailer_version']) ? $raw['phpmailer_version'] : '').'</td>';
        $output .= '<td>';
            if (isset($raw['status'])) {
                if ($raw['status'] == "1") {
                    $output .= '<span class="hidden">1</span><span class="rex-icon fa-check text-success"></span>';
                } else if ($raw['status'] == "0") {
                    $output .= '<span class="hidden">2</span><span class="rex-icon fa-question text-warning"></span>';
                } else if ($raw['status'] == "-1") {
                    $output .= '<span class="hidden">3</span><span class="rex-icon fa-exclamation-triangle text-danger"></span>';
                } else if ($raw['status'] == "2") {
                    $output .= '<span class="hidden">3</span><span class="rex-icon fa-arrow-right text-danger"></span>';
                }
            }
        $output .= '</td>';
        $output .= '<td>'.(isset($raw['plugin_version']) ? $raw['plugin_version'] : '').'</td>';
        $output .= '</tr>';
        $output .= '</tbody></table>';

        if (array_key_exists("phpmailer_config", $raw)) {
            $output .= '<table class="table table-striped"><thead><tr><th>'.$this->i18n('mailer').'</th><th>'.$this->i18n('detour_mode').'</th><th>'.$this->i18n('logging').'</th><th>'.$this->i18n('from').'</th><th>'.$this->i18n('host').'</th><th>'.$this->i18n('username').'</th></tr></thead><tbody>';
            $config = $raw['phpmailer_config'];
            $output .= '<tr>';
            $output .= '<td>';
            $output .= $config['mailer'];
            if ($config['mailer'] == "mail") {
                $output .= ' <span class="rex-icon fa-exclamation-triangle text-danger"></span>';
            }
            $output .= '</td>';

            $output .= '<td>';
            if (isset($config['detour_mode'])) {
                if ($config['detour_mode'] == "1") {
                    $output .= '<span>'.$this->i18n('yes').'</span> <span class="rex-icon fa-exclamation-triangle text-danger"></span>';
                } else if ($config['detour_mode'] == "0") {
                    $output .= '<span>'.$this->i18n('no').'</span> <span class="rex-icon fa-check text-success"></span>';
                }
            }
            $output .= '</td>';

            $output .= '<td>';
            if (isset($config['logging'])) {
                if ($config['logging'] == "1") {
                    $output .= '<span>'.$this->i18n('log_errors').'</span>';
                } else if ($config['logging'] == "0") {
                    $output .= '<span>'.$this->i18n('no').'</span> <span class="rex-icon fa-exclamation-triangle text-danger"></span>';
                } else if ($config['logging'] == "2") {
                    $output .= '<span>'.$this->i18n('log_all').'</span>';
                }
            }
            $output .= '</td>';

            $output .= '<td>'.$config["from"].'</td>';
            $output .= '<td>'.$config["host"].'</td>';
            $output .= '<td>'.$config["username"].'</td>';
            $output .= '</tr>';
            $output .= '</tbody></table>';
        }

        if (array_key_exists("maillog", $raw)) {
            $output .= '<table class="table table-striped"><thead><tr><th>'.$this->i18n('timestamp').'</th><th>'.$this->i18n('maillog_error').'</th><th>'.$this->i18n('maillog_from').'</th><th>'.$this->i18n('maillog_to').'</th><th>'.$this->i18n('maillog_subject').'</th></tr></thead><tbody>';
            $maillog = $raw['maillog'];
            foreach ($maillog as $entry) {
                $output .= '<tr>';
                $output .= '<td>'.$entry["timestamp"].'</td>';
                $output .= '<td><small>'.$entry["maillog_error"].'</small></td>';
                $output .= '<td>'.$entry["maillog_from"].'</td>';
                $output .= '<td>'.$entry["maillog_to"].'</td>';
                $output .= '<td>'.$entry["maillog_subject"].'</td>';
                $output .= '</tr>';
            }
            $output .= '</tbody></table>';
        }
    }
    
  } else {
    $output = "Keine PHPMailer Daten vorhanden!";
  }

    return $output;

}


