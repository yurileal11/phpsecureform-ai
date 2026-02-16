<?php
namespace PHPSecureForm;

class Analyzer {
    private $intercept;
    public function __construct(array $intercept) {
        $this->intercept = $intercept;
    }

    public function summarize(): array {
        $req = $this->intercept['request'] ?? [];
        $res = $this->intercept['response'] ?? [];

        $params = $this->extractParams($req);
        $files = $this->extractFiles($req);
        $csrf = $this->detectCSRF($req);
        $waf = WAFDetector::detect($res);
        $mutations = Mutator::generate($params, $files);

        return [
            'url' => $req['url'] ?? '',
            'method' => $req['method'] ?? '',
            'params' => $params,
            'files' => $files,
            'csrf_candidate' => $csrf,
            'waf_findings' => $waf,
            'suggested_mutations' => $mutations,
        ];
    }

    private function extractParams(array $req): array {
        $out = [];
        $headers = array_change_key_case($req['headers'] ?? [], CASE_LOWER);
        $body = $req['body'] ?? '';

        $json = json_decode($body, true);
        if (is_array($json)) {
            foreach ($json as $k=>$v) $out[$k] = ['type'=>gettype($v),'value'=>$v,'source'=>'json'];
            return $out;
        }

        parse_str($body, $parsed);
        foreach ($parsed as $k=>$v) {
            $out[$k] = ['type' => is_array($v)?'array':'string','value'=>$v,'source'=>'form'];
        }

        if (!empty($req['query'])) {
            parse_str($req['query'], $qparsed);
            foreach ($qparsed as $k=>$v) {
                $out[$k] = ['type'=>is_array($v)?'array':'string','value'=>$v,'source'=>'query'];
            }
        }

        return $out;
    }

    private function extractFiles(array $req): array {
        $files = [];
        $headers = array_change_key_case($req['headers'] ?? [], CASE_LOWER);
        $body = $req['body'] ?? '';

        if (isset($headers['content-type']) && stripos($headers['content-type'], 'multipart/form-data') !== false) {
            $boundary = null;
            if (preg_match('/boundary=(.*)$/', $headers['content-type'], $m)) {
                $boundary = trim($m[1], '"');
            }
            if ($boundary) {
                $parts = explode('--' . $boundary, $body);
                foreach ($parts as $part) {
                    if (stripos($part, 'Content-Disposition:') !== false) {
                        if (preg_match('/name="([^\"]+)"/', $part, $n)) {
                            $field = $n[1];
                            $filename = null;
                            if (preg_match('/filename="([^\"]*)"/', $part, $f)) $filename = $f[1];
                            $files[] = ['field'=>$field,'filename'=>$filename ?: '[unknown]','note'=>'parsed via naive multipart splitter'];
                        }
                    }
                }
            } else {
                $files[] = [
                    'field '=> 'unknown',
                    'note' => 'multipart without boundary header; cannot parse'
                ];
            }
        }
        return $files;
    }

    private function detectCSRF(array $req) {
        // Heuristics: look for common CSRF token parameter names in body or headers
        $body = $req['body'] ?? '';
        $common_names = ['csrf_token','csrf','_token','authenticity_token','X-CSRF-Token','x-csrf-token'];
        foreach ($common_names as $n) {

            foreach ($req['headers'] ?? [] as $hk=>$hv) {
                if (stripos($hk, $n) !== false || stripos($hv, $n) !== false) {
                    return [
                        'found_in' => 'header',
                        'name' => $n,
                        'value' => $hv
                    ];
                }
            }

            if (stripos($body, $n) !== false) {
                return [
                    'found_in' => 'body',
                    'name'=> $n
                ];
            }
        }
        return null;
    }
}