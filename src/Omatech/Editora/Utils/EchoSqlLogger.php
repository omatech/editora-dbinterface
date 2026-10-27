<?php

namespace Omatech\Editora\Utils;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Logging\Middleware;
use Psr\Log\AbstractLogger;

/**
 * Prints every executed statement, as DBAL's EchoSQLLogger did before DBAL 4 removed it.
 * Connection messages are skipped because their context carries the credentials.
 */
class EchoSqlLogger extends AbstractLogger
{
    public static function enable(Configuration $config)
    {
        $config->setMiddlewares([new Middleware(new self())]);
    }

    public function log($level, $message, array $context = []): void
    {
        if (!isset($context['sql'])) {
            return;
        }
        echo $context['sql'] . PHP_EOL;
        if (!empty($context['params'])) {
            var_dump($context['params']);
        }
        if (!empty($context['types'])) {
            var_dump($context['types']);
        }
    }
}
