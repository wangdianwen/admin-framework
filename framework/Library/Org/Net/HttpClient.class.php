<?php
/*
 *Copyright (C)2010 1verge.com (http://www.youku.com)
 *file: HttpClient.class.php
 *class descripe:
 *  Http客户端调用类
 *Author: hufan
 *Createtime: 2012.03.28
 *MSN:
 *Report Bugs:
 *Address:China BeiJing
 *Version:0.1.0
 *Latest modify time:
 */

namespace Org\Net;

class HttpClient {
	const TIMEOUT = 3;
	static public function httpGet($url, $timeout = 0, $json_decode = true, $ms = false) {
		if ($timeout < 1) {
			$timeout = self::TIMEOUT;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

		if ($ms) {
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout);
			curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
		} else {
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		}
		curl_setopt($ch, CURLOPT_URL, $url);

		$ret = curl_exec($ch);

		if (!$ret) {
			$info = curl_error($ch);
			//trigger_error($info.': '.$url);
		} else if ($json_decode) {
			$ret = json_decode($ret, true);
		}
		curl_close($ch);
		return $ret;
	}

	static public function httpPost($url, $data = array(), $timeout = 0, $json_decode = true, $ms = false) {
		if ($timeout < 1) {
			$timeout = self::TIMEOUT;
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

		if (is_array($data)) {
			$post_data = http_build_query($data);
		} else {
			$post_data = $data;
		}
		if ($ms) {
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout);
			curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
		} else {
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

		$ret = curl_exec($ch);
		if (!$ret) {
			$info = curl_error($ch);
			//trigger_error($info.': '.$url);
		} else if ($json_decode) {
			$ret = json_decode($ret, true);
		}
		curl_close($ch);
		return $ret;
	}

	static public function randomItem($set) {
		$count = count($set);
		if ($count < 1) {
			//trigger_error('set is empty');
			return false;
		} else if ($count == 1) {
			return $set[0];
		} else {
			return $set[mt_rand(0, $count - 1)];
		}
	}

	static public function rollingGet($urls, $timeout = 0, $json_decode = true) {
		if ($timeout < 1) {
			$timeout = self::TIMEOUT;
		}
		$delay = $timeout; // 毫秒
		$queue = curl_multi_init();
		$map = array();
		foreach ($urls as $url) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_NOSIGNAL, true);
			curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

			curl_multi_add_handle($queue, $ch);
			$map[strval($ch)] = $url;
		}

		$ret = array();
		do {
			while (($code = curl_multi_exec($queue, $active)) == CURLM_CALL_MULTI_PERFORM);

			if ($code != CURLM_OK) {
				break;
			}

			// a request was just completed -- find out which one
			while ($done = curl_multi_info_read($queue)) {
				$handle = $done['handle'];
				$data = self::httpCallback(curl_multi_getcontent($handle), $delay, $json_decode);
				$ret[$map[strval($handle)]] = $data;

				// remove the curl handle that just completed
				curl_multi_remove_handle($queue, $handle);
				curl_close($handle);
			}

			// Block for data in / output; error handling is done by curl_multi_exec
			if ($active > 0) {
				curl_multi_select($queue, $timeout);
			}
		} while ($active);

		curl_multi_close($queue);
		return $ret;
	}

	static private function httpCallback($data, $delay, $json_decode) {
		$delay > 3 and $delay = 3;
		usleep($delay);
		if (isset($data)) {
			$json_decode and $data = @json_decode($data, true);
		} else {
			$data = false;
		}
		return $data;
	}
}