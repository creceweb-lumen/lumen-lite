<?php
/**
 * Asset observer contract.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface AssetObserverInterface {
	/**
	 * @param string $type Asset type: style or script.
	 * @param string $handle WordPress asset handle.
	 * @return array{observed:bool,state:string,source?:string}
	 */
	public function observe( string $type, string $handle ): array;
}
