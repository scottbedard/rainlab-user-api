<?php

namespace Bedard\RainLabUserApi\Classes;

use Closure;
use Illuminate\Routing\Controller;

class ApiController extends Controller
{
    use \October\Rain\Extension\ExtendableTrait;

    /**
     * @var array Behaviors implemented by this controller.
     */
    public $implement;

    /**
     * Instantiate a new Controller instance.
     */
    public function __construct()
    {
        $this->extendableConstruct();
    }

    /**
     * Extend this object properties upon construction.
     *
     * @param Closure $callback
     */
    public static function extend(Closure $callback)
    {
        self::extendableExtendCallback($callback);
    }
}
