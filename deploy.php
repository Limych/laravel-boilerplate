<?php
namespace Deployer;

require 'recipe/laravel.php';

// Project name
set('application', 'my_project');

// Project repository
set('repository', '');
//set('branch', 'master');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

// Activate SSH multiplexing
set('ssh_multiplexing', true);

// Shared files/dirs between deploys
add('shared_files', []);
add('shared_dirs', []);

// Writable dirs by web server
add('writable_dirs', []);

// Default stage
set('default_stage', 'beta');

// Last releases keeping
set('keep_releases', 7);

set('writable_mode', 'chmod');



// Hosts

host('project.com')
    ->stage('prod')
    ->set('deploy_path', '~/{{hostname}}');

host('beta.project.com')
    ->stage('beta')
    ->set('deploy_path', '~/{{hostname}}')
    ->set('branch', function () {
        $releases = runLocally('git branch -r');
        $releases = explode(' ', trim(preg_replace('/[*\s]+/', ' ', $releases)));
        $releases = preg_filter('/.*(\brelease.*)/', '\\1', $releases);
        $max_release = 'develop';
        if (!empty($releases)) {
            $maxv = '0';
            foreach ($releases as $rel) {
                $v = preg_replace('/^release\W+/', '', $rel);
                if (version_compare($v, $maxv) > 0) {
                    $maxv = $v;
                    $max_release = $rel;
                }
            }
        }
        if (count($releases) > 0) {
            array_unshift($releases, $max_release);
            $max_release = askChoice('Select branch for deploy:', $releases, 0);
        }
        return $max_release;
    });



// Tasks

task('build', function () {
    run('cd {{release_path}} && build');
});

// Detect location of PHP interpretator
desc('Detect location of PHP interpretator');
task('detect:php', function () {
    set('php', function () {
        function refine($files, $dir = '') {
            $files = preg_replace('/-cgi\b/', '', $files);
            $files = array_unique(preg_split('/\s+/', $files));
            $php = '';
            $max_v = 0;
            foreach ($files as $fpath) {
                $fpath = $dir . $fpath;
                if (test("[ -x $fpath ]") && ($v = intval(run("$fpath -r 'echo PHP_VERSION_ID;'")))
                && $max_v < $v) {
                    $php = $fpath;
                    $max_v = $v;
                }
            }
            return $php;
        }

        try {
            $php = refine(run('ls /usr/bin | grep php'), '/usr/bin/');
        } catch (Exception $e) {
            // Nope
        }
        if (empty($php)) {
            try {
                $php = refine(run('which php'));
            } catch (Exception $e) {
                // Nope
            }
            if (empty($php)) {
                // Especially for Reg.ru
                $php = run('cat ~/php-bin/php');
                $php = preg_replace('/^\#\!/', '', $php);
                $php = refine($php);
            }
        }

        return $php;
    });
    writeln('PHP interpretator detected: {{php}}');
});
before('deploy:prepare', 'detect:php');

// Add symlink to current PHP version
desc('Add symlink to current PHP version');
task('symlink:php', function () {
    run('cd {{release_path}} && ln -s {{php}} php');
});
after('deploy:shared', 'symlink:php');

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.
before('deploy:symlink', 'artisan:migrate');

