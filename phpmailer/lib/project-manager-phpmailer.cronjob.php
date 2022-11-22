<?php

class rex_cronjob_project_manager_phpmailer extends rex_cronjob
{

    public function execute()
    {
        $websites = rex_sql::factory()->setDebug(0)->getArray('SELECT * FROM ' . rex::getTable('project_manager_domain') . ' ORDER BY updatedate asc');

        /* Addon-Abruf */
        $error = false;
        $multi_curl = curl_multi_init();
        $resps = array();
        $options = array(
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_HEADER         => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0',
            CURLOPT_TIMEOUT => 3 // seconds
        );
        foreach($websites as $website) {

            $domain = $website['domain'];
            $cms = $website['cms'];
            $ssl = $website['is_ssl'];
            $param = $website['param'];
            $param = explode(',', $param);
            $param = '&'.implode('&', $param);
            $protocol = ($ssl == 1) ? "https://" : "http://";

            $timestamp = time();

            $url = $protocol.urlencode($domain)."/index.php?rex-api-call=project_manager_phpmailer&api_key=".$website['api_key'].'&t='.$timestamp.$param;

            if ($cms == 5)
                $url = $protocol.urlencode($domain)."/index.php?rex-api-call=project_manager_phpmailer&api_key=".$website['api_key'].'&t='.$timestamp.$param;

            if ($cms == 4)
                continue;
                //$url = $protocol.urlencode($domain)."/project_manager_client.php?rex-api-call=project_manager&api_key=".$website['api_key'].'&t='.$timestamp.$param;

            $resps[$domain] = curl_init($url);
            curl_setopt_array($resps[$domain], $options);
            curl_multi_add_handle($multi_curl, $resps[$domain]);
        }

        $active = null;
        do {
            curl_multi_exec($multi_curl, $active);
        } while ($active > 0);

        foreach ($resps as $domain => $response) {

            $resp = curl_multi_getcontent($response);
            curl_multi_remove_handle($multi_curl, $response);

            $json = json_decode($resp, true);

            if(json_last_error() === JSON_ERROR_NONE && $json !== null) {
                rex_sql::factory()->setDebug(0)->setQuery('INSERT INTO ' . rex::getTable('project_manager_domain_phpmailer') . ' (`domain`, `raw`, `createdate`, `status`) VALUES(:domain, :response, NOW(), 1) 
                    ON DUPLICATE KEY UPDATE domain = :domain, `raw` = :response, createdate = NOW(), `status` = 1', [":domain" => $domain, ":response" => $resp] );
            } else {

                $resp = '{"status":0}';

                rex_sql::factory()->setDebug(0)->setQuery('INSERT INTO ' . rex::getTable('project_manager_domain_phpmailer') . ' (`domain`, `raw`, `createdate`, `status`) VALUES(:domain, :response, NOW(), -1)
                    ON DUPLICATE KEY UPDATE domain = :domain, `raw` = :response, createdate = NOW(), `status` = -1', [":domain" => $domain, ":response" => $resp] );
                $error = true;
            }

        }
        curl_multi_close($multi_curl);

        if ($error === true) {
            return false;
        } else {
            return true;
        }
    }
    
    public function getTypeName()
    {
        return rex_i18n::msg('project_manager_cronjob_phpmailer_name');
    }

    public function getParamFields()
    {
        return [];
    }
}
?>