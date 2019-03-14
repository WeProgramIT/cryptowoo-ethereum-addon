<?php

namespace BitWasp\Bitcoin\Network;

/** Class ETH_NetworkFactory
 *
 * @package BitWasp\Bitcoin\Network
 */
class ETH_Network_Factory extends CW_NetworkFactory {

	/** BitWasp factory
	 *
	 * @return Networks\ETH
	 * @throws \BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter Invalid Network Parameter exception.
	 */
	public static function ETH() {
		return new Networks\ETH();
	}
}
