<?php
namespace Polynomial;
class Equation
{
  protected $terms, $subEquations;
	public function __construct($equation = null)
	{
		$this->terms = array();
		$this->subEquations = array();
		
		if(is_a($equation, 'Polynomial\Equation'))
		{
			$terms = $equation->getTerms();
			foreach($terms as $term)
			{
				$this->addTerm($term->copy());
			}
		}
		else
		{
			if(gettype($equation) == 'string')
			{
				$this->parseFromString($equation);
				$this->sortTerms();
			}
			// By Victor Shvets---------------------------
			else if(gettype($equation) == 'array')
			{
				$countTerms = count($equation);
				for($i = 0; $i < $countTerms; $i++)
				{
					$term = new \Polynomial\Term($equation[$i], $countTerms - $i - 1, 'x');
					$this->addTerm($term->copy());
				}
			}
			//--------------------------------------------
		}
	}
	
	function parseTermFromString($stringTerm)
	{
		$stringTerm = trim($stringTerm);
		$pattern = "/(\-?)(\d*)([a-zA-Z]?)(\^?(\d?))/";
		preg_match($pattern, $stringTerm, $termParts);
		$coeff = intval($termParts[2]);
		$exponent = 0;
		if($termParts[3])
		{
			$exponent = $termParts[5]?$termParts[5]:1;
			$coeff = $coeff?$coeff:1;
		}
		$term = new \Polynomial\Term($coeff, $exponent, 'x');//$termParts[3]);	Victor Shvets
		
		if($termParts[1])
		{
			$term->invert();
		}
		
		return $term;
	}
	
	function parsePhrase($equation)
	{
		$equation = str_replace(array(' ', '--', '-'), array('', '+', '+-'), $equation);
		$terms = array();
		$stringTerms = explode('+', $equation);
		foreach($stringTerms as $stringTerm)
		{
			$stringTerm = str_replace(' ', '', $stringTerm);
			if($stringTerm)
			{
				$this->addTerm($this->parseTermFromString($stringTerm));
			}
		}
		return $terms;
	}
	
	function parseFromString($equation)
	{
		$terms = $this->parsePhrase($equation);
		$countTerms = count($terms);
		for($i = 0; $i < $countTerms; $i++)
		{
			$this->addTerm($terms[$i]);
		}
	}
	
	
	function pow($pow)
	{
		$result = new self($this);
		for($i = 0; $i < $pow-1; $i++)
		{
			$result = $result->multiplyBy($this);
		}
		return $result;
	}
	
	function multiplyBy($otherEquation)
	{
		if(!is_a($otherEquation, 'Polynomial\Equation'))
		{
			$otherEquation = new self($otherEquation);
		}
		
		$result = new self();
		
		$count1 = $otherEquation->getTermCount();
		$count2 = $this->getTermCount();
		
		for($i = 0; $i < $count1; $i++)
		{
			$term1 = $otherEquation->getTerm($i);
			for($j = 0; $j < $count2; $j++)
			{
				$term2 = $this->getTerm($j);
				$newTerm = $term1->multiplyBy($term2);
				$result->addTerm($newTerm);
			}
		}
		return $result;
	}
	
	function add($polynomialEntity)
	{
		if(is_a($polynomialEntity, '\Polynomial\PolynomialTerm'))
		{
			$result = new self($this);
			$result->addTerm($polynomialEntity);
			return $result;
		}
		return $this->addEquation($polynomialEntity);
	}
	
	function addEquation($polynomialEquation)
	{
		if(!is_a($polynomialEquation, 'Polynomial\Equation'))
		{
			$polynomialEquation = new self($polynomialEquation);
		}
		$terms = $polynomialEquation->getTerms();
		
		$addition = new self($this);
		
		foreach($terms as $term)
		{
			$addition->addTerm($term->copy());
		}
		return $addition;
	}
	
	function subtract($polynomialEquation)
	{
		if(!is_a($polynomialEquation, 'Polynomial\Equation'))
		{
			$polynomialEquation = new self($polynomialEquation);
		}
		$terms = $polynomialEquation->getTerms();
		
		foreach($terms as $term)
		{
			$this->subtractTerm($term->copy());
		}
		return $this;
	}
	
	function subtractTerm($term)
	{
		if(!is_a($term, 'Polynomial\Term'))
		{
			$term = $this->parseTermFromString($term);
		}
		if($term->getCoefficient() == 0)
		{
			return;
		}
		
		$term->invert();
		return $this->addTerm($term);
	}
	
	function addTerm($term)
	{
		if(!is_a($term, 'Polynomial\Term'))
		{
			$term = $this->parseTermFromString($term);
		}
		if($term->isZero())
		{
			return;
		}
		
					
		$searching = true;
		$found = false;
		$i = 0;
		$countTerms = count($this->terms);
		$indexToRemove=-1;
		
		if($countTerms > 0)
		{
			while($searching)
			{
				if($this->terms[$i]->matches($term))
				{
					$this->terms[$i] = $this->terms[$i]->copy();
					$this->terms[$i]->add($term);
					if($this->terms[$i]->getCoefficient() == 0)
					{
						$indexToRemove = $i;
					}
					$searching = false;
					$found = true;
				}
				else
				{
					$i++;
					if($i == $countTerms)
					{
						$searching = false;
					}
				}
			}
		}
		if(!$found)
		{
			$this->terms[] = $term;
		}
		
		if($indexToRemove >= 0)
		{
			unset($this->terms[$indexToRemove]);
			$this->terms = array_values($this->terms);
		}
		
		$this->sortTerms();
		
		return $this;
	}
	
	protected function sortTerms()
	{
		$bln_swap = true;
		$count = count($this->terms);
		for ($i = 0; $i < $count and $bln_swap; $i++) {
			$bln_swap = false;
			for ($j = 0; $j < ($count - 1); $j++) {
				if (
					$this->terms[$j]->getExponent() <
					$this->terms[$j + 1]->getExponent())
				{
					list($this->terms[$j], $this->terms[$j + 1]) = array($this->terms[$j + 1], $this->terms[$j]);
					$bln_swap = true;
				}
			}
		}
		
		$this->sorted = true;
	}
	
	function getIntegral($n = 1, $c = 0)
	{
		$result = new self();
		
		foreach($this->terms as $term)
		{
			$result->addTerm($term->getIntegral());
		}
		
		for($i = 0; $i < $n - 1; $i++)
		{
			$result = $result->getIntegral();
		}
		
		$result->addTerm($c);
		return $result;
	}
	
	function getDerivative($n = 1)
	{
		$result = new self();
		
		foreach($this->terms as $term)
		{
			$derivedTerm = $term->getDerivative();
			if($derivedTerm)
			{
				$result->addTerm($derivedTerm);
			}
		}
		
		for($i = 0; $i < ($n - 1); $i++)
		{
			$result = $result->getDerivative();
			if($result->isZero())
			{
				return $result;
			}
		}
		
		return $result;
	}
	
	function evaluateFor($x)
	{
		$val = 0.0;
		foreach($this->terms as $term)
		{
			$t = $term->getCoefficient() * pow($x, $term->getExponent());
			if($term->isNegative())
			{
				$t = -$t;
			}
			$val += $t;
		}
		return $val;
	}
	
	function isZero()
	{
		$terms = count($this->terms);
		if($terms == 0 || ($terms == 1 && $this->terms[0]->getCoefficient() == 0))
		{
			return true;
		}
		return false;
	}
	
	function isConstant()
	{
		$terms = count($this->terms);
		if($terms == 0 || ($terms == 1 && $this->terms[0]->getExponent() == 0))
		{
			return true;
		}
		
		return false;
	}
	
	function copy()
	{
		$copy = new self();
		foreach($this->terms as $term)
		{
			$copy->addTerm($term->copy());
		}
		return $copy;
	}
	
	function getTerms()
	{
		return $this->terms;
	}
	
	function getTerm($n)
	{
		return $this->terms[$n];
	}
	
	function getTermCount()
	{
		return count($this->terms);
	}
	
	function getDegree()
	{
		return $this->terms[0]->getExponent();
	}
	
	function __toString()
	{
		if(count($this->terms) == 0)
		{
			return '0';
		}
		$asString = '';
		foreach($this->terms as $term)
		{
			if($term->isNegative())
			{
				$asString .= ' - ';
			}
			else
			{
				$asString .= ' + ';
			}
			$asString .= $term->__toString();
		}
		if(!$this->terms[0]->isNegative())
		{
			$asString = trim(substr($asString, 2));
		}
		return $asString;
	}
}