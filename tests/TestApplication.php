<?php
namespace CakeDC\Auth\Test;
/**
 * Class TestApplication
 *
 * @package CakeDC\Auth\Test
 */
class TestApplication extends \Cake\Http\BaseApplication
{

    /**
     * Setup the middleware queue
     *
     * @param \Cake\Http\MiddlewareQueue $middleware The middleware queue to set in your App Class
     *
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware($middleware)
    {
        return $middleware;
    }

    /**
     * {@inheritDoc}
     */
    public function bootstrap()
    {
        parent::bootstrap();
        $this->addPlugin('CakeDC/Auth', [
            'path' => dirname(dirname(__FILE__)) . DS,
            'routes' => true,
            'bootstrap' => true
        ]);
    }
}