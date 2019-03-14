<?php
/** BitWasp class
 *
 * @package CryptoWoo Ethereum Addon
 * @subpackage BitWasp
 */

namespace BitWasp\Bitcoin\Network\Networks;

use BitWasp\Bitcoin\Network\Network;
use BitWasp\Bitcoin\Script\ScriptType;

/**
 * Class ETH
 *
 * @package BitWasp\Bitcoin\Network\Networks
 */
class ETH extends Network {

	/**
	 * {@inheritdoc}
	 * @see Network::$base58PrefixMap
	 *
	 * Values are base58 prefixes in Hexadecimal.
	 */
	protected $base58PrefixMap = [
		self::BASE58_ADDRESS_P2PKH => 'ff',	 // public prefix (PUBKEY_ADDRESS).
		self::BASE58_ADDRESS_P2SH  => 'ff', // private prefix (SCRIPT_ADDRESS).
		self::BASE58_WIF           => 'ff', // scripthash prefix (SECRET_KEY).

	];

	/**
	 * {@inheritdoc}
	 * @see Network::$bip32PrefixMap
	 */
	protected $bip32PrefixMap = [
		self::BIP32_PREFIX_XPUB => 'ffffffff', // extended prefix xpub public (EXT_PUBLIC_KEY).
		self::BIP32_PREFIX_XPRV => 'ffffffff', // extended prefix xpub private (EXT_SECRET_KEY).
	];

	/**
	 * {@inheritdoc}
	 * @see Network::$bip32ScriptTypeMap
	 */
	protected $bip32ScriptTypeMap = [
		self::BIP32_PREFIX_XPUB => ScriptType::P2PKH,
		self::BIP32_PREFIX_XPRV => ScriptType::P2PKH,
	];

	/**
	 * {@inheritdoc}
	 * @see Network::$signedMessagePrefix
	 */
	protected $signedMessagePrefix = 'Ethereum Signed Message';

	/**
	 * {@inheritdoc}
	 * @see Network::$p2pMagic
	 */
	protected $p2pMagic = null; // Protocol magic (pchMessageStart[3] . pchMessageStart[2] . pchMessageStart[1] . pchMessageStart[0]).
}
