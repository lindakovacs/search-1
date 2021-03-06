<?php
namespace Search\Test\TestCase\Model\Filter;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use Search\Manager;
use Search\Model\Filter\Compare;

class CompareTest extends TestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Search.Articles'
    ];

    /**
     * @return void
     */
    public function testSkipProcess()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        /* @var $filter \Search\Model\Filter\Compare|\PHPUnit_Framework_MockObject_MockObject */
        $filter = $this
            ->getMockBuilder('Search\Model\Filter\Compare')
            ->setConstructorArgs(['created', $manager])
            ->setMethods(['skip'])
            ->getMock();
        $filter
            ->expects($this->once())
            ->method('skip')
            ->willReturn(true);
        $filter->args(['created' => '2012-01-01 00:00:00']);
        $filter->query($articles->find());
        $filter->process();

        $this->assertEmpty($filter->query()->clause('where'));
    }

    /**
     * @return void
     */
    public function testProcess()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Compare('created', $manager, ['multiValue' => true]);
        $filter->args(['created' => '2012-01-01 00:00:00']);
        $filter->query($articles->find());
        $filter->process();

        $this->assertRegExp(
            '/WHERE Articles\.created >= :c0$/',
            $filter->query()->sql()
        );
        $this->assertEquals(
            ['2012-01-01 00:00:00'],
            Hash::extract($filter->query()->valueBinder()->bindings(), '{s}.value')
        );
    }

    /**
     * @return void
     */
    public function testProcessMode()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Compare('time', $manager, ['field' => ['created', 'modified']]);
        $filter->args(['time' => '2012-01-01 00:00:00']);
        $filter->query($articles->find());
        $filter->process();

        $this->assertRegExp(
            '/WHERE \(Articles\.created >= :c0 AND Articles\.modified >= :c1\)$/',
            $filter->query()->sql()
        );
        $this->assertEquals(
            ['2012-01-01 00:00:00', '2012-01-01 00:00:00'],
            Hash::extract($filter->query()->valueBinder()->bindings(), '{s}.value')
        );
    }

    /**
     * @return void
     */
    public function testProcessModeOr()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Compare('time', $manager, ['mode' => 'OR', 'field' => ['created', 'modified']]);
        $filter->args(['time' => '2012-01-01 00:00:00']);
        $filter->query($articles->find());
        $filter->process();

        $this->assertRegExp(
            '/WHERE \(Articles\.created >= :c0 OR Articles\.modified >= :c1\)$/',
            $filter->query()->sql()
        );
        $this->assertEquals(
            ['2012-01-01 00:00:00', '2012-01-01 00:00:00'],
            Hash::extract($filter->query()->valueBinder()->bindings(), '{s}.value')
        );
    }

    /**
     * @return void
     */
    public function testProcessMultiValueSafe()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Compare('created', $manager, ['multiValue' => true]);
        $filter->args(['created' => ['foo', 'bar']]);
        $filter->query($articles->find());
        $filter->process();

        $this->assertEmpty($filter->query()->clause('where'));
        $filter->query()->sql();
        $this->assertEmpty($filter->query()->valueBinder()->bindings());
    }

    /**
     * @return void
     */
    public function testProcessDefaultFallbackForDisallowedMultiValue()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Compare('created', $manager, ['defaultValue' => '2012-01-01 00:00:00']);
        $filter->args(['created' => ['foo', 'bar']]);
        $filter->query($articles->find());
        $filter->process();

        $this->assertRegExp(
            '/WHERE Articles\.created >= :c0$/',
            $filter->query()->sql()
        );

        $this->assertEquals(
            ['2012-01-01 00:00:00'],
            Hash::extract($filter->query()->valueBinder()->bindings(), '{s}.value')
        );
    }

    /**
     * @return void
     */
    public function testProcessNoDefaultFallbackForDisallowedMultiValue()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Compare('created', $manager);
        $filter->args(['created' => ['foo', 'bar']]);
        $filter->query($articles->find());
        $filter->process();

        $this->assertEmpty($filter->query()->clause('where'));
        $filter->query()->sql();
        $this->assertEmpty($filter->query()->valueBinder()->bindings());
    }
}
