<?php

// Aufruf: 
// /?rex-api-call=rex_api_project_manager_phpmailer&api_key=###

class rex_api_project_manager_phpmailer extends rex_api_function
{
    protected $published = true;

    public function execute()
    {
        rex_response::cleanOutputBuffers();
        $api_key = rex_request('api_key','string');
        $func = rex_request('func','string');
        
        if($api_key == rex_config::get('project_manager/client', 'project_manager_api_key')) {

            if(rex_addon::get('phpmailer')->isAvailable()) {
                
              # REDAXO / SERVER / ALLGEMEIN
  
              $params['status']           = 1;
              $params['phpmailer_version']    		= rex_addon::get('phpmailer')->getProperty('version');

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