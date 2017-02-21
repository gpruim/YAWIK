<?php
/**
 * YAWIK
 *
 * @filesource
 * @copyright (c) 2013 - 2016 Cross Solution (http://cross-solution.de)
 * @license   MIT
 * @author    weitz@cross-solution.de
 */

namespace Jobs\Form;

use Core\Entity\EntityInterface;
use Core\Entity\EntityTrait;
use Core\Entity\Hydrator\MappingEntityHydrator;
use Core\Form\HydratorStrategyAwareTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Jobs\Entity\Location;
use Jobs\Form\Hydrator\Strategy\LocationStrategy;
use Zend\Form\Element\Collection;
use Zend\Form\Exception;
use Zend\Form\Fieldset;
use Core\Entity\Hydrator\EntityHydrator;
use Zend\Form\FieldsetInterface;

/**
 * Defines the formular fields of the base formular of a job opening.
 */
class BaseFieldset extends Fieldset
{
    /**
     * name of the used geo location Engine
     *
     * @var string  $locationEngineType
     */
    protected $locationEngineType;

    /**
     * @param $locationEngineType
     */
    public function setLocationEngineType($locationEngineType)
    {
        $this->locationEngineType = $locationEngineType;
    }
 
    public function getHydrator()
    {
        if (!$this->hydrator) {
            $hydrator = new MappingEntityHydrator([
                'locations' => 'geoLocation'
            ]);

            $geoLocationStrategy = $this->get('geoLocation')->getHydratorStrategy();

            $locationsStrategy = new \Zend\Hydrator\Strategy\ClosureStrategy(
                /* extract */
                function ($value) use ($geoLocationStrategy)
                {
                    return $geoLocationStrategy->extract($value->first());
                },

                /* hydrate */
                function ($value) use ($geoLocationStrategy)
                {
                    return new ArrayCollection([$geoLocationStrategy->hydrate($value)]);
                }
            );

            $hydrator->addStrategy('locations', $locationsStrategy);

            /*
            $datetimeStrategy = new Hydrator\DatetimeStrategy();
            $datetimeStrategy->setHydrateFormat(Hydrator\DatetimeStrategy::FORMAT_MYSQLDATE);
            $hydrator->addStrategy('datePublishStart', $datetimeStrategy);
            */

            $this->setHydrator($hydrator);
        }
        return $this->hydrator;
    }

//

    public function init()
    {
        $this->setAttribute('id', 'job-fieldset');

        $this->setName('jobBase');

        $this->add(
            [
                'type' => 'Text',
                'name' => 'title',
                'options' => [
                    'label' => /*@translate*/ 'Job title',
                    'description' => /*@translate*/ 'Please enter the job title'
                ],
            ]
        );


        $this->add(
            [
                'type' => 'LocationSelect',
                'name' => 'geoLocation',
                'options' => [
                    'label' => /*@translate*/ 'Location',
                    'description' => /*@translate*/ 'Please enter the location of the job',
                    'location_entity' => Location::class,
                    'summary_value' => function() {
                            $loc = $this->object->getLocations()->first();
                            if (!$loc) { return ''; }

                            $value = $loc->getPostalCode() . ' ' . $loc->getCity() . ', ' . $loc->getRegion();
                            $value = trim($value, ' ,');

                            return $value;
                    },
                ],
                'attributes' => [
                    'data-width' => '100%',
                ]
            ]
        );

    }
}

