<?php

namespace Aa\ArrayDiff\Tests;

use Aa\ArrayDiff\Calculator;
use Aa\ArrayDiff\Diff\DiffInterface;
use Aa\ArrayDiff\Diff\DiffFormats;
use Aa\ArrayDiff\Matcher\ExpressionMatcher;
use Aa\ArrayDiff\Matcher\SimpleMatcher;
use PHPUnit_Framework_TestCase;

class CalculatorTest extends PHPUnit_Framework_TestCase
{
    use YamlFixtureAwareTrait;

    public function testExampleFromDocumentation()
    {
        $array1 = ['a' => 1, 'b' => 2, 'c' => 3];
        $array2 = ['a' => 7, 'b' => 2, 'd' => 3];

        $calc = new Calculator(new SimpleMatcher());
        $diff = $calc->calculateDiff($array1, $array2);

        $expected = <<<EOD
missing:
    -
        key_path: c
        expected: 3
unmatched:
    -
        key_path: a
        expected: 1
        actual: 7

EOD;

        $this->assertEquals($expected, $diff->toString());
    }

    /**
     * @dataProvider fixtureSimpleDataProvider
     */
    public function testCalculateDiffWithSimpleMatcher($expected, $actual, $expectedDiff)
    {
        $calculator = new Calculator(new SimpleMatcher());
        $calculator->setSequentialKeysSupported(true);
        
        $actualDiff = $calculator->calculateDiff($expected, $actual)->toArray(DiffFormats::FULL);
        
        $this->assertEquals($expectedDiff, $actualDiff);
    }

    /**
     * @dataProvider fixtureSimpleDataProvider
     */
    public function testThatSimpleMatcherDiffIsEqualToStandardArrayDiff($expected, $actual, $expectedDiff)
    {
        $calculator = new Calculator(new SimpleMatcher());
        $calculator->setSequentialKeysSupported(true);
        
        $standardDiff = array_diff_recursive($expected, $actual);
        $formattedAsStandardDiff = $actualDiff = $calculator->calculateDiff($expected, $actual)->toArray(DiffFormats::PHP_FUNCTION_ALIKE);

        $this->assertEquals($standardDiff, $formattedAsStandardDiff);
    }

    /**
     * @dataProvider fixtureExpressionDataProvider
     */
    public function testCalculateDiffWithExpressionMatcher($expected, $actual, $expectedDiff)
    {
        $calculator = new Calculator(new ExpressionMatcher());

        $actualDiff = $calculator->calculateDiff($expected, $actual)->toArray(DiffFormats::FULL);

        $this->assertEquals($expectedDiff, $actualDiff);
    }   
    
    public function fixtureSimpleDataProvider()
    {
        return $this->getDataFromFixtureFile('simple');
    }

    public function fixtureExpressionDataProvider()
    {
        return $this->getDataFromFixtureFile('expression');
    }
}

function array_diff_recursive(&$array1, &$array2)
{
    $diff = [];

    foreach ($array1 as $key => $value)
    {
        if (array_key_exists($key, $array2)) {
            
            if (is_array($value)) {
                
                $recursiveDiff = array_diff_recursive($value, $array2[$key]);

                if (count($recursiveDiff)) {
                    $diff[$key] = $recursiveDiff;
                }
            } elseif (!in_array($value, $array2)) {
                $diff[$key] = $value;
            }
        } elseif (!in_array($value, $array2)) {
            $diff[$key] = $value;
        }
    }

    return $diff;
}
