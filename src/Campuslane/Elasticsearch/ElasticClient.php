<?php namespace Campuslane\Elasticsearch;

use Elasticsearch;  // this is the official Elasticsearch php client class

/**
 * ElasticClient Class
 * This is our package class that we extend in our application.  It provides methods etc that 
 * are not application specific.
 */
class ElasticClient  {

	/**
	 * Client
	 * This is the elasticsearch client
	 * @var object
	 */
	public $client;

	/**
	 * Constructor
	 * Sets up the Elasticsearch Client
	 */
	public function __construct()
	{
		$this->client = new Elasticsearch\Client();
	}


	/**
	 * Create Index
	 * Create a new elasticsearch index.  If we get a bad request exception,
	 * we're assuming the index already exists.	
	 * @param  string $index index name
	 * @return integer  
	 */
	public function createIndex($index)
	{
		$data['index'] = $index;

		// try to create the index
		try
		{
			$response = $this->client->indices()->create($data);
		} 

		// if we got the bad request 400 exception, the index already existed
		catch (Elasticsearch\Common\Exceptions\BadRequest400Exception $e) 
		{
			return 2;	
		}

		return 1;
	}


	/**
	 * Drop Index
	 * Delete an elastic search index.  If we get the missing 404 exception,
	 * we return 2, otherwise we return 1.  Either way it should mean that the 
	 * index no longer exists.
	 * 
	 * @param  [type] $index [description]
	 * @return [type]        [description]
	 */
	public function dropIndex($index)
	{
		$data['index'] = $index;

		// try to delete the index
		try
		{
			$response = $this->client->indices()->delete($data);
		} 

		// if we got the missing 404 exception, there was no index to delete
		catch (Elasticsearch\Common\Exceptions\Missing404Exception $e) 
		{
			return 2;	
		}
		
		return  1;
	}


	/**
	 * Get Randomizer Seed
	 * We need some kind of numeric to use as a seed for the elasticsearch
	 * random_score function.  We'll use the session token reduced to only 
	 * numerics.  If none, then we just use a random string
	 * 	
	 * @return integer 
	 */
	public function getRandomizerSeed()
	{
		// get the session token or default to random number
		$session_id = \Session::get('_token') ?: 123;
		
		// strip out non-numeric characters to create randomizer seed
		return preg_replace('/[^0-9]/','',$session_id);
	}

	/**
	 * Remove URL Query Parameter
	 * Takes a url and the parameter to remove from the query string, 
	 * and returns the url with the parameter removed
	 * @param  string $url    
	 * @param  string $remove Key value of parameter to be removed
	 * @return string   The updated url
	 */
	protected function  removeUrlQueryParam($url,$remove) 
	{
		// parse the url
	    	$urlInfo=parse_url($url);
	    	
	    	// if no query string, just return the existing url
	    	if ( ! isset($urlInfo["query"]) ) return $url;

	    	// set the url string
	    	$str=$urlInfo["query"];
	    
	    	// initialize parameters array
	    	$parameters = [];

	    	// get the key value pairs
	    	$pairs = explode("&", $str);

	    	// loop through pairs
		foreach ($pairs as $pair) 
		{
			// set the parameters array
			list($k, $v) = array_map("urldecode", explode("=", $pair));
			$parameters[$k] = $v;
		}

		// remove the parameter we wanted to remove
		if(isset($parameters[$remove]))
		{
		    	unset($parameters[$remove]);
		}

		// build back the url string and return it
	    	return str_replace($str,http_build_query($parameters),$url);

	} 


	/**
	 * Pagination
	 * Creates the pagination links:
	 * 
	 * $page = the current page number
	 * $total = total number of listings returned
	 * $perPage = number of listings to show per page
	 * 
	 * @param  array $pages
	 * @return string Html to create pagination links
	 */
	public function paginationLinks($pages)
	{
		// if the pages is empty, doesn't exist, etc.
		if ( ! $pages ) return '';

		// extract array vars
		extract($pages);

		// if we didn't get a base url
		if ( ! isset($base_url) ) 
		{
			$base_url = \Request::fullUrl();
		}

		// remove any current page parameter from query string
		$base_url = $this->removeUrlQueryParam($base_url, 'p');

		// set the concatenator
		$concatenator = ( ! isset( $_SERVER['QUERY_STRING']) ) ? '?' : '&';
	
		// set next and previous page numbers
		$nextPageNumber = $page + 1;
		$previousPageNumber = (($page - 1) > 0) ? $page - 1 : 0;

		// calculate total pages
		$total_pages = ceil($total / $perPage);

		// set the previous page link and empty link
		$previousLabel = 'Previous Page';
		$previousPageLink = '<a href="'. $base_url . $concatenator . 'p='. $previousPageNumber .'">' . $previousLabel . '</a>';

		// set the next page link and empty link
		$nextLabel = "Next Page";
		$nextPageLink = '<a href="'. $base_url . $concatenator . 'p='. $nextPageNumber .'">' . $nextLabel. '</a>';
		
		// set the links
		$nextPage = ($page < $total_pages) ? $nextPageLink : $nextLabel;
		$previousPage = ($page > 1) ? $previousPageLink : $previousLabel;

		return $total . ' Total Listings &nbsp; &nbsp; Page ' . $page . ' of ' . $total_pages . ' &nbsp; &nbsp; ' . $previousPage . ' &nbsp; &nbsp; ' . $nextPage;
		
	}
}