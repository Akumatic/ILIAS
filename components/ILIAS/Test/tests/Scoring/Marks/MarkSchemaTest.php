<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

use ILIAS\Test\Scoring\Marks\MarkSchema;

/**
 * Unit tests for single choice questions
 * @author  Maximilian Becker <mbecker@databay.de>
 * @version $Id$
 * @ingroup ServicesTree
 */
class MarkSchemaTest extends ilTestBaseTestCase
{
    private MarkSchema $ass_mark_schema;
    protected $backupGlobals = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ass_mark_schema = new MarkSchema(0);
    }

    /**
     * Test constructor
     */
    public function testConstructor()
    {
        // Arrange
        $expected = is_array([]);

        // Act
        $actual = is_array($this->ass_mark_schema->getMarkSteps());

        // Assert
        $this->assertEquals(
            $actual,
            $expected,
            "Constructor failed, mark_steps not an array."
        );
    }

    /**
     * Test for createSimpleSchema using defaults.
     */
    public function testCreateSimpleSchemaDefaults()
    {
        // Arrange

        $txt_failed_short = "failed";
        $txt_failed_official = "failed";
        $percentage_failed = 0;
        $failed_passed = false;
        $txt_passed_short = "passed";
        $txt_passed_official = "passed";
        $percentage_passed = 50;
        $passed_passed = true;

        // Act
        $mark_schema = $this->ass_mark_schema->createSimpleSchema();
        $marks = $mark_schema->getMarkSteps();

        $failed = $marks[0];
        $passed = $marks[1];

        // Assert
        $this->assertEquals(
            $failed->getShortName(),
            $txt_failed_short,
            'Failed on $txt_failed_short'
        );
        $this->assertEquals(
            $failed->getOfficialName(),
            $txt_failed_official,
            'Failed on $txt_failed_official'
        );
        $this->assertEquals(
            $failed->getMinimumLevel(),
            $percentage_failed,
            'Failed on $percentage_failed'
        );
        $this->assertEquals(
            $failed->getPassed(),
            $failed_passed,
            'Failed on $failed_passed'
        );

        $this->assertEquals(
            $passed->getShortName(),
            $txt_passed_short,
            'Failed on $txt_passed_short'
        );
        $this->assertEquals(
            $passed->getOfficialName(),
            $txt_passed_official,
            'Failed on $txt_passed_official'
        );
        $this->assertEquals(
            $passed->getMinimumLevel(),
            $percentage_passed,
            'Failed on $percetage_passed'
        );
        $this->assertEquals(
            $passed->getPassed(),
            $passed_passed,
            'Failed on $passed_passed'
        );
    }

    /**
     * Test for createSimpleSchema using custom values.
     */
    public function testCreateSimpleSchemaCustom()
    {
        // Arrange
        $txt_failed_short = "failed";
        $txt_failed_official = "failed";
        $percentage_failed = 0;
        $failed_passed = false;
        $txt_passed_short = "passed";
        $txt_passed_official = "passed";
        $percentage_passed = 50;
        $passed_passed = true;

        // Act
        $mark_schema = $this->ass_mark_schema->createSimpleSchema(
            $txt_failed_short,
            $txt_failed_official,
            $percentage_failed,
            $failed_passed,
            $txt_passed_short,
            $txt_passed_official,
            $percentage_passed,
            $passed_passed
        );

        $marks = $mark_schema->getMarkSteps();

        $failed = $marks[0];
        $passed = $marks[1];

        // Assert
        $this->assertEquals(
            $failed->getShortName(),
            $txt_failed_short,
            'Failed on $txt_failed_short'
        );
        $this->assertEquals(
            $failed->getOfficialName(),
            $txt_failed_official,
            'Failed on $txt_failed_official'
        );
        $this->assertEquals(
            $failed->getMinimumLevel(),
            $percentage_failed,
            'Failed on $percentage_failed'
        );
        $this->assertEquals(
            $failed->getPassed(),
            $failed_passed,
            'Failed on $failed_passed'
        );

        $this->assertEquals(
            $passed->getShortName(),
            $txt_passed_short,
            'Failed on $txt_passed_short'
        );
        $this->assertEquals(
            $passed->getOfficialName(),
            $txt_passed_official,
            'Failed on $txt_passed_official'
        );
        $this->assertEquals(
            $passed->getMinimumLevel(),
            $percentage_passed,
            'Failed on $percetage_passed'
        );
        $this->assertEquals(
            $passed->getPassed(),
            $passed_passed,
            'Failed on $passed_passed'
        );
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testSaveToDb_regular()
    {
        /*
        // Arrange
        $ildb_stub = $this->createMock('ilDBInterface');

        $ildb_stub->expects($this->any())
            ->method('query')
            ->will($this->returnValue('foo'));

        $ildb_stub->expects($this->any())
            ->method('numRows')
            ->will($this->returnValue(1));

        $db_result_1 = array('cmi_node_id' => 8);
        $db_result_2 = array('cmi_node_id' => 10);
        $db_result_3 = array('cmi_node_id' => 12);
        $db_result_4 = array('cmi_node_id' => 14);

        $ildb_stub->expects($this->any())
            ->method('fetchAssoc')
            ->will($this->onConsecutiveCalls($db_result_1, $db_result_2, $db_result_3, $db_result_4));
        */
    }
}
