<?php

namespace CCB\DSpec\Parser;

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Error;

use CCB\DSpec\Cache\DependencyCache;

class CachedParser
{
    public function parse(DependencyCache $cache, array $filePaths)
    {
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $traverser     = new NodeTraverser;

        $nameResolver = new NameResolver();
        $traverser->addVisitor($nameResolver);

        $dependenciesVisitor = new DependenciesVisitor;
        $traverser->addVisitor($dependenciesVisitor);

        foreach ($filePaths as $filePath) {
            try {
                if (!file_exists($filePath)) continue;

                $adt = new Adt($filePath);
                $code = file_get_contents($filePath);

                $dependenciesVisitor->setAdt($adt);

                $traverser->traverse($parser->parse($code));

                $cache->add($adt);
            } catch (Error $e) {
                echo 'Parse Error: ', $e->getMessage();
            }
        }

        $cache->setLastBuilt();

        $cache->setDependencyFilePaths();

        return $cache;
    }
}
