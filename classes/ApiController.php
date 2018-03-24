<?php namespace GivingTeam\Auth\Classes;

use Illuminate\Routing\Controller;
use Closure;
use ValidationException;

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

    /**
     * Return a validation error.
     * 
     * @return Response
     */
    protected function validationError(ValidationException $e) {
        return response([
            'status' => 'invalid',
            'errors' => $e->getFields(),
        ], 400);
    }
}
