<?php

namespace Craft;

use Anecka\RetsRabbit\Core\ApiService;
use Anecka\RetsRabbit\Core\Bridges\CraftBridge;
use Anecka\RetsRabbit\Core\Resources\PropertiesResource;

class RetsRabbit_PropertiesService extends BaseApplicationComponent
{
	/**
	 * The api service from the core RR library
	 * 
	 * @var ApiService
	 */
	private $api;

	/**
	 * The properties resource endpoint
	 * 
	 * @var PropertiesResource
	 */
	private $resource;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$settings = craft()->plugins->getPlugin('retsRabbit')->getSettings();
		$bridge = new CraftBridge;

		//Set the token fetcher function so the core lib can grab tokens
		//from cache on the plugin's behalf
		$bridge->setTokenFetcher(function () {
			return craft()->retsRabbit_cache->get('access_token', true);
		});

		//Load the Craft Bridge into the ApiService
		$this->api = new ApiService($bridge);

		//Allow developer to override base endpoint
		if($settings->apiEndpoint) {
			$this->api->overrideBaseApiEndpoint($settings->apiEndpoint);
		}

		//Instantiate the PropertiesResource
		$this->resource = new PropertiesResource($this->api);
	}

	/**
	 * @param  array
	 * @return array
	 */
	public function search($params = array())
	{
		$res = $this->resource->search($params);

		if($res->didFail()) {
			$contents = $res->getResponse();

			if(isset($contents['error']) && isset($contents['error']['code'])) {
				RetsRabbitPlugin::log('A permission error occurred.', LogLevel::Error);
				
				$code = $contents['error']['code'];

				if($code == 'permission') {
					$success = craft()->retsRabbit_tokens->refresh();

					if(!is_null($success)) {
						$res = $this->resource->search($params);
					} else {
						RetsRabbitPlugin::log('Could not refresh the token during a search.', LogLevel::Error);
					}
				}
			}
		}

		return $res;
	}

	/**
	 * @param  string
	 * @return array
	 */
	public function find($id = '', $params = array())
	{
		$res = $this->resource->single($id, $params);

		if($res->didFail()) {
			$contents = $res->getResponse();

			if(isset($contents['error']) && isset($contents['error']['code'])) {
				RetsRabbitPlugin::log('A permission error occurred.', LogLevel::Error);
				
				$code = $contents['error']['code'];

				if($code == 'permission') {
					$success = craft()->retsRabbit_tokens->refresh();

					if(!is_null($success)) {
						$res = $this->resource->single($id, $params);
					} else {
						RetsRabbitPlugin::log('Could not refresh the token during property lookup.', LogLevel::Error);
					}
				}
			}
		}

		return $res;
	}
}