<?php

namespace KhanhArtisan\LaravelBackbone\Support;

use Symfony\Component\Finder\Finder as SymfonyFinder;

class Finder extends SymfonyFinder
{
    /**
     * Scan the given path and return all classes
     *
     * @param string $namespace
     * @return array
     */
    public function getClasses(string $namespace): array
    {
        $files = $this->files();
        $classes = [];

        foreach ($files as $file) {

            // Skip if not php file
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $fileNamespace = $namespace;
            $relativePath = $file->getRelativePath();
            $relativePathExploded = explode('/', $relativePath);
            if ($pathLevelCount = count($relativePathExploded)) {
                $relativeNamespace = array_slice($relativePathExploded, 0, $pathLevelCount);
                $fileNamespace .= '\\'.implode('\\', $relativeNamespace);
            }

            $fileName = preg_replace('/.php$/', '', $file->getFilename());
            $className = $fileNamespace.'\\'.$fileName;
            $className = implode('\\', array_filter(explode('\\', $className)));

            if (class_exists($className)) {
                $classes[] = $className;
            }
        }

        return $classes;
    }
}