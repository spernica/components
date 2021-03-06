<?php

namespace Mesour\ComponentsTests;

use Mesour;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

class LinkTestUrl extends Mesour\Tests\BaseTestCase
{

	public function testGetters()
	{
		$address = 'http://mesour.com';
		$args = ['key' => 'val[]'];
		$completeAddress = 'http://mesour.com?key=val%5B%5D';

		$link = new Mesour\Components\Link\Link;

		$url = new Mesour\Components\Link\Url($link, $address, $args);

		Assert::same($url->getLink(), $link);
		Assert::same($url->getArguments(), $args);
		Assert::same($url->getDestination(), $address);
		Assert::same($url->create(), $completeAddress);
	}

	public function testExceptionOnBadDestination()
	{
		Assert::exception(
			function () {
				$link = new Mesour\Components\Link\Link;

				new Mesour\Components\Link\Url($link, []);
			},
			Mesour\InvalidArgumentException::class
		);
	}

}

$test = new LinkTestUrl();
$test->run();
