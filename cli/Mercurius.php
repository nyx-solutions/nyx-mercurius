<?php

    namespace nyx\mercurius\wp\cli;

    use Curl\Curl;
    use JsonException;
    use RuntimeException;
    use Throwable;
    use WP_CLI;

    /**
     * NYX Mercurius
     */
    class Mercurius
    {
        /**
         * @var string
         */
        protected static string $name = 'NYX Mercurius';

        /**
         * @var string
         */
        protected static string $version = '';

        /**
         * @var string
         */
        protected static string $root_path = '';

        /**
         * @var string
         */
        protected static string $project_root_path = '';

        /**
         * @var string
         */
        protected static string $plugins_repository = '';

        /**
         * @var string
         */
        protected static string $plugins_repository_user = '';

        /**
         * @var string
         */
        protected static string $plugins_repository_pwd = '';

        #region Initialization
        /**
         * Initialize all of the available command.
         */
        public static function init(string $root_path, string $projetc_root_path, string $plugins_repository, string $plugins_repository_user, string $plugins_repository_pwd): void
        {
            if (class_exists('WP_CLI')) {
                static::$root_path               = $root_path;
                static::$project_root_path       = $projetc_root_path;
                static::$plugins_repository      = $plugins_repository;
                static::$plugins_repository_user = $plugins_repository_user;
                static::$plugins_repository_pwd  = $plugins_repository_pwd;

                WP_CLI::add_command('nyx project', ProjectCommand::class);
            }
        }
        #endregion

        #region Getters
        /**
         * @return string
         *
         * @throws JsonException
         */
        public static function get_version(): string
        {
            $composer_config = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true, 512, JSON_THROW_ON_ERROR);

            static::$version = (string)$composer_config['version'];

            return static::$version;
        }

        /**
         *
         * @return string
         */
        public static function get_name(): string
        {
            return static::$name;
       }

        /**
         * @return string
         */
        public static function get_root_path(): string
        {
            return static::$root_path;
        }

        /**
         * @return string
         */
        public static function get_project_root_path(): string
        {
            return static::$project_root_path;
        }

        /**
         * @return string
         */
        public static function get_plugins_repository(): string
        {
            return static::$plugins_repository;
        }

        /**
         * @return string
         */
        public static function get_plugins_repository_user(): string
        {
            return static::$plugins_repository_user;
        }

        /**
         * @return string
         */
        public static function get_plugins_repository_pwd(): string
        {
            return static::$plugins_repository_pwd;
        }

        /**
         * @param string $path
         *
         * @return string
         */
        public static function get_temp_directory(string $path = ''): string
        {
            $upload_dir = wp_upload_dir();

            $nyx_mercurius_dir = "{$upload_dir['basedir']}/nyx/mercurius";

            if (!is_dir($nyx_mercurius_dir) && !mkdir($nyx_mercurius_dir, 0775, true) && !is_dir($nyx_mercurius_dir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $nyx_mercurius_dir));
            }

            return "{$nyx_mercurius_dir}{$path}";
        }

        /**
         * @param string $name
         * @param int    $position
         * @param array  $params
         * @param array  $assoc_params
         * @param mixed  $default
         *
         * @return mixed
         */
        public static function get_var(string $name, int $position = 1, array $params = [], array $assoc_params = [], $default = null)
        {
            if (array_key_exists($name, $assoc_params)) {
                return $assoc_params[$name];
            }

            $position--;

            if (array_key_exists($position, $params)) {
                return $params[$position];
            }

            return $default;
        }
        #endregion

        #region Configurations
        /**
         * @var array|null
         */
        protected static ?array $configurations = null;

        /**
         * @var array
         */
        protected static array $environments = [];

        /**
         * @param string $environment
         *
         * @return array|false
         *
         * @throws JsonException|WP_CLI\ExitException
         */
        public static function load_config(string $environment)
        {
            if ($environment === 'global') {
                $environment = '';
            }

            if (is_array(static::$configurations) && array_key_exists($environment, static::$configurations)) {
                return static::$configurations[$environment];
            }

            $config_file = sprintf('%s/environments.json', static::$project_root_path);

            if (is_file($config_file) && is_readable($config_file)) {
                $config = json_decode(file_get_contents($config_file), true, 512, JSON_THROW_ON_ERROR);

                if (is_array($config) && !empty($config)) {
                    $environments = array_keys($config);

                    if (!empty($environments)) {
                        $environments = array_values(array_filter(
                            $environments,
                            static fn ($env) => ($env !== 'global')
                        ));

                        $global_environment_config = [];

                        if (array_key_exists('global', $config)) {
                            $global_environment_config = $config['global'];
                        }

                        $current_plugins = array_map(
                            static function ($plugin) {
                                $plugin_parts = explode('/', $plugin);

                                if (!empty($plugin_parts)) {
                                    return $plugin_parts[0];
                                }

                                return '';
                            },
                            array_keys(get_plugins())
                        );

                        $unknown_plugins = [];

                        foreach ($current_plugins as $current_plugin) {
                            if (!array_key_exists($current_plugin, $global_environment_config['default_plugins'])) {
                                $unknown_plugins[] = $current_plugin;
                            }
                        }

                        if (!empty($environments)) {
                            static::$environments = $environments;

                            if (in_array($environment, $environments, true)) {
                                $configurations = [];

                                foreach ($config as $environment_key => $environment_config) {
                                    if ($environment_key === 'global') {
                                        continue;
                                    }

                                    $configurations[$environment_key] = array_merge(
                                        static::template(),
                                        $global_environment_config,
                                        $environment_config
                                    );

                                    $configurations[$environment_key]['disabled_plugins'] = array_merge($configurations[$environment_key]['disabled_plugins'], $unknown_plugins);

                                    $configurations[$environment_key]['root'] = static::get_root_path();

                                    $default_plugins = [];

                                    foreach ($configurations[$environment_key]['default_plugins'] as $key => $value) {
                                        $plugin_envelope = [
                                            'can_update'  => ($value !== false),
                                            'from_remote' => false,
                                            'name'        => $key,
                                            'path'        => $key,
                                            'remote_path' => $key,
                                        ];

                                        if (is_string($value) && preg_match('/^%PLUGINS_REPOSITORY%.*$/', $value)) {
                                            $plugin_envelope['from_remote'] = true;
                                            $plugin_envelope['remote_path'] = str_replace('%PLUGINS_REPOSITORY%', static::get_plugins_repository(), $value);
                                            $plugin_envelope['path']        = static::get_temp_directory("/{$key}.zip");
                                            $plugin_envelope['can_update']  = (bool)static::download($plugin_envelope['remote_path'], $plugin_envelope['path']);
                                        }

                                        $default_plugins[$key] = $plugin_envelope;
                                    }
                                    $configurations[$environment_key]['default_plugins'] = $default_plugins;
                                }

                                static::$configurations = $configurations;

                                return static::$configurations[$environment];
                            }
                        }
                    }
                }

                return [];
            }

            return false;
        }
        #endregion

        #region Template
        /**
         * @return array
         */
        protected static function template(): array
        {
            return [
                'host'             => null,
                'root'             => null,
                'update_core'      => false,
                'update_plugins'   => false,
                'update_themes'    => false,
                'update_languages' => false,
                'default_plugins'  => [],
                'disabled_plugins' => [],
                'default_themes'   => [],
            ];
        }
        #endregion

        #region Download
        protected static array $downloaded = [];

        /**
         * @param string $source
         * @param string $target
         *
         * @return string
         *
         * @throws WP_CLI\ExitException
         */
        public static function download(string $source, string $target): string
        {
            if (in_array($target, static::$downloaded, true)) {
                return true;
            }

            if (is_file($target)) {
                unlink($target);
            }

            $request = new Curl();

            if (!empty(static::$plugins_repository_user)) {
                $request->setBasicAuthentication(static::$plugins_repository_user, static::$plugins_repository_pwd);
            }

            if ($request->download($source, $target)) {
                static::$downloaded[] = $target;

                WP_CLI::success(sprintf('Plugin downloaded from %s to temporary path %s.', $source, $target));

                return true;
            }

            WP_CLI::error(sprintf('Could not downloaded from %s.', $source));

            return false;
        }
        #endregion

        #region Configurations
        /**
         * @param array $configurations
         *
         * @return bool
         */
        public static function write_site_config(array $configurations): bool
        {
            $payload = [
                'host'           => $configurations['host'],
                'path'           => static::get_root_path(),
                'configurations' => $configurations,
            ];

            try {
                file_put_contents(static::get_project_root_path().'/environment.json', json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);

                return true;
            } catch (Throwable $exception){}

            return false;
        }
        #endregion
    }
