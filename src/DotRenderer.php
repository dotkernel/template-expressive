<?php

namespace DotKernel\Expressive\DotRenderer;
use DotKernel\DotTemplate\DotTemplate as DotTemplate;

use Zend\Expressive\Template\TemplateRendererInterface as TemplateRendererInterface;
use Zend\Expressive\Template\TemplatePath;

class DotRenderer implements TemplateRendererInterface
{
	
	private $_paths = array();
	private $_extensions = array('tpl','htpl', 'html');

	/**
	 * Render a template, optionally with parameters.
	 *
	 * Implementations MUST support the `namespace::template` naming convention,
	 * and allow omitting the filename extension.
	 *
	 * @param string $name
	 * @param array|object $params
	 * @return string
	 */
	public function render($name, $params = [])
	{
		$params['tpl_main'] = $this->_loadFile($name);
		$dotTemplate = new DotTemplate('.', 'keep', array(), $params);
		
		$templateAdapter = new TemplateAdapter($dotTemplate);
		$val = 'abc';
		$val = 'a+'.$this->_sum(10);
		
		
		$templateAdapter->setVar('VAR', $val);
		$templateAdapter->subst('VAR');
		
		$parseOutputKey = $templateAdapter->parse('OUTPUT', 'tpl_main');
		
		$templateAdapter->insertBefore($parseOutputKey, 'setvar', ['VAR2','val2']);
		$templateAdapter->insertBefore($parseOutputKey, 'subst', ['VAR2']);

		$templateAdapter->executeQueue();

		return $dotTemplate->get('OUTPUT');
	}
	
	private function _sum($a){ if($a ==0 ) return $a; return $this->_sum($a-1) + $a; } 
	
	/**
	 * Add a template path to the engine.
	 *
	 * Adds a template path, with optional namespace the templates in that path
	 * provide.
	 *
	 * @param string $path
	 * @param string $namespace
	 */
	public function addPath($path, $namespace = null)
	{
		$this->_paths[($namespace)?$namespace:'root'] = new TemplatePath($path, $namespace);
	}
	
	/**
	 * Retrieve configured paths from the engine.
	 *
	 * @return TemplatePath[]
	 */
	public function getPaths()
	{
		return $this->_paths;
	}
	
	/**
	 * Add a default parameter to use with a template.
	 *
	 * Use this method to provide a default parameter to use when a template is
	 * rendered. The parameter may be overridden by providing it when calling
	 * `render()`, or by calling this method again with a null value.
	 *
	 * The parameter will be specific to the template name provided. To make
	 * the parameter available to any template, pass the TEMPLATE_ALL constant
	 * for the template name.
	 *
	 * If the default parameter existed previously, subsequent invocations with
	 * the same template name and parameter name will overwrite.
	 *
	 * @param string $templateName Name of template to which the param applies;
	 *     use TEMPLATE_ALL to apply to all templates.
	 * @param string $param Param name.
	 * @param mixed $value
	 */
	public function addDefaultParam($templateName, $param, $value)
	{
		// not yet implemented
	}
	
	/**
	 * File load functions
	 */
	private function _loadFile($name)
	{
		$file = $this->_searchByName($name);
		if($file)
		{
			return file_get_contents($file);
		}
		return false;
	}
	
	/**
	 * Search file by name & within the namespace
	 * 
	 * Format is namespace::file
	 * Format: namespace::folder::file is also accepted
	 * 
	 * Returns filename if file was found
	 * Returns false if file was not found
	 * 
	 * @access public
	 * @param string $name
	 * @return string|bool $file
	 */
	private function _searchByName($name)
	{
		$nameArray = explode('::', $name);
		$namespace = array_shift($nameArray);
		$filename = implode('/', $nameArray);
		
		$file = $this->_searchNameInNamespace($filename, $namespace);
		// 
		return ($file) ? $file :false;
	}
	
	private function _searchNameInNamespace($file, $namespace)
	{
		$path = $this->_paths[$namespace]->getPath();
		foreach($this->_extensions as $extension)
		{
			if(file_exists($path.'/'.$file.'.'.$extension))
			{
				return $path.'/'.$file.'.'.$extension;
			}
		}
		return false;
	}
	
}