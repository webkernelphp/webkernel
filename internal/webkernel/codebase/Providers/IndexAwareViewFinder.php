<?php declare(strict_types=1);
namespace Webkernel\Providers;

use Illuminate\View\FileViewFinder;

class IndexAwareViewFinder extends FileViewFinder
{
    public function find($name)
    {
        try {
            return parent::find($name);
        } catch (\InvalidArgumentException $e) {
            $indexName = $name . '.index';
            if (view()->exists($indexName)) {
                return parent::find($indexName);
            }
            throw $e;
        }
    }
}
