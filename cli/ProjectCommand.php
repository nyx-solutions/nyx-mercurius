<?php

    namespace nyx\mercurius\wp\cli;

    use JsonException;
    use Throwable;
    use WP_CLI;
    use function in_array;
    use function is_array;

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
        protected string $version = '1.0.3';

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

                    $remote_plugins = array_map(
                        static fn ($p) => $p['name'],
                        array_values(
                            array_filter(
                                $configurations['default_plugins'],
                                static fn ($p) => $p['from_remote']
                            )
                        )
                    );

                    $non_updatable_plugins = array_map(
                        static fn ($p) => $p['name'],
                        array_values(
                            array_filter(
                                $configurations['default_plugins'],
                                static fn ($p) => !$p['can_update']
                            )
                        )
                    );

                    foreach ($configurations['default_plugins'] as $plugin) {
                        if ($plugin['can_update']) {
                            //if ($plugin['from_remote'] && in_array($plugin['name'], $current_installed_plugins, true)) {
                            try {
                                WP_CLI::runcommand("plugin uninstall {$plugin['name']} --deactivate");
                            } catch (Throwable $exception) {}
                            //}

                            WP_CLI::runcommand("plugin install {$plugin['path']} --activate");
                        }
                    }

                    if ($configurations['update_core']) {
                        WP_CLI::runcommand('core update');
                        WP_CLI::runcommand('core update-db');
                    }

                    if ($configurations['update_plugins']) {
                        $exclude = '';

                        if (!empty($remote_plugins)) {
                            $exclude = sprintf('--exclude=%s', implode(',', array_merge($remote_plugins, $non_updatable_plugins)));
                        }

                        WP_CLI::runcommand(sprintf('plugin update --all %s', $exclude));
                    }

                    if ($configurations['update_themes']) {
                        WP_CLI::runcommand('theme update --all');
                    }

                    if ($configurations['update_languages']) {
                        WP_CLI::runcommand('language core update');
                        WP_CLI::runcommand('language plugin update --all');
                        WP_CLI::runcommand('language theme update --all');
                    }

                    foreach ($configurations['default_plugins'] as $plugin) {
                        if (in_array($plugin['name'], $configurations['disabled_plugins'], true)) {
                            WP_CLI::runcommand("plugin deactivate {$plugin['name']}");
                        }
                    }

                    WP_CLI::success('Project configuration finished.');

                    return;
                }
            }

            WP_CLI::error('Environment or configuration file missing or missconfigured.');
        }
    }
