<?php
namespace Packfire\Template\Mustache;

/**
 * Test class for Mustache.
 * Generated by PHPUnit on 2012-09-19 at 05:09:54.
 */
class MustacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Packfire\Template\Mustache\Mustache
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Mustache('Hello {{name}}{{#intro}}, my name is {{intro}}{{/intro}}!');
        $this->object->parameters(array('name' => 'world'));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }

    public function testTemplate()
    {
        $this->object->template('Good day {{name}}!');
        $this->assertEquals('Good day world!', $this->object->render());
    }

    /**
     * @covers \Packfire\Template\Mustache\Mustache::parameters
     */
    public function testParameters()
    {
        $this->object->parameters(array('name' => 'Jack', 'intro' => 'Jill'));
        $this->assertEquals('Hello Jack, my name is Jill!', $this->object->render());
    }

    public function testEmptyParameters()
    {
        $this->object->parameters(array());
        $this->assertEquals('Hello !', $this->object->render());
    }

    public function testRender()
    {
        $this->assertEquals('Hello world!', $this->object->render());
    }

    public function testObjectRender()
    {
        $obj = new TestObject();
        $obj->template('Hello {{name}}{{#intro}}, my name is {{intro}}{{/intro}}!');
        $this->assertEquals('Hello Regina, my name is James Bond!', $obj->render());
    }

    public function testConstructorOptions()
    {
        $mustache = new Mustache(
            '[:label:]: [:>num:]',
            array(
                'escaper' => function ($text) {
                    return $text;
                },
                'open' => '[:',
                'close' => ':]',
                'loader' => new Loader\Arrayloader(array('num' => 5))
            )
        );

        $params = array(
            'label' => '<b>Random number</b>'
        );
        $output = $mustache->parameters($params)->render();
        $this->assertEquals('<b>Random number</b>: 5', $output);
    }

    public function testDotNotation()
    {
        $mustache = new Mustache();
        // purposely missed out the closing tag for more testing
        $mustache->template('Singapore {{#person.address.postalcode}}{{person.address.postalcode}}');
        $params = array(
            'person' => array(
                'address' => array(
                    'postalcode' => '649139'
                )
            )
        );
        $output = $mustache->parameters($params)->render();
        $this->assertEquals('Singapore 649139', $output);
    }

    public function testNonScalarOpenTags()
    {
        $mustache = new Mustache();
        $mustache->template('Singapore {{#person}}{{address.postalcode}}{{/person}}');
        $params = array(
            'person' => array(
                'address' => array(
                    'postalcode' => '649139'
                )
            )
        );
        $output = $mustache->parameters($params)->render();
        $this->assertEquals('Singapore 649139', $output);
    }

    public function testNotSet()
    {
        $mustache = new Mustache();
        // purposely missed out the closing tag for more testing
        $mustache->template('Singapore {{#person.address.block}}{{person.address.postalcode}}');
        $params = array(
            'person' => array(
                'address' => array(
                    'postalcode' => '649139'
                )
            )
        );
        $output = $mustache->parameters($params)->render();
        $this->assertEquals('Singapore ', $output);
    }

    public function testCallableProperty()
    {
        $mustache = new Mustache();
        // purposely missed out the closing tag for more testing
        $mustache->template('{{person}}!');
        $params = array(
            'person' => function () {
                return 'Cool';
            }
        );
        $output = $mustache->parameters($params)->render();
        $this->assertEquals('Cool!', $output);
    }

    public function testTagNesting()
    {
        $mustache = new Mustache();
        $mustache->template('{{^coin}}{{#coin}}COIN{{/coin}}{{/coin}}!');
        $output = $mustache->parameters(array('coin' => false))->render();
        $this->assertEquals('!', $output);
    }

    public function testInverts()
    {
        $mustache = new Mustache();
        $mustache->template('{{^coin}}COIN{{/coin}}!');
        $output = $mustache->parameters(array('coin' => false))->render();
        $this->assertEquals('COIN!', $output);
    }

    public function testArrayList()
    {
        $mustache = new Mustache();
        $mustache->template('{{#list}}|{{name}}{{/list}}!');
        $params = array(
            'list' => array(
                array('name' => 'sam'),
                array('name' => 'john'),
                array('name' => 'henry')
            )
        );
        $output = $mustache->parameters($params)->render();
        $this->assertEquals('|sam|john|henry!', $output);
    }

    public function testArrayList2()
    {
        $mustache = new Mustache();
        $mustache->template('{{list}}!');
        $params = array(
            'list' => array(
                'sam',
                'john',
                'henry'
            )
        );
        $output = $mustache->parameters($params)->render();
        $this->assertEquals('samjohnhenry!', $output);
    }

    public function testSwitchImmediate()
    {
        $mustache = new Mustache();
        $mustache->template("Testing is {{#bool}}awesome{{/bool}}{{^bool}}bad{{/bool}}.");
        $output = $mustache->render();
        $this->assertEquals("Testing is bad.", $output);
    }

    public function testDelimiterChange()
    {
        $mustache = new Mustache();
        $mustache->template('{{=<$ $>=}}Hello <$name$>!<$={{ }}=$> My name is {{name}}!');
        $output = $mustache->parameters(array('name' => 'world'))->render();
        $this->assertEquals('Hello world! My name is world!', $output);
    }

    public function testComment()
    {
        $mustache = new Mustache();
        $mustache->template('Jump over the {{name}} {{! pretty sure you can\'t make it there!}}!');
        $output = $mustache->parameters(array('name' => 'moon'))->render();
        $this->assertEquals('Jump over the moon !', $output);
    }

    public function testStandalone()
    {
        $mustache = new Mustache();
        $mustache->template("Testing\n   {{! some comment}}\nTesting");
        $output = $mustache->render();
        $this->assertEquals("Testing\nTesting", $output);
    }

    public function testStandalone2()
    {
        $mustache = new Mustache();
        $mustache->template("Testing\n{{#test}}\nTesting\n{{/test}}\nTesting");
        $output = $mustache->render();
        $this->assertEquals("Testing\nTesting", $output);
    }

    public function testStandalone3()
    {
        $mustache = new Mustache();
        $mustache->template("Testing\n{{test}}\nTesting");
        $output = $mustache->render();
        $this->assertEquals("Testing\n\nTesting", $output);
    }

    public function testEscapeTest()
    {
        $this->object->template('Good day {{ name }}!');
        $this->assertEquals('Good day &lt;b&gt;name&lt;/b&gt;!', $this->object->parameters(array('name' => '<b>name</b>'))->render());
    }

    public function testNoEscapeTest()
    {
        $this->object->template('Good day {{&name}}!');
        $this->assertEquals('Good day <b>name</b>!', $this->object->parameters(array('name' => '<b>name</b>'))->render());
    }

    public function testNoEscapeTest2()
    {
        $this->object->template('Good day {{{name}}}!');
        $this->assertEquals('Good day <b>name</b>!', $this->object->parameters(array('name' => '<b>name</b>'))->render());
    }

    public function testPartial()
    {
        $mustache = new Mustache();
        $mustache->template('There you go! {{>Loader/test}}');
        $loader = new Loader\FileSystemLoader(__DIR__);
        $output = $mustache->loader($loader)->parameters(array('name' => 'world'))->render();
        $this->assertEquals('There you go! My name is world.', $output);
    }
}
