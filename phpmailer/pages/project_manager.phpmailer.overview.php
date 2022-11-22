<?php

$showlist = true;
$data_id = rex_request('data_id', 'int', 0);
$func = rex_request('func', 'string');
$csrf_token = (rex_csrf_token::factory('cronjob'))->getValue();
$csrf = rex_csrf_token::factory('project_manager');

###############
###
### LISTVIEW
###
###############
if ($showlist) {

  $sql = 'SELECT * FROM (
              SELECT * FROM  '. rex::getTable('project_manager_domain') . ' as X  ORDER BY name ASC 
          ) AS D
          LEFT JOIN (
                        SELECT  status as status_phpmailer, createdate as createdate_phpmailer, domain as phpmailerdomain, raw
                        FROM ' . rex::getTable('project_manager_domain_phpmailer') . '
              ) as H
          ON D.domain = H.phpmailerdomain
          GROUP by D.domain
          ORDER BY name ASC';
  
  $items = rex_sql::factory()->getArray($sql);
  
  // Cronjobcall
  $sql2 = 'SELECT * FROM  '. rex::getTable('cronjob').'
          WHERE type = "rex_cronjob_project_manager_phpmailer"';
  $cronjob = rex_sql::factory()->getArray($sql2);
  $cronjobId = $cronjob[0]['id'];
  
  $refresh = '';
  if ($cronjobId != NULL) {
    $refresh = '<a href="/redaxo/index.php?page=project_manager/phpmailer/overview#" data-cronjob="/redaxo/index.php?page=cronjob/cronjobs&func=execute&oid='.$cronjobId.'&_csrf_token='.$csrf_token.'" target="_blank" class="pull-right callCronjob"><i class="fa fa-refresh"></i> PHPMailer Daten aktualisieren</a>';
  }
  echo rex_view::info("Anzahl der Domains und Projekte: ".count($items) . $refresh);
  
  $list = rex_list::factory($sql, 1000);
  $list->addTableAttribute('class', 'table-striped');
  $list->setNoRowsMessage($this->i18n('project_manager_phpmailer_domain_norows_message'));

  
  $list->setColumnFormat('id', 'Id');
  $list->addParam('page', 'project_manager/server');
  
  $list->setColumnParams('id', ['data_id' => '###id###', 'func' => 'edit']);
  $list->setColumnSortable('id');
  
  $list->removeColumn('id');
  $list->removeColumn('description');
  $list->removeColumn('api_key');
  $list->removeColumn('tags');
  $list->removeColumn('is_ssl');
  $list->removeColumn('cms');
  $list->removeColumn('cms_version');
  $list->removeColumn('createdate');
  $list->removeColumn('createdate_phpmailer');
  $list->removeColumn('rex_version');  
  $list->removeColumn('status');
  $list->removeColumn('http_code');
  $list->removeColumn('raw');
  $list->removeColumn('domain');
  $list->removeColumn('phpmailerdomain');
  $list->removeColumn('updatedate');
  $list->removeColumn('logdate');
  $list->removeColumn('maintenance');
  $list->removeColumn('param');
  
  $list->setColumnLabel('name', $this->i18n('project_manager_phpmailer_name'));
  $list->setColumnParams('name', ['page' => 'project_manager/server/projects', 'func' => 'updateinfos', 'domain' => '###domain###']);
  
  $list->setColumnLabel('createdate_phpmailer', $this->i18n('project_manager_phpmailer_updatedate'));
  $list->setColumnFormat('createdate_phpmailer', 'custom', function ($params) {
    return (rex_formatter::format($params['list']->getValue('createdate_phpmailer'),'date','d.m.Y H:i:s'));
  });
  
  // icon column (Domain hinzufügen bzw. bearbeiten)
  $thIcon = '<a href="'.$list->getUrl(['func' => 'domain_add']).'"><i class="rex-icon rex-icon-add-category"></i></a>';
  $tdIcon = '<i class="rex-icon rex-icon-structure-root-level"></i>';
  $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
  $list->setColumnParams($thIcon, ['func' => 'domain_edit', 'id' => '###id###']);
  $list->setColumnFormat($thIcon, 'custom', function ($params) {
      $filename = '';
      if (file_exists(rex_plugin::get('project_manager', 'server')->getAssetsPath('favicon/'.$params['list']->getValue('domain').'.png'))) {
        $filename = rex_plugin::get('project_manager', 'server')->getAssetsUrl('favicon/'.$params['list']->getValue('domain').'.png');
        return '<a href="http://'.$params['list']->getValue('domain').'/" target="_blank"><img src="'.$filename.'" width="16" /></a>';
      } else {
        return '<a href="http://'.$params['list']->getValue('domain').'/" target="_blank"><i class="fa fa-sitemap"></i></a>';
      }
  });
    
  $list->addColumn($this->i18n('project_manager_phpmailer_domain'), '###domain###', 3);
  //$list->setColumnParams($this->i18n('project_manager_phpmailer_domain'), ['page' => 'project_manager/server/projects', 'func' => 'updateinfos', 'domain' => '###domain###']);
  $list->setColumnFormat($this->i18n('project_manager_phpmailer_domain'), 'custom', function ($params) {
    return '<a href="http://'.$params['list']->getValue('domain').'/" target="_blank">'.$params['list']->getValue('domain').'</a>';
  });

  $list->setColumnLabel('status_phpmailer', $this->i18n('status'));
  $list->setColumnFormat('status_phpmailer', 'custom', function ($params) {
    if ($params['list']->getValue('status_phpmailer') == "1") {
      return '<span class="hidden">1</span><span class="rex-icon fa-check text-success"></span>';
    } else if ($params['list']->getValue('status_phpmailer') == "0") {
      return '<span class="hidden">2</span><span class="rex-icon fa-question text-warning"></span>';
    } else if ($params['list']->getValue('status_phpmailer') == "-1") {
      return '<span class="hidden">3</span><span class="rex-icon fa-exclamation-triangle text-danger"></span>';
    } else if ($params['list']->getValue('status_phpmailer') == "2") {
      return '<span class="hidden">3</span><span class="rex-icon fa-arrow-right text-danger"></span>';
    }
  });
  $list->setColumnLayout('status', ['<th data-sorter="digit">###VALUE###</th>', '<td>###VALUE###</td>']);

  $list->addColumn($this->i18n('phpmailer_version'), false, -1, ['<th>###VALUE###</th>', '<td class="rex-table-phpmailer_version">###VALUE### <i class="tablesorter-icon"></i></td>']);
  $list->setColumnLabel($this->i18n('phpmailer_version'), $this->i18n('phpmailer_version'));
  $list->setColumnFormat($this->i18n('phpmailer_version'), 'custom', function ($params) {
        if($params['list']->getValue('raw')) {
            $raw= json_decode($params['list']->getValue('raw'), true);
            if (array_key_exists("phpmailer_version", $raw)) {
                return $raw['phpmailer_version'];
            }
        }
  });

  $list->addColumn($this->i18n('mailer'), false, -1, ['<th>###VALUE###</th>', '<td class="rex-table-mailer">###VALUE###</td>']);
  $list->setColumnLabel($this->i18n('mailer'), $this->i18n('mailer'));
  $list->setColumnFormat($this->i18n('mailer'), 'custom', function ($params) {
    if($params['list']->getValue('raw')) {
        $raw= json_decode($params['list']->getValue('raw'), true);
        if (array_key_exists("phpmailer_config", $raw)) {
            $config = $raw['phpmailer_config'];

            if (isset($config['mailer'])) {
                $return = $config['mailer'];
                if ($config['mailer'] == "mail") {
                    $return .= ' <span class="rex-icon fa-exclamation-triangle text-danger"></span>';
                }
                return $return;
            }
            else {
                return '-';
            }

        } else {
            return '-';
        }
    }
  });

  $list->addColumn($this->i18n('detour_mode'), false, -1, ['<th>###VALUE###</th>', '<td class="rex-table-detour_mode">###VALUE###</td>']);
  $list->setColumnLabel($this->i18n('detour_mode'), $this->i18n('detour_mode'));
  $list->setColumnFormat($this->i18n('detour_mode'), 'custom', function ($params) {
    if($params['list']->getValue('raw')) {
        $raw= json_decode($params['list']->getValue('raw'), true);
        if (array_key_exists("phpmailer_config", $raw)) {
            $config = $raw['phpmailer_config'];

            if (isset($config['detour_mode'])) {
                if ($config['detour_mode'] == "1") {
                    $return = '<span>'.$this->i18n('yes').'</span> <span class="rex-icon fa-exclamation-triangle text-danger"></span>';
                } else if ($config['detour_mode'] == "0") {
                    $return = '<span>'.$this->i18n('no').'</span> <span class="rex-icon fa-check text-success"></span>';
                }
                return $return;
            }
            else {
                return '-';
            }

        } else {
            return '-';
        }
    }
  });

  $list->addColumn($this->i18n('logging'), false, -1, ['<th>###VALUE###</th>', '<td class="rex-table-logging">###VALUE###</td>']);
  $list->setColumnLabel($this->i18n('logging'), $this->i18n('logging'));
  $list->setColumnFormat($this->i18n('logging'), 'custom', function ($params) {
      if($params['list']->getValue('raw')) {
          $raw= json_decode($params['list']->getValue('raw'), true);
          if (array_key_exists("phpmailer_config", $raw)) {
              $config = $raw['phpmailer_config'];

              if (isset($config['logging'])) {
                  if ($config['logging'] == "1") {
                      $return = '<span>'.$this->i18n('log_errors').'</span>';
                  } else if ($config['logging'] == "0") {
                      $return = '<span>'.$this->i18n('no').'</span> <span class="rex-icon fa-exclamation-triangle text-danger"></span>';
                  } else if ($config['logging'] == "2") {
                      $return = '<span>'.$this->i18n('log_all').'</span>';
                  }
                  return $return;
              }
              else {
                  return '-';
              }

          } else {
              return '-';
          }
      }
  });

  $list->addColumn($this->i18n('maillog'), false, -1, ['<th>###VALUE###</th>', '<td class="rex-table-maillog">###VALUE###</td>']);
  $list->setColumnLabel($this->i18n('maillog'), $this->i18n('maillog'));
  $list->setColumnFormat($this->i18n('maillog'), 'custom', function ($params) {
      if($params['list']->getValue('raw')) {
        $raw= json_decode($params['list']->getValue('raw'), true);
        if (array_key_exists("maillog", $raw)) {
            $maillog = $raw['maillog'];
            return count($maillog).' '.$this->i18n('maillog_error').' <span class="rex-icon fa-exclamation-triangle text-danger"></span>';
        } else {
            return '-';
        }
      }
  });
  
  $content = $list->get();
  $content = str_replace('<table class="', '<table class="project_manager-tablesorter ', $content);
  
  $fragment = new rex_fragment();
  $fragment->setVar('title', $this->i18n('projects'));
  $fragment->setVar('content', $content, false);
  echo $fragment->parse('core/page/section.php');
}
