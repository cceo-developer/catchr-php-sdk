<?php

namespace CceoDeveloper\Catchr\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Throwable;

readonly class HttpReporter
{
    public function __construct(private PayloadBuilder $builder = new PayloadBuilder()) {}

    public function report(Throwable $e): void
    {
        $envs = Config::get('catchr.environments', []);
        $appEnv = Config::get('app.env');
        $endpoints = Config::get('catchr.endpoints', []);
        $timeout = (int) Config::get('catchr.timeout', 5);

        if (!is_array($endpoints)) {
            $endpoints = [];
        }

        $endpoints = array_values(array_filter(array_map('trim', $endpoints)));

        if (!Config::get('catchr.enabled', true) || empty($endpoints)) {
            return;
        }

        if (!empty($envs) && $appEnv && !in_array($appEnv, $envs, true)) {
            return;
        }

        $request = null;
        try {
            $candidate = app()->bound('request') ? app('request') : null;
            if ($candidate instanceof Request && $candidate->method()) {
                $request = $candidate;
            }
        } catch (Throwable $ignored) {
            @error_log(
                '[Catchr] Failed to report exception: ' .
                get_class($ignored) . ' - ' . $ignored->getMessage()
            );
        }

        $payload = $this->builder->build($e, $request);

        foreach ($endpoints as $endpoint) {
            try {
                Http::timeout($timeout)
                    ->acceptJson()
                    ->asJson()
                    ->post($endpoint, $payload);
            } catch (Throwable $ignored) {
                @error_log('[Catchr] Failed to post to endpoint: ' . $endpoint . ' | ' . get_class($ignored) . ' - ' . $ignored->getMessage());
            }
        }
    }
}
