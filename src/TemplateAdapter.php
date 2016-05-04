<?php

namespace DotKernel\Expressive\DotRenderer;
use DotKernel\DotTemplate\DotTemplate;

class TemplateAdapter
{
	private $_callArray = array();
	
	private $_tpl; 
	
	/**
	 * @return the $_callArray
	 */
	public function getCallArray()
	{
		return $this->_callArray;
	}

	public function __construct(DotTemplate $tpl)
	{
		$this->_tpl = $tpl;
	}
	
	public function __call($method, $arguments)
	{
		if(stripos($method,'get') !== false )
		{
			return  call_user_func_array([$this->_tpl, $method], $arguments);
		}
		return $this->call($method, $arguments);
	}
	public function call($method, $arguments, $customName = false)
	{
		$this->_callArray[] = ['method'=>$method, 'arguments'=>$arguments, 'name'=>$customName, 'before'=>[], 'after'=>[]];
		end($this->_callArray);         // move the internal pointer to the end of the array
		$key = key($this->_callArray);  // fetches the key of the element pointed to by the internal pointer
		reset($this->_callArray);
		return $key; 
	}
	
	public function insertBefore($name, $method, $params)
	{
		return $this->_insert('before', $name, $method, $params);
	}
	
	public function insertAfter($name, $method, $params)
	{
		$this->_insert('after', $name, $method, $params);
	}
	
	private function _insert($where, $name, $method, $arguments)
	{
		if(is_numeric($name))
		{
			$this->_callArray[$name][$where][] = ['method'=>$method, 'arguments'=>$arguments, $name=false, 'before'=>[], 'after'=>[]];
			return true;
		}
		if(is_string($name))
		{
			foreach($this->_callArray as &$call)
			{
				if(isset($call['name']) && $call['name'] == $name)
				{
					$call[$where][] = ['method'=>$method, 'arguments'=>$arguments, $name=false, 'before'=>[], 'after'=>[] ];
					return true;
				}
			}
		}
		return false;
	}
	
	public function executeQueue($queue = null)
	{
		#if(is_array($queue) == empty($queue)) return;
		if(is_null($queue))
		{
			$queue = $this->_callArray;
		}

		foreach($queue as $call)
		{
			if(isset($call['before']))
			{
				$this->executeQueue($call['before']);
			}
			$this->_execute($call['method'], $call['arguments']);
			
			if(isset($call['after']))
			{
				$this->executeQueue($call['after']);
			}
		}
	}
	
	private function _execute($method, $arguments)
	{
		call_user_func_array([$this->_tpl,$method], $arguments);
	}
	
}