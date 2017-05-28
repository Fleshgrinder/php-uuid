<?php

if (class_exists('UUID') === false) {
	/**
	 * RFC 4122 compliant immutable UUID class.
	 *
	 * This class provides generation and parsing capabilities for Universally
	 * Unique Identifiers (UUIDs, also known as Globally Unique Identifiers
	 * [GUIDs]) as specified in [RFC 4122][rfc].
	 *
	 * UUIDs are 128 bit integers that guarantee uniqueness across space and time.
	 * They are mainly used to assign identifiers to entities without requiring a
	 * central authority. They are thus particularly useful in distributed systems.
	 * They also allow very high allocation rates; up to 10 million per second per
	 * machine, if necessary.
	 *
	 * There are different types of UUIDs, known as variants. This implementation
	 * generates UUIDs according to the Leach-Salz variant; the one specified in
	 * [RFC 4122][rfc] as variant 1. Textual parsing supports both variant 1 and 2
	 * (Microsoft), and construction supports any kind of UUID. However, note that
	 * the provided methods are **not** guaranteed to provide meaningful results if
	 * any other variant than the Leach-Salz one is used.
	 *
	 * There are also different UUID generation algorithms, known as versions. This
	 * implementation provides generators for version 3, 4, and 5 UUIDs.
	 *
	 * Versions 3 and 5 are meant for generating UUIDs from “names” that are drawn
	 * from, and unique within, some “namespace”. They generate the same UUID for
	 * the same name in the same namespace across all RFC 4122 compliant
	 * implementations.
	 *
	 * Version 4 UUIDs are random and result in a new UUID upon every generation.
	 * The randomness is provided by PHP’s {@see random_bytes()} implementation,
	 * and thus uses the best available random source of the operating system.
	 *
	 * Versions 1 and 2 are not supported due to privacy/security concerns. Refer
	 * to the [Wikipedia article][wiki] for more information.
	 *
	 * ## Examples
	 * ```
	 * <?php
	 *
	 * // A random UUID with more randomness than the version 4 implementation.
	 * // This should NOT be used in real-world applications!
	 * $bytes    = random_bytes(16);
	 * $bytes{8} = chr(($bytes{8} & 0x1F) | (UUID::VARIANT_FUTURE_RESERVED << 5));
	 * $uuid     = UUID::fromBinary($bytes);
	 *
	 * $uuid = UUID::Nil();
	 * assert($uuid->isNil());
	 * assert($uuid->getVariant() === 0);
	 * assert($uuid->getVersion() === 0);
	 *
	 * $uuid = UUID::v3(UUID::NamespaceDNS(), 'php.net');
	 * assert($uuid->isNil() === false);
	 * assert($uuid->getVariant() === UUID::VARIANT_RFC4122);
	 * assert($uuid->getVersion() === UUID::VERSION_3_NAME_BASED_MD5);
	 *
	 * $uuid = UUID::v4();
	 * assert($uuid->isNil() === false);
	 * assert($uuid->getVariant() === UUID::VARIANT_RFC4122);
	 * assert($uuid->getVersion() === UUID::VERSION_4_RANDOM);
	 *
	 * $uuid = UUID::v5(UUID::NamespaceDNS(), 'php.net');
	 * assert($uuid->isNil() === false);
	 * assert($uuid->getVariant() === UUID::VARIANT_RFC4122);
	 * assert($uuid->getVersion() === UUID::VERSION_5_NAME_BASED_SHA1);
	 *
	 * $uuid = UUID::parse('urn:uuid:123E4567-E89B-12D3-A456-426655440000');
	 * assert($uuid->isNil() === false);
	 * assert($uuid->getVariant() === UUID::VARIANT_RFC4122);
	 * assert($uuid->getVersion() === UUID::VERSION_1_TIME_BASED);
	 *
	 * assert($uuid->toBinary() === "\x12\x3E\x45\x67\xE8\x9B\x12\xD3\xA4\x56\x42\x66\x55\x44\x00\x00");
	 * assert($uuid->toHex() === '123e4567e89b12d3a456426655440000');
	 * assert($uuid->toString() === '123e4567-e89b-12d3-a456-426655440000');
	 *
	 * ?>
	 * ```
	 *
	 * Comparison of UUIDs is possible with the default comparison operators of
	 * PHP.
	 *
	 * ```
	 * <?php
	 *
	 * $a = UUID::v5(UUID::NamespaceDNS(), 'php.net');
	 * $b = UUID::v5(UUID::NamespaceDNS(), 'php.net');
	 * $c = UUID::v4();
	 *
	 * assert($a == $b);
	 * assert($a != $c);
	 * assert($b != $c);
	 *
	 * $a = UUID::fromBinary("\1\1\1\1\1\1\1\1\1\1\1\1\1\1\1\1");
	 * $b = UUID::fromBinary("\2\2\2\2\2\2\2\2\2\2\2\2\2\2\2\2");
	 *
	 * assert($a < $b);
	 * assert($a != $b);
	 * assert($b < $a);
	 *
	 * $u1 = UUID::fromBinary("\1\1\1\1\1\1\1\1\1\1\1\1\1\1\1\1");
	 * $u2 = UUID::fromBinary("\2\2\2\2\2\2\2\2\2\2\2\2\2\2\2\2");
	 * $u3 = UUID::fromBinary("\3\3\3\3\3\3\3\3\3\3\3\3\3\3\3\3");
	 *
	 * $data = [$u3, $u2, $u1];
	 * sort($data);
	 * assert($data === [$u1, $u2, $u3]);
	 *
	 * ?>
	 * ```
	 *
	 * [rfc]: https://tools.ietf.org/html/rfc4122
	 * [wiki]: https://en.wikipedia.org/wiki/Universally_unique_identifier
	 * @since 7.2
	 * @see https://php.net/uuid
	 */
	final class UUID {
		/**
		 * Code for the (reserved) NCS variant of UUIDs.
		 *
		 * @since 7.2
		 * @see https://tools.ietf.org/html/rfc4122#section-4.1.1
		 */
		const VARIANT_NCS = 0;

		/**
		 * Code for the RFC 4122 variant of UUIDs.
		 *
		 * This implementation generates UUIDs of this variant only.
		 *
		 * @since 7.2
		 * @see https://tools.ietf.org/html/rfc4122#section-4.1.1
		 */
		const VARIANT_RFC4122 = 1;

		/**
		 * Code for the (reserved) Microsoft variant of UUIDs, the GUIDs.
		 *
		 * @since 7.2
		 * @see https://tools.ietf.org/html/rfc4122#section-4.1.1
		 */
		const VARIANT_MICROSOFT = 2;

		/**
		 * Version code for the future reserved variant of UUIDs.
		 *
		 * @since 7.2
		 * @see https://tools.ietf.org/html/rfc4122#section-4.1.1
		 */
		const VARIANT_FUTURE_RESERVED = 3;

		/**
		 * Version code for date-time and IEEE 802 MAC address UUIDs.
		 *
		 * Generation of this version is not supported by this implementation due
		 * to security concerns. Version 4 UUIDs are a good replacement for version
		 * 1 UUIDs without the privacy/security concerns (see [Wikipedia][wiki]).
		 *
		 * [wiki]: https://en.wikipedia.org/wiki/Universally_unique_identifier
		 * @since 7.2
		 * @see https://tools.ietf.org/html/rfc4122#section-4.2
		 * @see https://en.wikipedia.org/wiki/Universally_unique_identifier#Version_1_.28date-time_and_MAC_address.29
		 */
		const VERSION_1_TIME_BASED = 1;

		/**
		 * Version code for date-time and IEEE 802 MAC address UUIDs (DCE security
		 * algorithm).
		 *
		 * Generation of this version is not supported by this implementation due
		 * to security concerns, and uniqueness limitations for applications with
		 * high allocations. Version 4 UUIDs are a good replacement for version 2
		 * UUIDs without the privacy/security concerns (see [Wikipedia][wiki]), and
		 * they support high allocations.
		 *
		 * [wiki]: https://en.wikipedia.org/wiki/Universally_unique_identifier
		 * @since 7.2
		 * @see https://tools.ietf.org/html/rfc4122#section-4.2
		 * @see https://en.wikipedia.org/wiki/Universally_unique_identifier#Version_2_.28date-time_and_MAC_Address.2C_DCE_security_version.29
		 */
		const VERSION_2_DCE_SECURITY = 2;

		/**
		 * Version code for namespace/name-based MD5 hashed UUIDs.
		 *
		 * @since 7.2
		 * @see v3
		 * @see https://tools.ietf.org/html/rfc4122#section-4.3
		 * @see https://en.wikipedia.org/wiki/Universally_unique_identifier#Versions_3_and_5_.28namespace_name-based.29
		 */
		const VERSION_3_NAME_BASED_MD5 = 3;

		/**
		 * Version code for random UUIDs.
		 *
		 * @since 7.2
		 * @see v4
		 * @see https://tools.ietf.org/html/rfc4122#section-4.4
		 * @see https://en.wikipedia.org/wiki/Universally_unique_identifier#Version_4_.28random.29
		 */
		const VERSION_4_RANDOM = 4;

		/**
		 * Version code for namespace/name-based SHA1 hashed UUIDs.
		 *
		 * @since 7.2
		 * @see v5
		 * @see https://tools.ietf.org/html/rfc4122#section-4.3
		 * @see https://en.wikipedia.org/wiki/Universally_unique_identifier#Versions_3_and_5_.28namespace_name-based.29
		 */
		const VERSION_5_NAME_BASED_SHA1 = 5;

		/**
		 * This UUID's 128 bit integer value as 16 byte binary string.
		 *
		 * @since 7.2
		 * @see toBinary
		 * @var string
		 */
		private $bytes;

		/**
		 * Use {@see fromBinary} or {@see parse} to construct a new instance.
		 */
		private function __construct() {
			// NOOP
		}

		/**
		 * Construct new UUID instance from binary string of exactly 16 bytes.
		 *
		 * Any string of 16 bytes is accepted by this named constructor. This
		 * enables the construction of UUIDs of any variant and version, regardless
		 * of the {@see parse} implementation.
		 *
		 * ## Examples
		 * ```
		 * <?php
		 *
		 * // A random UUID with more randomness than the version 4 implementation.
		 * $uuid    = random_bytes(16);
		 * $uuid{8} = chr((ord($uuid{8}) & 0b00011111) | 0b11100000);
		 * $uuid    = UUID::fromBytes($uuid);
		 *
		 * assert($uuid->getVariant() === UUID::VARIANT_FUTURE_RESERVED);
		 *
		 * ?>
		 * ```
		 *
		 * @since 7.2
		 * @see https://php.net/uuid.fromBinary
		 * @see toBinary
		 * @param string $input string of exactly 16 bytes to construct the instance from.
		 * @return \UUID UUID constructed from the binary input.
		 * @throws \InvalidArgumentException if the input is not 16 bytes long.
		 */
		public static function fromBinary($input) {
			if (strlen($input) !== 16) {
				throw new InvalidArgumentException('Expected exactly 16 bytes, but got ' . strlen($input));
			}

			$uuid = new UUID;
			$uuid->bytes = $input;
			return $uuid;
		}

		/**
		 * Parse the given string as UUID.
		 *
		 * The following UUID representations are parsable:
		 *
		 * - hexadecimal (`00000000111122223333444444444444`),
		 * - string (`00000000-1111-2222-3333-444444444444`),
		 * - URNs (`urn:uuid:00000000-1111-2222-3333-444444444444`), and
		 * - Microsoft (`{00000000-1111-2222-3333-444444444444}`).
		 *
		 * Leading and trailing whitespace, namely spaces (` `) and tabs (`\t`), is
		 * ignored, so are leading opening braces (`{`) and trailing closing braces
		 * (`}`). Hyphens (`-`) are ignored everywhere. The parsing algorithm
		 * follows the [robustness principle][wrp] and is not meant for validation.
		 *
		 * ## Examples
		 * ```
		 * <?php
		 *
		 * // Parsing of canonical representations.
		 * UUID::parse('0123456789abcdef0123456789abcdef');
		 * UUID::parse('01234567-89ab-cdef-0123-456789abcdef');
		 * UUID::parse('urn:uuid:01234567-89ab-cdef-0123-456789abcdef');
		 * UUID::parse('{01234567-89ab-cdef-0123-456789abcdef}');
		 *
		 * // Leading and trailing garbage is ignored, so are extraneous hyphens.
		 * UUID::parse(" \t ---- { urn:uuid:----0123-4567-89ab-cdef-0123-4567-89ab-cdef---- } ---- \t ");
		 *
		 * // However, note that there cannot be whitespace or braces between the
		 * // URN scheme and the UUID itUUID.
		 * try {
		 *     UUID::parse('urn:uuid:{01234567-89ab-cdef-0123-456789abcdef');
		 * }
		 * catch (UUIDParseException $e) {
		 *     assert($e->getMessage() === 'Expected hexadecimal digit, but found '{' (0x7b)');
		 * }
		 *
		 * ?>
		 * ```
		 *
		 * [wrp]: https://en.wikipedia.org/wiki/Robustness_principle
		 * @since 7.2
		 * @see https://php.net/uuid.parse
		 * @see toHex
		 * @see toString
		 * @param string $input to parse as UUID and construct the instance from.
		 * @return \UUID UUID constructed from the parsed input.
		 * @throws \UUIDParseException if parsing of the input fails.
		 */
		public static function parse($input) {
			$input = ltrim($input, " \t{-");

			if (strpos($input, 'urn:uuid:') === 0) {
				$input = (string) substr($input, 9);
			}

			$input = rtrim($input, " \t}-");

			if (strlen($input) < 32) {
				throw new UUIDParseException('Expected at least 32 hexadecimal digits, but got ' . strlen($input), $input);
			}

			$input  = str_replace('-', '', $input);
			$binary = @hex2bin($input);
			if ($binary === false) {
				for ($position = 0, $limit = strlen($input); $position < $limit; ++$position) {
					$char = $input{$position};
					if (
						($char < '0' || $char > '9') &&
						($char < 'a' || $char > 'f') &&
						($char < 'A' || $char > 'F')
					) {
						throw new UUIDParseException(sprintf(
							"Expected hexadecimal digit, but found '{$char}' (0x%02x)",
							ord($char)
						), $input, $position);
					}
				}
			}

			$length = strlen($binary === false ? $input : $binary);
			if ($length < 16) {
				throw new UUIDParseException('Expected at least 32 hexadecimal digits, but got ' . ($length * 2), $input, $length - 1);
			}
			if ($length > 16) {
				throw new UUIDParseException('Expected no more than 32 hexadecimal digits', $input, $length - 1);
			}

			$uuid = new UUID;
			$uuid->bytes = $binary;
			return $uuid;
		}

		/**
		 * Construct new version 3 UUID.
		 *
		 * > RFC 4122 recommends {@see v5} over this one and states that version 3
		 * > UUIDs should be used if backwards compatibility is required only. This
		 * > is because MD5 has a higher collision probability compared to SHA1,
		 * > which is used by version 5; regardless of the truncation!
		 *
		 * Version 3 UUIDs are generated by MD5 hashing the concatenated
		 * namespace's byte representation and the given name. The namespace itself
		 * must be another UUID. This can be any UUID, or one of the predefined
		 * ones:
		 *
		 * - {@see NamespaceDNS}
		 * - {@see NamespaceOID}
		 * - {@see NamespaceURL}
		 * - {@see NamespaceX500}
		 *
		 * A particular name within the same namespace always results in the same
		 * version 3 UUID, across all RFC 4122 compliant UUID implementations.
		 * However, the namespace and name cannot be determined from the UUID
		 * alone.
		 *
		 * ## Examples
		 * ```
		 * <?php
		 *
		 * $uuid = UUID::v3(UUID::NamespaceDNS(), 'php.net');
		 *
		 * assert($uuid->isNil()      === false);
		 * assert($uuid->getVariant() === UUID::VARIANT_RFC4122);
		 * assert($uuid->getVersion() === UUID::VERSION_3_NAME_BASED_MD5);
		 * assert($uuid->toString()   === '11a38b9a-b3da-360f-9353-a5a725514269');
		 * assert($uuid               ==  UUID::v3(UUID::NamespaceDNS(), 'php.net'));
		 *
		 * ?>
		 * ```
		 *
		 * @since 7.2
		 * @see https://php.net/uuid.v3
		 * @see https://tools.ietf.org/html/rfc4122#section-4.3
		 * @see https://en.wikipedia.org/wiki/Universally_unique_identifier#Versions_3_and_5_.28namespace_name-based.29
		 * @param \UUID $namespace to construct the UUID in.
		 * @param string $name to construct the UUID from.
		 * @return \UUID UUID constructed from the name in the namespace.
		 * @throws \Exception if the namespace does not encapsulate a valid UUID.
		 */
		public static function v3(self $namespace, $name) {
			$uuid = new UUID;

			$uuid->bytes    = md5($namespace->toBinary() . $name, true);
			$uuid->bytes{6} = chr((ord($uuid->bytes{6}) & 0b00001111) | 0b00110000);
			$uuid->bytes{8} = chr((ord($uuid->bytes{8}) & 0b00111111) | 0b10000000);

			return $uuid;
		}

		/**
		/**
		 * Construct new version 4 UUID.
		 *
		 * Version 4 UUIDs are randomly generated from the best available random
		 * source. The selection of that source is determined by PHP's
		 * {@see random_bytes} implementation. Some systems may be bad at
		 * generating sufficient entropy, e.g. virtual machines. This might lead to
		 * collisions faster than desired. If this is the case, the {@see v5}
		 * version should be used.
		 *
		 * ## Examples
		 * ```
		 * <?php
		 *
		 * $uuid = UUID::v4();
		 *
		 * assert($uuid->isNil()      === false);
		 * assert($uuid->getVariant() === UUID::VARIANT_RFC4122);
		 * assert($uuid->getVersion() === UUID::VERSION_4_RANDOM);
		 * assert($uuid               !=  UUID::v4());
		 *
		 * ?>
		 * ```
		 *
		 * @since 7.2
		 * @see https://php.net/uuid.v4
		 * @see https://tools.ietf.org/html/rfc4122#section-4.4
		 * @see https://en.wikipedia.org/wiki/Universally_unique_identifier#Version_4_.28random.29
		 * @return \UUID UUID constructed from random data.
		 * @throws \Exception if it was not possible to gather sufficient entropy.
		 */
		public static function v4() {
			$uuid = new UUID;

			$uuid->bytes    = random_bytes(16);
			$uuid->bytes{6} = chr((ord($uuid->bytes{6}) & 0b00001111) | 0b01000000);
			$uuid->bytes{8} = chr((ord($uuid->bytes{8}) & 0b00111111) | 0b10000000);

			return $uuid;
		}

		/**
		 * Construct new version 5 UUID.
		 *
		 * Version 5 UUIDs are generated by MD5 hashing the concatenated
		 * namespace's byte representation and the given name. The namespace itself
		 * must be another UUID. This can be any UUID, or one of the predefined
		 * ones:
		 *
		 * - {@see NamespaceDNS}
		 * - {@see NamespaceOID}
		 * - {@see NamespaceURL}
		 * - {@see NamespaceX500}
		 *
		 * A particular name within the same namespace always results in the same
		 * version 5 UUID, across all RFC 4122 compliant UUID implementations.
		 * However, the namespace and name cannot be determined from the UUID
		 * alone.
		 *
		 * ## Examples
		 * ```
		 * <?php
		 *
		 * $uuid = UUID::v5(UUID::NamespaceDNS(), 'php.net');
		 *
		 * assert($uuid->isNil()      === false);
		 * assert($uuid->getVariant() === UUID::VARIANT_RFC4122);
		 * assert($uuid->getVersion() === UUID::VERSION_5_NAME_BASED_SHA1);
		 * assert($uuid->toString()   === 'c4a760a8-dbcf-5254-a0d9-6a4474bd1b62');
		 * assert($uuid               ==  UUID::v5(UUID::NamespaceDNS(), 'php.net'));
		 *
		 * ?>
		 * ```
		 *
		 * @since 7.2
		 * @see https://php.net/uuid.v5
		 * @see @see https://tools.ietf.org/html/rfc4122#section-4.3
		 * @see https://en.wikipedia.org/wiki/Universally_unique_identifier#Versions_3_and_5_.28namespace_name-based.29
		 * @param \UUID $namespace to construct the UUID in.
		 * @param string $name to construct the UUID from.
		 * @return \UUID UUID constructed from the name in the namespace.
		 * @throws \Exception if the namespace does not encapsulate a valid UUID.
		 */
		public static function v5(self $namespace, $name) {
			$uuid = new UUID;

			$uuid->bytes    = substr(sha1($namespace->toBinary() . $name, true), 0, 16);
			$uuid->bytes{6} = chr((ord($uuid->bytes{6}) & 0b00001111) | 0b01010000);
			$uuid->bytes{8} = chr((ord($uuid->bytes{8}) & 0b00111111) | 0b10000000);

			return $uuid;
		}

		/**
		 * Construct new Domain Name System (DNS) namespace UUID instance.
		 *
		 * @since 7.2
		 * @see https://php.net/uuid.NamespaceDNS
		 * @see https://tools.ietf.org/html/rfc4122#appendix-C
		 * @see https://en.wikipedia.org/wiki/Domain_Name_System
		 * @return \UUID Predefined DNS namespace UUID.
		 */
		public static function NamespaceDNS() {
			$uuid = new UUID;
			$uuid->bytes = "\x6b\xa7\xb8\x10\x9d\xad\x11\xd1\x80\xb4\x00\xc0\x4f\xd4\x30\xc8";
			return $uuid;
		}

		/**
		 * Construct new Object Identifier (OID) namespace UUID instance.
		 *
		 * @since 7.2
		 * @see https://php.net/uuid.NamespaceOID
		 * @see https://tools.ietf.org/html/rfc4122#appendix-C
		 * @see https://en.wikipedia.org/wiki/Object_identifier
		 * @return \UUID Predefined OID namespace UUID.
		 */
		public static function NamespaceOID() {
			$uuid = new UUID;
			$uuid->bytes = "\x6b\xa7\xb8\x12\x9d\xad\x11\xd1\x80\xb4\x00\xc0\x4f\xd4\x30\xc8";
			return $uuid;
		}

		/**
		 * Construct new Uniform Resource Locator (URL) namespace UUID instance.
		 *
		 * @since 7.2
		 * @see https://php.net/uuid.NamespaceURL
		 * @see https://tools.ietf.org/html/rfc4122#appendix-C
		 * @see https://en.wikipedia.org/wiki/URL
		 * @return \UUID Predefined URL namespace UUID.
		 */
		public static function NamespaceURL() {
			$uuid = new UUID;
			$uuid->bytes = "\x6b\xa7\xb8\x11\x9d\xad\x11\xd1\x80\xb4\x00\xc0\x4f\xd4\x30\xc8";
			return $uuid;
		}

		/**
		 * Construct new X.500 Distinguished Names (X.500 DN) namespace UUID
		 * instance. The names that are to be hashed in this namespace can be in
		 * DER or a text output format.
		 *
		 * @since 7.2
		 * @see https://php.net/uuid.NamespaceX500
		 * @see https://tools.ietf.org/html/rfc4122#appendix-C
		 * @see https://en.wikipedia.org/wiki/X.500
		 * @see https://en.wikipedia.org/wiki/Lightweight_Directory_Access_Protocol
		 * @return \UUID Predefined X.500 namespace UUID instance.
		 */
		public static function NamespaceX500() {
			$uuid = new UUID;
			$uuid->bytes = "\x6b\xa7\xb8\x14\x9d\xad\x11\xd1\x80\xb4\x00\xc0\x4f\xd4\x30\xc8";
			return $uuid;
		}

		/**
		 * Construct special nil UUID that has all 128 bits set to zero.
		 *
		 * @since 7.2
		 * @see https://php.net/uuid.Nil
		 * @see https://tools.ietf.org/html/rfc4122#section-4.1.7
		 * @see https://en.wikipedia.org/wiki/Universally_unique_identifier#Nil_UUID
		 * @return \UUID Predefined special nil UUID.
		 */
		public static function Nil() {
			$uuid = new UUID;
			$uuid->bytes = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
			return $uuid;
		}

		/**
		 * Callback for dynamic adding of properties which throws an {@see Exception}
		 * upon every invocation, direct or indirect. This is necessary to protect
		 * the promised immutability of this object. Not doing so could lead to
		 * problems with the comparison operators, since PHP always compares all
		 * properties.
		 *
		 * @since 7.2
		 * @see https://php.net/uuid.__set
		 * @param mixed $_
		 * @param mixed $__
		 * @return void
		 * @throws \Exception upon every invocation, direct or indirect.
		 */
		public function __set($_, $__) {
			throw new Exception('Cannot set dynamic properties on immutable ' . __CLASS__ . ' object');
		}

		/**
		 * Deserialization callback.
		 *
		 * @since 7.2
		 * @see https://php.net/uuid.__wakeup
		 * @see unserialize()
		 * @return void
		 * @throws \UnexpectedValueException if the value of the {@see bytes}
		 *     property is not of type string, or not exactly 16 bytes long.
		 */
		public function __wakeup() {
			if (is_string($this->bytes) === false) {
				$type = 'unknown';

				if (is_array($this->bytes)) {
					$type = 'array';
				}
				elseif ($this->bytes === true || $this->bytes === false) {
					$type = 'boolean';
				}
				elseif (is_float($this->bytes)) {
					$type = 'float';
				}
				elseif (is_int($this->bytes)) {
					$type = 'integer';
				}
				elseif ($this->bytes === null) {
					$type = 'null';
				}
				elseif (is_object($this->bytes)) {
					$type = 'object';
				}
				elseif (is_resource($this->bytes)) {
					$type = 'resource';
				}

				throw new UnexpectedValueException('Expected ' . __CLASS__ . '::$bytes value to be of type string, but found ' . $type);
			}

			if (strlen($this->bytes) !== 16) {
				throw new UnexpectedValueException('Expected ' . __CLASS__ . '::$bytes value to be exactly 16 bytes long, but found ' . strlen($this->bytes));
			}
		}

		/**
		 * Get the variant associated with this UUID.
		 *
		 * The variant specifies the internal data layout of a UUID. This
		 * implementation generates {@see UUID::VARIANT_RFC4122} UUIDs only,
		 * however, parsing and construction of other variants is supported.
		 *
		 * @since 7.2
		 * @see https://php.net/uuid.getVariant
		 * @see https://tools.ietf.org/html/rfc4122#section-4.1.1
		 * @see https://en.wikipedia.org/wiki/Universally_unique_identifier#Variants
		 * @see UUID::VARIANT_NCS
		 * @see UUID::VARIANT_RFC4122
		 * @see UUID::VARIANT_MICROSOFT
		 * @see UUID::VARIANT_FUTURE_RESERVED
		 * @return int An integer in [0, 3] where each value corresponds to one of
		 *     the `UUID::VARIANT_*` class constants.
		 * @throws \Exception if this instance does not encapsulate a valid UUID.
		 */
		public function getVariant() {
			$bin = $this->toBinary();
			$ord = ord($bin{8});
			if (($ord & 0xC0) === 0x80) return static::VARIANT_RFC4122;
			if (($ord & 0xE0) === 0xC0) return static::VARIANT_MICROSOFT;
			if (($ord & 0x80) === 0x00) return static::VARIANT_NCS;
			return static::VARIANT_FUTURE_RESERVED;
		}

		/**
		 * Get the version associated with this UUID.
		 *
		 * The version specifies which algorithm was used to generate the UUID.
		 * Note that the version might not be meaningful if another variant than
		 * the {@see UUID::VARIANT_RFC4122} was used to generate the UUID. This
		 * implementation generates {@see UUID::VARIANT_RFC4122} UUIDs only, but
		 * allows parsing and construction of other variants.
		 *
		 * @since 7.2
		 * @see https://php.net/uuid.getVersion
		 * @see https://tools.ietf.org/html/rfc4122#section-4.1.3
		 * @see https://en.wikipedia.org/wiki/Universally_unique_identifier#Versions
		 * @see UUID::VERSION_1_TIME_BASED
		 * @see UUID::VERSION_2_DCE_SECURITY
		 * @see UUID::VERSION_3_NAME_BASED_MD5
		 * @see UUID::VERSION_4_RANDOM
		 * @see UUID::VERSION_5_NAME_BASED_SHA1
		 * @return int An integer in [0, 15], the values [1, 5] correspond to one
		 *     of the `UUID::VERSION_*` class constants. The others are not defined
		 *     in RFC 4122.
		 * @throws \Exception if this instance does not encapsulate a valid UUID.
		 */
		public function getVersion() {
			$bin = $this->toBinary();
			return ord($bin{6}) >> 4;
		}

		/**
		 * Check if this UUID is the special nil UUID that has all 128 bits set to
		 * zero.
		 *
		 * @since 7.2
		 * @see https://php.net/uuid.isNil
		 * @see https://tools.ietf.org/html/rfc4122#section-4.1.7
		 * @see https://en.wikipedia.org/wiki/Universally_unique_identifier#Nil_UUID
		 * @see Nil
		 * @return bool **TRUE** if this is the special nil UUID; **FALSE**
		 *     otherwise.
		 * @throws \Exception if this instance does not encapsulate a valid UUID.
		 */
		public function isNil() {
			return $this->toBinary() === "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
		}

		/**
		 * Convert the UUID to its binary representation.
		 *
		 * The binary representation of a UUID is a string of exactly 16 bytes. It
		 * is the format that is used internally. It is also the format that should
		 * be used to store UUIDs in a database (e.g. in MySQL as `BINARY(16)`
		 * column). The resulting string, if generated by this implementation, is
		 * always in network byte order.
		 *
		 * ## Examples
		 * ```
		 * <?php
		 *
		 * assert(UUID::NamespaceDNS()->toBinary() === "\x6b\xa7\xb8\x10\x9d\xad\x11\xd1\x80\xb4\x00\xc0\x4f\xd4\x30\xc8");
		 *
		 * ?>
		 * ```
		 *
		 * @since 7.2
		 * @see https://php.net/uuid.toBinary
		 * @return string Binary representation of the UUID.
		 * @throws \Exception if this instance does not encapsulate a valid UUID.
		 */
		public function toBinary() {
			if (is_string($this->bytes) === false) {
				$type = 'unknown';

				if (is_array($this->bytes)) {
					$type = 'array';
				}
				elseif ($this->bytes === true || $this->bytes === false) {
					$type = 'boolean';
				}
				elseif (is_float($this->bytes)) {
					$type = 'float';
				}
				elseif (is_int($this->bytes)) {
					$type = 'integer';
				}
				elseif ($this->bytes === null) {
					$type = 'null';
				}
				elseif (is_object($this->bytes)) {
					$type = 'object';
				}
				elseif (is_resource($this->bytes)) {
					$type = 'resource';
				}

				throw new \Exception('Expected ' . __CLASS__ . '::$bytes value to be of type string, but found ' . $type);
			}

			if (strlen($this->bytes) !== 16) {
				throw new \Exception('Expected ' . __CLASS__ . '::$bytes value to be exactly 16 bytes long, but found ' . strlen($this->bytes));
			}

			return $this->bytes;
		}

		/**
		 * Convert the UUID to its hexadecimal representation.
		 *
		 * The hexadecimal representation of a UUID are 32 hexadecimal digits. The
		 * hexadecimal digits `a` through `f` are always formatted as lower case
		 * characters, in accordance with RFC 4122.
		 *
		 * ## Examples
		 * ```
		 * <?php
		 *
		 * assert(UUID::NamespaceDNS()->toHex() === '6ba7b8109dad11d180b400c04fd430c8');
		 *
		 * ?>
		 * ```
		 *
		 * @since 7.2
		 * @see https://php.net/uuid.toHex
		 * @return string Hexadecimal representation of the UUID.
		 * @throws \Exception if this instance does not encapsulate a valid UUID.
		 */
		public function toHex() {
			return bin2hex($this->toBinary());
		}

		/**
		 * Convert the UUID to its string representation.
		 *
		 * The string representation of a UUID are 32 hexadecimal digits separated
		 * by a hyphen into five groups of 8, 4, 4, 4, and 12 digits. The
		 * hexadecimal digits `a` through `f` are always formatted as lower case
		 * characters, in accordance with RFC 4122.
		 *
		 * ## Examples
		 * ```
		 * <?php
		 *
		 * assert(UUID::NamespaceDNS()->toString() === '6ba7b810-9dad-11d1-80b4-00c04fd430c8');
		 *
		 * ?>
		 * ```
		 *
		 * @since 7.2
		 * @see https://php.net/uuid.toString
		 * @see https://tools.ietf.org/html/rfc4122#page-4
		 * @see https://en.wikipedia.org/wiki/Universally_unique_identifier#Format
		 * @return string String representation of the UUID.
		 * @throws \Exception if this instance does not encapsulate a valid UUID.
		 */
		public function toString() {
			$hex = $this->toHex();

			return substr($hex, 0, 8) . '-' .
			       substr($hex, 8, 4) . '-' .
			       substr($hex, 12, 4) . '-' .
			       substr($hex, 16, 4) . '-' .
			       substr($hex, 20);
		}

		/**
		 * Callback for cloning of objects. This method is private and effectively
		 * disables cloning of this object, since it makes no sense to clone
		 * immutable objects.
		 *
		 * @return void
		 * @throws \Exception upon every invocation.
		 */
		private function __clone() {
			throw new Exception('Cannot clone immutable ' . __CLASS__ . ' object');
		}
	}
}
