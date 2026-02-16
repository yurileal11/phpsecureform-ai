<?php
namespace PHPSecureForm;

class Mutator {
    // Generate a list of non-destructive, safe mutations for parameters and file metadata
    public static function generate(array $params, array $files): array {
        $mutations = [];

        foreach ($params as $name => $meta) {
            $type = $meta['type'] ?? 'string';
            if ($type === 'string') {
                $mutations[] = ['type'=>'param','param'=>$name,'mutation'=>'empty','value'=>''];
                $mutations[] = ['type'=>'param','param'=>$name,'mutation'=>'long','value'=>str_repeat('A', 2048)];
                $mutations[] = ['type'=>'param','param'=>$name,'mutation'=>'special_chars','value'=>'<script>alert(1)</script>'];
                $mutations[] = ['type'=>'param','param'=>$name,'mutation'=>'null_byte','value'=>"test\0.jpg"];
            } elseif ($type === 'array') {
                $mutations[] = ['type'=>'param','param'=>$name,'mutation'=>'array_length','value'=>array_fill(0,10,'x')];
            }
        }

        foreach ($files as $f) {
            $field = $f['field'] ?? 'file';
            $mutations[] = ['type'=>'file','field'=>$field,'mutation'=>'double_extension','filename'=>'shell.php.jpg','content'=>'[SAMPLE TEXT FILE]'];
            $mutations[] = ['type'=>'file','field'=>$field,'mutation'=>'content_type_mismatch','filename'=>'sample.png','declared_content_type'=>'image/png','content'=>'[SAMPLE TEXT FILE]'];
            $mutations[] = ['type'=>'file','field'=>$field,'mutation'=>'large_file','filename'=>'large_test.bin','note'=>'Simulate >5MB test; actual upload requires user consent and isolated environment.'];
        }

        return $mutations;
    }
}