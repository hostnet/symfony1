<?php
declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

class sfPatternRoutingTest extends TestCase
{
    /**
     * @dataProvider findRouteProvider
     */
    public function testFindRoute($url, $expected_name, array $expected_params)
    {
        $dispatcher = new sfEventDispatcher();
        $router = new sfPatternRouting($dispatcher);
        $router->connect('hello_world', '/hello/world');
        $router->connect('hello_name', '/hello/:name');
        $router->connect('default', '/:module/:action/*');

        $result = $router->findRoute($url);
        self::assertSame($expected_name, $result['name']);
        self::assertSame($expected_params, $result['parameters']);
    }

    public function findRouteProvider()
    {
        return [
            ['/hello/world', 'hello_world', ['module' => 'default', 'action' => 'index']],
            ['/hello/henk', 'hello_name', ['module' => 'default', 'action' => 'index', 'name' => 'henk']],
            ['/a/b', 'default', ['module' => 'a', 'action' => 'b']],
            ['/a/b/c/d', 'default', ['c' => 'd', 'module' => 'a', 'action' => 'b']],
        ];
    }
}
