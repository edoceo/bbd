<?php
/**

*/

class S3
{

	function __construct()
	{
		$this->_c = Aws\S3\S3Client::factory(array(
			'key' => $_ENV['aws']['apikey'],
			'secret' => $_ENV['aws']['secret'],
			'region' => $_ENV['aws']['region'],
		));
	}

    /**
        @param $source Source Bucket:Object Key
        @param $target Local File to Save Into
    */
    public function get($source,$target)
    {
    	$bucket = strtok($source, ':');
    	$source = strtok($source);
    	$arg = array(
			'Bucket' => $bucket,
			'Key' => $source,
			'SaveAs' => $target,
		);
		print_r($arg);
        $r = $this->_c->getObject($arg);
		return $r;
    }

	/**
		@param $source FIle
		@param $target Bucket:Object
	*/
	function put($source,$target)
	{
		$bucket = strtok($target, ':');
		$target = strtok($target);
		$arg = array(
			'Bucket' => $bucket,
			'Key' => $target,
			'Body' => fopen($source,'r'),
			'StorageClass' => 'REDUCED_REDUNDANCY'
		);
		print_r($arg);
		$r = $this->_c->putObject($arg);
		return $r;
	}

}