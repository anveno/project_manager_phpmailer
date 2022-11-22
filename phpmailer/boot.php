<?php
// CRONJOB REGISTER
if (rex_addon::get('cronjob')->isAvailable()) {
  rex_cronjob_manager::registerType('rex_cronjob_project_manager_phpmailer');
}

// PROJECT_MANAGER_SERVER_DETAIL_HOOK
if (rex::isBackend() && rex::getUser()) {
  
  rex_extension::register('PROJECT_MANAGER_SERVER_DETAIL_HOOK', function (rex_extension_point $ep) {

    $exist = '';
    if ($ep->getSubject() != "") {
      $exist = $ep->getSubject();
    }
    
    $params = $ep->getParams();
    $panel = include(rex_path::plugin('project_manager', 'phpmailer', 'pages/content.phpmailer.php'));

    $fragment = new rex_fragment();
    $fragment->setVar('title', "PHPMailer", false);
    $fragment->setVar('body', $panel, false);
    $fragment->setVar('class', 'panel panel-info', false);
    $fragment->setVar('domain', $params["domain"], false);
    
    $fragment->setVar('collapse', true);
    $fragment->setVar('collapsed', true);
    $phpmailer = '<div class="col-md-12">'.$fragment->parse('core/page/section.php').'</div>';
    
    return $exist.$phpmailer;
    
  }, rex_extension::LATE);
}


if (rex::isBackend() && is_object(rex::getUser())) {
  rex_perm::register('project_manager_phpmailer[]');
}

