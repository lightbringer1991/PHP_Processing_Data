<?php
namespace Polynomial; 
class Term
{
   protected $coefficient, $exponent, $term, $neg;
	/**
	  * Constructor for PolynomialTerm
	  */
	function __construct($coefficient=1, $exponent=0, $term='x')
	{
		$this->coefficient=abs($coefficient);
		$this->neg = false;
		if($this->coefficient != $coefficient)
		{
			$this->neg = true;
		}
		$this->exponent=$exponent;
		$this->term=$term;
	}
	
	public function matches(\Polynomial\Term $otherTerm)
	{
		return (
			$this->exponent == $otherTerm->getExponent() &&
			$this->term == $otherTerm->getTerm()
		);
	}
	
	public function getDerivative()
	{
		$coef = $this->coefficient * $this->exponent;
		$exp = $this->exponent - 1;
		
		if($exp >= 0)
		{
			$derivative = new self($coef, $exp, $this->term);
			
			if($this->isNegative())
			{
				$derivative->invert();
			}
			return $derivative;
		}
	}
	
	public function getIntegral()
	{
		$coef = $this->coefficient / ($this->exponent + 1);
		$exp = $this->exponent + 1;
		$integral = new self($coef, $exp, $this->term);
		if($this->isNegative())
		{
			$integral->invert();
		}
		return $integral;
	}
	
	public function add(\Polynomial\Term $otherTerm)
	{
		if($otherTerm->isNegative() == $this->neg)
		{
			$this->coefficient += $otherTerm->getCoefficient();
		}
		else
		{
			$this->coefficient -= $otherTerm->getCoefficient();
			if($this->coefficient < 0)
			{
				$this->coefficient = abs($this->coefficient);
				$this->neg = !$this->neg;
			}
		}
	}
	
	public function copy()
	{
		$copy = new self($this->coefficient, $this->exponent, $this->term);
		if($this->neg)
		{
			$copy->invert();
		}
		return $copy;
	}
	
	public function multiplyBy($otherTerm)
	{
		$coef = $this->getCoefficient() * $otherTerm->getCoefficient();
		
		$newTerm = new self(
				$coef,
				$this->getExponent() + $otherTerm->getExponent(),
				$this->term?$this->term:$otherTerm->getTerm()
		);
		
		if($this->isNegative() != $otherTerm->isNegative())
		{
			$newTerm->invert();
		}
		return $newTerm;
	}
	
	public function __toString()
	{
		if($this->exponent == 0)
		{
			if($this->coefficient <> 0)
			{
				return $this->coefficient;
			}
			return '0';
		}
		
		$asString = '';
		if($this->coefficient <>0)
		{
			if($this->coefficient != 1)
			{
				$asString .= $this->coefficient;
			}
		}
		else
		{
			return '0';
		}
		$asString .= $this->term;
		if($this->exponent >1)
		{
			$asString .= '^'.$this->exponent;
		}
		return $asString;
	}
	
	function invert()
	{
		$this->neg = !$this->neg;
	}
	
	function isNegative()
	{
		return $this->neg;
	}
	
	function isZero()
	{
		return $this->coefficient == 0;
	}
	function setCoefficient($coefficient)
	{
		$this->coefficient=$coefficient;
	}
	function getCoefficient()
	{
		return $this->coefficient;
	}
	function setExponent($exponent)
	{
		$this->exponent=$exponent;
	}
	function getExponent()
	{
		return $this->exponent;
	}
	function setTerm($term)
	{
		$this->term=$term;
	}
	function getTerm()
	{
		return $this->term;
	}
}