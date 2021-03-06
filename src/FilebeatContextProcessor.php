<?php

namespace Cego;

use Throwable;
use UAParser\Parser;

class FilebeatContextProcessor
{
    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record): array
    {
        return self::applyContextFields($record);
    }

    /**
     * Apply various fields to context and return new record
     * @param array $record
     * @return array
     */
    private function applyContextFields(array $record)
    {
        if (!isset($record["context"])) {
            $record["context"] = [];
        }


        $record = self::applyUrlContextFields($record);
        $record = self::applyClientContextFields($record);
        $record = self::applyPHPContextFields($record);
        return self::applyUserAgentContextFields($record);
    }

    /**
     * Apply php fields and return new record
     * @param array $record
     * @return array
     */
    public static function applyPHPContextFields(array $record)
    {

        $record["context"]["php"] = [
            'sapi' => PHP_SAPI,
            'argc' => $_SERVER['argc'] ?? null,
            'argv_string' => $_SERVER['argv'] ?? null ? implode(' ', $_SERVER['argv']) : null
        ];
        return $record;
    }

    /**
     * Apply client fields and return new record
     * @param array $record
     * @return array
     */
    public static function applyClientContextFields(array $record)
    {

        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? null;

        if (!isset($ip) || $ip === null) {
            return $record;
        }

        $record["context"]["client"] = [
            'ip' => $ip
        ];
        return $record;
    }

    /**
     * Apply url fields and return new record
     * @param array $record
     * @return array
     */
    public static function applyUrlContextFields(array $record)
    {

        if (!isset($_SERVER['REQUEST_URI'])) {
            return $record;
        }

        $record["context"]["url"] = [
            'path' => $_SERVER['REQUEST_URI'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'domain' => $_SERVER['HTTP_HOST'] ?? null,
            'headers' => [
                'cf_request_id' => $_SERVER['HTTP_CF_REQUEST_ID'] ?? null,
                'cf_ray' => $_SERVER['HTTP_CF_RAY'] ?? null,
                'cf_warp_tag_id' => $_SERVER['HTTP_CF_WARP_TAG_ID'] ?? null,
                'cf_visitor' => $_SERVER['HTTP_CF_VISITOR'] ?? null,
                'cf_ipcountry' => $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null,
                'cf_cloudflared_proxy_tunnel_hostname' => $_SERVER['HTTP_CF_CLOUDFLARED_PROXY_TUNNEL_HOSTNAME'] ?? null,
                'x_forwarded_proto' => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null,
                'x_forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
                'x_forwarded_host' => $_SERVER['HTTP_X_FORWARDED_HOST'] ?? null
            ]
        ];
        return $record;
    }

    /**
     * Apply user agent fields and return new record
     * @param array $record
     * @return array
     */
    private static function applyUserAgentContextFields(array $record)
    {

        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return $record;
        }

        $record["context"]["user_agent"] = [
            "original" => $_SERVER['HTTP_USER_AGENT'],
            "browser" => [],
            "os" => []
        ];

        try {
            $parser = Parser::create();
            $result = $parser->parse($_SERVER['HTTP_USER_AGENT']);

            $record['context']['user_agent']['browser']['name'] = $result->ua->family;
            $record['context']['user_agent']['browser']['major'] = $result->ua->major;
            $record['context']['user_agent']['browser']['minor'] = $result->ua->minor;
            $record['context']['user_agent']['browser']['patch'] = $result->ua->patch;

            $record['context']['user_agent']['os']['name'] = $result->os->family;
            $record['context']['user_agent']['os']['major'] = $result->os->major;
            $record['context']['user_agent']['os']['minor'] = $result->os->minor;
            $record['context']['user_agent']['os']['patch'] = $result->os->patch;
            $record['context']['user_agent']['os']['patch_minor'] = $result->os->patchMinor;

            $record['context']['user_agent']['device.name'] = $result->device->family;
        } catch (Throwable $ex) {
            $record['context']['user_agent']['error'] = $ex->getMessage();
        }
        return $record;
    }

}
