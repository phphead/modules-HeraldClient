<?php namespace Modules;

use Modules\HeraldClient\Result as HeraldClientResult,
	Assert;

class HeraldClient {

	const TYPE_EMAIL     = 1;
	const TYPE_SMS       = 2;

	protected $_config;

	protected function _request(array $config, $content) {
		$headers = [
			"Content-Type: application/x-www-form-urlencoded",
			"Content-Length: " . strlen($content),
		];

		if ($config['hostname']) {
			$headers[] = "Host: " . $config['hostname'];
		}

		$headers[] = "";

		$context = stream_context_create([
			'http' => [
				'header'  => implode("\r\n", $headers),
				'method'  => 'POST',
				'content' => $content,
				'timeout' => 10,
			],
		]);

		$raw = file_get_contents($config['url'], null, $context);
		Assert::true(false !== $raw, 'Connection error');

		$json = @ json_decode($raw, true);
		Assert::true($json, 'Bad data format');

		$result = new HeraldClientResult();

		if ( ! empty($json['error'])) {
			$result->error = $json['error'];

		} else if ( ! empty($json['id'])) {
			$result->id = $json['id'];

		} else if ( ! empty($json['info'])) {
			$result->info = $json['info'];
		}

		return $result;
	}

	public function __construct(array $config) {
		$this->_config = $config + [
			'url'             => '',
			'access_key'      => '',
			'secret_key_hash' => '',
			'hostname'        => '',
		];
	}

	public function sendEmail($to, $subject, $text, array $options = []) {
		$config = & $this->_config;

		Assert::true($config['url']);
		Assert::true($config['access_key']);

		$content = http_build_query([
            'action'          => 'send_email',
			'access_key'      => $config['access_key'],
		    'secret_key_hash' => $config['secret_key_hash'],
		    'target'          => $to,
		    'subject'         => $subject,
		    'content'         => $text,
		] + $options);

		return self::_request($config, $content);
	}

	public function info($id) {
		$config = & $this->_config;

		Assert::true($config['url']);
		Assert::true($config['access_key']);

		$content = http_build_query([
			'action'          => 'info',
			'access_key'      => $config['access_key'],
			'secret_key_hash' => $config['secret_key_hash'],
			'id'              => $id,
		]);

		return self::_request($config, $content);
	}

	public function sendSms($to, $text) {
		$config = & $this->_config;

		Assert::true($config['url']);
		Assert::true($config['access_key']);

		$content = http_build_query([
			'action'          => 'send_sms',
			'access_key'      => $config['access_key'],
			'secret_key_hash' => $config['secret_key_hash'],
			'type_id'         => self::TYPE_EMAIL,
			'target'          => $to,
			'content'         => $text,
		]);

		return self::_request($config, $content);
	}

}
