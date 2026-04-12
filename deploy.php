<?php
namespace Deployer;
require 'recipe/laravel.php';

// Spécifier le chemin de Composer sur le serveur
set('bin/composer', '/opt/php8.2/bin/composer');

// ✅ POUR WINDOWS
set('use_rsync', false);           // Pas de rsync
set('ssh_multiplexing', false);    // Pas de ControlMaster

// Task pour builder les assets en local
task('build:assets', function () {
    writeln('🔨 Building assets locally...');
    runLocally('npm run build');
    writeln('✅ Assets built successfully!');
});

// Task pour uploader les assets buildés
task('upload:assets', function () {
    writeln('📤 Uploading assets to server...');
    upload('public/build/', '{{release_path}}/public/build/');
    writeln('✅ Assets uploaded successfully!');
});

// Exécuter avant le symlink
before('deploy:symlink', 'build:assets');
before('deploy:symlink', 'upload:assets');

set('application', 'agenda');
set('repository', 'git@github.com:BTIBelgium/agenda.git');
set('keep_releases', 3);

host('production')
    ->setHostname('2t9e9.ftp.infomaniak.com')
    ->setRemoteUser('2t9e9_luca')
    ->setPort(22)
    ->setSshMultiplexing(false)
    ->setSshArguments(['-i ~/.ssh/id_ed25519'])
    ->set('deploy_path', '/home/clients/59104c3799ba7af50a8b8ab45ac76a92/sites/agenda.btibel.app')
    ->set('branch', 'main')
    ->set('http_user', 'uid193474')
    ->set('writable_mode', 'chmod');

set('shared_files', ['.env']);
set('shared_dirs', ['storage']);

set('writable_dirs', [
    'bootstrap/cache',
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
]);

set('writable_use_sudo', false);
set('allow_anonymous_stats', false);

after('deploy:symlink', 'artisan:storage:link');
after('deploy:symlink', 'artisan:config:cache');
after('deploy:symlink', 'artisan:route:cache');
after('deploy:symlink', 'artisan:view:cache');
after('deploy:symlink', 'artisan:optimize');

after('deploy:failed', 'deploy:unlock');

task('deploy:success_message', function () {
    writeln('');
    writeln('✅ <fg=green;options=bold>Deployment successful!</>');
    writeln('🚀 <fg=cyan>Application is live on production</>');
    writeln('');
})->once();

after('deploy:success', 'deploy:success_message');