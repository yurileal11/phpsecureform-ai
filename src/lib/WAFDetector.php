<?php
namespace PHPSecureForm;

class WAFDetector {
    // Look for common WAF headers, response codes, or response fingerprints
    public static function detect(array $response): array {
        $findings = [];
        $headers = array_change_key_case($response['headers'] ?? [], CASE_LOWER);
        $body = $response['body'] ?? '';
        $status = $response['status'] ?? 0;

        $wafHeaders = ['x-sucuri-id','x-waf-status','x-firewall','server-protect','x-mod-security'];
        foreach ($wafHeaders as $h) {
            if (isset($headers[$h])) {
                $findings[] = ['type'=>'header','header'=>$h,'value'=>$headers[$h]];
            }
        }

        // Suspicious status codes often used by WAFs for blocking
        if (in_array($status, [406, 418, 403, 401])) {
            $findings[] = ['type'=>'status','status'=>$status,'note'=>'Possible WAF or access control response code'];
        }

        // Common phrases in blocked pages
        $block_signatures = ['access denied','request rejected','forbidden','mod_security','web application firewall','blocked by'];
        $lower = strtolower($body);
        foreach ($block_signatures as $sig) {
            if (strpos($lower, $sig) !== false) {
                $findings[] = ['type'=>'body_signature','signature'=>$sig];
            }
        }

        return $findings;
    }
}