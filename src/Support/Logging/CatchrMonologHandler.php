<?php

namespace CceoDeveloper\Catchr\Support\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Throwable;

class CatchrMonologHandler extends AbstractProcessingHandler
{
    public function __construct(int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        try {
            (new LogReporter())->report($record);
        } catch (Throwable $ignored) {
            @error_log('[Catchr] CatchrMonologHandler failed: ' . get_class($ignored) . ' - ' . $ignored->getMessage());
        }
    }
}