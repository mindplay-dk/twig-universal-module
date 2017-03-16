<?php

namespace TheCodingMachine;

use Simplex\Container;
use Twig_Environment;

class TwigServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testProvider()
    {
        $container = new Container();

        $provider = new TwigServiceProvider(__DIR__ . DIRECTORY_SEPARATOR . "Fixtures");

        $provider->bootstrap($container);

        $twig = $container->get(Twig_Environment::class);

        $result = $twig->render('test.twig', ['name' => 'David']);

        $this->assertEquals('Hello David', $result);
    }
}
