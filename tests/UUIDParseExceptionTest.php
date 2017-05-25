<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass UUIDParseException
 */
final class UUIDParseExceptionTest extends TestCase {
	/**
	 * @covers ::__construct
	 * @covers ::getInput
	 * @covers ::getPosition
	 */
	public static function testConstruct() {
		$r = __METHOD__ . '-reason';
		$i = __METHOD__ . '-input';
		$e = new UUIDParseException($r, $i);

		static::assertSame(0, $e->getCode());
		static::assertSame(__FILE__, $e->getFile());
		static::assertSame($r, $e->getMessage());
		static::assertSame($i, $e->getInput());
		static::assertSame(0, $e->getPosition());
		static::assertNull($e->getPrevious());
	}

	/**
	 * @covers ::__construct
	 * @covers ::getInput
	 * @covers ::getPosition
	 */
	public static function testConstructCustomPosition() {
		$r = __METHOD__ . '-reason';
		$i = __METHOD__ . '-input';
		$p = 42;
		$e = new UUIDParseException($r, $i, $p);

		static::assertSame(0, $e->getCode());
		static::assertSame(__FILE__, $e->getFile());
		static::assertSame($r, $e->getMessage());
		static::assertSame($i, $e->getInput());
		static::assertSame($p, $e->getPosition());
		static::assertNull($e->getPrevious());
	}

	/**
	 * @covers ::__construct
	 * @covers ::getInput
	 * @covers ::getPosition
	 */
	public static function testConstructWithPreviousError() {
		$r = __METHOD__ . '-reason';
		$i = __METHOD__ . '-input';
		$p = new Error;
		$e = new UUIDParseException($r, $i, 0, $p);

		static::assertSame(0, $e->getCode());
		static::assertSame(__FILE__, $e->getFile());
		static::assertSame($r, $e->getMessage());
		static::assertSame($i, $e->getInput());
		static::assertSame(0, $e->getPosition());
		static::assertSame($p, $e->getPrevious());
	}

	/**
	 * @covers ::__construct
	 * @covers ::getInput
	 * @covers ::getPosition
	 */
	public static function testConstructWithPreviousException() {
		$r = __METHOD__ . '-reason';
		$i = __METHOD__ . '-input';
		$p = new Exception;
		$e = new UUIDParseException($r, $i, 0, $p);

		static::assertSame(0, $e->getCode());
		static::assertSame(__FILE__, $e->getFile());
		static::assertSame($r, $e->getMessage());
		static::assertSame($i, $e->getInput());
		static::assertSame(0, $e->getPosition());
		static::assertSame($p, $e->getPrevious());
	}
}
