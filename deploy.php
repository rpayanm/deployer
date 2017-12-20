<?php

namespace Deployer;

require 'recipe/common.php';

require 'deploy.credentials.php';

task('deploy', [
  'deploy:info',
  'deploy:prepare',
  'deploy:lock',
  'deploy:release',
  'deploy:update_code',
  'deploy:shared',
  'deploy:symlink',
  'deploy:unlock',
  'cleanup'
]);

// Project name
set('application', '');

// Project repository
set('repository', '');

// Hosts
host('localhost')
  ->set('deploy_path', '{{application}}');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', TRUE);

//Set drupal site. Change if you use different site
set('drupal_site', 'default');

// Drupal 8 shared dirs
set('shared_dirs', [
  'web/sites/{{drupal_site}}/files',
]);

// Drupal 8 shared files
set('shared_files', [
  'web/sites/{{drupal_site}}/settings.php',
  //  'web/sites/{{drupal_site}}/services.yml',
]);

// Drupal 8 Writable dirs
set('writable_dirs', [
  'web/sites/{{drupal_site}}/files',
]);

// Drush
set('drush_path', '/usr/local/bin/drush');

// Tasks

// Backup
task('db:backup', function () {
  if (has('previous_release')) {
    $db_backup_name = time() . '.sql';
    cd('{{previous_release}}');
    run('mysqldump {{db_name}} -u{{db_user}} -p{{db_pass}} -h{{db_hostname}}> ' . $db_backup_name);
    run('gzip ' . $db_backup_name);
    writeln('<info>Backup created at {{previous_release}}/' . $db_backup_name . '</info>');
  }
  else {
    writeln('<error>We have not previous release.</error>');
  }
});

// Update drupal
task('update:drupal', function () {
  cd('{{release_path}}/web');
  run('{{drush_path}} cache-rebuild -y -v');
  run('{{drush_path}} updatedb -y -v');
  run('{{drush_path}} entity-updates -y -v');
  run('{{drush_path}} config-import -y -v');
  run('{{drush_path}} cache-rebuild -y -v');
});

task('drupal:permissions', function () {
  run('~/fix-permissions.sh --drupal_path={{application}}/current/web --drupal_user=root');
});

// Update
task('update', [
  'deploy:vendors',
  'drupal:permissions',
  'update:drupal',
]);

// db Backup
after('deploy:release', 'db:backup');

// Update
after('deploy:unlock', 'update');

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

