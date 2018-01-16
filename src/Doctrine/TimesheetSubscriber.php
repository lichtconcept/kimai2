<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine;

use App\Entity\UserPreference;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use App\Entity\Timesheet;

/**
 * A listener to make sure all Timesheet entries will have a proper duration.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class TimesheetSubscriber implements EventSubscriber
{

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'prePersist',
            'preUpdate',
        );
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $this->calculateFields($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $this->calculateFields($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    protected function calculateFields(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof Timesheet) {
            $duration = 0;
            if ($entity->getEnd() !== null) {
                $duration = $entity->getEnd()->getTimestamp() - $entity->getBegin()->getTimestamp();
                $entity->setDuration($duration);

                // TODO allow to set hourly rate on activity, project and customer and prefer these

                $hourlyRate = 0;
                foreach ($entity->getUser()->getPreferences() as $preference) {
                    if ($preference->getName() == UserPreference::HOURLY_RATE) {
                        $hourlyRate = (int) $preference->getValue();
                    }
                }

                $rate = $hourlyRate * ($duration / 3600);
                $entity->setRate($rate);
            }
        }
    }
}