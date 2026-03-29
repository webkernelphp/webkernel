<?php declare(strict_types=1);
namespace Webkernel\Query\Traits;

trait Exportable
{
    private function phpExport(mixed $value, int $depth = 0): string
    {
        if (!is_array($value)) {
            return var_export($value, true);
        }
        if (empty($value)) {
            return '[]';
        }
        $pad  = str_repeat('    ', $depth);
        $ipad = str_repeat('    ', $depth + 1);
        $list = array_is_list($value);
        $out  = [];
        foreach ($value as $k => $v) {
            $key   = $list ? '' : var_export($k, true) . ' => ';
            $out[] = $ipad . $key . $this->phpExport($v, $depth + 1) . ',';
        }
        return "[\n" . implode("\n", $out) . "\n{$pad}]";
    }
}
