<?php

/*
 * This file is a part of Sculpin.
 *
 * (c) Dragonfly Development Inc.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Delssajri\Sculpin\Bundle\ListsBundle;

use Sculpin\Core\Sculpin;
use Sculpin\Core\Event\SourceSetEvent;
use Sculpin\Core\Source\SourceSet;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Dflydev\Symfony\FinderFactory\FinderFactory;

/**
 * Lists Generator.
 *
 * @author Tatiana Drozdova <delssajri@gmail.com>
 */
class ListsGenerator implements EventSubscriberInterface
{

    /**
     * @var array
     */
    protected $listitems = array();
    
    protected $listMap = array(
            array(
                'pattern' => '_benefits',
                'target'  => 'benefits'
            ),
            array(
                'pattern' => '_partners',
                'target'  => 'partners'
            ),
            array(
                'pattern' => '_media',
                'target'  => 'media'
            ),
            array(
                'pattern' => '_awards',
                'target'  => 'awards'
            )
        );

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Sculpin::EVENT_BEFORE_RUN => 'beforeRun',
        );
    }

    protected function findPathTarget($firstPathEntry) {
        foreach($this->listMap as $listSpec) {
            if ($listSpec['pattern'] === $firstPathEntry) {
                return $listSpec['target'];
            }
        }
        return null;
    }

    public function beforeRun(SourceSetEvent $sourceSetEvent)
    {

        $finderFactory = new FinderFactory;
        $files = $finderFactory->createFinder()
            ->files()
            ->ignoreVCS(true)
            ->ignoreDotFiles(false)
            ->followLinks()
            ->in("./source");

        foreach ($files as $file) {
            $fileName = $file->getRelativePathname();

            $pathSegments = explode('/', $fileName);
            if (empty($pathSegments)) {
                continue;
            }

            $shifted = array_shift($pathSegments);
            $target = $this->findPathTarget($shifted);

            if (is_null($target)) {
                continue;
            }

            $templateName = array_pop($pathSegments);
            $items = array(
                'name' => $templateName,
                'file' => $fileName,
                'path' => $pathSegments
            );
            $this->addListItem($this->listitems, $target, $items);

            echo $file->getRelativePathname() . "\n";
        }

        // if ($source->isGenerated() || ! $source->canBeFormatted()) {
        //     // Skip generated and inappropriate sources.
        //     continue;
        // }

        $sourceSet = $sourceSetEvent->sourceSet();
        $this->setListItems($sourceSet);
    }

    protected function setListItems(SourceSet $sourceSet)
    {

        foreach ($sourceSet->allSources() as $source) {

            if ($source->isGenerated() || ! $source->canBeFormatted()) {
                continue;
            }

            $source->data()->set('list', $this->listitems);
        }

    }

    protected function addListItem(array &$listitems, $target, array $items)
    {
        if (!array_key_exists($target, $listitems)) {
            $listitems[$target] = array();
        }
        $a = &$listitems[$target];

        $aa = &$a;
        $pathSegments = $items['path'];

        $auxPathSegments = explode('_', $items['name']);
        array_pop($auxPathSegments);
        $pathSegments = array_merge($pathSegments, $auxPathSegments);

        while ( ! empty($pathSegments) ) {
            $segment = array_shift($pathSegments);
            if (!array_key_exists($segment, $aa)) {
                $aa[$segment] = array();
            }
            $aa = &$aa[$segment];
        }

        array_push($aa, $items);
    }

}
