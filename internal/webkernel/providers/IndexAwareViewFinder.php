<?php declare(strict_types=1);
namespace Webkernel\Providers;

use Illuminate\View\FileViewFinder;

class IndexAwareViewFinder extends FileViewFinder
{
    /**
     * @return string
     */
    public function find($name):string
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
