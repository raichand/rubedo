<?php
/**
 * Created by IntelliJ IDEA.
 * User: nainterceptor
 * Date: 06/08/14
 * Time: 17:16
 */

namespace RubedoAPI\Tools;


use RubedoAPI\Exceptions\APIEntityException;
use RubedoAPI\Exceptions\APIFilterException;
use Zend\Stdlib\JsonSerializable;

class VerbDefinitionEntity implements JsonSerializable {
    protected $verb;
    protected $description;
    protected $outputFilters = [];
    protected $inputFilters = [];
    protected $rights = array();

    function __construct($verb) {
        $this
            ->setVerb($verb)
            ->addInputFilter(
                (new FilterDefinitionEntity())
                    ->setDescription('Access token')
                    ->setKey('access_token')
                    ->setRename('identity')
                    ->setFilter('\\RubedoAPI\\Entities\\API\\Identity')
            )
            ->addOutputFilter(
                (new FilterDefinitionEntity())
                    ->setKey('success')
                    ->setRequired()
                    ->setDescription('Success of the query')
                    ->setFilter('boolean')
            )
            ->addOutputFilter(
                (new FilterDefinitionEntity())
                    ->setKey('message')
                    ->setDescription('Informations about the query')
                    ->setFilter('string')
            )
        ;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVerb()
    {
        return $this->verb;
    }

    /**
     * @param mixed $verb
     * @return $this
     */
    public function setVerb($verb)
    {
        $this->verb = $verb;
        return $this;
    }

    protected function hasIdentityRequired()
    {
        return $this->getInputFilter('access_token')->isRequired();
    }

    protected function identityRequired($has = true)
    {
        $this->editInputFilter('access_token', function(FilterDefinitionEntity &$filter) use ($has) {
            $filter->setRequired($has);
        });
        return $this;
    }

    /**
     * @param $right
     * @return $this
     * @internal param array $rights
     */
    public function addRight($right)
    {
        $this->rights[] = $right;
        if (!$this->hasIdentityRequired())
            $this->identityRequired();

        return $this;
    }

    /**
     * @return array
     */
    public function getRights()
    {
        return $this->rights;
    }

    /**
     * @return array
     */
    public function getInputFilters()
    {
        return $this->inputFilters;
    }

    /**
     * @param $key
     * @return FilterDefinitionEntity
     */
    public function getInputFilter($key)
    {
        if (!isset($this->inputFilters[$key]))
            return new FilterDefinitionEntity();
        return $this->inputFilters[$key];
    }

    /**
     * @param \RubedoAPI\Tools\FilterDefinitionEntity $inputFilter
     * @return $this
     */
    public function addInputFilter(FilterDefinitionEntity $inputFilter)
    {
        $this->inputFilters[$inputFilter->getKey()] = $inputFilter;
        return $this;
    }

    public function editInputFilter($key, $function)
    {
        if (!isset($this->inputFilters[$key])) {
            $filter = (new FilterDefinitionEntity())->setKey($key);
            $function($filter);
            $this->addInputFilter($filter);
        } else
            $function($this->inputFilters[$key]);
        return $this;
    }

    /**
     * @return array
     */
    public function getOutputFilters()
    {
        return $this->outputFilters;
    }

    public function filterInput($toFilter)
    {
        $filtered = [];
        foreach ($this->getInputFilters() as $key => $filter) {
            if (!($filter instanceof FilterDefinitionEntity))
                throw new APIEntityException('Filter in VerbDefinition must be FilterDefinitionEntity', 500);
            if ($filter->isRequired() && (!array_key_exists($key, $toFilter) || empty($toFilter[$key])))
                throw new APIFilterException('"' . $key . '" is required', 500);
            elseif (!array_key_exists($key, $toFilter))
                continue;
            $filtered[$filter->hasRename()?$filter->getRename():$key] = $filter->filter($key, $toFilter[$key]);

        }
        return $filtered;
    }

    public function filterOutput($toFilter)
    {
        $filtered = [];
        foreach ($this->getOutputFilters() as $key => $filter) {
            if (!($filter instanceof FilterDefinitionEntity))
                throw new APIEntityException('Filter in VerbDefinition must be FilterDefinitionEntity', 500);
            if ($filter->isRequired() && (!array_key_exists($key, $toFilter) || empty($toFilter[$key])))
                throw new APIFilterException('"' . $key . '" must be back.', 500);
            elseif (!array_key_exists($key, $toFilter))
                continue;
            $filtered[$key] = $filter->filter($key, $toFilter[$key]);

        }
        return $filtered;
    }

    /**
     * @param \RubedoAPI\Tools\FilterDefinitionEntity $outputFilter
     * @return $this
     */
    public function addOutputFilter(FilterDefinitionEntity $outputFilter)
    {
        $this->outputFilters[$outputFilter->getKey()] = $outputFilter;
        return $this;
    }
    public function editOutputFilter($key, $function)
    {
        if (!isset($this->outputFilters[$key])) {
            $filter = (new FilterDefinitionEntity())->setKey($key);
            $function($filter);
            $this->addOutputFilter($filter);
        } else
            $function($this->outputFilters[$key]);
        return $this;
    }

    protected function getFiltersSerialized(array $filterArray)
    {
        $filters = [];
        foreach ($filterArray as $filterName => $filter) {
            if (!$filter instanceof FilterDefinitionEntity)
                throw new APIEntityException('Filter in VerbDefinition must be FilterDefinitionEntity', 500);
            $filters[$filterName] = $filter->jsonSerialize();
        }
        return $filters;
    }

    protected function getOutputFiltersSerialized()
    {
        return $this->getFiltersSerialized($this->getOutputFilters());
    }

    protected function getInputFiltersSerialized()
    {
        return $this->getFiltersSerialized($this->getInputFilters());
    }

    function jsonSerialize() {
        return [
            'verb' => $this->getVerb(),
            'description' => $this->getDescription(),
            'input' => $this->getInputFiltersSerialized(),
            'output' => $this->getOutputFiltersSerialized(),
        ];
    }
}