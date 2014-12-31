<?php

use Tamayo\Stretchy\Connection;
use Tamayo\Stretchy\Search\Grammar;
use Tamayo\Stretchy\Search\Builder;
use Tamayo\Stretchy\Search\Processor;

class SearchGrammarTest extends PHPUnit_Framework_TestCase
{

	public function testSingleMatch()
	{
		$builder = $this->getBuilder();

		$builder->match('foo', 'bar', function($match)
		{
			$match->operator('and');
			$match->zeroTermsQuery('all');
			$match->cutoffFrequency(0.001);
			$match->lenient(true);
		});

		$json = $builder->toJson();

		$this->assertEquals('{"index":"*","body":{"query":{"match":{"foo":{"operator":"and","zero_terms_query":"all","cutoff_frequency":0.001,"lenient":true,"query":"bar","type":"boolean"}}}}}', $json);
	}

	public function testSingleMatchPhrase()
	{
		$builder = $this->getBuilder();

		$builder->matchPhrase('foo', 'bar');

		$json = $builder->toJson();

		$this->assertEquals('{"index":"*","body":{"query":{"match":{"foo":{"query":"bar","type":"phrase"}}}}}', $json);
	}

	public function testSingleMatchPhrasePrefix()
	{
		$builder = $this->getBuilder();

		$builder->matchPhrasePrefix('foo', 'bar');

		$json = $builder->toJson();

		$this->assertEquals('{"index":"*","body":{"query":{"match":{"foo":{"query":"bar","type":"phrase_prefix"}}}}}', $json);
	}

	public function testSingleMultiMatch()
	{
		$builder = $this->getBuilder();

		$builder->multiMatch(['foo', 'bar'], 'baz');

		$json = $builder->toJson();

		$this->assertEquals('{"index":"*","body":{"query":{"multi_match":{"fields":["foo","bar"],"query":"baz","type":"best_fields"}}}}', $json);
	}

	public function testSingleBoolWithNestedMatch()
	{
		$builder = $this->getBuilder();

		$builder->bool(function($query)
		{
			$query->must(function($must)
			{
				$must->match('foo', 'bar');
			});

			$query->mustNot(function($mustNot)
			{
				$mustNot->match('foo', 'baz');
			});

			$query->should(function($should)
			{
				$should->match('foo', 'bah');
			});

			$query->minimumShouldMatch(1);
		});

		$json = $builder->toJson();

		$this->assertEquals('{"index":"*","body":{"query":{"bool":{"should":[{"match":{"foo":{"query":"bah","type":"boolean"}}}],"must_not":[{"match":{"foo":{"query":"baz","type":"boolean"}}}],"must":[{"match":{"foo":{"query":"bar","type":"boolean"}}}],"minimum_should_match":1}}}}', $json);
	}

	public function testNestedBool()
	{
		$builder = $this->getBuilder();

		$builder->bool(function($query)
		{
			$query->must(function($must)
			{
				$must->match('foo', 'bar');

				$must->bool(function($subQuery)
				{
					$subQuery->must(function($must)
					{
						$must->match('foo', 'baz');
					});

					$subQuery->boost(1.0);
				});
			});

			$query->minimumShouldMatch(1);
		});

		$json = $builder->toJson();

		$this->assertEquals('{"index":"*","body":{"query":{"bool":{"must":[{"match":{"foo":{"query":"bar","type":"boolean"}}},{"bool":{"must":[{"match":{"foo":{"query":"baz","type":"boolean"}}}],"boost":1}}],"minimum_should_match":1}}}}', $json);
	}

	public function getGrammar()
	{
		return new Grammar;
	}

	public function getConnection()
	{
		$connection = Mockery::mock('Tamayo\Stretchy\Connection');

		$connection->shouldReceive('getIndexPrefix')->andReturn('');

		return $connection;
	}

	public function getProcessor()
	{
		return new Processor;
	}

	public function getBuilder()
	{
		return new Builder($this->getConnection(), $this->getGrammar(), $this->getProcessor());
	}
}
