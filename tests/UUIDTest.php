<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass UUID
 */
final class UUIDTest extends TestCase {
	/**
	 * @covers ::__construct
	 * @covers ::NamespaceDNS
	 * @covers ::getVariant
	 * @covers ::getVersion
	 * @covers ::isNil
	 * @covers ::toBinary
	 * @covers ::toHex
	 * @covers ::toString
	 */
	public static function testNamespaceDNS() {
		$uuid = UUID::NamespaceDNS();

		static::assertSame(UUID::VARIANT_RFC4122, $uuid->getVariant());
		static::assertSame(UUID::VERSION_1_TIME_BASED, $uuid->getVersion());
		static::assertFalse($uuid->isNil());
		static::assertSame("\x6b\xa7\xb8\x10\x9d\xad\x11\xd1\x80\xb4\x00\xc0\x4f\xd4\x30\xc8", $uuid->toBinary());
		static::assertSame('6ba7b8109dad11d180b400c04fd430c8', $uuid->toHex());
		static::assertSame('6ba7b810-9dad-11d1-80b4-00c04fd430c8', $uuid->toString());
	}

	/**
	 * @covers ::__construct
	 * @covers ::NamespaceOID
	 * @covers ::getVariant
	 * @covers ::getVersion
	 * @covers ::isNil
	 * @covers ::toBinary
	 * @covers ::toHex
	 * @covers ::toString
	 */
	public static function testNamespaceOID() {
		$uuid = UUID::NamespaceOID();

		static::assertSame(UUID::VARIANT_RFC4122, $uuid->getVariant());
		static::assertSame(UUID::VERSION_1_TIME_BASED, $uuid->getVersion());
		static::assertFalse($uuid->isNil());
		static::assertSame("\x6b\xa7\xb8\x12\x9d\xad\x11\xd1\x80\xb4\x00\xc0\x4f\xd4\x30\xc8", $uuid->toBinary());
		static::assertSame('6ba7b8129dad11d180b400c04fd430c8', $uuid->toHex());
		static::assertSame('6ba7b812-9dad-11d1-80b4-00c04fd430c8', $uuid->toString());
	}

	/**
	 * @covers ::__construct
	 * @covers ::NamespaceURL
	 * @covers ::getVariant
	 * @covers ::getVersion
	 * @covers ::isNil
	 * @covers ::toBinary
	 * @covers ::toHex
	 * @covers ::toString
	 */
	public static function testNamespaceURL() {
		$uuid = UUID::NamespaceURL();

		static::assertSame(UUID::VARIANT_RFC4122, $uuid->getVariant());
		static::assertSame(UUID::VERSION_1_TIME_BASED, $uuid->getVersion());
		static::assertFalse($uuid->isNil());
		static::assertSame("\x6b\xa7\xb8\x11\x9d\xad\x11\xd1\x80\xb4\x00\xc0\x4f\xd4\x30\xc8", $uuid->toBinary());
		static::assertSame('6ba7b8119dad11d180b400c04fd430c8', $uuid->toHex());
		static::assertSame('6ba7b811-9dad-11d1-80b4-00c04fd430c8', $uuid->toString());
	}

	/**
	 * @covers ::__construct
	 * @covers ::NamespaceX500
	 * @covers ::getVariant
	 * @covers ::getVersion
	 * @covers ::isNil
	 * @covers ::toBinary
	 * @covers ::toHex
	 * @covers ::toString
	 */
	public static function testNamespaceX500() {
		$uuid = UUID::NamespaceX500();

		static::assertSame(UUID::VARIANT_RFC4122, $uuid->getVariant());
		static::assertSame(UUID::VERSION_1_TIME_BASED, $uuid->getVersion());
		static::assertFalse($uuid->isNil());
		static::assertSame("\x6b\xa7\xb8\x14\x9d\xad\x11\xd1\x80\xb4\x00\xc0\x4f\xd4\x30\xc8", $uuid->toBinary());
		static::assertSame('6ba7b8149dad11d180b400c04fd430c8', $uuid->toHex());
		static::assertSame('6ba7b814-9dad-11d1-80b4-00c04fd430c8', $uuid->toString());
	}

	/**
	 * @covers ::__construct
	 * @covers ::Nil
	 * @covers ::getVariant
	 * @covers ::getVersion
	 * @covers ::isNil
	 * @covers ::toBinary
	 * @covers ::toHex
	 * @covers ::toString
	 */
	public static function testNil() {
		$uuid = UUID::Nil();

		static::assertSame(0, $uuid->getVariant());
		static::assertSame(0, $uuid->getVersion());
		static::assertTrue($uuid->isNil());
		static::assertSame("\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0", $uuid->toBinary());
		static::assertSame('00000000000000000000000000000000', $uuid->toHex());
		static::assertSame('00000000-0000-0000-0000-000000000000', $uuid->toString());
	}

	/**
	 * @covers ::__construct
	 * @covers ::fromBinary
	 * @covers ::__wakeup
	 */
	public static function testDeserialization() {
		$uuid = UUID::fromBinary("\1\1\1\1\1\1\1\1\1\1\1\1\1\1\1\1");

		static::assertEquals($uuid, unserialize(serialize($uuid)));
	}

	public static function provideInvalidBinaryValues() {
		return [
			's0'   => [''],
			's1'   => [' '],
			's15'  => ['               '],
			's17'  => ['                 '],
			'rand' => [str_repeat(' ', random_int(18, 256))],
		];
	}

	/**
	 * @covers ::__wakeup
	 * @dataProvider provideInvalidBinaryValues
	 * @expectedException UnexpectedValueException
	 * @expectedExceptionMessage Expected exactly 16 bytes, but found
	 */
	public static function testDeserializationFailure(string $binary) {
		$i = strlen($binary);

		unserialize("O:4:\"UUID\":1:{s:12:\"\0UUID\0binary\";s:{$i}:\"{$binary}\";}");
	}

	/**
	 * @covers ::__construct
	 * @covers ::fromBinary
	 * @covers ::toBinary
	 */
	public static function testFromBinary() {
		$binary = random_bytes(16);

		static::assertSame($binary, UUID::fromBinary($binary)->toBinary());
	}

	/**
	 * @covers ::fromBinary
	 * @dataProvider provideInvalidBinaryValues
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Expected exactly 16 bytes, but got
	 */
	public static function testFromBinaryFailure(string $binary) {
		UUID::fromBinary($binary);
	}

	public static function provideVariants() {
		return [
			[0, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],
			[1, "\x00\x00\x00\x00\x00\x00\x00\x00\x80\x00\x00\x00\x00\x00\x00\x00"],
			[2, "\x00\x00\x00\x00\x00\x00\x00\x00\xC0\x00\x00\x00\x00\x00\x00\x00"],
			[3, "\x00\x00\x00\x00\x00\x00\x00\x00\xE0\x00\x00\x00\x00\x00\x00\x00"],
		];
	}

	/**
	 * @covers ::__construct
	 * @covers ::fromBinary
	 * @covers ::getVariant
	 * @dataProvider provideVariants
	 */
	public static function testGetVariant(int $expected, string $binary) {
		static::assertSame($expected, UUID::fromBinary($binary)->getVariant());
	}

	public static function provideNCSVariants() {
		return [
			["\x00\x00\x00\x00\x00\x00\x00\x00\x60\x00\x00\x00\x00\x00\x00\x00"],
			["\x00\x00\x00\x00\x00\x00\x00\x00\x40\x00\x00\x00\x00\x00\x00\x00"],
			["\x00\x00\x00\x00\x00\x00\x00\x00\x20\x00\x00\x00\x00\x00\x00\x00"],
			["\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],
		];
	}

	/**
	 * @covers ::__construct
	 * @covers ::fromBinary
	 * @covers ::getVariant
	 * @dataProvider provideNCSVariants
	 */
	public static function testNCSVariants(string $binary) {
		static::assertSame(UUID::VARIANT_NCS, UUID::fromBinary($binary)->getVariant());
	}

	/**
	 * @covers ::__construct
	 * @covers ::fromBinary
	 * @covers ::getVariant
	 */
	public static function testRFCVariant() {
		static::assertSame(UUID::VARIANT_RFC4122, UUID::fromBinary("\x00\x00\x00\x00\x00\x00\x00\x00\xA0\x00\x00\x00\x00\x00\x00\x00")->getVariant());
	}

	public static function provideVersions() {
		$data = [];

		for ($i = 0; $i < 16; ++$i) {
			$data["version {$i}"] = [$i, UUID::fromBinary(sprintf("\x00\x00\x00\x00\x00\x00%s\x00\x00\x00\x00\x00\x00\x00\x00\x00", chr($i << 4)))];
		}

		return $data;
	}

	/**
	 * @covers ::getVersion
	 * @dataProvider provideVersions
	 */
	public static function testGetVersion(int $expected, UUID $uuid) {
		static::assertSame($expected, $uuid->getVersion());
	}

	public static function provideParseInput() {
		return [
			'hex'              => ['123e4567e89b12d3a456426655440000'],
			'string'           => ['123e4567-e89b-12d3-a456-426655440000'],
			'urn'              => ['urn:uuid:123e4567-e89b-12d3-a456-426655440000'],
			'microsoft'        => ['{123e4567-e89b-12d3-a456-426655440000}'],
			'case-sensitivity' => ['123E4567E89B12D3A456426655440000'],
			'leading garbage'  => [" \t{\t{ { \t123e4567e89b12d3a456426655440000"],
			'trailing garbage' => ["123e4567e89b12d3a456426655440000 \t}\t} } \t"],
			'too many hyphens' => ['----123e-4567-e89b-12d3-a456-4266-5544-0000----'],
			'complete garbage' => [" \t ---- { urn:uuid:----123e-4567-e89b-12d3-a456-4266-5544-0000---- } ---- \t "],
		];
	}

	/**
	 * @covers ::__construct
	 * @covers ::parse
	 * @dataProvider provideParseInput
	 */
	public static function testParse(string $input) {
		$p = new ReflectionProperty(UUID::class, 'binary');
		$p->setAccessible(true);

		static::assertSame("\x12\x3e\x45\x67\xe8\x9b\x12\xd3\xa4\x56\x42\x66\x55\x44\x00\x00", $p->getValue(UUID::parse($input)));
	}

	public static function provideInsufficientParseInput() {
		return [
			[''],
			['0'],
			['0000000000000000000000000000000'],
			['0123456789-0123456789-0123456789'],
			[" \t{012345678901234567890123456789}\t "],
		];
	}

	/**
	 * @covers ::parse
	 * @uses UUIDParseException
	 * @dataProvider provideInsufficientParseInput
	 * @expectedException UUIDParseException
	 * @expectedExceptionMessage Expected at least 32 hexadecimal digits, but got
	 */
	public static function testParseInsufficientLengthFailure(string $input) {
		UUID::parse($input);
	}

	/**
	 * @covers ::parse
	 * @uses UUIDParseException
	 * @expectedException UUIDParseException
	 * @expectedExceptionMessage Expected hexadecimal digit, but found 'P' (0x50)
	 */
	public static function testParseInvalidHexDigitFailure() {
		UUID::parse('01234567-89ab-cdef-0123-456789abcPHP');
	}

	/**
	 * @covers ::parse
	 * @uses UUIDParseException
	 * @expectedException UUIDParseException
	 * @expectedExceptionMessage Expected no more than 32 hexadecimal digits
	 */
	public static function testParseExcessiveLengthFailure() {
		UUID::parse('12345678-1234-1234-1234-123456789abcdef');
	}

	/**
	 * @covers ::__construct
	 * @covers ::NamespaceDNS
	 * @covers ::v3
	 * @covers ::toBinary
	 * @covers ::toString
	 */
	public static function testV3() {
		static::assertSame('11a38b9a-b3da-360f-9353-a5a725514269', UUID::v3(UUID::NamespaceDNS(), 'php.net')->toString());
	}

	/**
	 * @covers ::__construct
	 * @covers ::v4
	 */
	public static function testV4() {
		static::assertNotEquals(UUID::v4(), UUID::v4());
	}

	/**
	 * @covers ::__construct
	 * @covers ::NamespaceDNS
	 * @covers ::v5
	 * @covers ::toBinary
	 * @covers ::toString
	 */
	public static function testV5() {
		static::assertSame('c4a760a8-dbcf-5254-a0d9-6a4474bd1b62', UUID::v5(UUID::NamespaceDNS(), 'php.net')->toString());
	}

	/**
	 * @covers ::__construct
	 * @covers ::fromBinary
	 * @covers ::__set
	 * @expectedException Error
	 * @expectedExceptionMessage Cannot set dynamic properties on immutable UUID object
	 */
	public static function testMagicSet() {
		UUID::fromBinary('                ')->dynamic_property = 'value';
	}

	/**
	 * @covers ::__construct
	 * @covers ::v4
	 * @covers ::__clone
	 * @expectedException Error
	 * @expectedExceptionMessage Cannot clone immutable UUID object
	 */
	public static function testMagicClone() {
		$m = new ReflectionMethod(UUID::class, '__clone');
		$m->setAccessible(true);
		$m->invoke(UUID::v4());
	}
}
