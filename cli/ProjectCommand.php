<?php

    namespace nyx\mercurius\wp\cli;

    /**
     * NYX Mercurius Project Management Command
     */
    final class ProjectCommand
    {
        /**
         * @param string
         */
        protected string $version = '1.0.0';

        /**
         * Prints the NYX Mercurius Project Management Command
         *
         * @param array $params
         * @param array $extendedParams
         */
        public function version(array $params = [], array $extendedParams = []): void
        {
            WP_CLI::success("NYX Mercurius WP-CLI Project Management version {$this->version}.");
        }
    }
