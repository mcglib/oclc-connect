<?php namespace McGill;

/**
 * This class simplifies value retrieval from an array tree.
 */
class ArrayTreeValueRetriever {
	/**
	 * The arrayTree array.
	 *
	 * @var array
	 */
	protected $arrayTree;
	
	/**
	 * The cache array which stores previously parsed keys.
	 *
	 * @var array
	 */
	protected $cache;
	
	/**
	 * The references array which stores key-value pairs for dereferencing keys.
	 *
	 * @var array
	 */
	protected $references;

	/**
	 * Creates an ArrayTreeValueRetriever instance.
	 * 
	 * @param array $arrayTree_P
	 * @return void
	 */
	public function __construct(array $arrayTree_P){
		$this->arrayTree = $arrayTree_P;
		$this->cache = array();
		$this->references = array();
	}

	/**
	 * Gets key-value references.
	 * 
	 * @return void
	 */
	public function getReferences(){
		return $this->references;
	}

	/**
	 * Sets key-value references.
	 * 
	 * @param array $references_P
	 * @return void
	 */
	public function setReferences(array $references_P){
		$this->references = $references_P;
	}
	
	/**
	 * Dereferences the string of dot-separated array keys using key-value pairs in $this->references.
	 * 
	 * @param string $key_P
	 * @return string
	 */
	protected function dereference($key_P){
		$result = str_replace(array_keys($this->references), array_values($this->references), $key_P);
		return $result ? $result : null;
	}
	
	/**
	 * Gets the entire arrayTree array, or a specific value if a string of dot-separated array keys is passed. 
	 * It is also possible to use reference values provided that they are defined with setReferences(). This method
	 * caches keys in order to avoid repeated processing.
	 *
	 * Example 1:
	 * 		$this->get()
	 * returns:
	 * 		$this->arrayTree
	 *
	 * Example 2:
	 * 		$this->get('a.b.c.d.e.f')
	 * returns:
	 * 		$this->arrayTree['a']['b']['c']['d']['e']['f'].
	 *
	 * Example 3:
	 * 		$this->setReferences(array(
	 * 			'%x' => 'b',
	 *			'%y' => 'd',
	 * 			'%z' => 'f',
	 * 		));
	 * 		$this->get('a.%x.c.%y.e.%z')
	 * returns:
	 * 		$this->arrayTree['a']['b']['c']['d']['e']['f'].
	 * 
	 * @param string $key_P
	 * @return mixed
	 */
	public function get($key_P = null){
		$key_P = $this->dereference($key_P);

		if (isset($this->cache[$key_P])){
			return $this->cache[$key_P];
		}
		
		$result = $this->arrayTree;

		if (!empty($key_P)){
			foreach (explode('.', $key_P) as $key){
				if (isset($result[$key])){
					$result = $result[$key];
				} else {
					throw new \Exception("Invalid key '{$key}' in '{$key_P}'.");
				}
			}
		}

		return $this->cache[$key_P] = $result;
	}
	
	/**
	 * Checks if the string of dot-separated array keys is defined.
	 * 
	 * @param string $key_P
	 * @return bool
	 */
	public function exists($key_P){
		try {
			$this->get($key_P);
			return true;
		} catch (\Exception $e){
			return false;
		}
	}
}