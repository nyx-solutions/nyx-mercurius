<?php

    namespace nyx\mercurius\wp\cli;

    use \WP_CLI;

    /**
     * NYX Mercurius
     */
    final class Mercurius
    {
        public static function init(): void
        {
            if (class_exists('WP_CLI')) {
                WP_CLI::add_command('project', ProjectCommand::class);
            }
        }
    }
