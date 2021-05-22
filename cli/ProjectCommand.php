<?php

    namespace nyx\mercurius\wp\cli;

    use JsonException;
    use WP_CLI;

    /**
     * NYX Mercurius Project Management Command
     */
    class ProjectCommand
    {
        /**
         * @var string
         */
        protected string $name = 'Project Management Commands';

        /**
         * @param string
         */
        protected string $version = '1.0.1';

        /**
         * Prints the NYX Mercurius Project Management Command
         *
         * @throws JsonException
         */
        public function version(): void
        {
            $mercurius_name    = Mercurius::get_name();
            $mercurius_version = Mercurius::get_version();

            WP_CLI::success(sprintf('%s version %s // %s version %s', $mercurius_name, $mercurius_version, $this->name, $this->version));
        }

        /**
         * @param array $params
         * @param array $assoc_params
         *
         * @throws WP_CLI\ExitException|JsonException
         */
        public function configure(array $params = [], array $assoc_params = []): void
        {
            $environment = Mercurius::get_var('env', 1, $params, $assoc_params);

            if (!empty($environment)) {
                $configurations = Mercurius::load_config($environment);

                if (is_array($configurations)) {
                    $current_installed_plugins = Mercurius::get_current_installed_plugins();

                    if (Mercurius::write_site_config($configurations)) {
                        WP_CLI::success('Configuration file created with success at /environment.json.');
                    } else {
                        WP_CLI::error('The command could not create the required configuration file.');

                        return;
                    }

                    foreach ($configurations['default_plugins'] as $plugin) {
                        if ($plugin['can_update']) {
                            if ($plugin['from_remote'] && in_array($plugin['name'], $current_installed_plugins, true)) {
                                WP_CLI::runcommand("plugin uninstall {$plugin['name']} --deactivate");
                            }

                            WP_CLI::runcommand("plugin install {$plugin['path']} --activate");

                            if (in_array($plugin['name'], $configurations['disabled_plugins'], true)) {
                                WP_CLI::runcommand("plugin deactivate {$plugin['name']}");
                            }
                        }
                    }

                    if ($configurations['update_core']) {
                        WP_CLI::runcommand('core update');
                        WP_CLI::runcommand('core update-db');
                    }

                    if ($configurations['update_plugins']) {
                        WP_CLI::runcommand('plugin update --all');
                    }

                    if ($configurations['update_themes']) {
                        WP_CLI::runcommand('plugin update --all');
                    }

                    if ($configurations['update_languages']) {
                        WP_CLI::runcommand('language core update');
                    }

                    WP_CLI::success('Project configuration finished.');

                    return;
                }
            }

            WP_CLI::error('Environment or configuration file missing or missconfigured.');
        }
    }
