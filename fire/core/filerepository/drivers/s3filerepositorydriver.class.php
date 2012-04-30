<?php

class S3FileRepositoryDriver extends FileRepositoryDriver
{

    function create($destination_filename, $source_filepath)
    {
        $response = $this->s3()->create_object($this->options['bucket'], $destination_filename, array(
                                                                                                    'fileUpload' => $source_filepath,
                                                                                                    'acl' => AmazonS3::ACL_PUBLIC,
                                                                                               ));
    }

    function delete($filename)
    {
        $response = $this->s3()->delete_object($this->bucket(), $filename);
    }

    function exists($filename)
    {
        return $this->s3()->if_object_exists($this->bucket(), $filename);
    }

    function url($filename)
    {
        return $this->s3()->get_object_url($this->bucket(), $filename);
    }

    function get_file_names()
    {
        return $this->s3()->get_object_list($this->bucket());
    }
    
    private function bucket()
    {
        return $this->options['bucket'];
    }

    private $s3;
    /**
     * @return AmazonS3
     */
    private function s3()
    {
        if ($this->s3 == NULL) {
            $this->set_s3_credentials();
            $this->s3 = new AmazonS3();
            $this->s3()->use_ssl = false;
        }
        return $this->s3;
    }

    private function set_s3_credentials()
    {
        /**
         * Create a list of credential sets that can be used with the SDK.
         */
        CFCredentials::set(array(
        	// Credentials for the development environment.
        	'development' => array(

        		// Amazon Web Services Key. Found in the AWS Security Credentials. You can also pass
        		// this value as the first parameter to a service constructor.
        		'key' => $this->options['amazon_public_key'],

        		// Amazon Web Services Secret Key. Found in the AWS Security Credentials. You can also
        		// pass this value as the second parameter to a service constructor.
        		'secret' => $this->options['amazon_secret_key'],

        		// This option allows you to configure a preferred storage type to use for caching by
        		// default. This can be changed later using the set_cache_config() method.
        		//
        		// Valid values are: `apc`, `xcache`, or a file system path such as `./cache` or
        		// `/tmp/cache/`.
        		'default_cache_config' => '',

        		// Determines which Cerificate Authority file to use.
        		//
        		// A value of boolean `false` will use the Certificate Authority file available on the
        		// system. A value of boolean `true` will use the Certificate Authority provided by the
        		// SDK. Passing a file system path to a Certificate Authority file (chmodded to `0755`)
        		// will use that.
        		//
        		// Leave this set to `false` if you're not sure.
        		'certificate_authority' => false
        	),

        	// Specify a default credential set to use if there are more than one.
        	'@default' => 'development'
        ));
    }

}
