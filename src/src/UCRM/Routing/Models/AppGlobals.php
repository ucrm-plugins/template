<?php
declare(strict_types=1);

namespace UCRM\Routing\Models;


class AppGlobals
{
    /** @var string */
    public $hostUrl;

    /** @var string */
    public $baseUrl;

    /** @var string */
    public $homeRoute;

    /** @var string */
    public $locale;

    /** @var string */
    public $pluginName;



    public function __construct(array $values = [])
    {
        foreach($values as $property => $value)
        {
            if(property_exists($this, $property))
                $this->$property = $value;
        }
    }

}







