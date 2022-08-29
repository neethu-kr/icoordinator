/**
 * Gruntfile.js
 */
module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        php: {
            dist: {
                options: {
                    port: 8080,
                    base: 'public',
                    open: true,
                    keepalive: true
                }
            }
        },
        env : {
            options : {
                //Shared Options Hash
            },
            development: {
                APPLICATION_ENV : 'local'
            },
            test : {
                APPLICATION_ENV : 'test'
            },
            production : {
                APPLICATION_ENV : 'production'
            }
        },
        phpcs: {
            log: {
                dir: [
                    'application/src/**/*.php',
                    'application/config/**/*.php'
                ],
                options: {
                    bin: 'vendor/bin/phpcs',
                    reportFile: 'build/logs/checkstyle.xml',
                    report: 'checkstyle',
                    ignoreExitCode: true
                }
            },
            stdout: {
                dir: [
                    'application/src/**/*.php',
                    'application/config/**/*.php'
                ],
                options: {
                    bin: 'vendor/bin/phpcs'
                }
            },
            autofix: {
                dir: [
                    'application/src/**/*.php',
                    'application/config/**/*.php'
                ],
                options: {
                    bin: 'vendor/bin/phpcbf'
                }
            },
            options: {
                standard: 'PSR2'
            }
        },
        phplint: {
            all: ['application/**/*.php', 'tests/**/*.php']
        },
        phpmd: {
            stdout: {
                dir: 'application',
                options: {
                    reportFormat: 'text',
                    exclude: 'data'
                }
            },
            log: {
                dir: 'application',
                options: {
                    reportFormat: 'xml',
                    reportFile: 'build/logs/pmd.xml',
                    exclude: 'data'
                }
            },
            options: {
                bin: 'vendor/bin/phpmd',
                rulesets: 'codesize'
            }
        },
        phpcpd: {
            stdout: {
                dir: 'application',
                options: {
                    verbose: true,
                    quiet: false,
                    ignoreExitCode: true
                }
            },
            log: {
                dir: 'application',
                options: {
                    reportFile: 'build/logs/pmd-cpd.xml',
                    ignoreExitCode: true,
                    quiet: true
                }
            },
            options: {
                bin: 'vendor/bin/phpcpd',
                exclude: [
                    'data'
                ]
            }
        },
        pdepend: {
            log: {
                dir: [
                    'application'
                ],
                options: {
                    bin: 'vendor/bin/pdepend',
                    jdependXml: 'build/logs/jdepend.xml',
                    jdependChart: 'build/pdepend/dependencies.svg',
                    overviewPyramid: 'build/pdepend/overview-pyramid.svg',
                    ignoreDirectories: [
                        'data'
                    ]
                    //debug: true,
                }

            },
            options: {

            }
        },
        phpunit: {
            log: {
                dir: './tests/',
                options: {
                    configuration: 'tests/phpunit.xml',
                }
            },
            stdout: {
                dir: './tests/',
                options: {
                    noConfiguration: true
                }
            },
            options: {
                bin: 'vendor/bin/phpunit',
                bootstrap: './tests/bootstrap.php',
                colors: true,
                debug: true,
                followOutput: true
            }
        },
        composer: {
            development: {
                options: {
                    flags: [
                        'prefer-source'
                    ]
                }
            },
            test: {
                options: {
                    flags: [
                        'prefer-dist',
                        'optimize-autoloader'
                    ]
                }
            },
            production: {
                options: {
                    flags: [
                        'no-dev',
                        'prefer-dist',
                        'optimize-autoloader'
                    ]
                }
            }
        },
        shell: {
            composerInstall: {
                command: 'curl -sS https://getcomposer.org/installer | php'
            },
            phploc: {
                command: 'vendor/bin/phploc --log-csv build/logs/phploc.csv --count-tests application/ tests/'
            }
        },
        doctrine: {
            interactive: {
                options: {

                }
            },
            noninteractive: {
                options: {
                    commandOptions: {
                        migrations: {
                            migrate: {
                                noInteraction: true
                            }
                        }
                    }
                }
            },
            options: {
                em: [/*'main', */'portal']
            }
        },
        chmod: {
            options: {
                mode: '755'
            },
            applicationDataFolder: {
                options: {
                    mode: '777'
                },
                src: ['application/data', 'application/data/*']
            },
            binScripts: {
                src: ['bin/*']
            }
        },
        mkdir: {
            test: {
                options: {
                    create: ['build/api', 'build/coverage', 'build/logs', 'build/pdepend', 'build/phpdox']
                }
            }
        },
        clean: {
            test: {
                src: ['build/api', 'build/coverage', 'build/logs', 'build/pdepend', 'build/phpdox']
            }
        },
        bump: {
            options: {
                files: ['composer.json'],
                updateConfigs: [],
                commit: true,
                commitMessage: 'Release v%VERSION%',
                commitFiles: ['composer.json'],
                createTag: false,
                //tagName: 'v%VERSION%',
                //tagMessage: 'Version %VERSION%',
                push: false,
                //pushTo: 'origin',
                //gitDescribeOptions: '--tags --always --abbrev=1 --dirty=-d',
                globalReplace: false,
                prereleaseName: false,
                regExp: false
            }
        },
    });


    //loading modules
    grunt.loadNpmTasks('grunt-env');
    grunt.loadNpmTasks('grunt-shell');
    grunt.loadNpmTasks('grunt-php');
    grunt.loadNpmTasks('grunt-phpcs');
    grunt.loadNpmTasks('grunt-phplint');
    grunt.loadNpmTasks('grunt-phpunit');
    grunt.loadNpmTasks('grunt-composer');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-chmod');
    grunt.loadNpmTasks('grunt-mkdir');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-phpmd');
    grunt.loadNpmTasks('grunt-phpcpd');
    grunt.loadNpmTasks('grunt-pdepend');
    grunt.loadNpmTasks('grunt-bump');


    //register tasks
    grunt.registerTask('precommit', ['phplint:all']);
    grunt.registerTask('default', ['precommit']);
    grunt.registerTask('server', ['php']);


    grunt.registerTask('db', 'iCoordinator DB CLI', function() {
        var me = this;
        var done = this.async();
        var options = {
            cmd: 'bin/db',
            args: this.args,
            opts: {
                stdio: 'inherit'
            }
        };
        grunt.util.spawn(options, function(error, result, code) {
            done();
            return true;
        });
    });

    grunt.registerTask('mandrill', 'iCoordinator Mandrill CLI', function() {
        var me = this;
        var done = this.async();
        var options = {
            cmd: 'bin/mandrill',
            args: this.args,
            opts: {
                stdio: 'inherit'
            }
        };
        grunt.util.spawn(options, function(error, result, code) {
            done();
            return true;
        });
    });

    grunt.registerTask('chargify', 'iCoordinator Chargify CLI', function() {
        var me = this;
        var done = this.async();
        var options = {
            cmd: 'bin/chargify',
            args: this.args,
            opts: {
                stdio: 'inherit'
            }
        };
        grunt.util.spawn(options, function(error, result, code) {
            done();
            return true;
        });
    });


    grunt.registerMultiTask('doctrine', 'Doctrine CLI', function() {
        var me = this;
        var done = this.async();
        var async = require('async');

        async.eachSeries(me.options().em, function(em, callback) {
            var args = [(me.args || []).join(':')];

            //setup command specific options
            var commandOptions = me.options().commandOptions;
            if (commandOptions && commandOptions[me.args[0]] && commandOptions[me.args[0]][me.args[1]]) {
                commandOptions = commandOptions[me.args[0]][me.args[1]];
                for (var optionsName in commandOptions) {
                    switch (optionsName) {
                        case 'noInteraction':
                            args.push('--no-interaction');
                            break;
                    }
                }
            }

            var options = {
                cmd: 'bin/doctrine',
                args: args,
                opts: {
                    stdio: 'inherit'
                }
            };
            grunt.util.spawn(options, function (error, result, code) {
                callback();
            });
        }, function (error) {
            done(!error);
        });
    });

    grunt.registerTask('init', [
        'composer:development:self-update:',
        'composer:development:install',
        'chmod:applicationDataFolder',
        'chmod:binScripts'
    ]);

    grunt.registerTask('test', [
        'phpunit:stdout'
    ]);

    grunt.registerTask('build:production', [
        'env:production',
        'composer:production:self-update:',
        'composer:production:install',
        'chmod:applicationDataFolder',
        'chmod:binScripts'
    ]);

    grunt.registerTask('build:heroku', [
        'env:production',
        'chmod:applicationDataFolder',
        'chmod:binScripts',
        'doctrine:noninteractive:migrations:migrate',
        'doctrine:noninteractive:orm:generate:proxies',
        'mandrill:setup-templates',
        'chargify:setup-mappings'
    ]);

    grunt.registerTask('build:teamcity', [
        'env:production',
        'chmod:applicationDataFolder',
        'chmod:binScripts'
        //'doctrine:noninteractive:migrations:migrate',
        //'doctrine:noninteractive:orm:generate:proxies',
        //'mandrill:setup-templates',
        //'chargify:setup-mappings'
    ]);

    grunt.registerTask('build:test', [
        'composer:test:self-update:',
        'composer:test:install'
    ]);

    grunt.registerTask('migrate:production', [
        'env:production',
        'doctrine:noninteractive:migrations:migrate',
        'doctrine:noninteractive:orm:generate:proxies',
        'mandrill:setup-templates',
        'chargify:setup-mappings'
    ]);

    grunt.registerTask('code-analyse:stdout', [
        'phplint:all',
        'phpcs:stdout',
        'phpmd:stdout',
        'phpcpd:stdout',
    ]);

    grunt.registerTask('code-analyse:log', [
        'phplint:all',
        'phpcs:log',
        'phpmd:log',
        'phpcpd:log',
        'pdepend:log',
        'shell:phploc',
    ]);

    grunt.registerTask('update', 'Update project source and database', function() {
        grunt.task.run('composer:development:self-update:');
        grunt.task.run('composer:development:install');
        grunt.task.run('doctrine:interactive:migrations:migrate');
        grunt.task.run('doctrine:interactive:orm:generate:proxies');
        grunt.task.run('mandrill:setup-templates');
        grunt.task.run('chargify:setup-mappings');
    });
};